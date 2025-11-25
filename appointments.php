<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Filters
$search = trim($_GET['search'] ?? '');
$service = $_GET['service'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];
$param_types = '';

// Filter by patient name
if(!empty($search)){
    $where[] = "p.fullname LIKE ?";
    $params[] = "%$search%";
    $param_types .= "s";
}

// Filter by service
if(!empty($service)){
    $where[] = "a.service = ?";
    $params[] = $service;
    $param_types .= "s";
}

// Filter by status
if(!empty($status)){
    $where[] = "a.status = ?";
    $params[] = $status;
    $param_types .= "s";
}

// Combine WHERE conditions
$whereSql = '';
if(!empty($where)){
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Count total appointments
$stmt = $conn->prepare("SELECT COUNT(*) as total 
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.patient_id
                        $whereSql");
if(!empty($params)){
    $stmt->bind_param($param_types, ...$params); 
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Fetch appointments with LIMIT
$query = "SELECT a.*, p.fullname AS patient_name 
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          $whereSql
          ORDER BY a.appointment_date ASC
          LIMIT ?, ?";

$stmt = $conn->prepare($query);

// Prepare bind array
$bind_params = [];
$bind_types = $param_types;

// Add LIMIT params
$bind_params[] = &$start;
$bind_params[] = &$limit;
$bind_types .= "ii";

// If there are filters, merge them first
if(!empty($params)){
    $tmp_params = [];
    foreach($params as $key => $value){
        $tmp_params[$key] = &$params[$key];
    }
    $bind_params = array_merge($tmp_params, $bind_params);
}

// Bind dynamically
call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_types], $bind_params));

$stmt->execute();
$result = $stmt->get_result();

