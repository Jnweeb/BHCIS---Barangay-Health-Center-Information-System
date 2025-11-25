<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $item_name = $_POST['item_name'] ?? '';
    $category  = $_POST['category'] ?? '';
    $quantity  = $_POST['quantity'] ?? 0;
    $unit      = $_POST['unit'] ?? '';
    $status    = $_POST['status'] ?? 'Available';

    // Handle expiry date
    $expiry_date = $_POST['expiry_date'] ?? null;
    $expiry_date = $expiry_date ? "'$expiry_date'" : "NULL";

    // Prepare other fields with bind_param
    $stmt = $conn->prepare("
        INSERT INTO inventory (item_name, category, quantity, unit, status, expiry_date)
        VALUES (?, ?, ?, ?, ?, $expiry_date)
    ");

    $stmt->bind_param("ssiss", $item_name, $category, $quantity, $unit, $status);

    if($stmt->execute()) {
        $_SESSION['success'] = "Inventory item added successfully.";
    } else {
        $_SESSION['error'] = "Error adding item: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    header("Location: ../inventory.php");
    exit;
}
?>
