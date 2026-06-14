<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$conn = getDBConnection();

$result = $conn->query("
    SELECT c.id, c.name, c.slug, c.image,
           COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
    GROUP BY c.id
    ORDER BY c.sort_order, c.name
");

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = [
        'id'           => (int) $row['id'],
        'name'         => $row['name'],
        'category'     => $row['slug'],
        'src'          => $row['image'],
        'productCount' => (int) $row['product_count'],
    ];
}

echo json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
