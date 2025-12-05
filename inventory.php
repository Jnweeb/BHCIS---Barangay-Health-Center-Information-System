<?php
include('includes/auth.php');
include('includes/db_connect.php');

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';

// Count total items
$countQuery = "SELECT COUNT(*) as total FROM inventory 
               WHERE item_name LIKE ? OR category LIKE ?";
$stmtCount = $conn->prepare($countQuery);
$searchParam = "%$search%";
$stmtCount->bind_param("ss", $searchParam, $searchParam);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($total / $limit);

// Fetch items with pagination
$query = "SELECT * FROM inventory 
          WHERE item_name LIKE ? OR category LIKE ?
          ORDER BY item_id ASC
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssii", $searchParam, $searchParam, $start, $limit);
$stmt->execute();
$result = $stmt->get_result();

// Define categories
$categories = ['Medicine', 'Supplies', 'Vaccine', 'Disinfectant'];
// Units for dropdown
$units = ['tablets','bottles','rolls','packs','ml','liters','pieces','pairs'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory - BHCIS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <button id="toggleSidebar" class="sidebar-toggle">☰</button>

        <div class="sidebar-header">
            <img src="assets/images/logo1.png" alt="TMHC Logo">
            <h2>BHCIS</h2>
            <p class="welcome">
                <?= htmlspecialchars($_SESSION['fullname'] ?? '') ?><br>
                <small>(<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</small>
            </p>
        </div>
        <ul>
            <li>
                <a href="dashboard.php" >
                    <img class="icon" src="assets/icons/dashboard.svg" alt="Dashboard">
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="patients.php">
                    <img class="icon" src="assets/icons/patient.svg" alt="Patients">
                    <span class="menu-text">Patient</span>
                </a>
            </li>
            <li>
                <a href="appointments.php">
                    <img class="icon" src="assets/icons/appointment.svg" alt="Appointments">
                    <span class="menu-text">Appointment</span>
                </a>
            </li>
            <li>
                <a href="immunization.php">
                    <img class="icon" src="assets/icons/immunization.svg" alt="Immunization">
                    <span class="menu-text">Immunization</span>
                </a>
            </li>
            <li>
                <a href="inventory.php" class="active">
                    <img class="icon" src="assets/icons/inventory.svg" alt="Inventory">
                    <span class="menu-text">Inventory</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <img class="icon" src="assets/icons/reports.svg" alt="Reports">
                    <span class="menu-text">Reports</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <img class="icon" src="assets/icons/logout.svg" alt="Logout">
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Drug/Medicine Inventory</h1>
            <p>Manage health center inventory items.</p>
        </div>

        <button id="openAddModal" class="btn-primary">+ Add Item</button>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Search -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search item or category" value="<?= htmlspecialchars($search) ?>">
            <button class="btn-primary">Search</button>
        </form>

        <!-- Inventory Table -->
        <table class="patients-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['item_id'] ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= htmlspecialchars($row['unit']) ?></td>
                            <td><?= $row['expiry_date'] ? date("F d, Y", strtotime($row['expiry_date'])) : 'N/A' ?></td>
                            <td><?= $row['status'] ?></td>
                            <td>
                                <button class="btn-edit" 
                                    data-id="<?= $row['item_id'] ?>"
                                    data-name="<?= htmlspecialchars($row['item_name']) ?>"
                                    data-category="<?= htmlspecialchars($row['category']) ?>"
                                    data-quantity="<?= $row['quantity'] ?>"
                                    data-unit="<?= htmlspecialchars($row['unit']) ?>"
                                    data-expiry="<?= $row['expiry_date'] ?>"
                                    data-status="<?= $row['status'] ?>">Edit</button>
                                <a href="DELETE/delete_inventory.php?id=<?= $row['item_id'] ?>" class="btn-delete" onclick="return confirm('Delete this item?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">&laquo; Prev</a><?php endif; ?>
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next &raquo;</a><?php endif; ?>
        </div>

    </div>
</div>

<!-- Add Inventory Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Inventory Item</h2>
        <form action="ADD/add_inventory_process.php" method="POST">
            <label for="add_item_name">Item Name</label>
            <input type="text" name="item_name" id="add_item_name" required>


            <label for="category">Category</label>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat ?>"><?= $cat ?></option>
                <?php endforeach; ?>
            </select>

            <label for="add_quantity">Quantity</label>
            <input type="number" name="quantity" id="add_quantity" min="0" required>

            <label for="add_unit">Unit</label>
            <select name="unit" id="add_unit" required>
                <option value="">-- Select Unit --</option>
                <?php foreach($units as $u): ?>
                    <option value="<?= $u ?>"><?= ucfirst($u) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="add_expiry">Expiry Date</label>
            <input type="date" name="expiry_date" id="add_expiry">

            <label for="add_status">Status</label>
            <select name="status" id="add_status" required>
                <option value="Available">Available</option>
                <option value="Low Stock">Low Stock</option>
                <option value="Out of Stock">Out of Stock</option>
            </select>

            <button type="submit" class="btn-primary">Add Item</button>
        </form>
    </div>
</div>

<!-- Edit Inventory Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Inventory Item</h2>
        <form action="UPDATE/update_inventory_process.php" method="POST">
            <input type="hidden" name="item_id" id="edit_item_id">

            <label for="edit_item_name">Item Name</label>
            <input type="text" name="item_name" id="edit_item_name" required>

            <label for="category">Category</label>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat ?>"><?= $cat ?></option>
                <?php endforeach; ?>
            </select>

            <label for="edit_quantity">Quantity</label>
            <input type="number" name="quantity" id="edit_quantity" min="0" required>

            <label for="edit_unit">Unit</label>
            <select name="unit" id="edit_unit" required>
                <option value="">-- Select Unit --</option>
                <?php foreach($units as $u): ?>
                    <option value="<?= $u ?>"><?= ucfirst($u) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="edit_expiry">Expiry Date</label>
            <input type="date" name="expiry_date" id="edit_expiry">

            <label for="edit_status">Status</label>
            <select name="status" id="edit_status" required>
                <option value="Available">Available</option>
                <option value="Low Stock">Low Stock</option>
                <option value="Out of Stock">Out of Stock</option>
            </select>

            <button type="submit" class="btn-primary">Update Item</button>
        </form>
    </div>
</div>
<script src="assets/javascript/sidebar.js"></script>
<script src="assets/javascript/inventory.js"></script>
<script src="assets/javascript/hide.js"></script>
</body>
</html>
