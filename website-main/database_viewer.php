<?php
session_start();
require_once 'config/database.php';

// Simple web interface to view your MongoDB data
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'technician') {
    header('Location: index_mongodb.html');
    exit;
}

$db = new Database();
$action = $_GET['action'] ?? 'users';
?>
<!DOCTYPE html>
<html>
<head>
    <title>SADE Database Viewer</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .nav-tabs { display: flex; gap: 10px; margin: 20px 0; }
        .nav-tab { padding: 10px 20px; background: #f0f0f0; border-radius: 5px; text-decoration: none; }
        .nav-tab.active { background: #6366f1; color: white; }
        .data-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background: #f5f5f5; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>SADE Database Viewer</h1>
        
        <div class="nav-tabs">
            <a href="?action=users" class="nav-tab <?= $action === 'users' ? 'active' : '' ?>">Users</a>
            <a href="?action=devices" class="nav-tab <?= $action === 'devices' ? 'active' : '' ?>">Devices</a>
            <a href="?action=logs" class="nav-tab <?= $action === 'logs' ? 'active' : '' ?>">Access Logs</a>
            <a href="?action=system_logs" class="nav-tab <?= $action === 'system_logs' ? 'active' : '' ?>">System Logs</a>
        </div>

        <?php if ($action === 'users'): ?>
            <h2>Users</h2>
            <table class="data-table">
                <tr>
                    <th>User ID</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>PIN</th>
                    <th>Active</th>
                </tr>
                <?php
                $users = $db->getCollection('users')->find([])->toArray();
                foreach ($users as $user):
                ?>
                <tr>
                    <td><?= $user['userId'] ?></td>
                    <td><?= $user['userType'] ?></td>
                    <td><?= $user['personalInfo']['firstName'] . ' ' . $user['personalInfo']['lastName'] ?></td>
                    <td><?= $user['personalInfo']['email'] ?? 'N/A' ?></td>
                    <td><?= $user['authentication']['pin'] ?? 'N/A' ?></td>
                    <td><?= $user['authentication']['isActive'] ? 'Yes' : 'No' ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

        <?php elseif ($action === 'devices'): ?>
            <h2>Devices</h2>
            <table class="data-table">
                <tr>
                    <th>Device ID</th>
                    <th>Lab</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Door</th>
                    <th>Last Seen</th>
                </tr>
                <?php
                $devices = $db->getCollection('devices')->find([])->toArray();
                foreach ($devices as $device):
                ?>
                <tr>
                    <td><?= $device['deviceId'] ?></td>
                    <td><?= $device['location']['room'] ?></td>
                    <td><?= $device['deviceInfo']['type'] ?></td>
                    <td><?= $device['status']['online'] ? 'Online' : 'Offline' ?></td>
                    <td><?= $device['status']['doorLocked'] ? 'Locked' : 'Unlocked' ?></td>
                    <td><?= $device['createdAt']->toDateTime()->format('Y-m-d H:i:s') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

        <?php elseif ($action === 'logs'): ?>
            <h2>Recent Access Logs</h2>
            <table class="data-table">
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Lab</th>
                    <th>Action</th>
                    <th>Method</th>
                </tr>
                <?php
                $logs = $db->getCollection('access_logs')->find([], ['sort' => ['createdAt' => -1], 'limit' => 20])->toArray();
                foreach ($logs as $log):
                ?>
                <tr>
                    <td><?= $log['createdAt']->toDateTime()->format('Y-m-d H:i:s') ?></td>
                    <td><?= $log['userInfo']['name'] ?></td>
                    <td><?= $log['deviceInfo']['labId'] ?></td>
                    <td><?= $log['accessDetails']['action'] ?></td>
                    <td><?= $log['accessDetails']['method'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

        <?php elseif ($action === 'system_logs'): ?>
            <h2>Recent System Logs</h2>
            <table class="data-table">
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>User</th>
                    <th>Message</th>
                    <th>Severity</th>
                </tr>
                <?php
                $systemLogs = $db->getCollection('system_logs')->find([], ['sort' => ['createdAt' => -1], 'limit' => 20])->toArray();
                foreach ($systemLogs as $log):
                ?>
                <tr>
                    <td><?= $log['createdAt']->toDateTime()->format('Y-m-d H:i:s') ?></td>
                    <td><?= $log['eventInfo']['type'] ?></td>
                    <td><?= $log['userInfo']['name'] ?? 'System' ?></td>
                    <td><?= $log['eventInfo']['message'] ?></td>
                    <td><?= $log['eventInfo']['severity'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <p><a href="dashboard_mongodb.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
