<?php
/**
 * Ocayur Doctor Portal - Get Profile API
 * Fetches doctor profile information from the admin portal `doctors` table
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function cleanPhone($phone) {
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) > 10 && (substr($digits, 0, 2) === '91')) {
        $digits = substr($digits, 2);
    }
    return $digits;
}

$doctorId = $_GET['doctor_id'] ?? $_SESSION['doctor_id'] ?? null;
$phone = $_GET['phone'] ?? $_GET['phoneNumber'] ?? null;
$email = $_GET['email'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

$doctor = null;

// Case 1: Search by Doctor ID if known
if (!empty($doctorId)) {
    $stmt = mysqli_prepare($connection1, "SELECT * FROM `doctors` WHERE `id` = ? OR `doctorid` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "is", $doctorId, $doctorId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $doctor = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

// Case 2: Search by User ID session if doctor was not found yet
if (!$doctor && !empty($userId)) {
    $userStmt = mysqli_prepare($connection1, "SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
    mysqli_stmt_bind_param($userStmt, "i", $userId);
    mysqli_stmt_execute($userStmt);
    $userRes = mysqli_stmt_get_result($userStmt);
    $user = mysqli_fetch_assoc($userRes);
    mysqli_stmt_close($userStmt);

    if ($user) {
        $cleanP = cleanPhone($user['phone_number']);
        $docStmt = mysqli_prepare($connection1, "SELECT * FROM `doctors` WHERE `email` = ? OR `phone_number` = ? OR `phone_number` = ? OR `full_name` = ? LIMIT 1");
        mysqli_stmt_bind_param($docStmt, "ssss", $user['email'], $user['phone_number'], $cleanP, $user['full_name']);
        mysqli_stmt_execute($docStmt);
        $docRes = mysqli_stmt_get_result($docStmt);
        $doctor = mysqli_fetch_assoc($docRes);
        mysqli_stmt_close($docStmt);
    }
}

// Case 3: Search by Phone or Email query param
if (!$doctor && (!empty($phone) || !empty($email))) {
    $cleanP = $phone ? cleanPhone($phone) : '';
    $stmt = mysqli_prepare($connection1, "SELECT * FROM `doctors` WHERE (`email` = ? AND ? != '') OR (`phone_number` = ? AND ? != '') OR (`phone_number` = ? AND ? != '') LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ssssss", $email, $email, $phone, $phone, $cleanP, $cleanP);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $doctor = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

// Fallback for demo or development when opened directly without login
if (!$doctor) {
    $res = mysqli_query($connection1, "SELECT * FROM `doctors` WHERE `status` = 'Active' ORDER BY `id` ASC LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $doctor = mysqli_fetch_assoc($res);
    }
}

if (!$doctor) {
    echo json_encode([
        'success' => false,
        'message' => 'No doctor profile found.'
    ]);
    exit();
}

// Format DOB
$dobRaw = $doctor['date_of_birth'] ?? '';
if (!empty($dobRaw) && $dobRaw !== '0000-00-00') {
    $timestamp = strtotime($dobRaw);
    $doctor['date_of_birth_iso'] = date('Y-m-d', $timestamp);
    $doctor['date_of_birth_display'] = date('d/m/Y', $timestamp);
} else {
    $doctor['date_of_birth_iso'] = '1990-01-01';
    $doctor['date_of_birth_display'] = '01/01/1990';
}

// Save to session
$_SESSION['doctor_id'] = $doctor['id'];
$_SESSION['doctor_code'] = $doctor['doctorid'];
$_SESSION['doctor_profile'] = $doctor;

echo json_encode([
    'success' => true,
    'message' => 'Doctor profile retrieved successfully.',
    'doctor' => $doctor
]);
?>
