<?php
// Start session only if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

$error = "";

// =====================================================
//  1. HANDLE LOGIN SUBMISSION
// =====================================================
if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Prepare safe SQL query
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check password
            if (password_verify($password, $user['password'])) {

                // ❌ Block if INACTIVE
                if (strtolower($user['status']) === "inactive") {
                    $error = "Your account is inactive. Please contact the administrator.";
                } else {

                    // ✅ SUCCESS — Save session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['status'] = $user['status'];

                    header("Location: dashboard.php");
                    exit();
                }

            } else {
                $error = "Incorrect username or password.";
            }
        } else {
            $error = "Incorrect username or password.";
        }

        $stmt->close();
    }
}

// =====================================================
//  2. ACCESS PROTECTION FOR PAGES
// =====================================================

// If user is NOT logged in AND this is NOT login.php, block access
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && $current_page !== "login.php") {
    header("Location: login.php");
    exit();
}

// If user is INACTIVE and tries to access any page except login
if (
    isset($_SESSION['status']) && 
    strtolower($_SESSION['status']) === "inactive" && 
    $current_page !== "login.php"
) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=inactive");
    exit();
}

$conn->close();
?>
