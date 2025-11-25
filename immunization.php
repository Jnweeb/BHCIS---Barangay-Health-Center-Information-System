<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');

// --- Pagination setup ---
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $limit;

// --- Search term ---
$search = trim($_GET['search'] ?? '');

// --- Get patients for dropdown ---
$patients = [];
$patientsQuery = "SELECT patient_id, fullname FROM patients ORDER BY fullname ASC";
$patientsResult = mysqli_query($conn, $patientsQuery);
if ($patientsResult) {
    while ($p = mysqli_fetch_assoc($patientsResult)) {
        $patients[] = $p;
    }
}

// --- Count total immunizations for pagination ---
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

// --- Fetch immunizations with search & pagination ---
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
                            ORDER BY i.immunization_id ASC
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
        <div class="sidebar-header">
            <img src="assets/images/logo1.png" alt="TMHC Logo">
            <h2>BHCIS</h2>
            <p class="welcome"><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?><br><small>(<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</small></p>
        </div>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="patients.php">👨‍⚕️ Patients</a></li>
            <li><a href="appointments.php">📅 Appointments</a></li>
            <li><a href="immunization.php" class="active">💉 Immunization</a></li>
            <li><a href="inventory.php">💊 Inventory</a></li>
            <li><a href="reports.php">📊 Reports</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
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

<script>
// Modal functionality
const addModal = document.getElementById("addModal");
const editModal = document.getElementById("editModal");
document.getElementById("openAddModal").onclick = () => addModal.classList.add('show');
document.querySelectorAll(".modal .close").forEach(el => el.onclick = () => el.parentElement.parentElement.classList.remove('show'));
window.onclick = e => { if(e.target.classList.contains('modal')) e.target.classList.remove('show'); }

// Edit buttons
document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", () => {
        editModal.classList.add('show');
        document.getElementById("edit_immunization_id").value = btn.dataset.id;
        document.getElementById("edit_patient_id").value = btn.dataset.patient;
        document.getElementById("edit_vaccine").value = btn.dataset.vaccine;
        document.getElementById("edit_dose").value = btn.dataset.dose;
        document.getElementById("edit_date_administered").value = btn.dataset.date;
        document.getElementById("edit_status").value = btn.dataset.status;
    });
});
</script>
<script>
// Auto-hide alerts after 3 seconds
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s ease-out';
        setTimeout(() => alert.remove(), 500); // remove from DOM after fade
    }, 3000); // 3 seconds before starting fade
});
</script>

</body>
</html>

<?php $conn->close(); ?>
