<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

$appointment_id = $_GET['id'] ?? '';

if (!empty($appointment_id)) {
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting appointment: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid appointment ID.";
}

$conn->close();
header("Location: ../appointments.php");
exit();
