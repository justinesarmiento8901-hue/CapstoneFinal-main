<?php
session_start();

include 'dbForm.php';
require_once 'sms_queue_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => 'Invalid request.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

$vaccId = isset($_POST['vacc_id']) ? (int) $_POST['vacc_id'] : 0;
$infantId = isset($_POST['infant_id']) ? (int) $_POST['infant_id'] : 0;
$currentWeightRaw = isset($_POST['current_weight']) ? trim($_POST['current_weight']) : '';
$currentHeightRaw = isset($_POST['current_height']) ? trim($_POST['current_height']) : '';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

if ($vaccId <= 0 || $infantId <= 0) {
    $response['message'] = 'Invalid identifiers provided.';
    echo json_encode($response);
    exit;
}

$decimalPattern = '/^\d+(\.\d)?$/';

if ($currentWeightRaw === '' || !preg_match($decimalPattern, $currentWeightRaw)) {
    $response['message'] = 'Please enter a valid current weight with one decimal place (e.g., 23.3).';
    echo json_encode($response);
    exit;
}

if ($currentHeightRaw === '' || !preg_match($decimalPattern, $currentHeightRaw)) {
    $response['message'] = 'Please enter a valid current height with one decimal place (e.g., 75.4).';
    echo json_encode($response);
    exit;
}

$currentWeight = round((float) $currentWeightRaw, 1);
$currentHeight = round((float) $currentHeightRaw, 1);

if ($currentWeight < 0 || $currentHeight < 0) {
    $response['message'] = 'Measurements must be positive numbers.';
    echo json_encode($response);
    exit;
}

$remarks = mb_substr($remarks, 0, 255);

$vaccinatedByName = '';
$userSession = $_SESSION['user'] ?? null;
if ($userSession) {
    $userRole = $userSession['role'] ?? '';
    if ($userRole === 'healthworker') {
        $healthWorkerUserId = (int) ($userSession['id'] ?? 0);
        if ($healthWorkerUserId > 0) {
            $hwStmt = $con->prepare('SELECT TRIM(CONCAT_WS(" ", firstname, middlename, lastname)) AS full_name FROM healthworker WHERE user_id = ? LIMIT 1');
            if ($hwStmt) {
                $hwStmt->bind_param('i', $healthWorkerUserId);
                $hwStmt->execute();
                $hwResult = $hwStmt->get_result();
                if ($hwResult && $hwResult->num_rows > 0) {
                    $hwRow = $hwResult->fetch_assoc();
                    $vaccinatedByName = $hwRow['full_name'] ?? '';
                }
                $hwStmt->close();
            }
        }
    } elseif ($userRole === 'admin') {
        $adminStmt = $con->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
        if ($adminStmt) {
            $adminUserId = (int) ($userSession['id'] ?? 0);
            $adminStmt->bind_param('i', $adminUserId);
            $adminStmt->execute();
            $adminResult = $adminStmt->get_result();
            if ($adminResult && $adminResult->num_rows > 0) {
                $adminRow = $adminResult->fetch_assoc();
                $vaccinatedByName = $adminRow['name'] ?? '';
            }
            $adminStmt->close();
        }
    }
}
$vaccinatedByName = trim($vaccinatedByName);

