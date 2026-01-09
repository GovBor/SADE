<?php
session_start();
header('Content-Type: application/json');

// Database configuration (you can modify this for your MongoDB setup)
$config = [
    'technician_pins' => [
        '123456' => 'Tech. Victorio',
        '789012' => 'Admin User'
    ],
    'faculty_pins' => [
        '456789' => 'Prof. Cruz',
        '234567' => 'Prof. Smith'
    ]
];

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

// Check PIN based on user type
$valid_user = null;
if ($user_type === 'technician' && isset($config['technician_pins'][$pin])) {
    $valid_user = [
        'name' => $config['technician_pins'][$pin],
        'type' => 'technician',
        'pin' => $pin
    ];
} elseif ($user_type === 'faculty' && isset($config['faculty_pins'][$pin])) {
    $valid_user = [
        'name' => $config['faculty_pins'][$pin],
        'type' => 'faculty',
        'pin' => $pin
    ];
}

if ($valid_user) {
    // Set session data
    $_SESSION['user'] = $valid_user;
    $_SESSION['login_time'] = time();
    
    // Log successful login (you can save this to MongoDB later)
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_type' => $user_type,
        'user_name' => $valid_user['name'],
        'action' => 'LOGIN_SUCCESS',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Save to log file (replace with MongoDB later)
    file_put_contents('logs/login.log', json_encode($log_entry) . "\n", FILE_APPEND);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $valid_user,
        'redirect' => $user_type === 'technician' ? 'dashboard.php' : 'faculty_dashboard.php'
    ]);
} else {
    // Log failed login attempt
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_type' => $user_type,
        'pin_attempted' => $pin,
        'action' => 'LOGIN_FAILED',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    file_put_contents('logs/login.log', json_encode($log_entry) . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Invalid PIN. Please try again.'
    ]);
}
?>
