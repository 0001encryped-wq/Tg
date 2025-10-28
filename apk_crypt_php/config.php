<?php
// config.php
class Config {
    // Telegram Bot
    public static $BOT_TOKEN = "8068541206:AAEVLFaF8Y_tEf2NjWpRgae3p7V_eT7ZmUo";
    public static $ADMIN_ID = 8041279868;
    public static $LOG_CHANNEL = "-1001234567890";
    
    // Database
    public static $DB_FILE = __DIR__ . "/storage/db/apk_system.db";
    
    // File Paths
    public static $UPLOAD_DIR = __DIR__ . "/storage/apks/";
    public static $CRYPTED_DIR = __DIR__ . "/storage/crypted/";
    public static $TEMP_DIR = __DIR__ . "/temp/";
    public static $LOG_DIR = __DIR__ . "/storage/logs/";
    
    // System Settings
    public static $MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
    public static $EXPIRY_HOURS = 2;
    
    // User Plans
    public static $PLANS = [
        "FREE" => ["daily" => 3, "monthly" => 50],
        "PREMIUM" => ["daily" => 10, "monthly" => 300],
        "VIP" => ["daily" => 50, "monthly" => 1500]
    ];
    
    // Initialize System
    public static function init() {
        // Create directories
        $dirs = [
            self::$UPLOAD_DIR,
            self::$CRYPTED_DIR,
            self::$TEMP_DIR,
            self::$LOG_DIR,
            dirname(self::$DB_FILE)
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }
        
        // Initialize database
        Database::init();
    }
}
