<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $immunization_id = $_POST['immunization_id'] ?? '';
    $patient_id = $_POST['patient_id'] ?? '';
    $vaccine = $_POST['vaccine'] ?? '';
    $dose = $_POST['dose'] ?? '';
    $date_administered = $_POST['date_administered'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($immunization_id && $patient_id && $vaccine && $date_administered && $status) {
        $immunization_id = mysqli_real_escape_string($conn, $immunization_id);
        $patient_id = mysqli_real_escape_string($conn, $patient_id);
        $vaccine = mysqli_real_escape_string($conn, $vaccine);
        $dose = mysqli_real_escape_string($conn, $dose);
        $date_administered = mysqli_real_escape_string($conn, $date_administered);
        $status = mysqli_real_escape_string($conn, $status);

        // Corrected UPDATE query
        $query = "UPDATE immunizations 
                  SET patient_id='$patient_id', vaccine='$vaccine', dose='$dose', date_administered='$date_administered', status='$status' 
                  WHERE immunization_id='$immunization_id'";

        if(mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Immunization updated successfully.";
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
