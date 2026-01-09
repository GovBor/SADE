<?php
session_start();
require_once 'config/database_sql.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['courseName', 'courseCode', 'instructor', 'day', 'room', 'startTime', 'endTime'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Field $field is required"]);
        exit;
    }
}

try {
    $db = new Database();
    
    // Check for schedule conflicts
    $conflict = $db->fetch(
        "SELECT * FROM schedules WHERE room = ? AND day = ? AND 
         ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))",
        [
            $input['room'],
            $input['day'],
            $input['startTime'],
            $input['startTime'],
            $input['endTime'],
            $input['endTime']
        ]
    );
    
    if ($conflict) {
        echo json_encode([
            'success' => false,
            'message' => 'Schedule conflict detected. This time slot is already occupied.'
        ]);
        exit;
    }
    
    // Generate schedule ID
    $schedule_id = 'SCH_' . time() . '_' . $input['room'];
    
    // Insert new schedule
    $db->insert('schedules', [
        'schedule_id' => $schedule_id,
        'course_name' => $input['courseName'],
        'course_code' => $input['courseCode'],
        'instructor' => $input['instructor'],
        'day' => $input['day'],
        'room' => $input['room'],
        'start_time' => $input['startTime'],
        'end_time' => $input['endTime'],
        'created_by' => $_SESSION['user']['userId'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Log the action
    $log_id = 'SCHEDULE_ADD_' . time();
    $db->insert('system_logs', [
        'log_id' => $log_id,
        'event_type' => 'SCHEDULE_CREATED',
        'category' => 'SCHEDULE_MANAGEMENT',
        'severity' => 'INFO',
        'message' => 'New schedule created',
        'user_id' => $_SESSION['user']['userId'],
        'user_type' => strtoupper($_SESSION['user']['type']),
        'user_name' => $_SESSION['user']['name'],
        'component' => 'WEB_DASHBOARD',
        'additional_data' => json_encode([
            'schedule_id' => $schedule_id,
            'course_name' => $input['courseName'],
            'room' => $input['room'],
            'day' => $input['day']
        ])
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule added successfully',
        'schedule_id' => $schedule_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
