<?php
session_start();
include('includes/auth.php');
include('includes/db_connect.php');

// Helper: check if table exists
function tableExists($conn, $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

// Helper: get count from table with optional WHERE
function getCount($conn, $table, $where = '') {
    $query = "SELECT COUNT(*) AS total FROM $table" . ($where ? " WHERE $where" : "");
    $result = mysqli_query($conn, $query);
    return $result ? (int)(mysqli_fetch_assoc($result)['total'] ?? 0) : 0;
}

// Normalize role
$role = strtolower($_SESSION['role'] ?? '');

// --- Dashboard stats ---
$patients_total = getCount($conn, 'patients');
$pending_appointments = tableExists($conn, 'appointments') ? getCount($conn, 'appointments', "status='Pending'") : 0;
$inventory_total = tableExists($conn, 'inventory') ? getCount($conn, 'inventory') : 0;

// --- Chart Data: monthly counts ---
function getMonthlyCounts($conn, $table, $dateColumn) {
    if (!tableExists($conn, $table)) return [];
    $query = "SELECT DATE_FORMAT($dateColumn, '%Y-%m') AS month, COUNT(*) AS total
              FROM $table
              GROUP BY month
              ORDER BY month ASC";
    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[$row['month']] = (int)$row['total'];
        }
    }
    return $data;
}

// Patients, Appointments, Immunizations monthly
$patients_chart_data = getMonthlyCounts($conn, 'patients', 'date_registered');
$appointments_chart_data = getMonthlyCounts($conn, 'appointments', 'appointment_date');
$immunizations_chart_data = getMonthlyCounts($conn, 'immunizations', 'date_administered');

// Merge all months
$all_months = array_unique(array_merge(
    array_keys($patients_chart_data),
    array_keys($appointments_chart_data),
    array_keys($immunizations_chart_data)
));
sort($all_months);

// Prepare datasets
$patients_data = array_map(fn($m) => $patients_chart_data[$m] ?? 0, $all_months);
$appointments_data = array_map(fn($m) => $appointments_chart_data[$m] ?? 0, $all_months);
$immunizations_data = array_map(fn($m) => $immunizations_chart_data[$m] ?? 0, $all_months);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BHCIS Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <li><a href="dashboard.php" class="active">🏠 Dashboard</a></li>
            <li><a href="patients.php">👨‍⚕️ Patients</a></li>
            <li><a href="appointments.php">📅 Appointments</a></li>
            <li><a href="immunization.php">💉 Immunization</a></li>
            <li><a href="inventory.php">💊 Inventory</a></li>
            <li><a href="reports.php">📊 Reports</a></li>
            <?php if ($role === 'admin'): ?>
                <li><a href="register.php">➕ Register User</a></li>
            <?php endif; ?>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Barangay Health Center Information System</h1>
            <p>Welcome to the dashboard overview.</p>
        </div>

        <section class="dashboard">
            <div class="card-grid">
                <div class="card" onclick="location.href='patients.php'">
                    <h3>Total Registered Patients</h3>
                    <p><?= $patients_total ?></p>
                </div>
                <div class="card" onclick="location.href='appointments.php'">
                    <h3>Pending Appointments</h3>
                    <p><?= $pending_appointments ?></p>
                </div>
                <div class="card" onclick="location.href='inventory.php'">
                    <h3>Total Inventory Items</h3>
                    <p><?= $inventory_total ?></p>
                </div>
            </div>

            <div class="chart-card">
                <h3>Monthly Trends</h3>
                <canvas id="trendsChart"></canvas>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('trendsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($all_months) ?>,
        datasets: [
            {
                label: 'Patients',
                data: <?= json_encode($patients_data) ?>,
                backgroundColor: 'rgba(27, 140, 102, 0.7)',
                borderColor: 'rgba(27, 140, 102, 1)',
                borderWidth: 1
            },
            {
                label: 'Appointments',
                data: <?= json_encode($appointments_data) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Immunizations',
                data: <?= json_encode($immunizations_data) ?>,
                backgroundColor: 'rgba(255, 159, 64, 0.7)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            x: { title: { display: true, text: 'Month' } },
            y: { beginAtZero: true, title: { display: true, text: 'Count' }, precision: 0 }
        }
    }
});
</script>
</body>
</html>
