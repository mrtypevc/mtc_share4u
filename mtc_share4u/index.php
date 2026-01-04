<?php
/**
 * MTC_SHARE4U - Main Index Page
 * YouTube-inspired social media feed
 */

require_once __DIR__ . '/includes/functions.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$feed = PostManager::getFeed($page);
$trendingPosts = PostManager::getTrendingPosts(5);
$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTC_SHARE4U - Share Your World</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1><i class="fas fa-share-nodes"></i> MTC_SHARE4U</h1>
            </div>
            
            <div class="nav-search">
                <form action="search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Search videos, posts, questions..." class="search-input">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="nav-actions">
                <?php if ($currentUser): ?>
                    <a href="profile.php" class="nav-profile">
                        <img src="<?php echo $currentUser['profile_image'] ?: 'assets/images/default-avatar.png'; ?>" alt="Profile" class="nav-avatar">
                        <span class="nav-username"><?php echo htmlspecialchars($currentUser['display_name']); ?></span>
                    </a>
                    
                    <?php if (Security::isOwner()): ?>
                        <a href="admin/dashboard.php" class="nav-admin">
                            <i class="fas fa-shield-halved"></i> Admin
                        </a>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="nav-logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-login">Login</a>
                    <a href="register.php" class="nav-register">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3>Navigation</h3>
                <ul class="sidebar-menu">
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="trending.php"><i class="fas fa-fire"></i> Trending</a></li>
                    <li><a href="explore.php"><i class="fas fa-compass"></i> Explore</a></li>
                </ul>
            </div>
            
            <?php if ($currentUser): ?>
                <div class="sidebar-section">
                    <h3>Quick Actions</h3>
                    <ul class="sidebar-menu">
                        <li><a href="create.php"><i class="fas fa-plus"></i> Create Post</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li><a href="activity.php"><i class="fas fa-bell"></i> Activity Feed</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="sidebar-section">
                <h3>Trending Now</h3>
                <ul class="trending-list">
                    <?php foreach ($trendingPosts as $post): ?>
                        <li>
                            <a href="post.php?id=<?php echo $post['id']; ?>">
                                <span class="trending-rank">#1</span>
                                <span class="trending-title"><?php echo htmlspecialchars(truncateText($post['title'], 30)); ?></span>
                                <span class="trending-views"><i class="fas fa-eye"></i> <?php echo $post['like_count'] + $post['comment_count']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Feed Section -->
        <main class="feed-section">
            <div class="feed-header">
                <h2><i class="fas fa-stream"></i> Latest Posts</h2>
                <div class="feed-tabs">
                    <button class="tab-btn active">All Posts</button>
                    <?php if ($currentUser): ?>
                        <button class="tab-btn" onclick="window.location.href='activity.php'">Following</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="feed-content">
                <?php if (empty($feed['posts'])): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox fa-3x"></i>
                        <h3>No posts yet</h3>
                        <p>Be the first to share something amazing!</p>
                        <?php if ($currentUser): ?>
                            <a href="create.php" class="btn btn-primary">Create Post</a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary">Sign Up to Post</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($feed['posts'] as $post): ?>
                        <?php include __DIR__ . '/includes/post-card.php'; ?>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <?php if ($feed['page'] > 1): ?>
                            <a href="?page=<?php echo $feed['page'] - 1; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?php echo $feed['page']; ?> of <?php echo $feed['pages']; ?>
                        </span>
                        
                        <?php if ($feed['page'] < $feed['pages']): ?>
                            <a href="?page=<?php echo $feed['page'] + 1; ?>" class="pagination-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Floating Create Button -->
    <?php if ($currentUser): ?>
        <a href="create.php" class="fab-create">
            <i class="fas fa-plus"></i>
        </a>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>