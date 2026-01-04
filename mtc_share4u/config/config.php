<?php
/**
 * MTC_SHARE4U - Main Configuration File
 * High-performance social media platform optimized for Termux
 */

// Environment Configuration
define('APP_NAME', 'MTC_SHARE4U');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // Change to 'development' for debugging

// Database Configuration (JSON-based)
define('DB_PATH', __DIR__ . '/../database/');
define('USERS_DB', DB_PATH . 'users.json');
define('POSTS_DB', DB_PATH . 'posts.json');
define('COMMENTS_DB', DB_PATH . 'comments.json');
define('FOLLOWS_DB', DB_PATH . 'follows.json');
define('LIKES_DB', DB_PATH . 'likes.json');
define('BANNED_IPS_DB', DB_PATH . 'banned_ips.json');
define('RECOVERY_CODES_DB', DB_PATH . 'recovery_codes.json');
define('TRACKING_DB', DB_PATH . 'tracking.json');

// Owner Configuration
define('OWNER_USERNAME', 'TypeVC');
define('OWNER_EMAIL', 'typepanel@gmail.com');
define('OWNER_HARDWARE_KEY', 'MTC-2024-SECURE-KEY-789XYZ'); // Change this in production

// Security Configuration
define('SESSION_LIFETIME', 86400); // 24 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
define('IP_TRACKING_ENABLED', true);
define('GPS_TRACKING_ENABLED', true);

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024); // 500MB
define('MAX_IMAGE_SIZE', 20 * 1024 * 1024); // 20MB
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

// Allowed File Types
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'mp3', 'wav']);

// Blocked File Types (Security)
define('BLOCKED_FILE_TYPES', ['php', 'exe', 'sh', 'bat', 'cmd', 'ps1', 'js', 'html', 'htm', 'phtml']);

// Pagination & Display
define('POSTS_PER_PAGE', 20);
define('COMMENTS_PER_PAGE', 10);
define('SEARCH_RESULTS_LIMIT', 50);

// Rate Limiting
define('RATE_LIMIT_POSTS', 10); // Posts per hour
define('RATE_LIMIT_COMMENTS', 50); // Comments per hour
define('RATE_LIMIT_SEARCH', 100); // Searches per hour

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>