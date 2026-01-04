<?php
/**
 * MTC_SHARE4U - Track Download API Endpoint
 * Tracks file download counts
 */

require_once __DIR__ . '/../../includes/functions.php';

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

PostManager::incrementDownloadCount($postId);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>