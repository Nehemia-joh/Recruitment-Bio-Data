-- First, drop the existing table if you want to recreate it
DROP TABLE IF EXISTS users;

-- Create the users table with correct structure
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now insert the users with proper password hashes
-- Note: These are bcrypt hashes of the passwords
-- Admin@123 hash: $2y$10$YourHashedPasswordHere - You need to generate these in PHP
-- For now, let's insert them with placeholders and update them via PHP

INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@silverleaf.ac.tz', 'System Administrator', 'admin'),
('hr_manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr@silverleaf.ac.tz', 'HR Manager', 'hr_manager'),
('viewer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer@silverleaf.ac.tz', 'Report Viewer', 'viewer'),
('john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@silverleaf.ac.tz', 'John Doe', 'hr_manager'),
('jane_smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane.smith@silverleaf.ac.tz', 'Jane Smith', 'viewer');

-- Show the created table structure
DESCRIBE users;

-- Show the inserted users
SELECT user_id, username, email, full_name, role, is_active FROM users;