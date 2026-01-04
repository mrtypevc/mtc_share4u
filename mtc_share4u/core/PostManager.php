<?php
/**
 * MTC_SHARE4U - Post Management System
 * Handle post creation, retrieval, searching, and management
 */

class PostManager {
    
    /**
     * Create new post
     */
    public static function createPost($userId, $title, $content, $mediaType, $mediaFile, $triggerQuestions = []) {
        Auth::requireLogin();
        
        // Check rate limiting
        if (!Security::checkRateLimit('posts', RATE_LIMIT_POSTS)) {
            return ['success' => false, 'errors' => ['Post limit reached. Please wait before posting again.']];
        }
        
        // Validate inputs
        $title = Security::sanitizeInput($title);
        $content = Security::sanitizeInput($content);
        
        if (empty($title)) {
            return ['success' => false, 'errors' => ['Title is required']];
        }
        
        // Process media file
        $mediaResult = self::processMediaUpload($mediaFile, $mediaType);
        if (!$mediaResult['success']) {
            return $mediaResult;
        }
        
        $postsDb = Database::getInstance(POSTS_DB);
        
        // Create post
        $postId = $postsDb->insert([
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'media_type' => $mediaType,
            'media_path' => $mediaResult['path'],
            'thumbnail_path' => $mediaResult['thumbnail'] ?? null,
            'trigger_questions' => $triggerQuestions,
            'like_count' => 0,
            'comment_count' => 0,
            'download_count' => 0,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Track post creation
        Security::trackVisit($userId, 'post_create');
        
        return ['success' => true, 'post_id' => $postId];
    }

    /**
     * Process media upload
     */
    private static function processMediaUpload($file, $mediaType) {
        $allowedTypes = [];
        $uploadDir = '';
        $thumbnailDir = '';
        
        switch ($mediaType) {
            case 'video':
                $allowedTypes = ALLOWED_VIDEO_TYPES;
                $uploadDir = UPLOAD_PATH . 'videos/';
                $thumbnailDir = UPLOAD_PATH . 'thumbnails/';
                break;
            case 'image':
                $allowedTypes = ALLOWED_IMAGE_TYPES;
                $uploadDir = UPLOAD_PATH . 'images/';
                break;
            case 'file':
                $allowedTypes = ALLOWED_FILE_TYPES;
                $uploadDir = UPLOAD_PATH . 'files/';
                break;
            default:
                return ['success' => false, 'errors' => ['Invalid media type']];
        }
        
        // Validate file
        $validation = Security::validateUploadedFile($file, $allowedTypes);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Generate unique filename
        $extension = $validation['extension'];
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'errors' => ['Failed to save file']];
        }
        
        $result = [
            'success' => true,
            'path' => $filepath,
            'filename' => $filename,
            'extension' => $extension
        ];
        
        // Generate thumbnail for videos
        if ($mediaType === 'video') {
            $thumbnail = self::generateVideoThumbnail($filepath, $thumbnailDir);
            if ($thumbnail) {
                $result['thumbnail'] = $thumbnail;
            }
        }
        
        return $result;
    }

    /**
     * Generate video thumbnail
     */
    private static function generateVideoThumbnail($videoPath, $thumbnailDir) {
        $thumbnailFilename = uniqid() . '_thumb.jpg';
        $thumbnailPath = $thumbnailDir . $thumbnailFilename;
        
        // Try to generate thumbnail using ffmpeg
        if (exec("which ffmpeg")) {
            $command = "ffmpeg -i " . escapeshellarg($videoPath) . " -ss 00:00:01 -vframes 1 -vf scale=320:240 " . escapeshellarg($thumbnailPath) . " 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($thumbnailPath)) {
                return $thumbnailPath;
            }
        }
        
