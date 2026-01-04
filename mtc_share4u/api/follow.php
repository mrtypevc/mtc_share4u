<?php
/**
 * MTC_SHARE4U - Follow API Endpoint
 * Handles follow/unfollow functionality
 */

require_once __DIR__ . '/../../includes/functions.php';

// Require login
Auth::requireLogin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'errors' => ['Method not allowed']]);
    exit;
}

$targetUserId = $_POST['user_id'] ?? null;

if (!$targetUserId) {
    echo json_encode(['success' => false, 'errors' => ['User ID is required']]);
    exit;
}

$result = InteractionManager::followUser($targetUserId);

header('Content-Type: application/json');
echo json_encode($result);
?>