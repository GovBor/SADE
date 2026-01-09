<?php
require_once 'config/database.php';

$db = new Database();

// Create users collection with initial data
$users = $db->getCollection('users');

// Clear existing data (for testing)
$users->deleteMany([]);

// Insert initial users
$initialUsers = [
    [
        'userId' => 'T001',
        'userType' => 'TECHNICIAN',
        'personalInfo' => [
            'firstName' => 'Mike',
            'lastName' => 'Victorio',
            'email' => 'mike.victorio@ust.edu.ph',
            'title' => 'Tech. Victorio'
        ],
        'authentication' => [
            'pin' => '123456',
            'isActive' => true
        ],
        'permissions' => [
            'allowedLabs' => ['1811', '1812', '1815'],
            'accessLevel' => 'TECHNICIAN'
        ],
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'updatedAt' => new MongoDB\BSON\UTCDateTime()
    ],
    [
        'userId' => 'F001',
        'userType' => 'FACULTY',
        'personalInfo' => [
            'firstName' => 'Prof',
            'lastName' => 'Cruz',
            'email' => 'prof.cruz@ust.edu.ph',
            'title' => 'Prof. Cruz'
        ],
        'authentication' => [
            'pin' => '456789',
            'isActive' => true
        ],
        'permissions' => [
            'allowedLabs' => ['1811', '1812'],
            'accessLevel' => 'FACULTY'
        ],
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'updatedAt' => new MongoDB\BSON\UTCDateTime()
    ],
    [
        'userId' => 'U10001',
        'userType' => 'STUDENT',
        'personalInfo' => [
            'firstName' => 'Alice',
            'lastName' => 'Johnson',
            'email' => 'alice.johnson@ust.edu.ph',
            'idNumber' => '2020-123456',
            'course' => 'BSIT'
        ],
        'authentication' => [
            'barcodeId' => 'UST2020123456',
            'isActive' => true
        ],
        'permissions' => [
            'allowedLabs' => ['1811'],
            'accessLevel' => 'STUDENT'
        ],
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'updatedAt' => new MongoDB\BSON\UTCDateTime()
    ]
];

$result = $users->insertMany($initialUsers);
echo "Inserted " . $result->getInsertedCount() . " users<br>";

// Create devices collection
$devices = $db->getCollection('devices');
$devices->deleteMany([]);

$initialDevices = [
    [
        'deviceId' => 'SADE_DOOR_1811',
        'deviceInfo' => [
            'type' => 'DOOR_CONTROLLER',
            'model' => 'Arduino Uno + ESP32-CAM',
            'version' => '1.0.0'
        ],
        'location' => [
            'labId' => '1811',
            'building' => 'CICS Building',
            'floor' => '18th Floor',
            'room' => 'Lab 1811'
        ],
        'status' => [
            'online' => true,
            'doorLocked' => true,
            'keypadLocked' => false
        ],
        'createdAt' => new MongoDB\BSON\UTCDateTime()
    ],
    [
        'deviceId' => 'SADE_DOOR_1812',
        'deviceInfo' => [
            'type' => 'DOOR_CONTROLLER',
            'model' => 'Arduino Uno + ESP32-CAM',
            'version' => '1.0.0'
        ],
        'location' => [
            'labId' => '1812',
            'building' => 'CICS Building',
            'floor' => '18th Floor',
            'room' => 'Lab 1812'
        ],
        'status' => [
            'online' => false,
            'doorLocked' => true,
            'keypadLocked' => false
        ],
        'createdAt' => new MongoDB\BSON\UTCDateTime()
    ]
];

$result = $devices->insertMany($initialDevices);
echo "Inserted " . $result->getInsertedCount() . " devices<br>";

echo "<br>Setup completed! You can now use the login system.";
?>