// Fetch patients for dropdown
$patients_result = $conn->query("SELECT patient_id, fullname FROM patients ORDER BY fullname ASC");
$patients = [];
while($row = $patients_result->fetch_assoc()){
    $patients[$row['patient_id']] = $row['fullname'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BHCIS - Appointments</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/logo1.png" alt="TMHC Logo">
            <h2>BHCIS</h2>
            <p class="welcome"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Guest') ?><br>
            <small>(<?= htmlspecialchars($_SESSION['role'] ?? 'Unknown') ?>)</small></p>
        </div>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="patients.php">👨‍⚕️ Patients</a></li>
            <li><a href="appointments.php" class="active">📅 Appointments</a></li>
            <li><a href="immunization.php">💉 Immunization</a></li>
            <li><a href="inventory.php">💊 Inventory</a></li>
            <li><a href="reports.php">📊 Reports</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Patient's Appointments</h1>
            <p>Manage scheduled appointments</p>
        </div>

        <button id="openAddModal" class="btn-primary">+ Add Appointment</button>

        <!-- Alerts -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Filter Form -->
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search by patient name" value="<?= htmlspecialchars($search) ?>">
            <select name="service">
                <option value="">-- Select Service --</option>
                <option value="childcare" <?= $service=='childcare'?'selected':'' ?>>Childcare</option>
                <option value="maternal" <?= $service=='maternal'?'selected':'' ?>>Maternal</option>
                <option value="dental" <?= $service=='dental'?'selected':'' ?>>Dental</option>
                <option value="checkups" <?= $service=='checkups'?'selected':'' ?>>Checkups</option>
                <option value="other" <?= $service=='other'?'selected':'' ?>>Other</option>
            </select>
            <select name="status">
                <option value="">-- Select Status --</option>
                <option value="Pending" <?= $status=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Completed" <?= $status=='Completed'?'selected':'' ?>>Completed</option>
                <option value="Cancelled" <?= $status=='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn-primary">Filter</button>
        </form>

        <!-- Appointments Table -->
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient Name</th>
                    <th>Date & Time</th>
                    <th>Health Service</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['appointment_id'] ?></td>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($row['appointment_date'])) ?></td>
                            <td><span class="status <?= ucfirst($row['service']) ?>"><?= htmlspecialchars($row['service']) ?></span></td>
                            <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                            <td>
                                <button class="btn-edit editBtn"
                                    data-id="<?= $row['appointment_id'] ?>"
                                    data-patient_id="<?= $row['patient_id'] ?>"
                                    data-appointment_date="<?= date('Y-m-d\TH:i', strtotime($row['appointment_date'])) ?>"
                                    data-status="<?= $row['status'] ?>"
                                    data-service="<?= $row['service'] ?>"
                                >Edit</button>
                                <a href="DELETE/delete_appointment.php?id=<?= $row['appointment_id'] ?>" class="btn-delete" onclick="return confirm('Delete this appointment?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No appointments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            $startPage = max(1, $page-2);
            $endPage = min($totalPages, $page+2);
            $queryParams = "&search=" . urlencode($search) . "&service=" . urlencode($service) . "&status=" . urlencode($status);
            if($page > 1): ?>
                <a href="?page=<?= $page-1 ?><?= $queryParams ?>">&laquo; Prev</a>
            <?php endif; ?>
            <?php for($i=$startPage; $i<=$endPage; $i++): ?>
                <a href="?page=<?= $i ?><?= $queryParams ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?><?= $queryParams ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Appointment</h2>
        <form action="ADD/add_appointment_process.php" method="POST">
            <label for="add_patient_id">Patient:</label>
            <select name="patient_id" id="add_patient_id" required>
                <option value="">-- Select Patient --</option>
                <?php foreach($patients as $id=>$name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="add_appointment_date">Date & Time:</label>
            <input type="datetime-local" name="appointment_date" id="add_appointment_date" required>
            <label for="add_service">Service</label>
            <select name="service" id="add_service" required>
                <option value="">-- Select Service --</option>
                <option value="childcare">Childcare</option>
                <option value="maternal">Maternal</option>
                <option value="dental">Dental</option>
                <option value="checkups">Checkups</option>
                <option value="other">Other</option>
            </select>
            <label for="add_status">Status:</label>
            <select name="status" id="add_status" required>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <button type="submit" class="btn-primary">Add Appointment</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Update Appointment</h2>
        <form action="UPDATE/update_appointment_process.php" method="POST">
            <input type="hidden" name="appointment_id" id="edit_appointment_id">
            <label for="edit_patient_id">Patient:</label>
            <select name="patient_id" id="edit_patient_id" required>
                <option value="">-- Select Patient --</option>
                <?php foreach($patients as $id=>$name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="edit_appointment_date">Date & Time:</label>
            <input type="datetime-local" name="appointment_date" id="edit_appointment_date" required>
            <label for="edit_service">Service</label>
            <select name="service" id="edit_service" required>
                <option value="">-- Select Service --</option>
                <option value="childcare">Childcare</option>
                <option value="maternal">Maternal</option>
                <option value="dental">Dental</option>
                <option value="checkups">Checkups</option>
                <option value="other">Other</option>
            </select>
            <label for="edit_status">Status:</label>
            <select name="status" id="edit_status" required>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
            <button type="submit" class="btn-primary">Update Appointment</button>
        </form>
    </div>
</div>

<script>
// Add Modal
const addModal = document.getElementById("addModal");
document.getElementById("openAddModal").onclick = () => addModal.classList.add("show");
addModal.querySelector(".close").onclick = () => addModal.classList.remove("show");

// Edit Modal
const editModal = document.getElementById("editModal");
editModal.querySelector(".close").onclick = () => editModal.classList.remove("show");
document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", () => {
        editModal.classList.add("show");
        document.getElementById('edit_appointment_id').value = btn.dataset.id;
        document.getElementById('edit_patient_id').value = btn.dataset.patient_id;
        document.getElementById('edit_appointment_date').value = btn.dataset.appointment_date;
        document.getElementById('edit_status').value = btn.dataset.status;
        document.getElementById('edit_service').value = btn.dataset.service;
    });
});

// Close modals on outside click or ESC
window.addEventListener('click', e => {
    if(e.target === addModal) addModal.classList.remove("show");
    if(e.target === editModal) editModal.classList.remove("show");
});
window.addEventListener('keydown', e => {
    if(e.key === "Escape"){
        addModal.classList.remove("show");
        editModal.classList.remove("show");
    }
});

// Auto-hide alerts
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s ease-out';
        setTimeout(() => alert.remove(), 500);
    }, 3000);
});
</script>

</body>
</html>
<?php $conn->close(); ?>
