<?php
/**
 * MTC_SHARE4U - Admin Panel
 * Owner-only control panel for system management
 */

class AdminPanel {
    
    /**
     * Get admin dashboard data
     */
    public static function getDashboard() {
        Security::requireOwner();
        
        $usersDb = Database::getInstance(USERS_DB);
        $postsDb = Database::getInstance(POSTS_DB);
        $trackingDb = Database::getInstance(TRACKING_DB);
        $bannedIpsDb = Database::getInstance(BANNED_IPS_DB);
        
        $allUsers = $usersDb->getAll();
        $allPosts = $postsDb->getAll();
        $allTracking = $trackingDb->getAll();
        $bannedIps = $bannedIpsDb->find(['is_active' => true]);
        
        // Calculate statistics
        $stats = [
            'total_users' => count($allUsers),
            'active_users' => count(array_filter($allUsers, function($u) { return $u['is_active']; })),
            'total_posts' => count($allPosts),
            'active_posts' => count(array_filter($allPosts, function($p) { return $p['is_active']; })),
            'total_visits' => count($allTracking),
            'banned_ips' => count($bannedIps),
            'today_visits' => count(array_filter($allTracking, function($t) {
                return date('Y-m-d', $t['timestamp']) === date('Y-m-d');
            }))
        ];
        
        // Get recent tracking data
        $recentTracking = array_slice(array_reverse($allTracking), 0, 50);
        
        // Add user info to tracking
        foreach ($recentTracking as &$track) {
            if ($track['user_id']) {
                $user = $usersDb->getById($track['user_id']);
                if ($user) {
                    $track['username'] = $user['username'];
                    $track['display_name'] = $user['display_name'];
                }
            }
        }
        
        // Get recent posts
        $recentPosts = array_slice(array_reverse($allPosts), 0, 20);
        foreach ($recentPosts as &$post) {
            $user = $usersDb->getById($post['user_id']);
            if ($user) {
                $post['username'] = $user['username'];
                $post['display_name'] = $user['display_name'];
            }
        }
        
        return [
            'stats' => $stats,
            'recent_tracking' => $recentTracking,
            'recent_posts' => $recentPosts,
            'banned_ips' => array_values($bannedIps)
        ];
    }

    /**
     * Get live traffic data
     */
    public static function getLiveTraffic() {
        Security::requireOwner();
        
        $trackingDb = Database::getInstance(TRACKING_DB);
        $usersDb = Database::getInstance(USERS_DB);
        
        // Get tracking from last 5 minutes
        $fiveMinutesAgo = time() - 300;
        $allTracking = $trackingDb->getAll();
        
        $liveTraffic = array_filter($allTracking, function($t) use ($fiveMinutesAgo) {
            return $t['timestamp'] >= $fiveMinutesAgo;
        });
        
        // Group by IP and get latest data
        $ipData = [];
        foreach ($liveTraffic as $track) {
            $ip = $track['ip_address'];
            if (!isset($ipData[$ip]) || $track['timestamp'] > $ipData[$ip]['timestamp']) {
                $ipData[$ip] = $track;
            }
        }
        
        // Add user info
        foreach ($ipData as &$track) {
            if ($track['user_id']) {
                $user = $usersDb->getById($track['user_id']);
                if ($user) {
                    $track['username'] = $user['username'];
                    $track['display_name'] = $user['display_name'];
                }
            }
        }
        
        return array_values($ipData);
    }

