<?php
/**
 * MTC_SHARE4U - Security System
 * Advanced security features including IP tracking, GPS logging, and access control
 */

class Security {
    
    /**
     * Get real IP address of client
     */
    public static function getRealIP() {
        $ip = null;
        
        // Check for forwarded IPs
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    /**
     * Get GPS coordinates from client (requires browser permission)
     */
    public static function getGPSCoordinates() {
        // This requires client-side JavaScript
        // Server-side we can only store what the client sends
        return [
            'latitude' => isset($_POST['latitude']) ? floatval($_POST['latitude']) : null,
            'longitude' => isset($_POST['longitude']) ? floatval($_POST['longitude']) : null,
            'accuracy' => isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : null
        ];
    }

    /**
     * Track user visit with IP and GPS
     */
    public static function trackVisit($userId = null, $action = 'visit') {
        if (!IP_TRACKING_ENABLED) {
            return;
        }

        $ip = self::getRealIP();
        $gps = self::getGPSCoordinates();
        
        $trackingDb = Database::getInstance(TRACKING_DB);
        
        $trackingData = [
            'user_id' => $userId,
            'ip_address' => $ip,
            'latitude' => $gps['latitude'],
            'longitude' => $gps['longitude'],
            'accuracy' => $gps['accuracy'],
            'action' => $action,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => time()
        ];
        
        $trackingDb->insert($trackingData);
    }

    /**
     * Check if IP is banned
     */
    public static function isIPBanned($ip = null) {
        if ($ip === null) {
            $ip = self::getRealIP();
        }

        $bannedIpsDb = Database::getInstance(BANNED_IPS_DB);
        $bannedIp = $bannedIpsDb->findOne(['ip_address' => $ip, 'is_active' => true]);
        
        return $bannedIp !== null;
    }

    /**
     * Ban an IP address
     */
    public static function banIP($ip, $reason = '', $bannedBy = null) {
        $bannedIpsDb = Database::getInstance(BANNED_IPS_DB);
        
        // Check if already banned
        $existing = $bannedIpsDb->findOne(['ip_address' => $ip]);
        
        if ($existing) {
            return $bannedIpsDb->update($existing['id'], [
                'is_active' => true,
                'reason' => $reason,
                'banned_by' => $bannedBy,
                'banned_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $bannedIpsDb->insert([
            'ip_address' => $ip,
            'is_active' => true,
            'reason' => $reason,
            'banned_by' => $bannedBy,
            'banned_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Unban an IP address
     */
    public static function unbanIP($ip) {
        $bannedIpsDb = Database::getInstance(BANNED_IPS_DB);
        $bannedIp = $bannedIpsDb->findOne(['ip_address' => $ip]);
        
        if ($bannedIp) {
            return $bannedIpsDb->update($bannedIp['id'], ['is_active' => false]);
        }
        
        return false;
    }

    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('self::sanitizeInput', $data);
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate file type (Virus Guard)
     */
    public static function isFileTypeAllowed($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check against blocked types
        if (in_array($extension, BLOCKED_FILE_TYPES)) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate and sanitize uploaded file
     */
    public static function validateUploadedFile($file, $allowedTypes) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded or upload failed';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        $maxSize = self::getMaxFileSize($file['type']);
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum limit';
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check against blocked types
        if (!self::isFileTypeAllowed($file['name'])) {
            $errors[] = 'This file type is blocked for security reasons';
        }
        
        // Additional MIME type check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = self::getAllowedMimeTypes($allowedTypes);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $errors[] = 'File MIME type not allowed';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'extension' => $extension,
            'mime_type' => $mimeType
        ];
    }

    /**
     * Get maximum file size for type
     */
    private static function getMaxFileSize($type) {
        if (strpos($type, 'video') !== false) {
            return MAX_VIDEO_SIZE;
        } elseif (strpos($type, 'image') !== false) {
            return MAX_IMAGE_SIZE;
        } else {
            return MAX_FILE_SIZE;
        }
    }

    /**
     * Get allowed MIME types
     */
    private static function getAllowedMimeTypes($types) {
        $mimeMap = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav'
        ];
        
        $allowedMimes = [];
        foreach ($types as $type) {
            if (isset($mimeMap[$type])) {
                $allowedMimes[] = $mimeMap[$type];
            }
        }
        
        return $allowedMimes;
    }

    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Check rate limiting
     */
    public static function checkRateLimit($action, $limit) {
        $ip = self::getRealIP();
        $key = 'rate_limit_' . $action . '_' . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if hour has passed
        if (time() - $data['time'] > 3600) {
            $_SESSION[$key] = ['count' => 1, 'time' => time()];
            return true;
        }
        
        if ($data['count'] >= $limit) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }

    /**
     * Check if user is owner (TypeVC)
     */
    public static function isOwner() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->getById($_SESSION['user_id']);
        
        return $user && $user['username'] === OWNER_USERNAME;
    }

    /**
     * Verify hardware key for owner
     */
    public static function verifyHardwareKey($key) {
        return hash_equals(OWNER_HARDWARE_KEY, $key);
    }

    /**
     * Generate recovery code
     */
    public static function generateRecoveryCode($email) {
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        $ip = self::getRealIP();
        
        $recoveryDb = Database::getInstance(RECOVERY_CODES_DB);
        
        // Invalidate old codes for this email
        $oldCodes = $recoveryDb->find(['email' => $email, 'is_used' => false]);
        foreach ($oldCodes as $oldCode) {
            $recoveryDb->update($oldCode['id'], ['is_used' => true]);
        }
        
        $recoveryDb->insert([
            'email' => $email,
            'code' => $code,
            'ip_address' => $ip,
            'is_used' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $code;
    }

    /**
     * Verify recovery code
     */
    public static function verifyRecoveryCode($email, $code) {
        $recoveryDb = Database::getInstance(RECOVERY_CODES_DB);
        $recovery = $recoveryDb->findOne([
            'email' => $email,
            'code' => strtoupper($code),
            'is_used' => false
        ]);
        
        if ($recovery) {
            // Check if code is not expired (24 hours)
            $createdAt = strtotime($recovery['created_at']);
            if (time() - $createdAt > 86400) {
                return false;
            }
            
            // Mark as used
            $recoveryDb->update($recovery['id'], ['is_used' => true]);
            return true;
        }
        
        return false;
    }
}
?>