<?php
require_once 'config/database.php';
session_start();

// Check if retailer is logged in
if (!isset($_SESSION['retailer_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $manufacturer = $_POST['manufacturer'];
    $expiry_date = $_POST['expiry_date'];
    $retailer_id = $_SESSION['retailer_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO medicines (retailer_id, name, description, price, quantity, manufacturer, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$retailer_id, $name, $description, $price, $quantity, $manufacturer, $expiry_date]);
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to add medicine. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Medicine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .add-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 2rem 0;
        }
        .add-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .add-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .add-header h3 {
            margin: 0;
            font-weight: 600;
        }
        .add-body {
            padding: 2rem;
            background: white;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
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
        .btn-cancel {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2);
            background-color: #5a6268;
            border-color: #545b62;
        }
        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Medical Inventory</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link back-link" href="dashboard.php">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="add-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="add-card fade-in">
                        <div class="add-header">
                            <h3>Add New Medicine</h3>
                        </div>
                        <div class="add-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger fade-in"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label for="name" class="form-label">Medicine Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-4">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-4">
                                    <label for="price" class="form-label">Price (₹)</label>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                                </div>
                                <div class="mb-4">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                                </div>
                                <div class="mb-4">
                                    <label for="manufacturer" class="form-label">Manufacturer</label>
                                    <input type="text" class="form-control" id="manufacturer" name="manufacturer" required>
                                </div>
                                <div class="mb-4">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                                </div>
                                <div class="d-grid gap-3">
                                    <button type="submit" class="btn btn-primary btn-add">
                                        <i class="fas fa-plus me-2"></i>Add Medicine
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary btn-cancel">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 