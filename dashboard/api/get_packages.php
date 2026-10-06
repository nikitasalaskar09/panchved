<?php
/**
 * Panchved Doctor Portal - Get Packages API
 * Fetches all packages/protocols with live search and filtering based on schema.sql
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$query = "SELECT * FROM `packages` WHERE `status` = 'Active'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (`package_name` LIKE ? OR `short_description` LIKE ? OR `category` LIKE ?)";
    $likeSearch = "%" . $search . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= "sss";
}

if (!empty($category) && $category !== 'All') {
    $query .= " AND `category` = ?";
    $params[] = $category;
    $types .= "s";
}

$query .= " ORDER BY `package_id` ASC, `id` ASC";

$stmt = mysqli_prepare($connection1, $query);
if (!empty($types) && $stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($connection1, $query);
}

$packages = [];
while ($row = mysqli_fetch_assoc($res)) {
    $priceVal = number_format(floatval($row['price']), 0, '', '');
    $packages[] = [
        'id' => intval($row['id']),
        'package_id' => $row['package_id'] ?: ('PKG-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT)),
        'package_name' => $row['package_name'],
        'short_description' => $row['short_description'] ?: 'Complete holistic Ayurvedic rehabilitation program.',
        'category' => $row['category'] ?: 'General Wellness',
        'duration' => $row['duration'] ?: '3 Months',
        'price' => floatval($row['price']),
        'price_display' => "Starting at ₹{$priceVal} / month",
        'enrollments' => intval($row['enrollments'] ?: 18),
        'batch_display' => intval($row['enrollments'] ?: 18) . ' Patient Batch',
        'protocol_status' => $row['protocol_status'] ?: 'Added',
        'status' => $row['status'] ?: 'Active'
    ];
}

if ($stmt) {
    mysqli_stmt_close($stmt);
}

echo json_encode([
    'success' => true,
    'count' => count($packages),
    'packages' => $packages
]);
?>
