<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$conn = getDBConnection();
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

if ($category !== '') {
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.current_price, p.original_price,
               p.rating, p.description, p.image, p.is_new,
               c.slug AS category_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE c.slug = ? AND p.is_active = 1
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param('s', $category);
} else {
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.current_price, p.original_price,
               p.rating, p.description, p.image, p.is_new,
               c.slug AS category_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC
    ");
}

$stmt->execute();
$rows = $stmt->get_result();

$products = [];
while ($row = $rows->fetch_assoc()) {
    $products[] = [
        'id'            => (int) $row['id'],
        'src'           => $row['image'],
        'category'      => $row['category_slug'],
        'name'          => $row['name'],
        'currentPrice'  => $row['current_price'],
        'originalPrice' => $row['original_price'],
        'rating'        => (float) $row['rating'],
        'description'   => $row['description'],
        'isNew'         => (bool) $row['is_new'],
    ];
}

$stmt->close();
echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
