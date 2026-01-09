<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.html');
    exit;
}

$user = $_SESSION['user'];
$db = new Database();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        // Individual student registration
        $student_id = trim($_POST['student_id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        if (!empty($student_id) && !empty($full_name) && !empty($email)) {
            // Check if student already exists
            $existing = $db->fetch("SELECT id FROM users WHERE user_id = ? OR email = ?", [$student_id, $email]);
            
            if (!$existing) {
                $name_parts = explode(' ', $full_name, 2);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                
                $db->insert('users', [
                    'user_id' => $student_id,
                    'user_type' => 'STUDENT',
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'id_number' => $student_id,
                    'barcode_id' => 'UST' . $student_id,
                    'allowed_labs' => '["1811", "1812", "1815"]',
                    'access_level' => 'STUDENT'
                ]);
                
                $message = 'Student added successfully!';
                $messageType = 'success';
            } else {
                $message = 'Student with this ID or email already exists!';
                $messageType = 'error';
            }
        } else {
            $message = 'Please fill in all required fields!';
            $messageType = 'error';
        }
    }
}

// Get all users with search and filter
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (user_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_fill(0, 4, $searchTerm);
}

if ($filter !== 'all') {
    $whereClause .= " AND user_type = ?";
    $params[] = strtoupper($filter === 'students' ? 'student' : $filter);
}

$users = $db->fetchAll("SELECT * FROM users $whereClause ORDER BY created_at DESC", $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADE - Student Registration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .main-container {
            display: flex;
            min-height: 100vh;
            background: #f5f5f5;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #fbbf24;
            color: #333;
        }
        
        .sidebar-menu a.active {
            background: #f59e0b;
            color: white;
        }
        
        .content {
            flex: 1;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .upload-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn-template {
            background: #6b7280;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-template:hover {
            background: #4b5563;
        }
        
        .btn-upload {
            background: #f59e0b;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-upload:hover {
            background: #d97706;
        }
        
        .individual-registration {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .search-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-bar {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-btn {
            background: #f59e0b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            color: #666;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-tab.active {
            background: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }
        
        .users-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .file-upload {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 15px;
        }
        
        .file-upload.dragover {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        
        #fileInput {
            display: none;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SADE System</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="schedules.php" class="active">📅 Schedules</a></li>
                <li><a href="logs.php">📋 Logs</a></li>
                <li><a href="notifications.php">🔔 Notification</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="page-header">
                <h1 class="page-title">Student Registration</h1>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- CSV Upload Section -->
            <div class="upload-section">
                <div class="upload-buttons">
                    <a href="download_template.php" class="btn-template">📥 Download CSV Template</a>
                    <button class="btn-upload" onclick="document.getElementById('fileInput').click()">📤 Upload File</button>
                </div>
                
                <div class="file-upload" id="fileUpload">
                    <p>Drag and drop your CSV file here, or click "Upload File" to browse</p>
                    <input type="file" id="fileInput" accept=".csv" onchange="handleFileUpload(this)">
                </div>
            </div>

            <!-- Individual Student Registration -->
            <div class="individual-registration">
                <h2>Individual Student Registration</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">ID Number</label>
                            <input type="text" name="student_id" class="form-input" placeholder="2020-123456" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-input" placeholder="Full Name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" placeholder="name@example.com" required>
                            <small style="color: #666;">Please enter a valid email address</small>
                        </div>
                    </div>
                    <button type="submit" name="add_student" class="btn-upload">➕ Add Student</button>
                </form>
            </div>

            <!-- Search and Filter -->
            <div class="search-section">
                <form method="GET" class="search-bar">
                    <input type="text" name="search" class="search-input" placeholder="Search ID or name" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">All</button>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                </form>
                
                <div class="filter-tabs">
                    <a href="?search=<?php echo urlencode($search); ?>&filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=students" class="filter-tab <?php echo $filter === 'students' ? 'active' : ''; ?>">Students</a>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=faculty" class="filter-tab <?php echo $filter === 'faculty' ? 'active' : ''; ?>">Faculty</a>
                </div>
            </div>

            <!-- Users Table -->
            <div class="users-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Full Name</th>
                            <th>User Type</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-delete" onclick="deleteUser('<?php echo $user['user_id']; ?>')">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // File upload handling
        function handleFileUpload(input) {
            const file = input.files[0];
            if (file && file.type === 'text/csv') {
                const formData = new FormData();
                formData.append('csv_file', file);
                
                fetch('upload_csv.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('CSV uploaded successfully! ' + data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Upload failed: ' + error);
                });
            } else {
                alert('Please select a valid CSV file');
            }
        }

        // Drag and drop functionality
        const fileUpload = document.getElementById('fileUpload');
        
        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', () => {
            fileUpload.classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('fileInput').files = files;
                handleFileUpload(document.getElementById('fileInput'));
            }
        });

        // Delete user function
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
