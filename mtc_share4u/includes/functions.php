<?php
/**
 * MTC_SHARE4U - Helper Functions
 * Common utility functions used throughout the application
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PostManager.php';
require_once __DIR__ . '/../core/InteractionManager.php';
require_once __DIR__ . '/../core/AdminPanel.php';

/**
 * Initialize database files
 */
function initializeDatabase() {
    $dbFiles = [
        USERS_DB,
        POSTS_DB,
        COMMENTS_DB,
        FOLLOWS_DB,
        LIKES_DB,
        BANNED_IPS_DB,
        RECOVERY_CODES_DB,
        TRACKING_DB
    ];
    
    foreach ($dbFiles as $dbFile) {
        if (!file_exists($dbFile)) {
            file_put_contents($dbFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }
}

/**
 * Create upload directories
 */
function initializeUploadDirectories() {
    $directories = [
        UPLOAD_PATH . 'videos/',
        UPLOAD_PATH . 'images/',
        UPLOAD_PATH . 'files/',
        UPLOAD_PATH . 'thumbnails/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Check if user can download file
 */
function canDownload($postUserId) {
    $currentUser = Auth::getCurrentUser();
    
    if (!$currentUser) {
        return false;
    }
    
    // Owner can always download
    if (Security::isOwner()) {
        return true;
    }
    
    // Check if user's downloads are restricted
    $usersDb = Database::getInstance(USERS_DB);
    $user = $usersDb->getById($currentUser['id']);
    
    if ($user['download_restricted']) {
        return false;
    }
    
    return true;
}

/**
 * Format date for display
 */
function formatDate($dateString) {
    $timestamp = strtotime($dateString);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

/**
 * Generate shareable URL
 */
function generateShareUrl($postId) {
    return 'https://' . $_SERVER['HTTP_HOST'] . '/post.php?id=' . $postId;
}

/**
 * Get media type icon
 */
function getMediaTypeIcon($mediaType) {
    $icons = [
        'video' => '<i class="fas fa-video"></i>',
        'image' => '<i class="fas fa-image"></i>',
        'file' => '<i class="fas fa-file"></i>'
    ];
    
    return $icons[$mediaType] ?? '<i class="fas fa-file"></i>';
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

/**
 * Get current page URL
 */
function getCurrentPageUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Check for maintenance mode
 */
function isMaintenanceMode() {
    return file_exists(__DIR__ . '/../maintenance.mode');
}

/**
 * Enable maintenance mode
 */
function enableMaintenanceMode() {
    if (!Security::isOwner()) {
        return false;
    }
    
    touch(__DIR__ . '/../maintenance.mode');
    return true;
}

/**
 * Disable maintenance mode
 */
function disableMaintenanceMode() {
    if (!Security::isOwner()) {
        return false;
    }
    
    if (file_exists(__DIR__ . '/../maintenance.mode')) {
        unlink(__DIR__ . '/../maintenance.mode');
    }
    
    return true;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = []) {
    $logEntry = [
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => Security::getRealIP(),
        'timestamp' => time()
    ];
    
    $logFile = DB_PATH . 'activity.log';
    $logLine = json_encode($logEntry) . PHP_EOL;
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

/**
 * Get user statistics
 */
function getUserStatistics($userId) {
    $usersDb = Database::getInstance(USERS_DB);
    $postsDb = Database::getInstance(POSTS_DB);
    $likesDb = Database::getInstance(LIKES_DB);
    $commentsDb = Database::getInstance(COMMENTS_DB);
    
    $user = $usersDb->getById($userId);
    
    if (!$user) {
        return null;
    }
    
    $posts = $postsDb->find(['user_id' => $userId]);
    $totalLikes = 0;
    
    foreach ($posts as $post) {
        $totalLikes += $post['like_count'] ?? 0;
    }
    
    $userComments = $commentsDb->find(['user_id' => $userId]);
    $receivedLikes = count($likesDb->find(['user_id' => $userId]));
    
    return [
        'posts' => count($posts),
        'followers' => $user['follower_count'] ?? 0,
        'following' => $user['following_count'] ?? 0,
        'total_likes' => $totalLikes,
        'comments' => count($userComments),
        'received_likes' => $receivedLikes
    ];
}

/**
 * Clean old tracking data (older than 30 days)
 */
function cleanOldTrackingData() {
    if (!Security::isOwner()) {
        return false;
    }
    
    $trackingDb = Database::getInstance(TRACKING_DB);
    $allTracking = $trackingDb->getAll();
    
    $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);
    $count = 0;
    
    foreach ($allTracking as $track) {
        if ($track['timestamp'] < $thirtyDaysAgo) {
            $trackingDb->delete($track['id']);
            $count++;
        }
    }
    
    return $count;
}

/**
 * Export database to JSON
 */
function exportDatabase() {
    if (!Security::isOwner()) {
        return false;
    }
    
    $export = [
        'timestamp' => time(),
        'users' => Database::getInstance(USERS_DB)->getAll(),
        'posts' => Database::getInstance(POSTS_DB)->getAll(),
        'comments' => Database::getInstance(COMMENTS_DB)->getAll(),
        'likes' => Database::getInstance(LIKES_DB)->getAll(),
        'follows' => Database::getInstance(FOLLOWS_DB)->getAll(),
        'tracking' => Database::getInstance(TRACKING_DB)->getAll()
    ];
    
    return json_encode($export, JSON_PRETTY_PRINT);
}

/**
 * Initialize application
 */
function initializeApp() {
    // Check IP ban
    if (Security::isIPBanned()) {
        http_response_code(403);
        die('Access Denied: Your IP address has been banned.');
    }
    
    // Check maintenance mode
    if (isMaintenanceMode() && !Security::isOwner()) {
        http_response_code(503);
        include __DIR__ . '/../public/maintenance.php';
        exit;
    }
    
    // Initialize databases
    initializeDatabase();
    
    // Initialize upload directories
    initializeUploadDirectories();
    
    // Track visit
    if (Auth::isLoggedIn()) {
        Security::trackVisit($_SESSION['user_id'], 'visit');
    } else {
        Security::trackVisit(null, 'visit');
    }
}

// Initialize app on include
initializeApp();
?>