<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');

$patient_id = $_GET['id'] ?? 0;
$patient_id = (int)$patient_id;

// Fetch patient info
$patientQuery = "SELECT *, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age FROM patients WHERE patient_id = $patient_id";
$patientResult = mysqli_query($conn, $patientQuery);
$patient = mysqli_fetch_assoc($patientResult);

if(!$patient) {
    echo "<p>Patient not found.</p>";
    exit;
}

// Determine age group badge
$age = $patient['age'];
if($age <= 1) { $ageLabel='Infant'; $ageClass='pending'; }
elseif($age <= 12) { $ageLabel='Child'; $ageClass='completed'; }
elseif($age <= 19) { $ageLabel='Teenager'; $ageClass='warning'; }
elseif($age <= 59) { $ageLabel='Adult'; $ageClass='active'; }
else { $ageLabel='Senior'; $ageClass='inactive'; }

// Health service badge
$service = strtolower($patient['health_service'] ?? 'other');
$serviceClass = $service;

// Fetch appointments & immunizations
$appointments = mysqli_query($conn, "SELECT * FROM appointments WHERE patient_id = $patient_id ORDER BY appointment_date DESC");
$immunizations = mysqli_query($conn, "SELECT * FROM immunizations WHERE patient_id = $patient_id ORDER BY date_administered DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BHCIS - Patients</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
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
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Patient Details</h1>
            <p>Viewing full information for <strong><?php echo htmlspecialchars($patient['fullname']); ?></strong></p>
        </div>

        <!-- Patient Info Table -->
        <div class="card">
            <h2>Patient Information</h2>
            <table class="patients-table">
                <tbody>
                    <tr>
                        <th>Full Name</th>
                        <td><?php echo htmlspecialchars($patient['fullname']); ?></td>
                    </tr>
                    <tr>
                        <th>Birthdate</th>
                        <td><?= date('M d, Y', strtotime($patient['birthdate'])) ?></td>
                    </tr>
                    <tr>
                        <th>Age</th>
                        <td><?php echo $patient['age']; ?> <span class="status <?php echo $ageClass; ?>"><?php echo $ageLabel; ?></span></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?php echo $patient['gender']; ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?php echo htmlspecialchars($patient['address']); ?></td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td><?php echo htmlspecialchars($patient['contact']); ?></td>
                    </tr>
                    <tr>
                        <th>Health Service</th>
                        <td><span class="status <?php echo $serviceClass; ?>"><?php echo ucfirst($service); ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Appointments Table -->
        <div class="card">
            <h2>Appointments</h2>
            <table class="patients-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($appointments) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($appointments)): ?>
                            <tr>
                                <td><?php echo $row['appointment_date']; ?></td>
                                <td><?php echo ucfirst($row['service']); ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($row['status']);
                                    if($status=='pending') echo '<span class="status pending">Pending</span>';
                                    elseif($status=='completed') echo '<span class="status completed">Completed</span>';
                                    elseif($status=='cancelled') echo '<span class="status cancelled">Cancelled</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Immunizations Table -->
        <div class="card">
            <h2>Immunizations</h2>
            <table class="patients-table">
                <thead>
                    <tr>
                        <th>Date Administered</th>
                        <th>Vaccine</th>
                        <th>Dose</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($immunizations) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($immunizations)): ?>
                            <tr>
                                <td><?php echo $row['date_administered']; ?></td>
                                <td><?php echo htmlspecialchars($row['vaccine']); ?></td>
                                <td><?php echo htmlspecialchars($row['dose']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;">No immunizations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Back Button -->
        <div style="margin-top: 1rem;">
            <a href="patients.php" class="btn-primary">← Back to Patients List</a>
        </div>
    </div>
<script src="assets/javascript/patient.js"></script>
<script src="assets/javascript/sidebar.js"></script>
</body>
</html>
