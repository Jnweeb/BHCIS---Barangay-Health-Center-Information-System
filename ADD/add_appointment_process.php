<?php
session_start();
include('../includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $status = $_POST['status'] ?? 'Pending';
    $service = $_POST['service'] ?? '';

    if(empty($patient_id) || empty($appointment_date) || empty($service)) {
        $_SESSION['error'] = "Please fill all required fields.";
        header("Location: ../appointments.php");
        exit();
    }

    // Insert appointment
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, status, service) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $patient_id, $appointment_date, $status, $service);
    if($stmt->execute()) {
        // Update patient's health_service
        $update = $conn->prepare("UPDATE patients SET health_service = ? WHERE patient_id = ?");
        $update->bind_param("si", $service, $patient_id);
        $update->execute();

        $_SESSION['success'] = "Appointment added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add appointment.";
    }

    $stmt->close();
    $conn->close();
    header("Location: ../appointments.php");
}
