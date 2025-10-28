<?php
// upload.php
require_once 'config.php';
require_once 'database.php';
require_once 'apk_crypt.php';
require_once 'telegram.php';

Config::init();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get user info (in real scenario, this would come from auth)
    $user_id = $_POST['user_id'] ?? 0;
    $username = $_POST['username'] ?? 'Unknown';
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit;
    }
    
    // Check file upload
    if (!isset($_FILES['apk_file'])) {
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        exit;
    }
    
    $file = $_FILES['apk_file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $file['error']]);
        exit;
    }
    
    if ($file['size'] > Config::$MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'error' => 'File too large']);
        exit;
    }
    
    if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'apk') {
        echo json_encode(['success' => false, 'error' => 'Only APK files allowed']);
        exit;
    }
    
    // Check user quota
    Database::createUser($user_id, $username);
    $user = Database::getUser($user_id);
    $daily_used = Database::getDailyUsage($user_id);
    
    if ($daily_used >= $user['daily_quota']) {
        echo json_encode(['success' => false, 'error' => 'Daily quota exceeded: ' . $daily_used . '/' . $user['daily_quota']]);
        exit;
    }
    
    if ($user['total_used'] >= $user['monthly_quota']) {
        echo json_encode(['success' => false, 'error' => 'Monthly quota exceeded: ' . $user['total_used'] . '/' . $user['monthly_quota']]);
        exit;
    }
    
    // Process APK
    $temp_path = Config::$TEMP_DIR . uniqid() . '.apk';
    move_uploaded_file($file['tmp_name'], $temp_path);
    
    $crypted_path = APKCrypt::cryptAPK($temp_path, $user_id);
    
    if ($crypted_path) {
        // Update usage
        Database::updateUsage($user_id);
        Database::logAPK($user_id, $username, $file['name'], $file['size']);
        
        // Log to Telegram channel
        $log_message = "📱 APK Processed\n\n👤 User: " . ($username ?: "ID: $user_id") . 
                      "\n📁 File: " . $file['name'] . 
                      "\n📦 Size: " . round($file['size'] / 1024) . " KB" .
                      "\n📅 Date: " . date('Y-m-d H:i:s') .
                      "\n📊 Plan: " . $user['plan'];
        
        TelegramBot::sendMessage(Config::$LOG_CHANNEL, $log_message);
        
        // Return download link
        $download_filename = basename($crypted_path);
        echo json_encode([
            'success' => true,
            'message' => 'APK successfully crypted!',
            'download_url' => 'download.php?file=' . $download_filename,
            'expiry' => date('Y-m-d H:i:s', time() + (Config::$EXPIRY_HOURS * 3600))
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'APK processing failed']);
    }
    
    // Cleanup
    if (file_exists($temp_path)) unlink($temp_path);
    
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
