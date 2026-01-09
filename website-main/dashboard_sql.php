<?php
session_start();
require_once 'config/database_sql.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.html');
    exit;
}

$user = $_SESSION['user'];
$db = new Database();

// Get device status
$devices = $db->fetchAll("SELECT * FROM devices ORDER BY lab_id");

// Get today's summary
$today = date('Y-m-d');
$activeSessions = $db->fetch("SELECT COUNT(*) as count FROM attendance_sessions WHERE session_date = ? AND session_status = 'IN_PROGRESS'", [$today]);
$securityAlerts = $db->fetch("SELECT COUNT(*) as count FROM security_alerts WHERE DATE(created_at) = ? AND is_acknowledged = 0", [$today]);

// Get recent access logs
$recentLogs = $db->fetchAll(
    "SELECT al.*, u.first_name, u.last_name, d.room 
     FROM access_logs al 
     LEFT JOIN users u ON al.user_id = u.user_id 
     LEFT JOIN devices d ON al.device_id = d.device_id 
     ORDER BY al.timestamp DESC 
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADE Dashboard - <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dashboard-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #ff6b35;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background: #e55a2b;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .lab-status {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .lab-indicators {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .lab-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .status-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
        
        .status-dot.online {
            background: #10b981;
        }
        
        .status-dot.offline {
            background: #ef4444;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .alert-badge {
            background: #ef4444;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .access-log {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .log-entry {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-time {
            font-size: 12px;
            color: #666;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .action-btn:hover {
            background: #5855eb;
        }
        
        .action-btn.override {
            background: #f59e0b;
        }
        
        .action-btn.override:hover {
            background: #d97706;
        }
        
        .critical-alert {
            background: #ef4444;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert-dismiss {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">SADE Dashboard | <?php echo ucfirst($user['type']); ?></h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($user['name']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Critical Alert Example -->
        <?php
        $criticalAlert = $db->fetch("SELECT * FROM security_alerts WHERE severity = 'CRITICAL' AND is_acknowledged = 0 ORDER BY created_at DESC LIMIT 1");
        if ($criticalAlert):
        ?>
        <div class="critical-alert">
            <span><strong>CRITICAL ALERT:</strong> <?php echo htmlspecialchars($criticalAlert['title']); ?></span>
            <button class="alert-dismiss" onclick="acknowledgeAlert('<?php echo $criticalAlert['alert_id']; ?>')">ACKNOWLEDGE</button>
        </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="lab-status">
                <h2>Lab Status</h2>
                <div class="lab-indicators">
                    <?php foreach ($devices as $device): ?>
                    <div class="lab-indicator">
                        <div class="status-dot <?php echo $device['is_online'] ? 'online' : 'offline'; ?>"></div>
                        <span><?php echo htmlspecialchars($device['room']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <h3>Real-time Access Log</h3>
                <div class="access-log">
                    <?php foreach ($recentLogs as $log): ?>
                    <div class="log-entry">
                        <div>
                            <strong>[<?php echo date('H:i:s', strtotime($log['timestamp'])); ?>]</strong>
                            <?php echo htmlspecialchars($log['room'] ?? 'Lab ' . $log['lab_id']); ?>:
                            <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                            <?php echo strtolower(str_replace('_', ' ', $log['action'])); ?>
                        </div>
                        <div class="log-time">
                            <?php echo date('Y-m-d H:i', strtotime($log['timestamp'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-card">
                <h2>Today's Summary</h2>
                <div class="summary-item">
                    <span>Active Sessions:</span>
                    <strong><?php echo $activeSessions['count']; ?></strong>
                </div>
                <div class="summary-item">
                    <span>Security Alerts:</span>
                    <span class="alert-badge"><?php echo $securityAlerts['count']; ?> NEW</span>
                </div>
                <div class="summary-item">
                    <span>Lab 1811:</span>
                    <span>Online</span>
                </div>
                <div class="summary-item">
                    <span>Lab 1812:</span>
                    <span>Offline</span>
                </div>
                <div class="summary-item">
                    <span>Lab 1815:</span>
                    <span>Online</span>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <?php foreach ($devices as $device): ?>
                <?php if (in_array($device['lab_id'], $user['allowedLabs'])): ?>
                <button class="action-btn override" onclick="unlockDoor('<?php echo $device['lab_id']; ?>')">
                    Override <?php echo htmlspecialchars($device['room']); ?>
                </button>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <button class="action-btn" onclick="location.href='view_logs.php'">View Alerts</button>
            <button class="action-btn" onclick="generateReport()">Generate Report</button>
            <button class="action-btn" onclick="location.href='system_health.php'">System Health</button>
        </div>
    </div>

    <script>
        function unlockDoor(labId) {
            if (confirm('Override door lock for Lab ' + labId + '?')) {
                fetch('door_control_sql.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'unlock',
                        lab_id: labId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }

        function acknowledgeAlert(alertId) {
            fetch('acknowledge_alert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    alert_id: alertId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function generateReport() {
            alert('Report generation feature coming soon!');
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
