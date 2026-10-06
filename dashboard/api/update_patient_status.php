<?php
/**
 * Panchved Doctor Portal - Update Patient Status API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

$patientId = $input['patient_id'] ?? $input['id'] ?? null;
$newStatus = trim($input['status'] ?? 'Ongoing');

$validStatuses = ['Ongoing', 'Completed', 'Active', 'Inactive'];
if (!in_array($newStatus, $validStatuses)) {
    $newStatus = 'Ongoing';
}

if (empty($patientId)) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required.']);
    exit();
}

// Update in `patients` table
$stmt = mysqli_prepare($connection1, "UPDATE `patients` SET `status` = ? WHERE `id` = ? OR `patient_id` = ?");
mysqli_stmt_bind_param($stmt, "sis", $newStatus, $patientId, $patientId);
$executed = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($executed) {
    echo json_encode([
        'success' => true,
        'message' => "Patient status updated to {$newStatus} successfully!",
        'status' => $newStatus
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update patient status: ' . mysqli_error($connection1)
    ]);
}
?>
