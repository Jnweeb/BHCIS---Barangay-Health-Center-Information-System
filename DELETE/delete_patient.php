<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    $query = "DELETE FROM patients WHERE patient_id='$id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Patient deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting patient: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error'] = "Invalid patient ID!";
}

// Redirect back to patients.php
header("Location: ../patients.php");
exit();
