<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$lab_id = $input['lab_id'] ?? '';

if ($action === 'unlock' && !empty($lab_id)) {
    // Send command to Arduino (you'll implement this based on your setup)
    $command = json_encode([
        'command' => 'UNLOCK_DOOR',
        'lab_id' => $lab_id,
        'user_id' => $_SESSION['user']['name'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // For now, just log the command (replace with actual Arduino communication)
    file_put_contents('logs/door_commands.log', $command . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lab ' . $lab_id . ' unlocked successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid command'
    ]);
}
?>
