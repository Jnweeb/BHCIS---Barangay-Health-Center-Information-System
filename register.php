<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');

// Restrict admin access
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Pagination settings
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

/* -----------------------------
   ADD USER
------------------------------ */
if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user_role = $_POST['role'];

    $check = $conn->prepare("SELECT * FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows > 0) {
        $_SESSION['error'] = "Username already exists.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, username, password, role, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssss", $fullname, $username, $hashed_password, $user_role);
        $_SESSION['success'] = $stmt->execute() ? "User added successfully." : "Failed to add user.";
    }
    header("Location: register.php");
    exit();
}

/* -----------------------------
   EDIT USER
------------------------------ */
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $user_role = $_POST['role'];
    $password = $_POST['password'];

    $check = $conn->prepare("SELECT * FROM users WHERE username=? AND user_id!=?");
    $check->bind_param("si", $username, $user_id);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows > 0) {
        $_SESSION['error'] = "Username already exists.";
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, role=?, password=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $fullname, $username, $user_role, $hashed, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, role=? WHERE user_id=?");
            $stmt->bind_param("sssi", $fullname, $username, $user_role, $user_id);
        }

        $_SESSION['success'] = $stmt->execute() ? "User updated successfully." : "Failed to update user.";
    }

    header("Location: register.php");
    exit();
}

/* -----------------------------
   DELETE USER
------------------------------ */
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $_SESSION['success'] = $stmt->execute() ? "User deleted successfully." : "Failed to delete user.";
    header("Location: register.php");
    exit();
}

/* -----------------------------
   ACTIVATE / DEACTIVATE USER
------------------------------ */
if (isset($_GET['toggle'])) {
    $user_id = $_GET['toggle'];

    // Get status
    $stmt = $conn->prepare("SELECT status FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $new_status = ($row['status'] === 'active') ? 'inactive' : 'active';

    $update = $conn->prepare("UPDATE users SET status=? WHERE user_id=?");
    $update->bind_param("si", $new_status, $user_id);

    $_SESSION['success'] = $update->execute()
        ? "User is now: $new_status"
        : "Failed to update status.";

    header("Location: register.php");
    exit();
}

/* -----------------------------
   PAGINATION - COUNT USERS
------------------------------ */
$countQuery = "SELECT COUNT(*) as total FROM users";
$totalResult = mysqli_query($conn, $countQuery);
$total = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($total / $limit);

// Fetch users (paginated)
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY user_id ASC LIMIT $start, $limit");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BHCIS - Users</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
<div class="container">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/logo1.png" alt="TMHC Logo">
            <h2>BHCIS</h2>
            <p class="welcome">
                <?=htmlspecialchars($_SESSION['fullname'])?><br>
                <small>(<?=htmlspecialchars($_SESSION['role'])?>)</small>
            </p>
        </div>

        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="register.php" class="active">👥 Users</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>ADMIN User Management</h1>
            <p>Register, edit, activate or deactivate users.</p>
        </div>

        <!-- Alerts -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <button id="openUserModal" class="btn-primary">+ Add User</button>

        <!-- Users Table -->
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?=$row['user_id']?></td>
                        <td><?=htmlspecialchars($row['fullname'])?></td>
                        <td><?=htmlspecialchars($row['username'])?></td>
                        <td><?=htmlspecialchars($row['role'])?></td>
                        <td>
                            <span class="<?= $row['status']=='active' ? 'status-active':'status-inactive' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>

                        <td>
                            <button class="btn-edit editBtn"
                                data-id="<?=$row['user_id']?>"
                                data-fullname="<?=htmlspecialchars($row['fullname'])?>"
                                data-username="<?=htmlspecialchars($row['username'])?>"
                                data-role="<?=$row['role']?>">
                                Edit
                            </button>

                            <a href="register.php?toggle=<?=$row['user_id']?>"
                               class="btn-warning"
                               onclick="return confirm('Are you sure you want to <?=$row['status']=="active" ? "deactivate" : "activate"?> this user?');">
                                <?=$row['status']=="active" ? "Deactivate" : "Activate"?>
                            </a>

                            <a href="register.php?delete=<?=$row['user_id']?>"
                               class="btn-delete"
                               onclick="return confirm('Delete this user permanently?');">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i==$page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Add User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New User</h2>
        <form method="POST" action="register.php">
            <label>Full Name</label>
            <input type="text" name="fullname" required>

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <!-- Add User Password -->
            <div class="password-wrapper">
                <input type="password" name="password" id="add_password" required>
                <span class="toggle-password" id="toggleAddPassword"></span>
            </div>

            <label>Role</label>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="healthworker">Health Worker</option>
                <option value="nurse">Nurse</option>
            </select>

            <button type="submit" name="register" class="btn-primary">Add User</button>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit User</h2>
        <form method="POST" action="register.php">

            <input type="hidden" name="user_id" id="edit_user_id">

            <label>Full Name</label>
            <input type="text" name="fullname" id="edit_fullname" required>

            <label>Username</label>
            <input type="text" name="username" id="edit_username" required>

            <label>Password (leave blank to keep current)</label>
            <!-- Edit User Password -->
            <div class="password-wrapper">
                <input type="password" name="password" id="edit_password">
                <span class="toggle-password" id="toggleEditPassword"></span>
            </div>

            <label>Role</label>
            <select name="role" id="edit_role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="healthworker">Health Worker</option>
                <option value="nurse">Nurse</option>
            </select>

            <button type="submit" name="edit_user" class="btn-primary">Update User</button>
        </form>
    </div>
</div>

<script>
// Modals
const userModal = document.getElementById("userModal");
const editUserModal = document.getElementById("editUserModal");
document.getElementById("openUserModal").onclick = () => userModal.classList.add("show");
userModal.querySelector(".close").onclick = () => userModal.classList.remove("show");
editUserModal.querySelector(".close").onclick = () => editUserModal.classList.remove("show");

// Edit modal fill
document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", () => {
        editUserModal.classList.add("show");
        document.getElementById("edit_user_id").value = btn.dataset.id;
        document.getElementById("edit_fullname").value = btn.dataset.fullname;
        document.getElementById("edit_username").value = btn.dataset.username;
        document.getElementById("edit_role").value = btn.dataset.role;
        document.getElementById("edit_password").value = "";
    });
});

// Hide alert automatically
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => alert.style.opacity = "0", 3000);
    setTimeout(() => alert.remove(), 3500);
});

const togglePassword = (input, icon) => {
    icon.onclick = () => {
        input.type = input.type === "password" ? "text" : "password";
        icon.classList.toggle("show"); // toggles eye / eye-slash
    };
};

togglePassword(document.getElementById("add_password"), document.getElementById("toggleAddPassword"));
togglePassword(document.getElementById("edit_password"), document.getElementById("toggleEditPassword"));

</script>
</body>
</html>