    /**
     * Get all users
     */
    public static function getAllUsers($page = 1, $limit = 50) {
        Security::requireOwner();
        
        $usersDb = Database::getInstance(USERS_DB);
        $allUsers = $usersDb->getAll();
        
        // Sort by created_at
        usort($allUsers, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $users = array_slice($allUsers, $offset, $limit);
        
        return [
            'users' => $users,
            'total' => count($allUsers),
            'page' => $page,
            'pages' => ceil(count($allUsers) / $limit)
        ];
    }

    /**
     * Get user details with full history
     */
    public static function getUserDetails($userId) {
        Security::requireOwner();
        
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->getById($userId);
        
        if (!$user) {
            return null;
        }
        
        // Get user's posts
        $postsDb = Database::getInstance(POSTS_DB);
        $posts = $postsDb->find(['user_id' => $userId]);
        
        // Get user's comments
        $commentsDb = Database::getInstance(COMMENTS_DB);
        $comments = $commentsDb->find(['user_id' => $userId]);
        
        // Get user's tracking history
        $trackingDb = Database::getInstance(TRACKING_DB);
        $tracking = $trackingDb->find(['user_id' => $userId]);
        
        // Get followers
        $followsDb = Database::getInstance(FOLLOWS_DB);
        $followers = $followsDb->find(['following_id' => $userId]);
        $following = $followsDb->find(['follower_id' => $userId]);
        
        return [
            'user' => $user,
            'posts' => $posts,
            'comments' => $comments,
            'tracking' => $tracking,
            'followers' => $followers,
            'following' => $following
        ];
    }

    /**
     * Delete any post
     */
    public static function deleteAnyPost($postId) {
        Security::requireOwner();
        
        return PostManager::deletePost($postId);
    }

    /**
     * Restrict user downloads
     */
    public static function restrictUserDownloads($userId, $restricted = true) {
        Security::requireOwner();
        
        return Auth::restrictUserDownloads($userId, $restricted);
    }

    /**
     * Deactivate user
     */
    public static function deactivateUser($userId) {
        Security::requireOwner();
        
        return Auth::deactivateUser($userId);
    }

    /**
     * Activate user
     */
    public static function activateUser($userId) {
        Security::requireOwner();
        
        $usersDb = Database::getInstance(USERS_DB);
        $usersDb->update($userId, ['is_active' => true]);
        
        return ['success' => true];
    }

    /**
     * Ban IP
     */
    public static function banIP($ip, $reason = '') {
        Security::requireOwner();
        
        return Security::banIP($ip, $reason, 'TypeVC');
    }

    /**
     * Unban IP
     */
    public static function unbanIP($ip) {
        Security::requireOwner();
        
        return Security::unbanIP($ip);
    }

    /**
     * Generate recovery code for user
     */
    public static function generateRecoveryCode($email) {
        Security::requireOwner();
        
        $email = Security::sanitizeInput($email);
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->findOne(['email' => $email]);
        
        if (!$user) {
            return ['success' => false, 'errors' => ['User not found']];
        }
        
        $code = Security::generateRecoveryCode($email);
        
        return [
            'success' => true,
            'code' => $code,
            'email' => $email,
            'username' => $user['username']
        ];
    }

    /**
     * Get all banned IPs
     */
    public static function getBannedIPs() {
        Security::requireOwner();
        
        $bannedIpsDb = Database::getInstance(BANNED_IPS_DB);
        $allBanned = $bannedIpsDb->getAll();
        
        // Get active bans
        $activeBans = array_filter($allBanned, function($ban) {
            return $ban['is_active'] === true;
        });
        
        return array_values($activeBans);
    }

    /**
     * Get all recovery codes
     */
    public static function getRecoveryCodes() {
        Security::requireOwner();
        
        $recoveryDb = Database::getInstance(RECOVERY_CODES_DB);
        $allCodes = $recoveryDb->getAll();
        
        // Sort by created_at
        usort($allCodes, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $allCodes;
    }

    /**
     * Delete post and all related data
     */
    public static function deletePostCompletely($postId) {
        Security::requireOwner();
        
        $postsDb = Database::getInstance(POSTS_DB);
        $post = $postsDb->getById($postId);
        
        if (!$post) {
            return ['success' => false, 'errors' => ['Post not found']];
        }
        
        // Delete post
        $postsDb->delete($postId);
        
        // Delete all likes
        $likesDb = Database::getInstance(LIKES_DB);
        $likes = $likesDb->find(['post_id' => $postId]);
        foreach ($likes as $like) {
            $likesDb->delete($like['id']);
        }
        
        // Delete all comments
        $commentsDb = Database::getInstance(COMMENTS_DB);
        $comments = $commentsDb->find(['post_id' => $postId]);
        foreach ($comments as $comment) {
            $commentsDb->delete($comment['id']);
        }
        
        // Delete media files
        if ($post['media_path'] && file_exists($post['media_path'])) {
            unlink($post['media_path']);
        }
        
        if ($post['thumbnail_path'] && file_exists($post['thumbnail_path'])) {
            unlink($post['thumbnail_path']);
        }
        
        return ['success' => true];
    }

    /**
     * Get system health status
     */
    public static function getSystemHealth() {
        Security::requireOwner();
        
        $health = [
            'database_accessible' => true,
            'upload_writable' => is_writable(UPLOAD_PATH),
            'disk_usage' => self::getDiskUsage(),
            'database_size' => self::getDatabaseSize(),
            'active_sessions' => self::getActiveSessionCount(),
            'timestamp' => time()
        ];
        
        return $health;
    }

    /**
     * Get disk usage
     */
    private static function getDiskUsage() {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'total' => self::formatBytes($total),
            'used' => self::formatBytes($used),
            'free' => self::formatBytes($free),
            'percentage' => round(($used / $total) * 100, 2)
        ];
    }

    /**
     * Get database size
     */
    private static function getDatabaseSize() {
        $dbPath = DB_PATH;
        $totalSize = 0;
        
        $files = glob($dbPath . '*.json');
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return self::formatBytes($totalSize);
    }

    /**
     * Get active session count
     */
    private static function getActiveSessionCount() {
        $trackingDb = Database::getInstance(TRACKING_DB);
        $allTracking = $trackingDb->getAll();
        
        // Count unique users in last 30 minutes
        $thirtyMinutesAgo = time() - 1800;
        $activeUsers = array_filter($allTracking, function($t) use ($thirtyMinutesAgo) {
            return $t['timestamp'] >= $thirtyMinutesAgo && $t['user_id'];
        });
        
        return count(array_unique(array_column($activeUsers, 'user_id')));
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>