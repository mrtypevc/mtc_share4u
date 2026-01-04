<?php
/**
 * MTC_SHARE4U - Delete Post API Endpoint
 * Handles post deletion
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

$postId = $_POST['post_id'] ?? null;

if (!$postId) {
    echo json_encode(['success' => false, 'errors' => ['Post ID is required']]);
    exit;
}

$result = PostManager::deletePost($postId);

header('Content-Type: application/json');
echo json_encode($result);
?>