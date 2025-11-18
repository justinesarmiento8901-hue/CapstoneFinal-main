<?php
session_start();

include 'dbForm.php';
require_once __DIR__ . '/lib/GrowthHelpers.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => 'Invalid request.'
];

$infantId = isset($_GET['infant_id']) ? (int) $_GET['infant_id'] : 0;

if ($infantId <= 0) {
    $response['message'] = 'Invalid infant identifier.';
    echo json_encode($response);
    exit;
}

$infantStmt = $con->prepare('SELECT id, sex, dateofbirth, weight, height FROM infantinfo WHERE id = ? LIMIT 1');
if (!$infantStmt) {
    $response['message'] = 'Failed to prepare infant query.';
    echo json_encode($response);
    exit;
}

$healthWorkerFullName = '';
$userSession = $_SESSION['user'] ?? null;
if ($userSession && ($userSession['role'] ?? '') === 'healthworker') {
    $healthWorkerUserId = (int) ($userSession['id'] ?? 0);
    if ($healthWorkerUserId > 0) {
        $hwStmt = $con->prepare('SELECT TRIM(CONCAT_WS(" ", firstname, middlename, lastname)) AS full_name FROM healthworker WHERE user_id = ? LIMIT 1');
        if ($hwStmt) {
            $hwStmt->bind_param('i', $healthWorkerUserId);
            $hwStmt->execute();
            $hwResult = $hwStmt->get_result();
            if ($hwResult && $hwResult->num_rows > 0) {
                $hwRow = $hwResult->fetch_assoc();
                $healthWorkerFullName = $hwRow['full_name'] ?? '';
            }
            $hwStmt->close();
        }
    }
}

$infantStmt->bind_param('i', $infantId);
$infantStmt->execute();
$infantResult = $infantStmt->get_result();

if (!$infantResult || $infantResult->num_rows === 0) {
    $infantStmt->close();
    $response['message'] = 'Infant record not found.';
    echo json_encode($response);
    exit;
}

$infant = $infantResult->fetch_assoc();
$infantStmt->close();

$currentWeight = isset($infant['weight']) ? (float) $infant['weight'] : 0.0;
$currentHeight = isset($infant['height']) ? (float) $infant['height'] : 0.0;
$sexRaw = $infant['sex'] ?? null;
$dobRaw = $infant['dateofbirth'] ?? null;

$sexNormalized = normalizeSex($sexRaw);
$ageInMonths = computeAgeInMonths($dobRaw);

$referenceData = null;
if ($ageInMonths !== null) {
    $referenceData = fetchGrowthReference($ageInMonths, $con, $sexNormalized);
}

$prevStmt = $con->prepare('SELECT previous_weight, previous_height, growth_status FROM infant_previous_records WHERE infant_id = ? ORDER BY id DESC LIMIT 1');
$previousWeight = 0.0;
$previousHeight = 0.0;
$previousGrowthStatus = '';
if ($prevStmt) {
    $prevStmt->bind_param('i', $infantId);
    $prevStmt->execute();
    $prevResult = $prevStmt->get_result();
    if ($prevResult && $prevResult->num_rows > 0) {
        $prevRow = $prevResult->fetch_assoc();
        $previousWeight = isset($prevRow['previous_weight']) ? (float) $prevRow['previous_weight'] : 0.0;
        $previousHeight = isset($prevRow['previous_height']) ? (float) $prevRow['previous_height'] : 0.0;
        $previousGrowthStatus = $prevRow['growth_status'] ?? '';
    }
    $prevStmt->close();
}

$weightMin = $referenceData ? number_format((float) $referenceData['weight_min'], 1) : '--';
$weightMax = $referenceData ? number_format((float) $referenceData['weight_max'], 1) : '--';
$heightMin = $referenceData ? number_format((float) $referenceData['height_min'], 1) : '--';
$heightMax = $referenceData ? number_format((float) $referenceData['height_max'], 1) : '--';

$previousWeightFormatted = formatMeasurement($previousWeight);
$previousHeightFormatted = formatMeasurement($previousHeight);
$currentWeightFormatted = $currentWeight > 0 ? number_format($currentWeight, 1) : '';
$currentHeightFormatted = $currentHeight > 0 ? number_format($currentHeight, 1) : '';

$statusLabel = computeStatus($previousGrowthStatus, $previousWeight, $previousHeight, $currentWeight, $currentHeight);
$classification = '';
if ($ageInMonths !== null && $referenceData) {
    $classification = classifyGrowth($ageInMonths, $currentWeight, $currentHeight, $con, $referenceData, $sexNormalized);
}

$response['success'] = true;
$response['message'] = 'Growth data loaded.';
$response['data'] = [
    'sex' => $sexNormalized ?? '--',
    'age_in_months' => $ageInMonths,
    'weight_min' => $weightMin,
    'weight_max' => $weightMax,
    'height_min' => $heightMin,
    'height_max' => $heightMax,
    'previous_weight' => $previousWeightFormatted,
    'previous_height' => $previousHeightFormatted,
    'current_weight' => $currentWeightFormatted,
    'current_height' => $currentHeightFormatted,
    'status_label' => $statusLabel,
    'classification' => $classification,
    'vaccinated_by' => $healthWorkerFullName
];

echo json_encode($response);
exit;
