<?php
// database.php
require_once 'config.php';

class Database {
    private static $pdo = null;
    
    public static function getConnection() {
        if (self::$pdo === null) {
            self::$pdo = new PDO("sqlite:" . Config::$DB_FILE);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }
    
    public static function init() {
        $pdo = self::getConnection();
        
        // Users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY,
            username TEXT,
            plan TEXT DEFAULT 'FREE',
            daily_quota INTEGER DEFAULT 3,
            monthly_quota INTEGER DEFAULT 50,
            total_used INTEGER DEFAULT 0,
            join_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_paid INTEGER DEFAULT 0
        )");
        
        // Admins table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            admin_id INTEGER PRIMARY KEY,
            username TEXT,
            added_by INTEGER,
            added_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // APK Logs table
        $pdo->exec("CREATE TABLE IF NOT EXISTS apk_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            username TEXT,
            original_name TEXT,
            file_size INTEGER,
            process_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // System settings
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )");
        
        // Daily usage
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_usage (
            user_id INTEGER,
            usage_date DATE,
            count INTEGER DEFAULT 0,
            PRIMARY KEY (user_id, usage_date)
        )");
        
        // Insert default data
        $pdo->exec("INSERT OR IGNORE INTO system_settings (key, value) VALUES 
            ('bot_status', 'on'),
            ('heartbeat', 'on')");
            
        $pdo->exec("INSERT OR IGNORE INTO admins (admin_id, username, added_by) VALUES 
            (" . Config::$ADMIN_ID . ", 'Owner', " . Config::$ADMIN_ID . ")");
    }
    
    // User Management
    public static function getUser($user_id) {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function createUser($user_id, $username = null) {
        $pdo = self::getConnection();
        $plan = Config::$PLANS['FREE'];
        
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO users 
            (user_id, username, daily_quota, monthly_quota) 
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $username, $plan['daily'], $plan['monthly']]);
    }
    
    public static function updateUsage($user_id) {
        $pdo = self::getConnection();
        $today = date('Y-m-d');
        
        // Update daily usage
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO daily_usage 
            (user_id, usage_date, count) 
            VALUES (?, ?, COALESCE((SELECT count FROM daily_usage WHERE user_id = ? AND usage_date = ?), 0) + 1)");
        $stmt->execute([$user_id, $today, $user_id, $today]);
        
        // Update total used
        $pdo->exec("UPDATE users SET total_used = total_used + 1 WHERE user_id = $user_id");
    }
    
    public static function getDailyUsage($user_id) {
        $pdo = self::getConnection();
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT count FROM daily_usage WHERE user_id = ? AND usage_date = ?");
        $stmt->execute([$user_id, $today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['count'] : 0;
    }
    
    // Admin Management
    public static function isAdmin($user_id) {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE admin_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    }
    
    public static function addAdmin($admin_id, $username, $added_by) {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO admins (admin_id, username, added_by) VALUES (?, ?, ?)");
        $stmt->execute([$admin_id, $username, $added_by]);
    }
    
    // System Settings
    public static function getSetting($key) {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : null;
    }
    
    public static function updateSetting($key, $value) {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO system_settings (key, value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    // Statistics
    public static function getTotalUsers() {
        $pdo = self::getConnection();
        return $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
    
    public static function getTodayAPKs() {
        $pdo = self::getConnection();
        return $pdo->query("SELECT COUNT(*) FROM apk_logs WHERE DATE(process_date) = DATE('now')")->fetchColumn();
    }
    
    // Logging
    public static function logAPK($user_id, $username, $filename, $filesize) {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("INSERT INTO apk_logs (user_id, username, original_name, file_size) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $username, $filename, $filesize]);
    }
}