        return null;
    }

    /**
     * Get post by ID
     */
    public static function getPost($postId) {
        $postsDb = Database::getInstance(POSTS_DB);
        $post = $postsDb->getById($postId);
        
        if (!$post || !$post['is_active']) {
            return null;
        }
        
        // Get user info
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->getById($post['user_id']);
        
        if ($user) {
            $post['username'] = $user['username'];
            $post['display_name'] = $user['display_name'];
            $post['profile_image'] = $user['profile_image'];
        }
        
        return $post;
    }

    /**
     * Get feed posts
     */
    public static function getFeed($page = 1, $limit = POSTS_PER_PAGE) {
        $postsDb = Database::getInstance(POSTS_DB);
        $allPosts = $postsDb->getAll();
        
        // Filter active posts and sort by date
        $activePosts = array_filter($allPosts, function($post) {
            return $post['is_active'] === true;
        });
        
        // Sort by created_at descending
        usort($activePosts, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $posts = array_slice($activePosts, $offset, $limit);
        
        // Add user info to each post
        $usersDb = Database::getInstance(USERS_DB);
        foreach ($posts as &$post) {
            $user = $usersDb->getById($post['user_id']);
            if ($user) {
                $post['username'] = $user['username'];
                $post['display_name'] = $user['display_name'];
                $post['profile_image'] = $user['profile_image'];
            }
        }
        
        return [
            'posts' => $posts,
            'total' => count($activePosts),
            'page' => $page,
            'pages' => ceil(count($activePosts) / $limit)
        ];
    }

    /**
     * Get user's posts
     */
    public static function getUserPosts($userId, $page = 1, $limit = POSTS_PER_PAGE) {
        $postsDb = Database::getInstance(POSTS_DB);
        $allPosts = $postsDb->find(['user_id' => $userId]);
        
        // Filter active posts and sort by date
        $activePosts = array_filter($allPosts, function($post) {
            return $post['is_active'] === true;
        });
        
        // Sort by created_at descending
        usort($activePosts, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $posts = array_slice($activePosts, $offset, $limit);
        
        return [
            'posts' => $posts,
            'total' => count($activePosts),
            'page' => $page,
            'pages' => ceil(count($activePosts) / $limit)
        ];
    }

    /**
     * YouTube-style intelligent search
     */
    public static function searchPosts($query, $page = 1, $limit = SEARCH_RESULTS_LIMIT) {
        $query = strtolower(trim(Security::sanitizeInput($query)));
        
        if (strlen($query) < 2) {
            return ['success' => false, 'errors' => ['Query too short']];
        }
        
        // Check rate limiting
        if (!Security::checkRateLimit('search', RATE_LIMIT_SEARCH)) {
            return ['success' => false, 'errors' => ['Search limit reached. Please wait.']];
        }
        
        $postsDb = Database::getInstance(POSTS_DB);
        $allPosts = $postsDb->getAll();
        
        $results = [];
        $prioritizedResults = [];
        $standardResults = [];
        
        foreach ($allPosts as $post) {
            if (!$post['is_active']) {
                continue;
            }
            
            $title = strtolower($post['title']);
            $content = strtolower($post['content']);
            $relevanceScore = 0;
            
            // Check trigger questions (highest priority)
            if (!empty($post['trigger_questions'])) {
                foreach ($post['trigger_questions'] as $question) {
                    $questionLower = strtolower($question);
                    if (strpos($questionLower, $query) !== false || $query === $questionLower) {
                        $relevanceScore += 100; // Highest priority
                    }
                }
            }
            
            // Check title match (second priority)
            if (strpos($title, $query) !== false) {
                $relevanceScore += 50;
                // Exact match bonus
                if ($title === $query) {
                    $relevanceScore += 30;
                }
            }
            
            // Check content match (third priority)
            if (strpos($content, $query) !== false) {
                $relevanceScore += 20;
            }
            
            // Partial word matching
            $words = explode(' ', $query);
            foreach ($words as $word) {
                if (strlen($word) > 2) {
                    if (strpos($title, $word) !== false) {
                        $relevanceScore += 10;
                    }
                    if (strpos($content, $word) !== false) {
                        $relevanceScore += 5;
                    }
                }
            }
            
            // Add user info
            $usersDb = Database::getInstance(USERS_DB);
            $user = $usersDb->getById($post['user_id']);
            if ($user) {
                $post['username'] = $user['username'];
                $post['display_name'] = $user['display_name'];
            }
            
            // Sort by relevance
            if ($relevanceScore > 0) {
                $post['relevance_score'] = $relevanceScore;
                
                if ($relevanceScore >= 100) {
                    $prioritizedResults[] = $post;
                } else {
                    $standardResults[] = $post;
                }
            }
        }
        
        // Sort results by relevance score
        usort($prioritizedResults, function($a, $b) {
            return $b['relevance_score'] - $a['relevance_score'];
        });
        
        usort($standardResults, function($a, $b) {
            return $b['relevance_score'] - $a['relevance_score'];
        });
        
        // Combine results
        $allResults = array_merge($prioritizedResults, $standardResults);
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $posts = array_slice($allResults, $offset, $limit);
        
        return [
            'success' => true,
            'posts' => $posts,
            'total' => count($allResults),
            'page' => $page,
            'pages' => ceil(count($allResults) / $limit),
            'query' => $query
        ];
    }

    /**
     * Delete post
     */
    public static function deletePost($postId) {
        $post = self::getPost($postId);
        
        if (!$post) {
            return ['success' => false, 'errors' => ['Post not found']];
        }
        
        $currentUser = Auth::getCurrentUser();
        $isOwner = Security::isOwner();
        
        // Check if user can delete (owner or post creator)
        if (!$isOwner && $post['user_id'] != $currentUser['id']) {
            return ['success' => false, 'errors' => ['Unauthorized']];
        }
        
        $postsDb = Database::getInstance(POSTS_DB);
        $postsDb->update($postId, ['is_active' => false]);
        
        return ['success' => true];
    }

    /**
     * Increment like count
     */
    public static function incrementLikeCount($postId) {
        $postsDb = Database::getInstance(POSTS_DB);
        $post = $postsDb->getById($postId);
        
        if ($post) {
            $postsDb->update($postId, [
                'like_count' => ($post['like_count'] ?? 0) + 1
            ]);
        }
    }

    /**
     * Decrement like count
     */
    public static function decrementLikeCount($postId) {
        $postsDb = Database::getInstance(POSTS_DB);
        $post = $postsDb->getById($postId);
        
        if ($post) {
            $newCount = max(0, ($post['like_count'] ?? 0) - 1);
            $postsDb->update($postId, ['like_count' => $newCount]);
        }
    }

    /**
     * Increment comment count
     */
    public static function incrementCommentCount($postId) {
        $postsDb = Database::getInstance(POSTS_DB);
        $post = $postsDb->getById($postId);
        
        if ($post) {
            $postsDb->update($postId, [
                'comment_count' => ($post['comment_count'] ?? 0) + 1
            ]);
        }
    }

    /**
     * Increment download count
     */
    public static function incrementDownloadCount($postId) {
        $postsDb = Database::getInstance(POSTS_DB);
        $post = $postsDb->getById($postId);
        
        if ($post) {
            $postsDb->update($postId, [
                'download_count' => ($post['download_count'] ?? 0) + 1
            ]);
        }
    }

    /**
     * Get trending posts
     */
    public static function getTrendingPosts($limit = 10) {
        $postsDb = Database::getInstance(POSTS_DB);
        $allPosts = $postsDb->getAll();
        
        // Filter active posts from last 7 days
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $recentPosts = array_filter($allPosts, function($post) use ($sevenDaysAgo) {
            return $post['is_active'] && $post['created_at'] >= $sevenDaysAgo;
        });
        
        // Calculate engagement score (likes + comments * 2)
        foreach ($recentPosts as &$post) {
            $post['engagement_score'] = ($post['like_count'] ?? 0) + (($post['comment_count'] ?? 0) * 2);
        }
        
        // Sort by engagement score
        usort($recentPosts, function($a, $b) {
            return $b['engagement_score'] - $a['engagement_score'];
        });
        
        // Get top posts
        $posts = array_slice($recentPosts, 0, $limit);
        
        // Add user info
        $usersDb = Database::getInstance(USERS_DB);
        foreach ($posts as &$post) {
            $user = $usersDb->getById($post['user_id']);
            if ($user) {
                $post['username'] = $user['username'];
                $post['display_name'] = $user['display_name'];
            }
        }
        
        return $posts;
    }
}
?>