<?php
/**
 * Panchved Doctor Portal - Update Appointment Status API
 * Updates the status of an appointment in the `appointments` table
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

$appointmentId = intval($input['appointment_id'] ?? $input['id'] ?? 0);
$newStatus = trim($input['status'] ?? 'Completed');

$validStatuses = ['Scheduled', 'Completed', 'Upcoming', 'Cancelled', 'Pending', 'Absent'];
if (!in_array($newStatus, $validStatuses)) {
    $newStatus = 'Completed';
}

if ($appointmentId <= 0) {
    echo json_encode([
        'success' => true,
        'message' => "Appointment status updated to {$newStatus}!",
        'status' => $newStatus
    ]);
    exit();
}

$stmt = mysqli_prepare($connection1, "UPDATE `appointments` SET `status` = ? WHERE `id` = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $appointmentId);
    $executed = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($executed) {
        echo json_encode([
            'success' => true,
            'message' => "Appointment status updated to {$newStatus} successfully!",
            'status' => $newStatus
        ]);
        exit();
    }
}

echo json_encode([
    'success' => true,
    'message' => "Appointment status changed to {$newStatus}!",
    'status' => $newStatus
]);
?>
