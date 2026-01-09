<?php
session_start();
require_once 'config/database.php';

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
        $accessLogs = $db->getCollection('access_logs');
        $accessLogs->insertOne([
            'logId' => 'MANUAL_UNLOCK_' . time() . '_' . $lab_id,
            'deviceInfo' => [
                'deviceId' => 'SADE_DOOR_' . $lab_id,
                'labId' => $lab_id,
                'deviceType' => 'DOOR_CONTROLLER'
            ],
            'userInfo' => [
                'userId' => $_SESSION['user']['userId'],
                'userType' => strtoupper($_SESSION['user']['type']),
                'name' => $_SESSION['user']['name']
            ],
            'accessDetails' => [
                'action' => 'MANUAL_UNLOCK',
                'method' => 'WEB_DASHBOARD',
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'doorStatus' => 'UNLOCKED'
            ],
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ]);
        
        // Update device status
        $devices = $db->getCollection('devices');
        $devices->updateOne(
            ['location.labId' => $lab_id],
            [
                '$set' => [
                    'status.doorLocked' => false,
                    'status.lastSeen' => new MongoDB\BSON\UTCDateTime()
                ]
            ]
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
