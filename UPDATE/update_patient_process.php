<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);

    $query = "UPDATE patients 
              SET fullname='$fullname', birthdate='$birthdate', gender='$gender', address='$address', contact='$contact'
              WHERE patient_id='$id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Patient updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating patient: " . mysqli_error($conn);
    }

    header("Location: ../patients.php");
    exit();
} else {
    header("Location: ../patients.php");
    exit();
}
