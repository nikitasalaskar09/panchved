<?php
/**
 * Ocayur Doctor Portal - Get Patients API
 * Provides live patient directory, multi-parameter search & filtering, and database pagination
 * Queries the MySQL `patients` table directly based on schema.sql
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

if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= strtoupper($w[0]);
            }
        }
        return substr($initials, 0, 2) ?: 'PT';
    }
}

$search = trim($_GET['search'] ?? '');
$packageFilter = trim($_GET['package'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, intval($_GET['limit'] ?? 5)); // 5 items per page to match the UI screenshot

// Build Dynamic SQL Query & Parameter Bindings
$whereClauses = [];
$params = [];
$types = "";

// 1. Search Query
if (!empty($search)) {
    $whereClauses[] = "(`full_name` LIKE ? OR `patient_id` LIKE ? OR `phone_number` LIKE ? OR `package_name` LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

// 2. Package Filter (supports multiple comma-separated values)
if (!empty($packageFilter)) {
    $packages = array_map('trim', explode(',', $packageFilter));
    $placeholders = implode(',', array_fill(0, count($packages), '?'));
    $whereClauses[] = "`package_name` IN ($placeholders)";
    foreach ($packages as $pkg) {
        $params[] = $pkg;
        $types .= "s";
    }
}

// 3. Status Filter (supports multiple comma-separated values)
if (!empty($statusFilter)) {
    $statuses = array_map('trim', explode(',', $statusFilter));
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $whereClauses[] = "`status` IN ($placeholders)";
    foreach ($statuses as $st) {
        $params[] = $st;
        $types .= "s";
    }
}

$whereSql = "";
if (count($whereClauses) > 0) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

// 4. Count Total Records matching criteria directly in SQL
$countSql = "SELECT COUNT(*) as total FROM `patients` $whereSql";
$countStmt = mysqli_prepare($connection1, $countSql);

$totalItems = 0;
if ($countStmt) {
    if (!empty($types)) {
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
    }
    mysqli_stmt_execute($countStmt);
    $countRes = mysqli_stmt_get_result($countStmt);
    if ($countRow = mysqli_fetch_assoc($countRes)) {
        $totalItems = intval($countRow['total']);
    }
    mysqli_stmt_close($countStmt);
} else {
    // Fallback count query
    $countRes = mysqli_query($connection1, $countSql);
    if ($countRes && $countRow = mysqli_fetch_assoc($countRes)) {
        $totalItems = intval($countRow['total']);
    }
}

// Calculate pagination
$totalPages = max(1, ceil($totalItems / $limit));
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

// 5. Fetch Paginated Records directly from MySQL
$dataSql = "SELECT * FROM `patients` $whereSql ORDER BY `id` ASC LIMIT ? OFFSET ?";
$dataStmt = mysqli_prepare($connection1, $dataSql);

$patients = [];
if ($dataStmt) {
    $dataTypes = $types . "ii";
    $dataParams = array_merge($params, [$limit, $offset]);
    mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
    mysqli_stmt_execute($dataStmt);
    $dataRes = mysqli_stmt_get_result($dataStmt);

    while ($row = mysqli_fetch_assoc($dataRes)) {
        $name = !empty($row['full_name']) ? $row['full_name'] : 'Patient';
        $age = intval($row['age'] ?? 0);
        if ($age <= 0 && !empty($row['dob'])) {
            $birthDate = new DateTime($row['dob']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }

        $pCode = !empty($row['patient_id']) ? $row['patient_id'] : ('P' . str_pad($row['id'], 3, '0', STR_PAD_LEFT));
        $pkg = !empty($row['package_name']) ? $row['package_name'] : '-';
        $status = ucfirst(strtolower($row['status'] ?: 'Ongoing'));

        $patients[] = [
            'id' => intval($row['id']),
            'patient_id' => $pCode,
            'full_name' => $name,
            'initials' => getInitials($name),
            'age' => $age > 0 ? $age : '-',
            'phone_number' => $row['phone_number'] ?? '',
            'email' => $row['email'] ?? '',
            'gender' => $row['gender'] ?: 'Male',
            'package_name' => $pkg,
            'total_appointments' => intval($row['total_appointments'] ?? 0),
            'status' => $status,
            'status_class' => (strtolower($status) === 'completed') ? 'progress-completed' : 'progress-ongoing'
        ];
    }
    mysqli_stmt_close($dataStmt);
}

// Fetch all available packages for filter options strictly from patients table
$pkgQuery = "SELECT DISTINCT `package_name` FROM `patients` WHERE `package_name` IS NOT NULL AND `package_name` != '' ORDER BY `package_name` ASC";
$pkgRes = mysqli_query($connection1, $pkgQuery);
$availablePackages = [];
if ($pkgRes) {
    while ($pRow = mysqli_fetch_assoc($pkgRes)) {
        $pkgName = trim($pRow['package_name']);
        if (!empty($pkgName) && !in_array($pkgName, $availablePackages)) {
            $availablePackages[] = $pkgName;
        }
    }
}

// Fetch all available statuses for filter options strictly from patients table
$statusQuery = "SELECT DISTINCT `status` FROM `patients` WHERE `status` IS NOT NULL AND `status` != '' ORDER BY `status` ASC";
$statusRes = mysqli_query($connection1, $statusQuery);
$availableStatuses = [];
if ($statusRes) {
    while ($sRow = mysqli_fetch_assoc($statusRes)) {
        $stName = ucfirst(strtolower(trim($sRow['status'])));
        if (!empty($stName) && !in_array($stName, $availableStatuses)) {
            $availableStatuses[] = $stName;
        }
    }
}

$startItem = $totalItems > 0 ? ($offset + 1) : 0;
$endItem = min($offset + $limit, $totalItems);

echo json_encode([
    'success' => true,
    'patients' => $patients,
    'available_packages' => $availablePackages,
    'available_statuses' => $availableStatuses,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'items_per_page' => $limit,
        'total_items' => $totalItems,
        'start_item' => $startItem,
        'end_item' => $endItem
    ]
]);
?>

