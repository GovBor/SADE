<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['csv_file'];
$filePath = $file['tmp_name'];

// Validate file type
if ($file['type'] !== 'text/csv' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Please upload a valid CSV file']);
    exit;
}

try {
    $db = new Database();
    $handle = fopen($filePath, 'r');
    
    if ($handle === false) {
        throw new Exception('Could not open CSV file');
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    
    // Validate headers
    $requiredHeaders = ['student_id', 'first_name', 'last_name', 'email'];
    $missingHeaders = array_diff($requiredHeaders, $headers);
    
    if (!empty($missingHeaders)) {
        fclose($handle);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required columns: ' . implode(', ', $missingHeaders)
        ]);
        exit;
    }
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $lineNumber = 1;
    
    // Process each row
    while (($data = fgetcsv($handle)) !== false) {
        $lineNumber++;
        
        // Create associative array from headers and data
        $row = array_combine($headers, $data);
        
        // Validate required fields
        if (empty($row['student_id']) || empty($row['first_name']) || empty($row['email'])) {
            $errors[] = "Line $lineNumber: Missing required fields";
            $errorCount++;
            continue;
        }
        
        // Validate email format
        if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Line $lineNumber: Invalid email format";
            $errorCount++;
            continue;
        }
        
        // Check if student already exists
        $existing = $db->fetch(
            "SELECT id FROM users WHERE user_id = ? OR email = ?", 
            [$row['student_id'], $row['email']]
        );
        
        if ($existing) {
            $errors[] = "Line $lineNumber: Student ID or email already exists";
            $errorCount++;
            continue;
        }
        
        // Insert student
        try {
            $db->insert('users', [
                'user_id' => $row['student_id'],
                'user_type' => 'STUDENT',
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'] ?? '',
                'email' => $row['email'],
                'id_number' => $row['student_id'],
                'course' => $row['course'] ?? '',
                'barcode_id' => 'UST' . $row['student_id'],
                'allowed_labs' => '["1811", "1812", "1815"]',
                'access_level' => 'STUDENT',
                'is_active' => true
            ]);
            
            $successCount++;
            
        } catch (Exception $e) {
            $errors[] = "Line $lineNumber: Database error - " . $e->getMessage();
            $errorCount++;
        }
    }
    
    fclose($handle);
    
    // Log the upload activity
    $db->insert('system_logs', [
        'log_id' => 'CSV_UPLOAD_' . time(),
        'event_type' => 'CSV_UPLOAD',
        'message' => "CSV upload completed: $successCount successful, $errorCount errors",
        'user_id' => $_SESSION['user']['userId'],
        'user_name' => $_SESSION['user']['name'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'component' => 'WEB_DASHBOARD'
    ]);
    
    $message = "Upload completed: $successCount students added successfully";
    if ($errorCount > 0) {
        $message .= ", $errorCount errors occurred";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'details' => [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => array_slice($errors, 0, 10) // Limit to first 10 errors
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>
