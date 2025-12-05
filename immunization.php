<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');

//Pagination
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $limit;

//Search term
$search = trim($_GET['search'] ?? '');

//Get patients for dropdown
$patients = [];
$patientsQuery = "SELECT patient_id, fullname FROM patients ORDER BY fullname ASC";
$patientsResult = mysqli_query($conn, $patientsQuery);
if ($patientsResult) {
    while ($p = mysqli_fetch_assoc($patientsResult)) {
        $patients[] = $p;
    }
}

//Count total immunizations for pagination
if ($search) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total 
                            FROM immunizations i
                            JOIN patients p ON i.patient_id = p.patient_id
                            WHERE p.fullname LIKE ? OR i.vaccine LIKE ?");
    $likeSearch = "%$search%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM immunizations");
}
$stmt->execute();
$countResult = $stmt->get_result();
$total = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($total / $limit);
$stmt->close();

//Fetch immunizations with search & pagination
if ($search) {
    $stmt = $conn->prepare("SELECT i.*, p.fullname AS patient_name
                            FROM immunizations i
                            JOIN patients p ON i.patient_id = p.patient_id
                            WHERE p.fullname LIKE ? OR i.vaccine LIKE ?
                            ORDER BY i.immunization_id ASC
                            LIMIT ?, ?");
    $likeSearch = "%$search%";
    $stmt->bind_param("ssii", $likeSearch, $likeSearch, $start, $limit);
} else {
    $stmt = $conn->prepare("SELECT i.*, p.fullname AS patient_name
                            FROM immunizations i
                            JOIN patients p ON i.patient_id = p.patient_id
                            ORDER BY i.immunization_id DESC
                            LIMIT ?, ?");
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Immunizations - BHCIS</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <button id="toggleSidebar" class="sidebar-toggle">☰</button>

        <div class="sidebar-header">
            <img src="assets/images/logo1.png" alt="TMHC Logo">
            <h2>BHCIS</h2>
            <p class="welcome">
                <?= htmlspecialchars($_SESSION['fullname'] ?? '') ?><br>
                <small>(<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</small>
            </p>
        </div>
        <ul>
            <li>
                <a href="dashboard.php">
                    <img class="icon" src="assets/icons/dashboard.svg" alt="Dashboard">
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="patients.php">
                    <img class="icon" src="assets/icons/patient.svg" alt="Patients">
                    <span class="menu-text">Patient</span>
                </a>
            </li>
            <li>
                <a href="appointments.php">
                    <img class="icon" src="assets/icons/appointment.svg" alt="Appointments">
                    <span class="menu-text">Appointment</span>
                </a>
            </li>
            <li>
                <a href="immunization.php" class="active">
                    <img class="icon" src="assets/icons/immunization.svg" alt="Immunization">
                    <span class="menu-text">Immunization</span>
                </a>
            </li>
            <li>
                <a href="inventory.php">
                    <img class="icon" src="assets/icons/inventory.svg" alt="Inventory">
                    <span class="menu-text">Inventory</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <img class="icon" src="assets/icons/reports.svg" alt="Reports">
                    <span class="menu-text">Reports</span>
                </a>
            </li>

            <li>
                <a href="logout.php">
                    <img class="icon" src="assets/icons/logout.svg" alt="Logout">
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Immunization Record </h1>
            <p>Manage patient immunization records.</p>
        </div>

        <button id="openAddModal" class="btn-primary">+ Add Immunization</button>

        <!-- Add Modal -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add Immunization</h2>
                <form action="ADD/add_immunization_process.php" method="POST">
                    <label for="patient_id">Patient</label>
                    <select name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php foreach($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="vaccine">Vaccine/Medicine</label>
                    <input type="text" name="vaccine" required>

                    <label for="dose">Dose</label>
                    <input type="text" name="dose" required>

                    <label for="date_administered">Date Given</label>
                    <input type="date" name="date_administered" required>

                    <label for="status">Status</label>
                    <select name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <button type="submit" class="btn-primary">Add</button>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Immunization</h2>
                <form action="UPDATE/update_immunization_process.php" method="POST">
                    <input type="hidden" name="immunization_id" id="edit_immunization_id">

                    <label for="edit_patient_id">Patient</label>
                    <select name="patient_id" id="edit_patient_id" required>
                        <option value="">Select Patient</option>
                        <?php foreach($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="edit_vaccine">Vaccine/Medicine</label>
                    <input type="text" name="vaccine" id="edit_vaccine" required>

                    <label for="edit_dose">Dose</label>
                    <input type="text" name="dose" id="edit_dose" required>

                    <label for="edit_date_administered">Date Given</label>
                    <input type="date" name="date_administered" id="edit_date_administered" required>

                    <label for="edit_status">Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <button type="submit" class="btn-primary">Update</button>
                </form>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Search -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by patient or vaccine" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-primary">Search</button>
        </form>

        <!-- Table -->
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Vaccine/Medicine</th>
                    <th>Dose</th>
                    <th>Date Administered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['immunization_id'] ?></td>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= htmlspecialchars($row['vaccine']) ?></td>
                            <td><?= htmlspecialchars($row['dose']) ?></td>
                            <td><?= $row['date_administered'] ?></td>
                            <td><span class="status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <button class="btn-edit editBtn"
                                    data-id="<?= $row['immunization_id'] ?>"
                                    data-patient="<?= $row['patient_id'] ?>"
                                    data-vaccine="<?= htmlspecialchars($row['vaccine']) ?>"
                                    data-dose="<?= htmlspecialchars($row['dose']) ?>"
                                    data-date="<?= $row['date_administered'] ?>"
                                    data-status="<?= $row['status'] ?>"
                                >Edit</button>
                                <a href="DELETE/delete_immunization.php?id=<?= $row['immunization_id'] ?>" class="btn-delete" onclick="return confirm('Delete this record?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="assets/javascript/sidebar.js"></script>
<script src="assets/javascript/immuno.js"></script>
<script src="assets/javascript/hide.js"></script>
</body>
</html>

<?php $conn->close(); ?>
