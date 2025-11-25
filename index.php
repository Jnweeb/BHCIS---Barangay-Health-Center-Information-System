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

        // Block inactive users from logging in
        if (strtolower($row['status']) === 'inactive') {
            header("Location: index.php?error=inactive");
            exit();
        }

        // Verify password
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true); // Secure session

            // Save session data
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['status'] = $row['status'];  // <-- Needed for blocking access

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }

    } else {
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

    <div class="login-container">
        <img src="assets/images/logo.png" alt="TMHC Logo">
        <h2>Tinambacan Main Health Center</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'inactive') : ?>
            <div class="alert alert-danger">
                Your account is inactive. Please contact the administrator.
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>

            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <span id="togglePassword" class="toggle-password"></span>
            </div>

            <button type="submit" name="login">Login</button>

            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        </form>

        <footer>
            <p>Barangay Health Center Information System (BHCIS) © <?php echo date('Y'); ?></p>
            <p>Created By Jan Lloyd Blanco BSCS - 3A</p>
        </footer>
    </div>

<script>
// Show/hide password
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

togglePassword.addEventListener('click', () => {
    passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
    togglePassword.classList.toggle('show'); // uses CSS to optionally change icon
});
</script>

<script>
setTimeout(() => {
    const alert = document.querySelector('.alert-danger');
    if(alert) alert.classList.add('hide');
}, 3000);
</script>
</body>
</html>
