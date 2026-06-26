<?php
// toggle_wishlist.php
session_start();
require_once 'config/db.php';

// Check if a customer session is active
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'not_logged_in', 'message' => 'Please login first']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    // Check if the item is already inside the user's wishlist
    $check_query = "SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
    $check_result = mysqli_query($conn, $check_query);
    
    header('Content-Type: application/json');
    
    if (mysqli_num_rows($check_result) > 0) {
        // If it exists, remove it
        $delete_query = "DELETE FROM wishlist WHERE user_id = $user_id AND product_id = $product_id";
        if (mysqli_query($conn, $delete_query)) {
            echo json_encode(['status' => 'removed']);
        }
    } else {
        // If it doesn't exist, insert it
        $insert_query = "INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)";
        if (mysqli_query($conn, $insert_query)) {
            echo json_encode(['status' => 'added']);
        }
    }
    exit;
}