<?php
/**
 * Ocayur Doctor Portal - Get Protocol Details API
 * Fetches specific protocol guidelines based on schema.sql
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

$packageId = intval($_GET['id'] ?? 0);
$packageCode = trim($_GET['package'] ?? $_GET['package_id'] ?? '');

$package = null;
if ($packageId > 0) {
    $stmt = mysqli_prepare($connection1, "SELECT * FROM `packages` WHERE `id` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $packageId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $package = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
} elseif (!empty($packageCode)) {
    // Try matching package_id or package_name or slug
    $stmt = mysqli_prepare($connection1, "SELECT * FROM `packages` WHERE `package_id` = ? OR `package_name` LIKE ? LIMIT 1");
    $likeCode = "%" . str_replace('-', ' ', $packageCode) . "%";
    mysqli_stmt_bind_param($stmt, "ss", $packageCode, $likeCode);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $package = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if (!$package) {
    // Default fallback
    $res = mysqli_query($connection1, "SELECT * FROM `packages` WHERE `id` = 1 LIMIT 1");
    $package = mysqli_fetch_assoc($res);
}

// Fallback protocol guidelines matching Image 2
$protocolName = $package['package_name'] ?? 'Stress & Sleep Balance Protocol';
$dietHydration = $package['diet_hydration'] ?: 'Personalized dietary recommendations, food preferences, and daily water intake guidelines based on the patient\'s assessment.';
$yogaPhysio = $package['yoga_physio'] ?: 'Prescribed yoga, breathing exercises, and physiotherapy practices to support relaxation and physical wellbeing.';
$ayurvedaDincharya = $package['ayurveda_dinacharya'] ?: "A customized daily wellness schedule comprising diet, hydration, physical activity, and Ayurvedic practices as per the doctor's protocol.\nMorning Ritual: Detox drink, Abhyanga, or Nasya, as prescribed.\nNight Ritual: Abhyanga, Padabhyanga, music therapy, meditation, or herbal night tea, as recommended.";
$dailyActivity = $package['daily_activity'] ?: 'Personalized step goals, physical activity recommendations, and sleep routine guidelines.';
$patientMonitoring = $package['patient_monitoring'] ?: 'Regular tracking of weight, abdomen girth, blood pressure, and blood sugar levels to assess patient progress.';
$followupReview = $package['followup_review'] ?: 'Scheduled consultations to evaluate patient response, review monitoring records, and modify the protocol based on clinical assessment.';

echo json_encode([
    'success' => true,
    'protocol' => [
        'id' => intval($package['id'] ?? 1),
        'package_id' => $package['package_id'] ?? 'PKG-001',
        'package_name' => $protocolName,
        'duration' => $package['duration'] ?? '3 Months',
        'price' => floatval($package['price'] ?? 7999.00),
        'enrollments' => intval($package['enrollments'] ?? 18),
        'short_description' => $package['short_description'] ?? '',
        'diet_hydration' => $dietHydration,
        'yoga_physio' => $yogaPhysio,
        'ayurveda_dinacharya' => $ayurvedaDincharya,
        'daily_activity' => $dailyActivity,
        'patient_monitoring' => $patientMonitoring,
        'followup_review' => $followupReview
    ]
]);
?>
