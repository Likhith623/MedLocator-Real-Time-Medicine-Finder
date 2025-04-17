<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT c.*, m.name, m.price, m.image_url, r.name as retailer_name
    FROM cart_items c
    JOIN medicines m ON c.medicine_id = m.id
    JOIN retailers r ON m.retailer_id = r.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MedZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .empty-cart-icon {
            font-size: 5rem;
            color: #e9ecef;
            margin-bottom: 1.5rem;
        }
        .cart-summary {
            position: sticky;
            top: 20px;
        }
        .cart-item {
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            transform: translateY(-5px);
        }
        .order-placed {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .order-placed:hover {
            transform: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">MedZone</a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light">Continue Shopping</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Shopping Cart</h2>
        
        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5 fade-in">
                <i class="bi bi-cart3 empty-cart-icon"></i>
                <h3 class="mt-3">Your cart is empty</h3>
                <p class="text-muted">Add some medicines to your cart</p>
                <a href="index.php" class="btn btn-primary mt-3">Browse Medicines</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="card cart-item fade-in" id="cart-item-<?php echo $item['id']; ?>">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <img src="<?php echo $item['image_url'] ?? 'https://via.placeholder.com/100x100?text=Medicine'; ?>" 
                                             class="medicine-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="col">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-muted mb-0">Sold by: <?php echo htmlspecialchars($item['retailer_name']); ?></p>
                                        <p class="h5 mb-0 mt-2">₹<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <div class="col-auto">
                                        <div class="quantity-control d-flex align-items-center">
                                            <button class="btn btn-outline-primary quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">-</button>
                                            <input type="number" class="form-control mx-2 text-center" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="10" onchange="updateQuantity(<?php echo $item['id']; ?>, 'set', this.value)">
                                            <button class="btn btn-outline-primary quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">+</button>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-danger" onclick="removeItem(<?php echo $item['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card cart-summary">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span id="subtotal">₹<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee</span>
                                <span>₹40.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total</strong>
                                <strong id="total">₹<?php echo number_format($total + 40, 2); ?></strong>
                            </div>
                            <button class="btn btn-primary w-100" id="checkout-btn" onclick="placeOrder()">Proceed to Checkout</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast for notifications -->
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">MedZone</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize toast
        const toast = new bootstrap.Toast(document.querySelector('.toast'));
        
        function updateQuantity(itemId, action, value = null) {
            let quantity;
            const input = document.querySelector(`#cart-item-${itemId} input`);
            
            if (action === 'increase') {
                quantity = parseInt(input.value) + 1;
            } else if (action === 'decrease') {
                quantity = parseInt(input.value) - 1;
            } else {
                quantity = parseInt(value);
            }
            
            if (quantity < 1) quantity = 1;
            if (quantity > 10) quantity = 10;
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&item_id=${itemId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = quantity;
                    updateTotals();
                } else {
                    document.querySelector('.toast-body').textContent = data.error;
                    toast.show();
                }
            });
        }

        function removeItem(itemId) {
            if (confirm('Are you sure you want to remove this item?')) {
                fetch('cart_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&item_id=${itemId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = document.querySelector(`#cart-item-${itemId}`);
                        item.remove();
                        updateTotals();
                        
                        // Check if cart is empty
                        if (document.querySelectorAll('.cart-item').length === 0) {
                            location.reload();
                        }
                    } else {
                        document.querySelector('.toast-body').textContent = data.error;
                        toast.show();
                    }
                });
            }
        }

        function updateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.cart-item').forEach(item => {
                const price = parseFloat(item.querySelector('.h5').textContent.replace('₹', ''));
                const quantity = parseInt(item.querySelector('input').value);
                subtotal += price * quantity;
            });
            
            document.getElementById('subtotal').textContent = `₹${subtotal.toFixed(2)}`;
            document.getElementById('total').textContent = `₹${(subtotal + 40).toFixed(2)}`;
        }
        
        function placeOrder() {
            // Display the order placed message
            document.querySelector('.toast-body').textContent = 'Your order is placed!';
            toast.show();
            
            // Disable the button after clicking
            const checkoutBtn = document.getElementById('checkout-btn');
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'Order Placed';
            checkoutBtn.classList.add('order-placed');
            
            // Clear the cart by sending a request to cart_actions.php
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear_cart'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show empty cart
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    document.querySelector('.toast-body').textContent = 'Error clearing cart: ' + data.error;
                    toast.show();
                }
            });
        }
    </script>
</body>
</html> 