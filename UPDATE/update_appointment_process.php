<?php
session_start();
include('../includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $patient_id = $_POST['patient_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $status = $_POST['status'] ?? 'Pending';
    $service = $_POST['service'] ?? '';

    if(empty($appointment_id) || empty($patient_id) || empty($appointment_date) || empty($service)) {
        $_SESSION['error'] = "Please fill all required fields.";
        header("Location: ../appointments.php");
        exit();
    }

    // Update appointment
    $stmt = $conn->prepare("UPDATE appointments SET patient_id = ?, appointment_date = ?, status = ?, service = ? WHERE appointment_id = ?");
    $stmt->bind_param("isssi", $patient_id, $appointment_date, $status, $service, $appointment_id);

    if($stmt->execute()) {
        // Update patient's health_service
        $update = $conn->prepare("UPDATE patients SET health_service = ? WHERE patient_id = ?");
        $update->bind_param("si", $service, $patient_id);
        $update->execute();

        $_SESSION['success'] = "Appointment updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update appointment.";
    }

    $stmt->close();
    $conn->close();
    header("Location: ../appointments.php");
}
