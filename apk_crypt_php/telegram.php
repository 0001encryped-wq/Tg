<?php
// telegram.php
require_once 'config.php';

class TelegramBot {
    
    public static function sendMessage($chat_id, $text, $reply_markup = null) {
        $url = "https://api.telegram.org/bot" . Config::$BOT_TOKEN . "/sendMessage";
        
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        
        return self::makeRequest($url, $data);
    }
    
    public static function sendDocument($chat_id, $document_path, $caption = '') {
        $url = "https://api.telegram.org/bot" . Config::$BOT_TOKEN . "/sendDocument";
        
        $data = [
            'chat_id' => $chat_id,
            'caption' => $caption
        ];
        
        $document = new CURLFile($document_path);
        $data['document'] = $document;
        
        return self::makeRequest($url, $data, true);
    }
    
    public static function getFile($file_id) {
        $url = "https://api.telegram.org/bot" . Config::$BOT_TOKEN . "/getFile?file_id=" . $file_id;
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
    
    public static function downloadFile($file_path) {
        $url = "https://api.telegram.org/file/bot" . Config::$BOT_TOKEN . "/" . $file_path;
        $temp_path = Config::$TEMP_DIR . uniqid() . ".apk";
        
        $file_content = file_get_contents($url);
        if ($file_content !== false) {
            file_put_contents($temp_path, $file_content);
            return $temp_path;
        }
        
        return false;
    }
    
    public static function testConnection() {
        $url = "https://api.telegram.org/bot" . Config::$BOT_TOKEN . "/getMe";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        return $data['ok'] ?? false;
    }
    
    private static function makeRequest($url, $data, $is_file = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($is_file) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response !== false;
    }
}
