<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Get POST data
$pin = $_POST['pin'] ?? '';
$user_type = $_POST['user_type'] ?? '';

// Validate input
if (empty($pin) || empty($user_type)) {
    echo json_encode([
        'success' => false,
        'message' => 'PIN and user type are required'
    ]);
    exit;
}

try {
    $db = new Database();
    $users = $db->getCollection('users');
    
    // Find user by PIN and user type
    $user = $users->findOne([
        'authentication.pin' => $pin,
        'userType' => strtoupper($user_type),
        'authentication.isActive' => true
    ]);
    
    if ($user) {
        // Convert MongoDB document to array
        $userArray = $user->toArray();
        
        // Set session data
        $_SESSION['user'] = [
            'userId' => $userArray['userId'],
            'name' => $userArray['personalInfo']['firstName'] . ' ' . $userArray['personalInfo']['lastName'],
            'type' => strtolower($userArray['userType']),
            'email' => $userArray['personalInfo']['email'] ?? '',
            'allowedLabs' => $userArray['permissions']['allowedLabs'] ?? []
        ];
        $_SESSION['login_time'] = time();
        
        // Log successful login to MongoDB
        $systemLogs = $db->getCollection('system_logs');
        $systemLogs->insertOne([
            'logId' => 'LOGIN_' . time() . '_' . $userArray['userId'],
            'eventInfo' => [
                'type' => 'USER_LOGIN',
                'category' => 'AUTHENTICATION',
                'severity' => 'INFO',
                'message' => ucfirst($user_type) . ' login successful'
            ],
            'userInfo' => [
                'userId' => $userArray['userId'],
                'userType' => $userArray['userType'],
                'name' => $_SESSION['user']['name'],
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ],
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ]);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $_SESSION['user'],
            'redirect' => $user_type === 'technician' ? 'dashboard_mongodb.php' : 'faculty_dashboard.php'
        ]);
        
    } else {
        // Log failed login attempt
        $systemLogs = $db->getCollection('system_logs');
        $systemLogs->insertOne([
            'logId' => 'FAILED_LOGIN_' . time(),
            'eventInfo' => [
                'type' => 'LOGIN_FAILED',
                'category' => 'SECURITY',
                'severity' => 'WARN',
                'message' => 'Failed login attempt'
            ],
            'userInfo' => [
                'userType' => strtoupper($user_type),
                'pinAttempted' => $pin,
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ],
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Invalid PIN. Please try again.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
