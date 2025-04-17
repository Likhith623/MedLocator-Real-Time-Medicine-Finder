<?php
require_once 'config/database.php';
session_start();

// Check if retailer is logged in
if (!isset($_SESSION['retailer_id'])) {
    header("Location: login.php");
    exit();
}

// Handle medicine deletion
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ? AND retailer_id = ?");
    $stmt->execute([$_POST['delete_id'], $_SESSION['retailer_id']]);
    header("Location: dashboard.php");
    exit();
}

// Fetch all medicines for the logged-in retailer
$stmt = $pdo->prepare("SELECT * FROM medicines WHERE retailer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['retailer_id']]);
$medicines = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 2rem 0;
        }
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .welcome-text {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        .logout-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .logout-link:hover {
            color: rgba(255, 255, 255, 0.8);
        }
        .btn-add {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
        }
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .table thead {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
        }
        .table th {
            font-weight: 500;
            padding: 1rem;
            border: none;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .btn-action {
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .btn-edit {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        .btn-edit:hover {
            background-color: #ffca2c;
            border-color: #ffc720;
            color: #000;
        }
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #e9ecef;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Medical Inventory</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['retailer_name']); ?></span>
                <a class="nav-link logout-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-card fade-in">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Medicine Inventory</h2>
                    <a href="add_medicine.php" class="btn btn-primary btn-add">
                        <i class="fas fa-plus-circle me-2"></i>Add New Medicine
                    </a>
                </div>

                <?php if (empty($medicines)): ?>
                    <div class="empty-state fade-in">
                        <i class="fas fa-pills"></i>
                        <h4>No Medicines Added Yet</h4>
                        <p>Click the button above to add your first medicine to the inventory.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive fade-in">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Manufacturer</th>
                                    <th>Expiry Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medicines as $medicine): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                                        <td>₹<?php echo number_format($medicine['price'], 2); ?></td>
                                        <td><?php echo $medicine['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($medicine['manufacturer']); ?></td>
                                        <td><?php echo $medicine['expiry_date']; ?></td>
                                        <td>
                                            <a href="edit_medicine.php?id=<?php echo $medicine['id']; ?>" class="btn btn-sm btn-action btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this medicine?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $medicine['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-action btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 