<?php
session_start();
require_once 'config/database_sql.php';

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
    
    // Find user by PIN and user type
    $user = $db->fetch(
        "SELECT * FROM users WHERE pin = ? AND user_type = ? AND is_active = 1",
        [$pin, strtoupper($user_type)]
    );
    
    if ($user) {
        // Set session data
        $_SESSION['user'] = [
            'userId' => $user['user_id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'type' => strtolower($user['user_type']),
            'email' => $user['email'] ?? '',
            'allowedLabs' => json_decode($user['allowed_labs'] ?? '[]', true),
            'accessLevel' => $user['access_level']
        ];
        $_SESSION['login_time'] = time();
        
        // Log successful login
        $logId = 'LOGIN_' . time() . '_' . $user['user_id'];
        $db->insert('system_logs', [
            'log_id' => $logId,
            'event_type' => 'USER_LOGIN',
            'category' => 'AUTHENTICATION',
            'severity' => 'INFO',
            'message' => ucfirst($user_type) . ' login successful',
            'user_id' => $user['user_id'],
            'user_type' => $user['user_type'],
            'user_name' => $_SESSION['user']['name'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'component' => 'WEB_DASHBOARD'
        ]);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $_SESSION['user'],
            'redirect' => $user_type === 'technician' ? 'dashboard_sql.php' : 'faculty_dashboard.php'
        ]);
        
    } else {
        // Log failed login attempt
        $logId = 'FAILED_LOGIN_' . time();
        $db->insert('system_logs', [
            'log_id' => $logId,
            'event_type' => 'LOGIN_FAILED',
            'category' => 'SECURITY',
            'severity' => 'WARN',
            'message' => 'Failed login attempt',
            'user_type' => strtoupper($user_type),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'component' => 'WEB_DASHBOARD',
            'additional_data' => json_encode(['pin_attempted' => $pin])
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
