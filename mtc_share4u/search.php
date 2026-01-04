<?php
/**
 * MTC_SHARE4U - Search Page
 * YouTube-style intelligent search with FAQ triggers
 */

require_once __DIR__ . '/includes/functions.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$results = null;

if ($query) {
    $results = PostManager::searchPosts($query, $page);
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo htmlspecialchars($query); ?> - MTC_SHARE4U</title>
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
                    <input type="text" name="q" placeholder="Search videos, posts, questions..." 
                           class="search-input" value="<?php echo htmlspecialchars($query); ?>" autofocus>
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="nav-actions">
                <?php if ($currentUser): ?>
                    <a href="profile.php" class="nav-profile">
                        <img src="<?php echo $currentUser['profile_image'] ?: 'assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="nav-avatar">
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
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3>Navigation</h3>
                <ul class="sidebar-menu">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="trending.php"><i class="fas fa-fire"></i> Trending</a></li>
                    <li><a href="explore.php"><i class="fas fa-compass"></i> Explore</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3>Search Tips</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 0.75rem; color: var(--text-secondary);">
                        <i class="fas fa-lightbulb" style="color: var(--accent-warning);"></i> 
                        Try specific questions for FAQ results
                    </li>
                    <li style="margin-bottom: 0.75rem; color: var(--text-secondary);">
                        <i class="fas fa-lightbulb" style="color: var(--accent-warning);"></i> 
                        Use partial titles for broader results
                    </li>
                    <li style="color: var(--text-secondary);">
                        <i class="fas fa-lightbulb" style="color: var(--accent-warning);"></i> 
                        FAQ triggers show results first
                    </li>
                </ul>
            </div>
        </aside>

        <main class="feed-section">
            <div class="feed-header">
                <h2>
                    <?php if ($query): ?>
                        <i class="fas fa-search"></i> Search Results for "<?php echo htmlspecialchars($query); ?>"
                    <?php else: ?>
                        <i class="fas fa-search"></i> Search
                    <?php endif; ?>
                </h2>
            </div>
            
            <div class="feed-content">
                <?php if (!$query): ?>
                    <div class="empty-state">
                        <i class="fas fa-search fa-3x"></i>
                        <h3>Start Searching</h3>
                        <p>Enter keywords, questions, or post titles to find content</p>
                    </div>
                <?php elseif (!$results['success']): ?>
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                        <h3>Search Error</h3>
                        <p><?php echo htmlspecialchars(implode(', ', $results['errors'])); ?></p>
                    </div>
                <?php elseif (empty($results['posts'])): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox fa-3x"></i>
                        <h3>No Results Found</h3>
                        <p>Try different keywords or browse our <a href="index.php">latest posts</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results['posts'] as $post): ?>
                        <?php 
                        // Set relevance score for display
                        $post['relevance_score'] = $post['relevance_score'] ?? 0;
                        ?>
                        <?php include __DIR__ . '/includes/post-card.php'; ?>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <?php if ($results['page'] > 1): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $results['page'] - 1; ?>" 
                               class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">
                            Page <?php echo $results['page']; ?> of <?php echo $results['pages']; ?>
                            (<?php echo $results['total']; ?> results)
                        </span>
                        
                        <?php if ($results['page'] < $results['pages']): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $results['page'] + 1; ?>" 
                               class="pagination-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($currentUser): ?>
        <a href="create.php" class="fab-create">
            <i class="fas fa-plus"></i>
        </a>
    <?php endif; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>