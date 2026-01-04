<?php
/**
 * Post Card Template
 * Reusable post display component
 */

$currentUser = Auth::getCurrentUser();
$isLiked = $currentUser ? InteractionManager::isPostLiked($post['id'], $currentUser['id']) : false;
$isFollowing = $currentUser ? InteractionManager::isFollowing($post['user_id'], $currentUser['id']) : false;
$canDownload = canDownload($post['user_id']);
?>

<article class="post-card" data-post-id="<?php echo $post['id']; ?>">
    <!-- Post Header -->
    <div class="post-header">
        <div class="post-author">
            <img src="<?php echo $post['profile_image'] ?: '/assets/images/default-avatar.png'; ?>" 
                 alt="<?php echo htmlspecialchars($post['display_name']); ?>" 
                 class="author-avatar">
            
            <div class="author-info">
                <h4 class="author-name"><?php echo htmlspecialchars($post['display_name']); ?></h4>
                <p class="author-username">@<?php echo htmlspecialchars($post['username']); ?></p>
                <p class="post-time"><?php echo formatDate($post['created_at']); ?></p>
            </div>
        </div>
        
        <div class="post-actions">
            <?php if ($currentUser && ($currentUser['id'] == $post['user_id'] || Security::isOwner())): ?>
                <button class="post-action-btn delete-post-btn" data-post-id="<?php echo $post['id']; ?>">
                    <i class="fas fa-trash"></i>
                </button>
            <?php endif; ?>
            
            <button class="post-action-btn share-btn" onclick="sharePost('<?php echo $post['id']; ?>')">
                <i class="fas fa-share"></i>
            </button>
        </div>
    </div>
    
    <!-- Post Content -->
    <div class="post-content">
        <h3 class="post-title">
            <a href="post.php?id=<?php echo $post['id']; ?>">
                <?php echo htmlspecialchars($post['title']); ?>
            </a>
        </h3>
        
        <?php if (!empty($post['content'])): ?>
            <p class="post-description"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($post['trigger_questions'])): ?>
            <div class="trigger-questions">
                <strong><i class="fas fa-question-circle"></i> FAQ:</strong>
                <?php foreach (array_slice($post['trigger_questions'], 0, 3) as $question): ?>
                    <span class="trigger-question"><?php echo htmlspecialchars($question); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Post Media -->
    <?php if ($post['media_type'] === 'video'): ?>
        <div class="post-media post-video">
            <video controls poster="<?php echo $post['thumbnail_path'] ?: ''; ?>">
                <source src="<?php echo $post['media_path']; ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    <?php elseif ($post['media_type'] === 'image'): ?>
        <div class="post-media post-image">
            <img src="<?php echo $post['media_path']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
        </div>
    <?php elseif ($post['media_type'] === 'file'): ?>
        <div class="post-media post-file">
            <div class="file-info">
                <i class="fas fa-file-download fa-3x"></i>
                <div class="file-details">
                    <p class="file-name"><?php echo htmlspecialchars(basename($post['media_path'])); ?></p>
                    <p class="file-size">
                        <i class="fas fa-download"></i> <?php echo $post['download_count']; ?> downloads
                    </p>
                </div>
            </div>
            <?php if ($canDownload): ?>
                <a href="<?php echo $post['media_path']; ?>" download class="btn btn-download" 
                   onclick="trackDownload('<?php echo $post['id']; ?>')">
                    <i class="fas fa-download"></i> Download
                </a>
            <?php else: ?>
                <div class="download-restricted">
                    <i class="fas fa-lock"></i> Download Restricted
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Post Stats -->
    <div class="post-stats">
        <button class="stat-btn like-btn <?php echo $isLiked ? 'liked' : ''; ?>" 
                data-post-id="<?php echo $post['id']; ?>"
                <?php echo $currentUser ? '' : 'onclick="showLoginModal()"'; ?>>
            <i class="fas fa-heart"></i>
            <span class="stat-count"><?php echo $post['like_count']; ?></span>
        </button>
        
        <button class="stat-btn comment-btn" onclick="scrollToComments('<?php echo $post['id']; ?>')">
            <i class="fas fa-comment"></i>
            <span class="stat-count"><?php echo $post['comment_count']; ?></span>
        </button>
        
        <button class="stat-btn share-btn" onclick="sharePost('<?php echo $post['id']; ?>')">
            <i class="fas fa-share"></i>
        </button>
    </div>
    
    <!-- Follow Button -->
    <?php if ($currentUser && $currentUser['id'] != $post['user_id']): ?>
        <div class="post-follow">
            <button class="btn btn-follow <?php echo $isFollowing ? 'following' : ''; ?>" 
                    data-user-id="<?php echo $post['user_id']; ?>">
                <?php echo $isFollowing ? 'Following' : 'Follow'; ?>
            </button>
        </div>
    <?php endif; ?>
</article>