<?php
// create_admin.php
// Place this file in the same folder as db_connect.php and run once.
// It will create an admin user with username "admin" and password "admin123"
// (you can change those values below before running).

include 'db_connect.php'; // your existing connection, must set $conn (mysqli)

// --- Configuration: change these before running if you want ---
$admin_username = 'admin';
$admin_password_plain = 'admin123';
$admin_fullname = 'Administrator';
$admin_role = 'admin';
// -----------------------------------------------------------

if (!isset($conn) || !$conn) {
    die("Database connection not available. Check db_connect.php\n");
}

// Optional: Example users table schema (run once if you don't have it)
/*
CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(255) DEFAULT NULL,
  role VARCHAR(50) DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

try {
    // 1) Check if the user already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "User '{$admin_username}' already exists. No changes made.\n";
        $stmt->close();
        exit;
    }
    $stmt->close();

    // 2) Hash the password
    // Use PASSWORD_BCRYPT or PASSWORD_DEFAULT (recommended)
    $hash = password_hash($admin_password_plain, PASSWORD_BCRYPT);
    if ($hash === false) throw new Exception("Password hashing failed.");

    // 3) Insert the user using a prepared statement
    $ins = $conn->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, ?)");
    if (!$ins) throw new Exception("Prepare failed: " . $conn->error);
    $ins->bind_param("ssss", $admin_username, $hash, $admin_fullname, $admin_role);
    $ok = $ins->execute();
    if (!$ok) {
        // If duplicate or other error
        throw new Exception("Insert failed: " . $ins->error);
    }

    echo "Admin user '{$admin_username}' created successfully.\n";
    echo "Username: {$admin_username}\n";
    echo "Password (plain): {$admin_password_plain}\n";
    echo "Password hash stored: {$hash}\n";

    $ins->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
