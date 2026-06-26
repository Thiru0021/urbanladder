<?php
// admin/products/get_subcategories.php
require_once '../../config/db.php';

if (isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
    
    // Fetch only subcategories attached to the selected parent category
    $query = "SELECT id, name, sub_id FROM subcategories WHERE category_id = $category_id AND status = 1 ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    
    $subcategories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $subcategories[] = $row;
    }
    
    // Return data back as a JSON string array matrix
    header('Content-Type: application/json');
    echo json_encode($subcategories);
    exit;
}