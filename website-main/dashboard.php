<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.html');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADE Dashboard - <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>SADE Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="status-cards">
                <div class="status-card">
                    <h3>Lab 1811</h3>
                    <div class="status-indicator online"></div>
                    <p>Online</p>
                </div>
                
                <div class="status-card">
                    <h3>Lab 1812</h3>
                    <div class="status-indicator offline"></div>
                    <p>Offline</p>
                </div>
                
                <div class="status-card">
                    <h3>Lab 1815</h3>
                    <div class="status-indicator online"></div>
                    <p>Online</p>
                </div>
            </div>
            
            <div class="quick-actions">
                <button class="action-btn" onclick="unlockDoor('1811')">Unlock Lab 1811</button>
                <button class="action-btn" onclick="unlockDoor('1812')">Unlock Lab 1812</button>
                <button class="action-btn" onclick="unlockDoor('1815')">Unlock Lab 1815</button>
            </div>
        </div>
    </div>

    <script>
        function unlockDoor(labId) {
            if (confirm('Unlock Lab ' + labId + '?')) {
                fetch('door_control.php', {
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
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }
    </script>
</body>
</html>
