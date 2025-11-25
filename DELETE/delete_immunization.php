<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if(isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    $query = "DELETE FROM immunizations WHERE immunization_id='$id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Immunization deleted successfully.";
    } else {
        $_SESSION['error'] = "Database error: " . mysqli_error($conn);
    }
}

header("Location: ../immunization.php");
exit();
