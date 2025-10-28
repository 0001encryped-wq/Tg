<?php
// apk_crypt.php
require_once 'config.php';

class APKCrypt {
    
    public static function cryptAPK($input_path, $user_id) {
        $output_filename = "signed_" . $user_id . "_" . time() . ".apk";
        $output_path = Config::$CRYPTED_DIR . $output_filename;
        
        // Copy original to output
        if (!copy($input_path, $output_path)) {
            return false;
        }
        
        // Add time bomb metadata
        self::addTimeBomb($output_path, $user_id);
        
        // Bypass protection
        self::bypassProtection($output_path);
        
        return $output_path;
    }
    
    private static function addTimeBomb($apk_path, $user_id) {
        $expiry_time = time() + (Config::$EXPIRY_HOURS * 3600);
        
        $metadata = [
            'expiry' => $expiry_time,
            'user_id' => $user_id,
            'timestamp' => time(),
            'type' => 'time_bomb_v1'
        ];
        
        $metadata_json = json_encode($metadata);
        $encrypted_data = base64_encode($metadata_json);
        
        // Add to ZIP comment (stealthy)
        self::addToZipComment($apk_path, $encrypted_data);
        
        // Add as file in APK
        self::addToAPK($apk_path, $encrypted_data);
    }
    
    private static function addToZipComment($apk_path, $data) {
        // Read existing APK
        $apk_data = file_get_contents($apk_path);
        
        // Find ZIP end central directory
        $eocd_pos = strrpos($apk_data, "\x50\x4b\x05\x06");
        
        if ($eocd_pos !== false) {
            // Extract existing comment length
            $comment_length = unpack('v', substr($apk_data, $eocd_pos + 20, 2))[1];
            
            // Create new comment
            $new_comment = "CRYPTED_" . $data;
            $new_comment_length = strlen($new_comment);
            
            // Replace comment length and add new comment
            $new_eocd = substr($apk_data, 0, $eocd_pos + 20) .
                        pack('v', $new_comment_length) .
                        $new_comment;
            
            file_put_contents($apk_path, $new_eocd);
        }
    }
    
    private static function addToAPK($apk_path, $data) {
        // Use zip extension if available
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($apk_path, ZipArchive::CREATE) === TRUE) {
                $zip->addFromString('META-INF/CRYPT_DATA.dat', $data);
                
                // Modify AndroidManifest if exists
                $manifest = $zip->getFromName('AndroidManifest.xml');
                if ($manifest) {
                    $zip->addFromString('AndroidManifest.xml', $manifest . '<!-- CRYPTED -->');
                }
                
                $zip->close();
                return true;
            }
        }
        
        return false;
    }
    
    private static function bypassProtection($apk_path) {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($apk_path, ZipArchive::CREATE) === TRUE) {
                // Add fake certificate files
                $zip->addFromString('META-INF/FAKE_CERT.SF', self::generateFakeCert());
                $zip->addFromString('META-INF/FAKE_CERT.RSA', base64_encode(random_bytes(256)));
                
                $zip->close();
            }
        }
    }
    
    private static function generateFakeCert() {
        return "Signature-Version: 1.0\n" .
               "Created-By: 1.0 (Android)\n" .
               "SHA1-Digest-Manifest: fake_signature_bypass\n" .
               "X-Android-APK-Signed: 2\n\n" .
               "Name: classes.dex\n" .
               "SHA1-Digest: fake_digest_for_bypass\n";
    }
}
