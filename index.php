<?php
require_once 'config/database.php';
session_start();

// Get user's location if logged in
$user_latitude = null;
$user_longitude = null;
$user_address = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT latitude, longitude, address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_location = $stmt->fetch();
    
    if ($user_location) {
        $user_latitude = $user_location['latitude'];
        $user_longitude = $user_location['longitude'];
        $user_address = $user_location['address'];
    }
}

// Fetch all medicines with retailer information
$query = "
    SELECT m.*, r.name as retailer_name, r.latitude as retailer_latitude, r.longitude as retailer_longitude,
    r.address as retailer_address
    FROM medicines m 
    JOIN retailers r ON m.retailer_id = r.id 
    ORDER BY m.created_at DESC
";

// If user is logged in and has location, add distance calculation
if ($user_latitude && $user_longitude) {
    $query = "
        SELECT m.*, r.name as retailer_name, r.latitude as retailer_latitude, r.longitude as retailer_longitude,
        r.address as retailer_address,
        (
            6371 * acos(
                cos(radians(?)) * cos(radians(r.latitude)) * 
                cos(radians(r.longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(r.latitude))
            )
        ) AS distance
        FROM medicines m 
        JOIN retailers r ON m.retailer_id = r.id 
        ORDER BY distance, m.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_latitude, $user_longitude, $user_latitude]);
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}

$medicines = $stmt->fetchAll();

// Get unique categories
$categories = array_unique(array_column($medicines, 'category'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedZone - Medicine Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar {
            background-color: #0d6efd;
            padding: 15px 0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .search-bar {
            max-width: 500px;
            margin: 0 auto;
        }
        .medicine-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .medicine-card:hover {
            transform: translateY(-5px);
        }
        .medicine-image {
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .category-pill {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 15px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .category-pill:hover, .category-pill.active {
            background-color: #0d6efd;
            color: white;
        }
        .cart-icon {
            position: relative;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .location-badge {
            background-color: #198754;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
        }
        .distance-badge {
            background-color: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .filter-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="logo" href="index.php">MedZone</a>
            <div class="search-bar">
                <input type="text" class="form-control" id="searchInput" placeholder="Search medicines...">
            </div>
            <div class="ms-auto d-flex align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="blockchain_explorer.php">Blockchain Explorer</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="text-light me-3 cart-icon">
                        <i class="bi bi-cart3" style="font-size: 24px;"></i>
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> My Account
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="user_login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="user_register.php" class="btn btn-light">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="filter-section">
            <div class="row">
                <div class="col-md-6">
                    <div class="filter-title">Categories</div>
                    <div class="d-flex flex-wrap">
                        <div class="category-pill active" data-category="all">All</div>
                        <?php foreach ($categories as $category): ?>
                            <?php if (!empty($category)): ?>
                                <div class="category-pill" data-category="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="filter-title">Location</div>
                    <div class="d-flex flex-wrap">
                        <div class="category-pill active" data-location="all">All Locations</div>
                        <div class="category-pill" data-location="nearby">Nearby</div>
                        <div class="category-pill" data-location="city">Same City</div>
                    </div>
                    <?php if ($user_address): ?>
                        <small class="text-muted mt-2 d-block">Your location: <?php echo htmlspecialchars($user_address); ?></small>
                    <?php else: ?>
                        <small class="text-muted mt-2 d-block">Please <a href="user_login.php">login</a> to see location-based results</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="medicineContainer">
            <?php foreach ($medicines as $medicine): ?>
                <div class="col medicine-item" 
                     data-category="<?php echo htmlspecialchars($medicine['category'] ?? ''); ?>"
                     data-distance="<?php echo isset($medicine['distance']) ? round($medicine['distance'], 1) : 999; ?>"
                     data-city="<?php echo explode(',', $medicine['retailer_address'])[0] ?? ''; ?>">
                    <div class="card medicine-card h-100">
                        <img src="<?php echo $medicine['image_url'] ?? 'https://via.placeholder.com/300x200?text=Medicine'; ?>" 
                             class="medicine-image" alt="<?php echo htmlspecialchars($medicine['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($medicine['name']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($medicine['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">₹<?php echo number_format($medicine['price'], 2); ?></span>
                                <button class="btn btn-primary add-to-cart" data-id="<?php echo $medicine['id']; ?>">
                                    Add to Cart
                                </button>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted d-block">
                                    Sold by: <?php echo htmlspecialchars($medicine['retailer_name']); ?>
                                    <?php if (isset($medicine['distance'])): ?>
                                        <span class="distance-badge">
                                            <?php echo round($medicine['distance'], 1); ?> km away
                                        </span>
                                    <?php endif; ?>
                                </small>
                                <small class="text-muted d-block">
                                    <?php echo htmlspecialchars($medicine['retailer_address']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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
        
        // Update cart count
        function updateCartCount() {
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cartCount').textContent = data.count;
                }
            });
        }

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const medicineId = this.getAttribute('data-id');
                
                fetch('cart_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add&medicine_id=${medicineId}&quantity=1`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.toast-body').textContent = 'Medicine added to cart!';
                        toast.show();
                        updateCartCount();
                    } else {
                        document.querySelector('.toast-body').textContent = data.error || 'Failed to add medicine to cart';
                        toast.show();
                    }
                })
                .catch(error => {
                    document.querySelector('.toast-body').textContent = 'An error occurred. Please try again.';
                    toast.show();
                });
            });
        });

        // Update cart count on page load
        updateCartCount();

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const medicineItems = document.querySelectorAll('.medicine-item');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            medicineItems.forEach(item => {
                const medicineName = item.querySelector('.card-title').textContent.toLowerCase();
                const medicineDesc = item.querySelector('.card-text').textContent.toLowerCase();
                const retailerName = item.querySelector('.text-muted').textContent.toLowerCase();
                
                if (medicineName.includes(searchTerm) || 
                    medicineDesc.includes(searchTerm) || 
                    retailerName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Category filter
        document.querySelectorAll('.category-pill[data-category]').forEach(pill => {
            pill.addEventListener('click', function() {
                // Remove active class from all pills
                document.querySelectorAll('.category-pill[data-category]').forEach(p => {
                    p.classList.remove('active');
                });
                
                // Add active class to clicked pill
                this.classList.add('active');
                
                const category = this.getAttribute('data-category');
                
                medicineItems.forEach(item => {
                    if (category === 'all' || item.getAttribute('data-category') === category) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Location filter
        document.querySelectorAll('.category-pill[data-location]').forEach(pill => {
            pill.addEventListener('click', function() {
                // Remove active class from all location pills
                document.querySelectorAll('.category-pill[data-location]').forEach(p => {
                    p.classList.remove('active');
                });
                
                // Add active class to clicked pill
                this.classList.add('active');
                
                const location = this.getAttribute('data-location');
                
                medicineItems.forEach(item => {
                    const distance = parseFloat(item.getAttribute('data-distance'));
                    const city = item.getAttribute('data-city');
                    
                    if (location === 'all') {
                        item.style.display = '';
                    } else if (location === 'nearby' && distance <= 5) {
                        item.style.display = '';
                    } else if (location === 'city' && city) {
                        // Show items from the same city (first part of address)
                        const userCity = '<?php echo $user_address ? explode(',', $user_address)[0] : ''; ?>';
                        if (userCity && city.includes(userCity)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html> 