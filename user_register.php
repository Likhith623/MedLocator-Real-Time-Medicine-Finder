<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $phone, $address, $latitude, $longitude]);
        $_SESSION['message'] = "Registration successful! Please login.";
        header("Location: user_login.php");
        exit();
    } catch(PDOException $e) {
        $error = "Registration failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - MedZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .btn-primary {
            padding: 10px 20px;
            border-radius: 25px;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
        }
        .location-status {
            font-size: 14px;
            margin-top: 5px;
        }
        .location-status.success {
            color: #198754;
        }
        .location-status.error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="text-center mb-4">
                    <h1 class="logo">MedZone</h1>
                    <p class="text-muted">Your trusted medicine delivery platform</p>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Create Account</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="registrationForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                                <div id="locationStatus" class="location-status"></div>
                            </div>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Create Account</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="user_login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const locationStatus = document.getElementById('locationStatus');
        const submitBtn = document.getElementById('submitBtn');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const addressInput = document.getElementById('address');

        // Function to get address from coordinates using reverse geocoding
        async function getAddressFromCoordinates(latitude, longitude) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
                const data = await response.json();
                return data.display_name;
            } catch (error) {
                console.error('Error getting address:', error);
                return null;
            }
        }

        // Function to get current location
        function getCurrentLocation() {
            locationStatus.textContent = 'Getting your location...';
            locationStatus.className = 'location-status';

            if (!navigator.geolocation) {
                locationStatus.textContent = 'Geolocation is not supported by your browser';
                locationStatus.className = 'location-status error';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    latitudeInput.value = latitude;
                    longitudeInput.value = longitude;

                    // Get address from coordinates
                    const address = await getAddressFromCoordinates(latitude, longitude);
                    if (address) {
                        addressInput.value = address;
                        locationStatus.textContent = 'Location captured successfully!';
                        locationStatus.className = 'location-status success';
                        submitBtn.disabled = false;
                    } else {
                        locationStatus.textContent = 'Could not get address from coordinates. Please enter manually.';
                        locationStatus.className = 'location-status error';
                        submitBtn.disabled = false;
                    }
                },
                (error) => {
                    let errorMessage = 'Error getting your location. ';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Please allow location access to continue.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                    }
                    locationStatus.textContent = errorMessage;
                    locationStatus.className = 'location-status error';
                    submitBtn.disabled = false;
                }
            );
        }

        // Get location when page loads
        getCurrentLocation();

        // Add retry button if location capture fails
        locationStatus.addEventListener('click', function(e) {
            if (e.target.classList.contains('error')) {
                getCurrentLocation();
            }
        });
    </script>
</body>
</html> 