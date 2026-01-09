<?php
session_start();
require_once 'config/database.php';


if (!isset($_SESSION['user'])) {
    header('Location: index.html');
    exit;
}

$user = $_SESSION['user'];
$db = new Database();
$devices = $db->getCollection('devices');
$deviceList = $devices->find([])->toArray();
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #ff6b35;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
        }
        
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin: 10px auto;
        }
        
        .status-indicator.online {
            background-color: #10b981;
        }
        
        .status-indicator.offline {
            background-color: #ef4444;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .action-btn:hover {
            background: #5855eb;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>SADE Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <span>(<?php echo ucfirst($user['type']); ?>)</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="status-cards">
                <?php foreach ($deviceList as $device): ?>
                <div class="status-card">
                    <h3><?php echo htmlspecialchars($device['location']['room']); ?></h3>
                    <div class="status-indicator <?php echo $device['status']['online'] ? 'online' : 'offline'; ?>"></div>
                    <p><?php echo $device['status']['online'] ? 'Online' : 'Offline'; ?></p>
                    <small>Door: <?php echo $device['status']['doorLocked'] ? 'Locked' : 'Unlocked'; ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="quick-actions">
                <?php foreach ($deviceList as $device): ?>
                    <?php if (in_array($device['location']['labId'], $user['allowedLabs'])): ?>
                    <button class="action-btn" onclick="unlockDoor('<?php echo $device['location']['labId']; ?>')">
                        Unlock <?php echo htmlspecialchars($device['location']['room']); ?>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function unlockDoor(labId) {
            if (confirm('Unlock Lab ' + labId + '?')) {
                fetch('door_control_mongodb.php', {
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
                        // Refresh page to update status
                        location.reload();
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }
    </script>
</body>
</html>
