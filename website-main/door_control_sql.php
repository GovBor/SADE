<?php
session_start();
require_once 'config/database_sql.php';

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
    try {
        $db = new Database();
        
        // Check if user has permission for this lab
        if (!in_array($lab_id, $_SESSION['user']['allowedLabs'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Access denied for Lab ' . $lab_id
            ]);
            exit;
        }
        
        // Log the door control action
        $logId = 'MANUAL_UNLOCK_' . time() . '_' . $lab_id;
        $db->insert('access_logs', [
            'log_id' => $logId,
            'device_id' => 'SADE_DOOR_' . $lab_id,
            'lab_id' => $lab_id,
            'user_id' => $_SESSION['user']['userId'],
            'user_type' => strtoupper($_SESSION['user']['type']),
            'user_name' => $_SESSION['user']['name'],
            'action' => 'MANUAL_UNLOCK',
            'method' => 'WEB_DASHBOARD',
            'door_status' => 'UNLOCKED'
        ]);
        
        // Update device status
        $db->update('devices', 
            [
                'door_locked' => 0,
                'last_seen' => date('Y-m-d H:i:s')
            ],
            'lab_id = ?',
            [$lab_id]
        );
        
        // Here you would send the actual command to Arduino
        // For now, we'll just simulate it
        
        echo json_encode([
            'success' => true,
            'message' => 'Lab ' . $lab_id . ' unlocked successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid command'
    ]);
}
?>
