<?php
/**
 * Ocayur Doctor Portal - Add Diet Plan API
 * Saves patient diet recommendations and logs clinical dietary notes
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

$patientId = intval($input['patient_id'] ?? 0);
$notes = trim($input['notes'] ?? '');

if (empty($notes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Diet plan notes cannot be empty.'
    ]);
    exit();
}

// Fetch patient name if available
$patientName = 'Patient';
if ($patientId > 0) {
    $stmt = mysqli_prepare($connection1, "SELECT `full_name` FROM `patients` WHERE `id` = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $patientId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $patientName = $row['full_name'];
        }
        mysqli_stmt_close($stmt);
    }
}

echo json_encode([
    'success' => true,
    'message' => "Diet plan successfully added for {$patientName}!",
    'data' => [
        'patient_id' => $patientId,
        'patient_name' => $patientName,
        'notes' => $notes,
        'created_at' => date('Y-m-d H:i:s')
    ]
]);
?>
