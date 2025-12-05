<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Get filters
$search = mysqli_real_escape_string($conn, trim($_GET['search'] ?? ''));
$gender = $_GET['gender'] ?? '';
$age_group = $_GET['age_group'] ?? '';

// Build WHERE clause for patients
$where = [];

if(!empty($search)) {
    $where[] = "p.fullname LIKE '%$search%'";
}

if(!empty($gender)) {
    $where[] = "p.gender = '".mysqli_real_escape_string($conn, $gender)."'";
}

// Map age_group to SQL conditions
if(!empty($age_group)) {
    switch($age_group) {
        case 'infant':
            $where[] = "TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) BETWEEN 0 AND 1";
            break;
        case 'child':
            $where[] = "TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) BETWEEN 2 AND 12";
            break;
        case 'teenager':
            $where[] = "TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) BETWEEN 13 AND 19";
            break;
        case 'adult':
            $where[] = "TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) BETWEEN 20 AND 59";
            break;
        case 'senior':
            $where[] = "TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) >= 60";
            break;
    }
}

$whereSql = '';
if(!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Count total patients
$countQuery = "SELECT COUNT(*) as total FROM patients p $whereSql";
$countResult = mysqli_query($conn, $countQuery);
$total = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($total / $limit);

// Fetch patients with age and latest health service
$query = "
    SELECT p.*, TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) AS age,
           a.service AS latest_service,
           a.appointment_date AS latest_date
    FROM patients p
    LEFT JOIN appointments a 
        ON a.patient_id = p.patient_id
        AND a.appointment_date = (
            SELECT appointment_date
            FROM appointments
            WHERE patient_id = p.patient_id
            ORDER BY ABS(TIMESTAMPDIFF(SECOND, appointment_date, NOW())) ASC
            LIMIT 1
        )
    $whereSql
    ORDER BY p.patient_id DESC
    LIMIT $start, $limit
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BHCIS - Patients</title>
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
                <a href="dashboard.php" >
                    <img class="icon" src="assets/icons/dashboard.svg" alt="Dashboard">
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="patients.php" class="active">
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
                <a href="immunization.php">
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
            <h1>Patient's Record </h1>
            <p>List of recorded patients in the system.</p>
        </div>

        <!-- Add Patient Button -->
        <button id="openModal" class="btn-primary">+ Add Patient</button>

        <!-- Patient Modal -->
        <div id="patientModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add New Patient</h2>
                <form action="ADD/add_patient_process.php" method="POST">
                    <label for="fullname">Full Name</label>
                    <input type="text" name="fullname" id="fullname" required>

                    <label for="birthdate">Birthdate</label>
                    <input type="date" name="birthdate" id="birthdate" required>

                    <label for="gender">Gender</label>
                    <select name="gender" id="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" readonly>

                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" required>

                    <label for="contact">Contact Number</label>
                    <div class="contact-wrapper">
                        <input type="text" id="contact" name="contact" 
                            placeholder="09XXXXXXXXXX" 
                            maxlength="11" pattern="\d{11}" required>
                    </div>
                    <button type="submit" class="btn-primary">Add Patient</button>
                </form>
            </div>
        </div>
        <!-- Edit Patient Modal -->
        <div id="editPatientModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Patient</h2>
                <form action="UPDATE/update_patient_process.php" method="POST">
                    <input type="hidden" name="patient_id" id="edit_patient_id">
                    
                    <label for="edit_fullname">Full Name</label>
                    <input type="text" name="fullname" id="edit_fullname" required>

                    <label for="edit_birthdate">Birthdate</label>
                    <input type="date" name="birthdate" id="edit_birthdate" required>

                    <label for="edit_gender">Gender</label>
                    <select name="gender" id="edit_gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>


                    <label for="edit_age">Age</label>
                    <input type="number" name="age" id="edit_age" readonly>

                    <label for="edit_address">Address</label>
                    <input type="text" name="address" id="edit_address" required>

                    <label for="edit_contact">Contact</label>
                    <input type="text" name="contact" id="edit_contact" required>

                    <button type="submit" class="btn-primary">Update Patient</button>
                </form>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="GET" action="patients.php" class="filter-form">
            <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">

            <select name="age_group">
                <option value="">-- Select Age Group --</option>
                <option value="infant" <?php if(isset($_GET['age_group']) && $_GET['age_group']=='infant') echo 'selected'; ?>>Infant</option>
                <option value="child" <?php if(isset($_GET['age_group']) && $_GET['age_group']=='child') echo 'selected'; ?>>Child</option>
                <option value="teenager" <?php if(isset($_GET['age_group']) && $_GET['age_group']=='teenager') echo 'selected'; ?>>Teenager</option>
                <option value="adult" <?php if(isset($_GET['age_group']) && $_GET['age_group']=='adult') echo 'selected'; ?>>Adult</option>
                <option value="senior" <?php if(isset($_GET['age_group']) && $_GET['age_group']=='senior') echo 'selected'; ?>>Senior</option>
            </select>

            <select name="gender">
                <option value="">-- Select Gender --</option>
                <option value="Male" <?php if(isset($_GET['gender']) && $_GET['gender']=='Male') echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if(isset($_GET['gender']) && $_GET['gender']=='Female') echo 'selected'; ?>>Female</option>
            </select>

            <button type="submit">Filter</button>

        </form>

        <!-- Patients Table -->
         <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Address</th>                   
                    <th>Health Service</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['patient_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                        <td><?php echo $row['gender']; ?></td>
                        <td>
                            <?php echo $row['age']; ?> 
                            <?php 
                            // Optional: add age group badge
                            $age = $row['age'];
                            if($age <= 1) echo '<span class="status pending">Infant</span>';
                            elseif($age <= 12) echo '<span class="status completed">Child</span>';
                            elseif($age <= 19) echo '<span class="status warning">Teenager</span>';
                            elseif($age <= 59) echo '<span class="status active">Adult</span>';
                            else echo '<span class="status inactive">Senior</span>';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>                        
                        <td>
                            <?php if(!empty($row['latest_service'])): ?>
                                <span class="status <?= strtolower($row['latest_service']) ?>">
                                    <?= htmlspecialchars(ucfirst($row['latest_service'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="status inactive">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view_patient.php?id=<?php echo $row['patient_id']; ?>" class="btn-primary">View</a>
                            <button class="btn-edit editBtn" 
                                data-id="<?php echo $row['patient_id']; ?>"
                                data-fullname="<?php echo htmlspecialchars($row['fullname']); ?>"
                                data-birthdate="<?php echo $row['birthdate']; ?>"
                                data-gender="<?php echo $row['gender']; ?>"
                                data-address="<?php echo htmlspecialchars($row['address']); ?>"
                                data-contact="<?php echo htmlspecialchars($row['contact']); ?>"
                            >Edit</button>
                            <a href="DELETE/delete_patient.php?id=<?php echo $row['patient_id']; ?>" 
                                class="btn-delete" 
                                onclick="return confirm('Are you sure you want to delete this patient?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No patients found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="assets/javascript/sidebar.js"></script>
<script src="assets/javascript/jquery-3.6.0.min.js"></script>
<script src="assets/javascript/patient.js"></script>
<script src="assets/javascript/hide.js"></script>
</body>
</html>
