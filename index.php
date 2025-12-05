<?php
session_start();
include('includes/db_connect.php');

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // First check password
        if (password_verify($password, $row['password'])) {

            // THEN check if inactive (only for this valid username)
            if (strtolower($row['status']) === 'inactive') {
                header("Location: index.php?error=inactive&user=" . urlencode($username));
                exit();
            }

            // Login success
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['status'] = $row['status'];

            header("Location: dashboard.php");
            exit();

        } else {
            // Wrong password
            $error = "Invalid username or password.";
        }

    } else {
        // Username does not exist
        $error = "Invalid username or password.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BHCIS Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="split-wrapper">

    <!-- LEFT SIDE INFO CARD -->
    <div class="info-section">
        <div class="info-card">
            <div class="info-title">
                <img src="assets/images/logo1.png" class="info-logo" alt="Health Center Logo">
                <h1>Barangay Health Center Information System</h1>
            </div>
            <h3>Tinambacan Main Health Center</h3>
            <p>
                Welcome to the official Health Center Information System (BHCIS).  
                This system helps maintain accurate records for patients, health workers, 
                medical services, and barangay health operations.
            </p>

            <ul>
                <li>✔ Fast and organized patient management</li>
                <li>✔ Secure user login and role-based access</li>
                <li>✔ Centralized health data records</li>
                <li>✔ Easy monitoring of daily operations</li>
            </ul>
        </div>
    </div>

    <div class="login-container">
        <img src="assets/images/logo1.png" alt="TMHC Logo">
        <h2>Tinambacan Main Health Center</h2>

        <!-- INACTIVE ACCOUNT ALERT -->
        <?php if (isset($_GET['error']) && $_GET['error'] === 'inactive') : ?>
            <div class="alert alert-danger">
                The account <strong><?= htmlspecialchars($_GET['user'] ?? ''); ?></strong> is inactive. 
                Please contact the administrator.
            </div>
        <?php endif; ?>
        <!-- INVALID LOGIN ALERT -->
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>

            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <span id="togglePasswordIndex" class="toggle-password-index"></span>
            </div>

            <button type="submit" name="login">Login</button>
        </form>

        <footer>
            <p>Barangay Health Center Information System (BHCIS) © <?php echo date('Y'); ?></p>
            <p>Created By Jan Lloyd Blanco BSCS - 3A</p>
        </footer>
    </div>
</div>
<script src="assets/javascript/index.js"></script>
<script src="assets/javascript/hide.js"></script>
</body>
</html>
