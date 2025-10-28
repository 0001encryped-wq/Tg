<?php
// bot.php
require_once 'config.php';
require_once 'database.php';
require_once 'apk_crypt.php';
require_once 'telegram.php';

Config::init();

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    exit;
}

// Handle message
if (isset($update['message'])) {
    $message = $update['message'];
    $user_id = $message['from']['id'];
    $username = $message['from']['username'] ?? 'Unknown';
    $text = $message['text'] ?? '';
    $chat_id = $message['chat']['id'];
    
    // Handle commands
    if ($text === '/start') {
        if (Database::isAdmin($user_id)) {
            TelegramBot::sendMessage($chat_id, "👋 Welcome back, admin! Use /admin for panel");
        } else {
            TelegramBot::sendMessage($chat_id, "🤖 APK Crypt Bot\n\nSend any APK file to crypt it with 2-hour expiry!");
        }
    }
    
    elseif ($text === '/admin' && Database::isAdmin($user_id)) {
        $total_users = Database::getTotalUsers();
        $today_apks = Database::getTodayAPKs();
        $bot_status = Database::getSetting('bot_status');
        
        $admin_panel = "🛠️ Admin Panel\n\n" .
                      "🤖 Status: " . strtoupper($bot_status) . "\n" .
                      "👥 Users: $total_users\n" .
                      "📱 Today APKs: $today_apks";
        
        TelegramBot::sendMessage($chat_id, $admin_panel);
    }
    
    // Handle APK document
    elseif (isset($message['document'])) {
        $document = $message['document'];
        $filename = $document['file_name'] ?? 'unknown.apk';
        
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'apk') {
            TelegramBot::sendMessage($chat_id, "❌ Please send only APK files.");
            exit;
        }
        
        // Check quota
        Database::createUser($user_id, $username);
        $user = Database::getUser($user_id);
        $daily_used = Database::getDailyUsage($user_id);
        
        if ($daily_used >= $user['daily_quota']) {
            TelegramBot::sendMessage($chat_id, "❌ Daily quota exceeded: $daily_used/" . $user['daily_quota']);
            exit;
        }
        
        // Get file
        $file_info = TelegramBot::getFile($document['file_id']);
        if (!$file_info['ok']) {
            TelegramBot::sendMessage($chat_id, "❌ Error getting file from Telegram.");
            exit;
        }
        
        $temp_path = TelegramBot::downloadFile($file_info['result']['file_path']);
        if (!$temp_path) {
            TelegramBot::sendMessage($chat_id, "❌ Error downloading file.");
            exit;
        }
        
        // Process APK
        TelegramBot::sendMessage($chat_id, "🔄 Processing your APK...");
        
        $crypted_path = APKCrypt::cryptAPK($temp_path, $user_id);
        
        if ($crypted_path) {
            // Update usage and log
            Database::updateUsage($user_id);
            Database::logAPK($user_id, $username, $filename, $document['file_size']);
            
            // Send crypted APK
            $caption = "✅ APK Successfully Crypted!\n\n⚠️ Warning: Expires in " . Config::$EXPIRY_HOURS . " hours\n📁 Original: $filename\n🎯 Plan: " . $user['plan'];
            
            if (TelegramBot::sendDocument($chat_id, $crypted_path, $caption)) {
                TelegramBot::sendMessage($chat_id, "✅ APK sent successfully!");
            }
            
            // Cleanup
            unlink($crypted_path);
        } else {
            TelegramBot::sendMessage($chat_id, "❌ Error processing APK.");
        }
        
        unlink($temp_path);
    }
}
