<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $vaccine = $_POST['vaccine'] ?? '';
    $dose = $_POST['dose'] ?? ''; // new dose field
    $date_administered = $_POST['date_administered'] ?? '';
    $status = $_POST['status'] ?? 'Pending';

    if ($patient_id && $vaccine && $date_administered && $status) {
        $patient_id = mysqli_real_escape_string($conn, $patient_id);
        $vaccine = mysqli_real_escape_string($conn, $vaccine);
        $dose = mysqli_real_escape_string($conn, $dose); // escape dose
        $date_administered = mysqli_real_escape_string($conn, $date_administered);
        $status = mysqli_real_escape_string($conn, $status);

        // Insert including dose
        $query = "INSERT INTO immunizations (patient_id, vaccine, dose, date_administered, status) 
                  VALUES ('$patient_id', '$vaccine', '$dose', '$date_administered', '$status')";

        if(mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Immunization added successfully.";
        } else {
            $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }
}

header("Location: ../immunization.php");
exit();
?>
