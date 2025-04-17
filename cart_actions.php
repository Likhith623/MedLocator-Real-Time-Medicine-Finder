<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $medicine_id = $_POST['medicine_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            try {
                // Check if item already exists in cart
                $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE user_id = ? AND medicine_id = ?");
                $stmt->execute([$user_id, $medicine_id]);
                $existing_item = $stmt->fetch();
                
                if ($existing_item) {
                    // Update quantity
                    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
                    $stmt->execute([$quantity, $existing_item['id']]);
                } else {
                    // Add new item
                    $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, medicine_id, quantity) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $medicine_id, $quantity]);
                }
                
                $response['success'] = true;
                $response['message'] = 'Item added to cart';
            } catch(PDOException $e) {
                $response['error'] = 'Failed to add item to cart';
            }
            break;
            
        case 'update':
            $item_id = $_POST['item_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            try {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $item_id, $user_id]);
                $response['success'] = true;
                $response['message'] = 'Cart updated';
            } catch(PDOException $e) {
                $response['error'] = 'Failed to update cart';
            }
            break;
            
        case 'remove':
            $item_id = $_POST['item_id'] ?? 0;
            
            try {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
                $stmt->execute([$item_id, $user_id]);
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
            } catch(PDOException $e) {
                $response['error'] = 'Failed to remove item';
            }
            break;
            
        case 'count':
            try {
                $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch();
                $response['success'] = true;
                $response['count'] = $result['count'] ?? 0;
            } catch(PDOException $e) {
                $response['error'] = 'Failed to get cart count';
            }
            break;
            
        case 'clear_cart':
            try {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $response['success'] = true;
                $response['message'] = 'Cart cleared successfully';
            } catch(PDOException $e) {
                $response['error'] = 'Failed to clear cart';
            }
            break;
            
        default:
            $response['error'] = 'Invalid action';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?> 