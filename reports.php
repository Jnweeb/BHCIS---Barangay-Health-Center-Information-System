<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');
require_once('tcpdf/tcpdf.php'); // TCPDF library

// Helper to check table existence
function tableExists($conn, $table) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

// Fetch data
$patientsResult = tableExists($conn, 'patients') ? mysqli_query($conn, "SELECT *, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age FROM patients ORDER BY patient_id ASC") : false;
$appointments = tableExists($conn,'appointments') ? mysqli_query($conn, "SELECT a.*, p.fullname as patient_name FROM appointments a JOIN patients p ON a.patient_id=p.patient_id ORDER BY a.appointment_id ASC") : [];
$vaccineSummary = tableExists($conn,'immunizations') ? mysqli_query($conn,"SELECT vaccine, COUNT(*) as total FROM immunizations GROUP BY vaccine") : [];
$inventoryItems = tableExists($conn,'inventory') ? mysqli_query($conn,"SELECT * FROM inventory ORDER BY item_id ASC") : [];

// Stats
$patientCount = tableExists($conn, 'patients') ? (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM patients"))['total'] : 0;
$newPatientsMonth = tableExists($conn,'patients') ? (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM patients WHERE MONTH(date_registered) = MONTH(CURDATE()) AND YEAR(date_registered) = YEAR(CURDATE())"))['total'] : 0;
$totalAppointments = tableExists($conn, 'appointments') ? (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments"))['total'] : 0;
$totalVaccines = tableExists($conn, 'immunizations') ? (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM immunizations"))['total'] : 0;

// Prepare vaccine data
$vaccineData = [];
if ($vaccineSummary && mysqli_num_rows($vaccineSummary) > 0) {
    while($row = mysqli_fetch_assoc($vaccineSummary)) $vaccineData[] = $row;
}

// Generate PDF
if(isset($_GET['pdf']) && $_GET['pdf'] == 1){
    $pdf = new TCPDF();
    $pdf->AddPage();
    $html = "<h1>Medical & Statistical Report</h1>
             <h3>Summary</h3>
             <ul>
               <li>Total Patients: $patientCount</li>
               <li>New Patients This Month: $newPatientsMonth</li>
               <li>Total Appointments: $totalAppointments</li>
               <li>Total Vaccines Administered: $totalVaccines</li>
             </ul>";

    // Patients Table
    if($patientsResult && mysqli_num_rows($patientsResult) > 0){
        $html .= "<h3>Patient's Record</h3>
                  <table border='1' cellpadding='4'>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Address</th>
                        <th>Contact</th>
                    </tr>";
        while($row = mysqli_fetch_assoc($patientsResult)){
            $html .= "<tr>
                        <td>{$row['patient_id']}</td>
                        <td>{$row['fullname']}</td>
                        <td>{$row['gender']}</td>
                        <td>{$row['age']}</td>
                        <td>{$row['address']}</td>
                        <td>{$row['contact']}</td>
                      </tr>";
        }
        $html .= "</table>";
    }

    // Appointments Table
    if($appointments && mysqli_num_rows($appointments) > 0){
        $html .= "<h3>Appointments Record</h3>
                  <table border='1' cellpadding='4'>
                    <tr><th>ID</th><th>Patient</th><th>Date & Time</th><th>Health Service</th><th>Status</th></tr>";
        while($row = mysqli_fetch_assoc($appointments)){
            $html .= "<tr>
                        <td>{$row['appointment_id']}</td>
                        <td>{$row['patient_name']}</td>
                        <td>".date('M d, Y h:i A', strtotime($row['appointment_date']))."</td>
                        <td>{$row['service']}</td>
                        <td>{$row['status']}</td>
                      </tr>";
        }
        $html .= "</table>";
    }

    // Vaccination Table
    if(count($vaccineData) > 0){
        $html .= "<h3>Vaccination Record</h3>
                  <table border='1' cellpadding='4'>
                    <tr><th>Vaccine</th><th>Total Administered</th></tr>";
        foreach($vaccineData as $v){
            $html .= "<tr><td>{$v['vaccine']}</td><td>{$v['total']}</td></tr>";
        }
        $html .= "</table>";
    }

    // Inventory Table
    if($inventoryItems && mysqli_num_rows($inventoryItems) > 0){
        $html .= "<h3>Inventory Items</h3>
                  <table border='1' cellpadding='4'>
                    <tr><th>ID</th><th>Item Name</th><th>Quantity</th><th>Unit</th><th>Expiry Date</th></tr>";
        while($item = mysqli_fetch_assoc($inventoryItems)){
            $html .= "<tr>
                        <td>{$item['item_id']}</td>
                        <td>{$item['item_name']}</td>
                        <td>{$item['quantity']}</td>
                        <td>{$item['unit']}</td>
                        <td>{$item['expiry_date']}</td>
                      </tr>";
        }
        $html .= "</table>";
    }

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('report.pdf', 'I');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports - BHCIS</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/logo1.png" alt="TMHC Logo">
            <h2>BHCIS</h2>
            <p class="welcome"><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?><br>
            <small>(<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</small></p>
        </div>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="patients.php">👨‍⚕️ Patients</a></li>
            <li><a href="appointments.php">📅 Appointments</a></li>
            <li><a href="immunization.php">💉 Immunization</a></li>
            <li><a href="inventory.php">💊 Inventory</a></li>
            <li><a href="reports.php" class="active">📊 Reports</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Overall Reports</h1>
            <p>Overview of patients, appointments, immunizations, and inventory.</p>
            <a href="?pdf=1" class="btn-download">📄 Generate PDF</a>
        </div>

        <!-- Summary Cards -->
        <div class="card-grid">
            <div class="card"><h3>Total Patients</h3><p><?= $patientCount ?></p></div>
            <div class="card"><h3>New Patients This Month</h3><p><?= $newPatientsMonth ?></p></div>
            <div class="card"><h3>Total Appointments</h3><p><?= $totalAppointments ?></p></div>
            <div class="card"><h3>Total Vaccines Administered</h3><p><?= $totalVaccines ?></p></div>
        </div>

        <!-- Patients Table -->
        <h2>Patient's Record</h2>
        <?php if($patientsResult && mysqli_num_rows($patientsResult) > 0): ?>
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Birthdate</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Address</th>
                    <th>Contact</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = mysqli_fetch_assoc($patientsResult)): ?>
                <tr>
                    <td><?= $row['patient_id'] ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= $row['birthdate'] ?></td>
                    <td><?= $row['gender'] ?></td>
                    <td><?= $row['age'] ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= htmlspecialchars($row['contact']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;">No patients found.</p>
        <?php endif; ?>

        <!-- Appointments Table -->
        <h2>Appointment's Record</h2>
        <?php if($appointments && mysqli_num_rows($appointments) > 0): ?>
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Date & Time</th>
                    <th>Health Service</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = mysqli_fetch_assoc($appointments)): ?>
                <tr>
                    <td><?= $row['appointment_id'] ?></td>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($row['appointment_date'])) ?></td>
                    <td><?= htmlspecialchars($row['service']) ?></td>
                    <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;">No appointments found.</p>
        <?php endif; ?>

        <!-- Immunization Table -->
        <h2>Immunization Record</h2>
        <?php if(count($vaccineData) > 0): ?>
        <table class="patients-table">
            <thead>
                <tr>
                    <th>Vaccine/Medicine</th>
                    <th>Total Administered</th>
                </tr>  
            </thead>
            <tbody>
            <?php foreach($vaccineData as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['vaccine']) ?></td>
                    <td><?= $v['total'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;">No vaccine records found.</p>
        <?php endif; ?>

        <!-- Inventory Table -->
        <h2>Inventory Items</h2>
        <?php if($inventoryItems && mysqli_num_rows($inventoryItems) > 0): ?>
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
            <?php while($item = mysqli_fetch_assoc($inventoryItems)): ?>
                <tr>
                    <td><?= $item['item_id'] ?></td>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= $item['unit'] ?></td>
                    <td><?= $item['expiry_date'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;">No inventory records found.</p>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
