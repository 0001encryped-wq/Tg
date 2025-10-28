<?php
// deploy.php - Auto setup script
echo "🚀 APK Crypt System - Auto Deploy\n";
echo "==================================\n";

// Create directory structure
$dirs = [
    'storage/apks',
    'storage/crypted', 
    'storage/logs',
    'storage/db',
    'temp'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Created directory: $dir\n";
    }
}

// Check requirements
$requirements = [
    'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO SQLite Support' => extension_loaded('pdo_sqlite'),
    'cURL Support' => extension_loaded('curl'),
    'Zip Extension' => extension_loaded('zip'),
    'Write Permissions' => is_writable('storage/') && is_writable('temp/')
];

echo "\n🔧 System Check:\n";
foreach ($requirements as $req => $status) {
    echo $status ? "✅ " : "❌ ";
    echo "$req\n";
}

// Initialize config
if (!file_exists('config.php')) {
    echo "\n❌ config.php not found - please create it from the template\n";
} else {
    require_once 'config.php';
    Config::init();
    echo "\n✅ System initialized successfully!\n";
}

echo "\n📝 Next Steps:\n";
echo "1. Edit config.php with your Telegram bot token\n";
echo "2. Set up webhook: https://api.telegram.org/bot<TOKEN>/setWebhook?url=<YOUR_URL>/bot.php\n";
echo "3. Access your dashboard at: https://your-domain.com/\n";
echo "4. Test with: php -f bot.php (for testing)\n";

echo "\n🎯 Deployment Complete!\n";
