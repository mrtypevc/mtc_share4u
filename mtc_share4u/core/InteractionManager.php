<?php
/**
 * MTC_SHARE4U - Interaction Manager
 * Handle likes, follows, and comments
 */

class InteractionManager {
    
    /**
     * Like a post
     */
    public static function likePost($postId) {
        Auth::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $likesDb = Database::getInstance(LIKES_DB);
        
        // Check if already liked
        $existingLike = $likesDb->findOne([
            'user_id' => $userId,
            'post_id' => $postId
        ]);
        
        if ($existingLike) {
            // Unlike
            $likesDb->delete($existingLike['id']);
            PostManager::decrementLikeCount($postId);
            return ['success' => true, 'liked' => false];
        }
        
        // Like
        $likesDb->insert([
            'user_id' => $userId,
            'post_id' => $postId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        PostManager::incrementLikeCount($postId);
        
        return ['success' => true, 'liked' => true];
    }

    /**
     * Check if user liked a post
     */
    public static function isPostLiked($postId, $userId = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        if (!$userId) {
            return false;
        }
        
        $likesDb = Database::getInstance(LIKES_DB);
        $like = $likesDb->findOne([
            'user_id' => $userId,
            'post_id' => $postId
        ]);
        
        return $like !== null;
    }

    /**
     * Get post likes
     */
    public static function getPostLikes($postId, $limit = 50) {
        $likesDb = Database::getInstance(LIKES_DB);
        $likes = $likesDb->find(['post_id' => $postId]);
        
        // Sort by created_at
        usort($likes, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Get user info
        $usersDb = Database::getInstance(USERS_DB);
        $result = [];
        
        foreach (array_slice($likes, 0, $limit) as $like) {
            $user = $usersDb->getById($like['user_id']);
            if ($user) {
                $result[] = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'display_name' => $user['display_name'],
                    'profile_image' => $user['profile_image'],
                    'liked_at' => $like['created_at']
                ];
            }
        }
        
        return $result;
    }

    /**
     * Follow a user
     */
    public static function followUser($targetUserId) {
        Auth::requireLogin();
        
        $userId = $_SESSION['user_id'];
        
        // Can't follow yourself
        if ($userId == $targetUserId) {
            return ['success' => false, 'errors' => ['Cannot follow yourself']];
        }
        
        $followsDb = Database::getInstance(FOLLOWS_DB);
        
        // Check if already following
        $existingFollow = $followsDb->findOne([
            'follower_id' => $userId,
            'following_id' => $targetUserId
        ]);
        
        if ($existingFollow) {
            // Unfollow
            $followsDb->delete($existingFollow['id']);
            self::updateFollowCounts($userId, $targetUserId, false);
            return ['success' => true, 'following' => false];
        }
        
        // Follow
        $followsDb->insert([
            'follower_id' => $userId,
            'following_id' => $targetUserId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        self::updateFollowCounts($userId, $targetUserId, true);
        
        return ['success' => true, 'following' => true];
    }

    /**
     * Check if user is following
     */
    public static function isFollowing($targetUserId, $userId = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        if (!$userId) {
            return false;
        }
        
        $followsDb = Database::getInstance(FOLLOWS_DB);
        $follow = $followsDb->findOne([
            'follower_id' => $userId,
            'following_id' => $targetUserId
        ]);
        
        return $follow !== null;
    }

    /**
     * Update follow counts
     */
    private static function updateFollowCounts($followerId, $followingId, $isFollowing) {
        $usersDb = Database::getInstance(USERS_DB);
        
        $follower = $usersDb->getById($followerId);
        $following = $usersDb->getById($followingId);
        
        if ($follower) {
            $usersDb->update($followerId, [
                'following_count' => ($follower['following_count'] ?? 0) + ($isFollowing ? 1 : -1)
            ]);
        }
        
        if ($following) {
            $usersDb->update($followingId, [
                'follower_count' => ($following['follower_count'] ?? 0) + ($isFollowing ? 1 : -1)
            ]);
        }
    }

    /**
     * Get user followers
     */
    public static function getFollowers($userId, $page = 1, $limit = 50) {
        $followsDb = Database::getInstance(FOLLOWS_DB);
        $follows = $followsDb->find(['following_id' => $userId]);
        
        // Sort by created_at
        usort($follows, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $follows = array_slice($follows, $offset, $limit);
        
        // Get user info
        $usersDb = Database::getInstance(USERS_DB);
        $result = [];
        
        foreach ($follows as $follow) {
            $user = $usersDb->getById($follow['follower_id']);
            if ($user) {
                $result[] = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'display_name' => $user['display_name'],
                    'profile_image' => $user['profile_image'],
                    'followed_at' => $follow['created_at']
                ];
            }
        }
        
        return $result;
    }

    /**
     * Get user following
     */
    public static function getFollowing($userId, $page = 1, $limit = 50) {
        $followsDb = Database::getInstance(FOLLOWS_DB);
        $follows = $followsDb->find(['follower_id' => $userId]);
        
        // Sort by created_at
        usort($follows, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $follows = array_slice($follows, $offset, $limit);
        
        // Get user info
        $usersDb = Database::getInstance(USERS_DB);
        $result = [];
        
        foreach ($follows as $follow) {
            $user = $usersDb->getById($follow['following_id']);
            if ($user) {
                $result[] = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'display_name' => $user['display_name'],
                    'profile_image' => $user['profile_image'],
                    'following_at' => $follow['created_at']
                ];
            }
        }
        
        return $result;
    }

    /**
     * Add comment to post
     */
    public static function addComment($postId, $content, $mediaUrl = null) {
        Auth::requireLogin();
        
        // Check rate limiting
        if (!Security::checkRateLimit('comments', RATE_LIMIT_COMMENTS)) {
            return ['success' => false, 'errors' => ['Comment limit reached. Please wait.']];
        }
        
        $userId = $_SESSION['user_id'];
        $content = Security::sanitizeInput($content);
        
        if (empty($content)) {
            return ['success' => false, 'errors' => ['Comment cannot be empty']];
        }
        
        $commentsDb = Database::getInstance(COMMENTS_DB);
        
        $commentId = $commentsDb->insert([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
            'media_url' => $mediaUrl,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Increment comment count
        PostManager::incrementCommentCount($postId);
        
        return ['success' => true, 'comment_id' => $commentId];
    }

    /**
     * Get post comments
     */
    public static function getPostComments($postId, $page = 1, $limit = COMMENTS_PER_PAGE) {
        $commentsDb = Database::getInstance(COMMENTS_DB);
        $comments = $commentsDb->find(['post_id' => $postId]);
        
        // Filter active comments and sort by created_at
        $activeComments = array_filter($comments, function($comment) {
            return $comment['is_active'] === true;
        });
        
        usort($activeComments, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $comments = array_slice($activeComments, $offset, $limit);
        
        // Get user info
        $usersDb = Database::getInstance(USERS_DB);
        $result = [];
        
        foreach ($comments as $comment) {
            $user = $usersDb->getById($comment['user_id']);
            if ($user) {
                $result[] = [
                    'comment_id' => $comment['id'],
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'display_name' => $user['display_name'],
                    'profile_image' => $user['profile_image'],
                    'content' => $comment['content'],
                    'media_url' => $comment['media_url'],
                    'created_at' => $comment['created_at']
                ];
            }
        }
        
        return [
            'comments' => $result,
            'total' => count($activeComments),
            'page' => $page,
            'pages' => ceil(count($activeComments) / $limit)
        ];
    }

    /**
     * Delete comment
     */
    public static function deleteComment($commentId) {
        Auth::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $commentsDb = Database::getInstance(COMMENTS_DB);
        $comment = $commentsDb->getById($commentId);
        
        if (!$comment) {
            return ['success' => false, 'errors' => ['Comment not found']];
        }
        
        // Owner can delete any comment
        $isOwner = Security::isOwner();
        
        // Check if user can delete
        if (!$isOwner && $comment['user_id'] != $userId) {
            return ['success' => false, 'errors' => ['Unauthorized']];
        }
        
        $commentsDb->update($commentId, ['is_active' => false]);
        
        return ['success' => true];
    }

    /**
     * Get user activity feed (posts from followed users)
     */
    public static function getActivityFeed($userId, $page = 1, $limit = POSTS_PER_PAGE) {
        $followsDb = Database::getInstance(FOLLOWS_DB);
        $follows = $followsDb->find(['follower_id' => $userId]);
        
        $followingIds = array_map(function($follow) {
            return $follow['following_id'];
        }, $follows);
        
        if (empty($followingIds)) {
            return [
                'posts' => [],
                'total' => 0,
                'page' => $page,
                'pages' => 0
            ];
        }
        
        $postsDb = Database::getInstance(POSTS_DB);
        $allPosts = $postsDb->getAll();
        
        // Filter posts from followed users
        $feedPosts = array_filter($allPosts, function($post) use ($followingIds) {
            return $post['is_active'] && in_array($post['user_id'], $followingIds);
        });
        
        // Sort by created_at descending
        usort($feedPosts, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Pagination
        $offset = ($page - 1) * $limit;
        $posts = array_slice($feedPosts, $offset, $limit);
        
        // Add user info
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
            'total' => count($feedPosts),
            'page' => $page,
            'pages' => ceil(count($feedPosts) / $limit)
        ];
    }
}
?>