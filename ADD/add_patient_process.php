<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);

    // Insert into patients table
    $query = "INSERT INTO patients (fullname, birthdate, gender, address, contact) 
              VALUES ('$fullname', '$birthdate', '$gender', '$address', '$contact')";

    if (mysqli_query($conn, $query)) {
        // Success
        $_SESSION['success'] = "Patient added successfully!";
    } else {
        // Error
        $_SESSION['error'] = "Error adding patient: " . mysqli_error($conn);
    }

    // Redirect back to patients.php
    header("Location: ../patients.php");
    exit();
} else {
    // Invalid request
    header("Location: ../patients.php");
    exit();
}