$infantStmt = $con->prepare('SELECT weight, height FROM infantinfo WHERE id = ? LIMIT 1');
if (!$infantStmt) {
    $response['message'] = 'Failed to prepare query for infant data.';
    echo json_encode($response);
    exit;
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

$infantRow = $infantResult->fetch_assoc();
$infantStmt->close();

$previousWeight = isset($infantRow['weight']) ? (float) $infantRow['weight'] : 0.0;
$previousHeight = isset($infantRow['height']) ? (float) $infantRow['height'] : 0.0;

$insertStmt = $con->prepare('INSERT INTO infant_previous_records (infant_id, previous_weight, previous_height, remarks, growth_status) VALUES (?, ?, ?, ?, ?)');
if (!$insertStmt) {
    $response['message'] = 'Failed to prepare query for saving growth history.';
    echo json_encode($response);
    exit;
}

$growthStatus = 'Maintained';
$weightDiff = $currentWeight - $previousWeight;
$heightDiff = $currentHeight - $previousHeight;

if ($weightDiff > 0.0 || $heightDiff > 0.0) {
    $growthStatus = 'Improving';
} elseif ($weightDiff < 0.0 || $heightDiff < 0.0) {
    $growthStatus = 'Needs Attention';
}

$insertStmt->bind_param('iddss', $infantId, $previousWeight, $previousHeight, $remarks, $growthStatus);

if (!$insertStmt->execute()) {
    $response['message'] = 'Failed to save growth history.';
    echo json_encode($response);
    exit;
}

$insertStmt->close();

$updateInfantStmt = $con->prepare('UPDATE infantinfo SET weight = ?, height = ? WHERE id = ?');
if ($updateInfantStmt) {
    $updateInfantStmt->bind_param('ddi', $currentWeight, $currentHeight, $infantId);
    $updateInfantStmt->execute();
    $updateInfantStmt->close();
}

$status = 'Completed';
if ($vaccinatedByName !== '') {
    $remarksUpdateStmt = $con->prepare('UPDATE tbl_vaccination_schedule SET status = ?, remarks = ?, vaccinatedby = ? WHERE vacc_id = ?');
    if ($remarksUpdateStmt) {
        $remarksUpdateStmt->bind_param('sssi', $status, $remarks, $vaccinatedByName, $vaccId);
        $remarksUpdateStmt->execute();
        $remarksUpdateStmt->close();
    }
} else {
    $remarksUpdateStmt = $con->prepare('UPDATE tbl_vaccination_schedule SET status = ?, remarks = ?, vaccinatedby = NULL WHERE vacc_id = ?');
    if ($remarksUpdateStmt) {
        $remarksUpdateStmt->bind_param('ssi', $status, $remarks, $vaccId);
        $remarksUpdateStmt->execute();
        $remarksUpdateStmt->close();
    }
}

$detailsInfoStmt = $con->prepare('SELECT infant_id, vaccine_name, stage FROM tbl_vaccination_schedule WHERE vacc_id = ? LIMIT 1');
$detailInfantId = null;
$detailVaccineName = '';
$detailStage = '';
if ($detailsInfoStmt) {
    $detailsInfoStmt->bind_param('i', $vaccId);
    $detailsInfoStmt->execute();
    $detailsResult = $detailsInfoStmt->get_result();
    if ($detailsResult && ($detailsRow = $detailsResult->fetch_assoc())) {
        $detailInfantId = (int) ($detailsRow['infant_id'] ?? 0);
        $detailVaccineName = $detailsRow['vaccine_name'] ?? '';
        $detailStage = $detailsRow['stage'] ?? '';
    }
    $detailsInfoStmt->close();
}

if (empty($detailStage) && $detailVaccineName !== '') {
    $refStmt = $con->prepare('SELECT age_stage FROM tbl_vaccine_reference WHERE vaccine_name = ? LIMIT 1');
    if ($refStmt) {
        $refStmt->bind_param('s', $detailVaccineName);
        $refStmt->execute();
        $refResult = $refStmt->get_result();
        if ($refResult) {
            $refRow = $refResult->fetch_assoc();
            if ($refRow) {
                $detailStage = $refRow['age_stage'] ?? '';
            }
        }
        $refStmt->close();
    }
}

if ($detailInfantId && $detailVaccineName !== '') {
    $detailExists = false;
    $detailId = null;

    if ($detailStage !== '') {
        $checkStmt = $con->prepare('SELECT id FROM tbl_vaccination_details WHERE infant_id = ? AND vaccine_name = ? AND stage = ? LIMIT 1');
        if ($checkStmt) {
            $checkStmt->bind_param('iss', $detailInfantId, $detailVaccineName, $detailStage);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult && ($checkRow = $checkResult->fetch_assoc())) {
                $detailExists = true;
                $detailId = (int) $checkRow['id'];
            }
            $checkStmt->close();
        }
    }

    if (!$detailExists) {
        $checkAnyStmt = $con->prepare('SELECT id FROM tbl_vaccination_details WHERE infant_id = ? AND vaccine_name = ? LIMIT 1');
        if ($checkAnyStmt) {
            $checkAnyStmt->bind_param('is', $detailInfantId, $detailVaccineName);
            $checkAnyStmt->execute();
            $checkAnyResult = $checkAnyStmt->get_result();
            if ($checkAnyResult && ($checkAnyRow = $checkAnyResult->fetch_assoc())) {
                $detailExists = true;
                $detailId = (int) $checkAnyRow['id'];
            }
            $checkAnyStmt->close();
        }
    }

    if ($detailExists && $detailId) {
        $updateDetailStmt = $con->prepare('UPDATE tbl_vaccination_details SET status = ?, stage = ?, updated_at = NOW() WHERE id = ?');
        if ($updateDetailStmt) {
            $stageValue = $detailStage !== '' ? $detailStage : null;
            $updateDetailStmt->bind_param('ssi', $status, $stageValue, $detailId);
            $updateDetailStmt->execute();
            $updateDetailStmt->close();
        }
    } else {
        $insertDetailStmt = $con->prepare('INSERT INTO tbl_vaccination_details (infant_id, vaccine_name, stage, status, updated_at) VALUES (?, ?, ?, ?, NOW())');
        if ($insertDetailStmt) {
            $stageValue = $detailStage !== '' ? $detailStage : null;
            $insertDetailStmt->bind_param('isss', $detailInfantId, $detailVaccineName, $stageValue, $status);
            $insertDetailStmt->execute();
            $insertDetailStmt->close();
        }
    }
}

removeFromSmsQueue($con, $vaccId);

$response['success'] = true;
$response['message'] = 'Growth data saved and vaccination marked as completed.';
$response['data'] = [
    'status' => $status
];

echo json_encode($response);
exit;
