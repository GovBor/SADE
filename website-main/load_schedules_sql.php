<?php
session_start();
require_once 'config/database_sql.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$room = $_GET['room'] ?? '';

try {
    $db = new Database();
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($room)) {
        $whereClause .= " AND room = ?";
        $params[] = $room;
    }
    
    $schedules = $db->fetchAll(
        "SELECT * FROM schedules $whereClause ORDER BY day, start_time",
        $params
    );
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
