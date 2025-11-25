<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

// Check if item ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid inventory item.";
    header("Location: inventory.php");
    exit;
}

$item_id = (int)$_GET['id'];

// Delete the item
$stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
$stmt->bind_param("i", $item_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Inventory item deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete the item.";
}

$stmt->close();
$conn->close();
header("Location: ../inventory.php");
exit;
?>
