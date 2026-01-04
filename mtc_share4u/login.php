<?php
/**
 * MTC_SHARE4U - Login Page
 */

require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $hardwareKey = $_POST['hardware_key'] ?? '';
    
    $result = Auth::login($username, $password, $hardwareKey);
    
    if ($result['success']) {
        // Check if user needs to provide hardware key
        if ($result['user']['is_owner'] && !$hardwareKey) {
            $error = 'Please provide your hardware key for owner access.';
        } else {
            setFlashMessage('success', 'Welcome back, ' . $result['user']['display_name'] . '!');
            redirect('index.php');
        }
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
    <title>Login - MTC_SHARE4U</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }
        
        .login-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            box-shadow: var(--shadow-heavy);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            font-size: 2rem;
            color: var(--accent-primary);
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: var(--text-secondary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: border-color var(--transition-speed);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--accent-secondary);
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background-color: var(--accent-primary);
            color: var(--text-primary);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-speed);
        }
        
        .btn-login:hover {
            background-color: var(--accent-danger);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .login-footer a {
            color: var(--accent-secondary);
            font-weight: 500;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            border: 1px solid var(--accent-danger);
            color: var(--accent-danger);
        }
        
        .alert-success {
            background-color: rgba(46, 166, 64, 0.1);
            border: 1px solid var(--accent-success);
            color: var(--accent-success);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-share-nodes"></i> MTC_SHARE4U</h1>
                <p>Welcome back! Please login to continue.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username" autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password" autocomplete="current-password">
                </div>
                
                <div class="form-group">
                    <label for="hardware_key">
                        Hardware Key 
                        <small style="color: var(--text-secondary);">(Owner only)</small>
                    </label>
                    <input type="password" id="hardware_key" name="hardware_key" 
                           placeholder="Enter hardware key if owner" autocomplete="off">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                <p style="margin-top: 0.5rem;">
                    <a href="forgot-password.php">Forgot Password?</a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Add GPS coordinates to login
        document.querySelector('form').addEventListener('submit', function(e) {
            if (window.userLatitude !== undefined) {
                const latitudeInput = document.createElement('input');
                latitudeInput.type = 'hidden';
                latitudeInput.name = 'latitude';
                latitudeInput.value = window.userLatitude;
                
                const longitudeInput = document.createElement('input');
                longitudeInput.type = 'hidden';
                longitudeInput.name = 'longitude';
                longitudeInput.value = window.userLongitude;
                
                const accuracyInput = document.createElement('input');
                accuracyInput.type = 'hidden';
                accuracyInput.name = 'accuracy';
                accuracyInput.value = window.userAccuracy;
                
                this.appendChild(latitudeInput);
                this.appendChild(longitudeInput);
                this.appendChild(accuracyInput);
            }
        });
    </script>
</body>
</html>