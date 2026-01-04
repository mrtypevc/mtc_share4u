<?php
/**
 * MTC_SHARE4U - User Profile Page
 */

require_once __DIR__ . '/includes/functions.php';

$userId = isset($_GET['id']) ? $_GET['id'] : null;

// If no ID provided, show current user's profile
if (!$userId && Auth::isLoggedIn()) {
    $userId = $_SESSION['user_id'];
}

// Require login to view profiles
if (!$userId) {
    setFlashMessage('info', 'Please login to view profiles');
    redirect('login.php');
}

$usersDb = Database::getInstance(USERS_DB);
$user = $usersDb->getById($userId);

if (!$user || !$user['is_active']) {
    setFlashMessage('error', 'User not found');
    redirect('index.php');
}

$currentUser = Auth::getCurrentUser();
$isOwnProfile = $currentUser && $currentUser['id'] == $userId;
$isFollowing = $currentUser ? InteractionManager::isFollowing($userId, $currentUser['id']) : false;
$userPosts = PostManager::getUserPosts($userId);
$userStats = getUserStatistics($userId);

// Handle profile update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnProfile) {
    $displayName = $_POST['display_name'] ?? '';
    $about = $_POST['about'] ?? '';
    
    $result = Auth::updateProfile($displayName, $about);
    
    if ($result['success']) {
        $success = 'Profile updated successfully!';
        $user = $usersDb->getById($userId); // Refresh user data
    } else {
        $error = implode('<br>', $result['errors']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['display_name']); ?> - MTC_SHARE4U</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            padding: 3rem 2rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-info {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent-primary);
        }
        
        .profile-details {
            flex: 1;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .profile-username {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .profile-bio {
            color: var(--text-primary);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .profile-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-owner {
            background-color: var(--accent-primary);
            color: var(--text-primary);
        }
        
        .badge-member {
            background-color: var(--accent-secondary);
            color: var(--bg-primary);
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            padding: 1.5rem;
            background-color: var(--bg-secondary);
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-primary);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        .profile-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .profile-tabs {
            display: flex;
            gap: 1rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .tab-btn {
            padding: 1rem 2rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: color var(--transition-speed);
        }
        
        .tab-btn.active {
            color: var(--accent-primary);
            border-bottom: 2px solid var(--accent-primary);
        }
        
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
            }
        }
    </style>
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

    <!-- Profile Content -->
    <div class="profile-header">
        <div class="profile-container">
            <div class="profile-info">
                <img src="<?php echo $user['profile_image'] ?: '/assets/images/default-avatar.png'; ?>" 
                     alt="<?php echo htmlspecialchars($user['display_name']); ?>" 
                     class="profile-avatar">
                
                <div class="profile-details">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['display_name']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="profile-badges">
                        <?php if ($user['is_owner']): ?>
                            <span class="badge badge-owner"><i class="fas fa-crown"></i> Owner</span>
                        <?php else: ?>
                            <span class="badge badge-member"><i class="fas fa-user"></i> Member</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($user['about']): ?>
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
                    <?php endif; ?>
                    
                    <p style="color: var(--text-secondary);">
                        <i class="fas fa-calendar"></i> Joined <?php echo date('F Y', strtotime($user['created_at'])); ?>
                    </p>
                    
                    <div class="profile-actions">
                        <?php if (!$isOwnProfile && $currentUser): ?>
                            <button class="btn btn-follow <?php echo $isFollowing ? 'following' : ''; ?>" 
                                    data-user-id="<?php echo $user['id']; ?>">
                                <?php echo $isFollowing ? 'Following' : 'Follow'; ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($isOwnProfile): ?>
                            <button class="btn btn-primary" onclick="toggleEditForm()">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $userStats['posts']; ?></div>
                    <div class="stat-label">Posts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $userStats['followers']; ?></div>
                    <div class="stat-label">Followers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $userStats['following']; ?></div>
                    <div class="stat-label">Following</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $userStats['total_likes']; ?></div>
                    <div class="stat-label">Total Likes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <?php if ($isOwnProfile): ?>
        <div class="profile-container" id="editProfileForm" style="display: none; margin-bottom: 2rem;">
            <div class="create-card" style="padding: 2rem; background-color: var(--bg-secondary); border-radius: 20px;">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-user-edit"></i> Edit Profile</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="background-color: rgba(231, 76, 60, 0.1); border: 1px solid var(--accent-danger); color: var(--accent-danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="background-color: rgba(46, 166, 64, 0.1); border: 1px solid var(--accent-success); color: var(--accent-success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="display_name">Display Name</label>
                        <input type="text" id="display_name" name="display_name" required
                               value="<?php echo htmlspecialchars($user['display_name']); ?>"
                               style="width: 100%; padding: 1rem; background-color: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary);">
                    </div>
                    
                    <div class="form-group">
                        <label for="about">About</label>
                        <textarea id="about" name="about" rows="4"
                                  style="width: 100%; padding: 1rem; background-color: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); resize: vertical;"><?php echo htmlspecialchars($user['about']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn" onclick="toggleEditForm()" style="margin-left: 1rem;">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- User Posts -->
    <div class="profile-container">
        <div class="profile-tabs">
            <button class="tab-btn active">Posts</button>
            <button class="tab-btn">Liked</button>
            <button class="tab-btn">About</button>
        </div>
        
        <div class="feed-content">
            <?php if (empty($userPosts['posts'])): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox fa-3x"></i>
                    <h3>No posts yet</h3>
                    <p><?php echo $isOwnProfile ? 'Start sharing your content!' : 'This user hasn\'t posted anything yet.'; ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($userPosts['posts'] as $post): ?>
                    <?php include __DIR__ . '/includes/post-card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($currentUser): ?>
        <a href="create.php" class="fab-create">
            <i class="fas fa-plus"></i>
        </a>
    <?php endif; ?>

    <script>
        function toggleEditForm() {
            const form = document.getElementById('editProfileForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>