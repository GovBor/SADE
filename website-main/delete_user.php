<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    $db = new Database();
    
    // Check if user exists
    $user = $db->fetch("SELECT * FROM users WHERE user_id = ?", [$user_id]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Delete user
    $db->query("DELETE FROM users WHERE user_id = ?", [$user_id]);
    
    // Log the deletion
    $db->insert('system_logs', [
        'log_id' => 'USER_DELETE_' . time(),
        'event_type' => 'USER_DELETE',
        'message' => 'User deleted: ' . $user['first_name'] . ' ' . $user['last_name'],
        'user_id' => $_SESSION['user']['userId'],
        'user_name' => $_SESSION['user']['name'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'component' => 'WEB_DASHBOARD'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Delete failed: ' . $e->getMessage()
    ]);
}
?>
