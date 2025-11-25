<?php
session_start();
include('../includes/auth.php');
include('../includes/db_connect.php');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $item_id   = (int)$_POST['item_id'];
    $item_name = trim($_POST['item_name']);
    $category  = trim($_POST['category']);
    $quantity  = (int)$_POST['quantity'];
    $unit      = trim($_POST['unit']);
    $status    = trim($_POST['status']);

    // Handle expiry date
    $expiry_date = $_POST['expiry_date'] ?? null;
    $expiry_date = $expiry_date ? "'$expiry_date'" : "NULL";

    if($item_id && $item_name && $category && $quantity >= 0 && $unit && $status){

        // expiry_date must be inserted manually — cannot use bind_param for NULL
        $query = "
            UPDATE inventory 
            SET item_name = ?, 
                category = ?, 
                quantity = ?, 
                unit = ?, 
                status = ?, 
                expiry_date = $expiry_date 
            WHERE item_id = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssissi", $item_name, $category, $quantity, $unit, $status, $item_id);

        if($stmt->execute()){
            $_SESSION['success'] = "Inventory item updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update item: " . $stmt->error;
        }

        $stmt->close();

    } else {
        $_SESSION['error'] = "All fields are required and quantity must be 0 or more.";
    }
}

header("Location: ../inventory.php");
exit();
?>
