<?php
/**
 * MTC_SHARE4U - Authentication System
 * User registration, login, and session management
 */

class Auth {
    
    /**
     * Register new user
     */
    public static function register($username, $email, $password, $displayName = null, $about = '') {
        // Sanitize inputs
        $username = Security::sanitizeInput($username);
        $email = Security::sanitizeInput($email);
        $displayName = $displayName ? Security::sanitizeInput($displayName) : $username;
        $about = Security::sanitizeInput($about);
        
        // Validate inputs
        $errors = self::validateRegistration($username, $email, $password);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $usersDb = Database::getInstance(USERS_DB);
        
        // Check if username already exists
        if ($usersDb->findOne(['username' => $username])) {
            return ['success' => false, 'errors' => ['Username already taken']];
        }
        
        // Check if email already exists
        if ($usersDb->findOne(['email' => $email])) {
            return ['success' => false, 'errors' => ['Email already registered']];
        }
        
        // Create user
        $userId = $usersDb->insert([
            'username' => $username,
            'email' => $email,
            'password' => Security::hashPassword($password),
            'display_name' => $displayName,
            'about' => $about,
            'profile_image' => null,
            'is_owner' => ($username === OWNER_USERNAME),
            'is_active' => true,
            'download_restricted' => false,
            'follower_count' => 0,
            'following_count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Track registration
        Security::trackVisit($userId, 'registration');
        
        // Auto-login after registration
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['is_owner'] = ($username === OWNER_USERNAME);
        
        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Login user
     */
    public static function login($username, $password, $hardwareKey = null) {
        $username = Security::sanitizeInput($username);
        
        // Check rate limiting
        if (!Security::checkRateLimit('login', MAX_LOGIN_ATTEMPTS)) {
            return ['success' => false, 'errors' => ['Too many login attempts. Please try again later.']];
        }
        
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->findOne(['username' => $username]);
        
        if (!$user) {
            return ['success' => false, 'errors' => ['Invalid username or password']];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'errors' => ['Account is suspended']];
        }
        
        // Verify password
        if (!Security::verifyPassword($password, $user['password'])) {
            return ['success' => false, 'errors' => ['Invalid username or password']];
        }
        
        // Owner requires hardware key verification
        if ($user['is_owner']) {
            if (!$hardwareKey || !Security::verifyHardwareKey($hardwareKey)) {
                return ['success' => false, 'errors' => ['Invalid hardware key']];
            }
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['is_owner'] = $user['is_owner'];
        $_SESSION['login_time'] = time();
        
        // Track login
        Security::trackVisit($user['id'], 'login');
        
        // Reset rate limit on successful login
        unset($_SESSION['rate_limit_login_' . Security::getRealIP()]);
        
        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout user
     */
    public static function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        // Track logout
        if ($userId) {
            Security::trackVisit($userId, 'logout');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        return ['success' => true];
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
    }

    /**
     * Get current user
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $usersDb = Database::getInstance(USERS_DB);
        return $usersDb->getById($_SESSION['user_id']);
    }

    /**
     * Require login (redirect if not logged in)
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Require owner access
     */
    public static function requireOwner() {
        self::requireLogin();
        
        if (!Security::isOwner()) {
            header('Location: /index.php');
            exit;
        }
    }

    /**
     * Update profile
     */
    public static function updateProfile($displayName, $about) {
        self::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $usersDb = Database::getInstance(USERS_DB);
        
        $updateData = [
            'display_name' => Security::sanitizeInput($displayName),
            'about' => Security::sanitizeInput($about)
        ];
        
        $usersDb->update($userId, $updateData);
        
        // Update session
        $_SESSION['display_name'] = $updateData['display_name'];
        
        return ['success' => true];
    }

    /**
     * Change password
     */
    public static function changePassword($currentPassword, $newPassword) {
        self::requireLogin();
        
        $userId = $_SESSION['user_id'];
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->getById($userId);
        
        // Verify current password
        if (!Security::verifyPassword($currentPassword, $user['password'])) {
            return ['success' => false, 'errors' => ['Current password is incorrect']];
        }
        
        // Validate new password
        $errors = self::validatePassword($newPassword);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Update password
        $usersDb->update($userId, [
            'password' => Security::hashPassword($newPassword)
        ]);
        
        return ['success' => true];
    }

    /**
     * Request password recovery
     */
    public static function requestPasswordRecovery($email) {
        $email = Security::sanitizeInput($email);
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->findOne(['email' => $email]);
        
        if (!$user) {
            // Don't reveal if email exists
            return ['success' => true, 'message' => 'If the email exists, a recovery code has been generated'];
        }
        
        // Generate recovery code
        $code = Security::generateRecoveryCode($email);
        
        return [
            'success' => true,
            'message' => 'Contact TypeVC at ' . OWNER_EMAIL . ' for your secret code'
        ];
    }

    /**
     * Reset password with recovery code
     */
    public static function resetPassword($email, $code, $newPassword) {
        $email = Security::sanitizeInput($email);
        
        // Verify recovery code
        if (!Security::verifyRecoveryCode($email, $code)) {
            return ['success' => false, 'errors' => ['Invalid or expired recovery code']];
        }
        
        // Validate new password
        $errors = self::validatePassword($newPassword);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Update password
        $usersDb = Database::getInstance(USERS_DB);
        $user = $usersDb->findOne(['email' => $email]);
        
        if ($user) {
            $usersDb->update($user['id'], [
                'password' => Security::hashPassword($newPassword)
            ]);
        }
        
        return ['success' => true];
    }

    /**
     * Validate registration data
     */
    private static function validateRegistration($username, $email, $password) {
        $errors = [];
        
        // Username validation
        if (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = 'Username must be between 3 and 20 characters';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }
        
        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }
        
        // Password validation
        $passwordErrors = self::validatePassword($password);
        $errors = array_merge($errors, $passwordErrors);
        
        return $errors;
    }

    /**
     * Validate password strength
     */
    private static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }

    /**
     * Restrict user downloads (admin function)
     */
    public static function restrictUserDownloads($userId, $restricted = true) {
        if (!Security::isOwner()) {
            return ['success' => false, 'errors' => ['Unauthorized']];
        }
        
        $usersDb = Database::getInstance(USERS_DB);
        $usersDb->update($userId, ['download_restricted' => $restricted]);
        
        return ['success' => true];
    }

    /**
     * Deactivate user account (admin function)
     */
    public static function deactivateUser($userId) {
        if (!Security::isOwner()) {
            return ['success' => false, 'errors' => ['Unauthorized']];
        }
        
        $usersDb = Database::getInstance(USERS_DB);
        $usersDb->update($userId, ['is_active' => false]);
        
        return ['success' => true];
    }
}
?>