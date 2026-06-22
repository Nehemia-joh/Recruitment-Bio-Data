<?php
// admin/create_sample_users.php
// Run this file once to create sample users with hashed passwords

require_once '../config/database.php';

$pdo = getDBConnection();

// Sample users data
$users = [
    [
        'username' => 'admin',
        'password' => password_hash('Admin@123', PASSWORD_DEFAULT),
        'email' => 'admin@silverleaf.ac.tz',
        'full_name' => 'System Administrator',
        'role' => 'admin'
    ],
    [
        'username' => 'hr_manager',
        'password' => password_hash('HR@123', PASSWORD_DEFAULT),
        'email' => 'hr@silverleaf.ac.tz',
        'full_name' => 'HR Manager',
        'role' => 'hr_manager'
    ],
    [
        'username' => 'viewer',
        'password' => password_hash('Viewer@123', PASSWORD_DEFAULT),
        'email' => 'viewer@silverleaf.ac.tz',
        'full_name' => 'Report Viewer',
        'role' => 'viewer'
    ],
    [
        'username' => 'john_doe',
        'password' => password_hash('John@2024', PASSWORD_DEFAULT),
        'email' => 'john.doe@silverleaf.ac.tz',
        'full_name' => 'John Doe',
        'role' => 'hr_manager'
    ],
    [
        'username' => 'jane_smith',
        'password' => password_hash('Jane@2024', PASSWORD_DEFAULT),
        'email' => 'jane.smith@silverleaf.ac.tz',
        'full_name' => 'Jane Smith',
        'role' => 'viewer'
    ]
];

try {
    // Create users table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'hr_manager', 'viewer') DEFAULT 'viewer',
            is_active BOOLEAN DEFAULT TRUE,
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Insert users
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, email, full_name, role) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        password = VALUES(password),
        full_name = VALUES(full_name),
        role = VALUES(role)
    ");
    
    foreach ($users as $user) {
        $stmt->execute([
            $user['username'],
            $user['password'],
            $user['email'],
            $user['full_name'],
            $user['role']
        ]);
    }
    
    echo "Sample users created successfully!\n";
    echo "--------------------------------\n";
    echo "Admin: admin / Admin@123\n";
    echo "HR Manager: hr_manager / HR@123\n";
    echo "Viewer: viewer / Viewer@123\n";
    echo "John Doe: john_doe / John@2024\n";
    echo "Jane Smith: jane_smith / Jane@2024\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>