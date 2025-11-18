<?php
include 'dbForm.php';
require_once 'sms_queue_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Invalid request';
    exit;
}

$vacc_id = isset($_POST['vacc_id']) && $_POST['vacc_id'] !== '' ? intval($_POST['vacc_id']) : 0;
$infant_id = isset($_POST['infant_id']) ? intval($_POST['infant_id']) : 0;
$vaccine_name = isset($_POST['vaccine_name']) ? trim($_POST['vaccine_name']) : '';
$date_vaccination = isset($_POST['date_vaccination']) ? $_POST['date_vaccination'] : null;
$next_dose_date = isset($_POST['next_dose_date']) && $_POST['next_dose_date'] !== '' ? $_POST['next_dose_date'] : null;
$time = isset($_POST['time']) && $_POST['time'] !== '' ? $_POST['time'] : null;
$statusInput = isset($_POST['status']) ? trim($_POST['status']) : 'Pending';
$normalizedStatus = ($statusInput === 'Completed') ? 'Completed' : 'Pending';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
$parent_phone = isset($_POST['parent_phone']) ? trim($_POST['parent_phone']) : '';
$barangay = isset($_POST['barangay']) ? trim($_POST['barangay']) : '';

ensureSmsQueueTable($con);
ensureParentBarangayColumn($con);
ensureScheduleBarangayColumn($con);

if ($infant_id <= 0 || $vaccine_name === '' || !$date_vaccination || !$time || $barangay === '') {
    echo 'Missing required fields';
    exit;
}

if ($vacc_id === 0) {
    $status = 'Pending';
    // INSERT new vaccination schedule
    $stmt = mysqli_prepare($con, "INSERT INTO tbl_vaccination_schedule 
        (infant_id, vaccine_name, date_vaccination, next_dose_date, time, status, remarks, barangay) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isssssss", $infant_id, $vaccine_name, $date_vaccination, $next_dose_date, $time, $status, $remarks, $barangay);

    $executed = mysqli_stmt_execute($stmt);
    if ($executed) {
        $newId = mysqli_insert_id($con);
        if ($status === 'Completed') {
            removeFromSmsQueue($con, $newId);
        } else {
            syncSmsQueue($con, $newId, $infant_id, $parent_phone, $barangay);
        }
        echo 'success_add';
    } else {
        echo 'Insert error: ' . mysqli_error($con);
    }
    mysqli_stmt_close($stmt);
} else {
    $status = $normalizedStatus;
    // UPDATE existing vaccination record
    $stmt = mysqli_prepare($con, "UPDATE tbl_vaccination_schedule 
        SET infant_id=?, vaccine_name=?, date_vaccination=?, next_dose_date=?, time=?, status=?, remarks=?, barangay=? 
        WHERE vacc_id=?");
    mysqli_stmt_bind_param($stmt, "isssssssi", $infant_id, $vaccine_name, $date_vaccination, $next_dose_date, $time, $status, $remarks, $barangay, $vacc_id);

    $executed = mysqli_stmt_execute($stmt);
    if ($executed) {
        if ($status === 'Completed') {
            removeFromSmsQueue($con, $vacc_id);
        } else {
            syncSmsQueue($con, $vacc_id, $infant_id, $parent_phone, $barangay);
        }
        echo 'success_update';
    } else {
        echo 'Update error: ' . mysqli_error($con);
    }
    mysqli_stmt_close($stmt);
}

// 🔁 Sync with vaccination_details table
// Get the stage from vaccine reference if available
$stage = 'Unknown';
$ref_query = mysqli_prepare($con, "SELECT age_stage FROM tbl_vaccine_reference WHERE vaccine_name = ? LIMIT 1");
mysqli_stmt_bind_param($ref_query, "s", $vaccine_name);
mysqli_stmt_execute($ref_query);
$ref_result = mysqli_stmt_get_result($ref_query);
if ($ref_row = mysqli_fetch_assoc($ref_result)) {
    $stage = $ref_row['age_stage'];
}
mysqli_stmt_close($ref_query);

// Check if record exists in tbl_vaccination_details
$check = mysqli_prepare($con, "SELECT id FROM tbl_vaccination_details WHERE infant_id=? AND vaccine_name=? AND stage=? LIMIT 1");
mysqli_stmt_bind_param($check, "iss", $infant_id, $vaccine_name, $stage);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$exists = mysqli_fetch_assoc($result);
mysqli_stmt_close($check);

if ($exists) {
    // Update existing record
    $update_det = mysqli_prepare($con, "UPDATE tbl_vaccination_details SET status=?, updated_at=NOW() WHERE id=?");
    mysqli_stmt_bind_param($update_det, "si", $status, $exists['id']);
    mysqli_stmt_execute($update_det);
    mysqli_stmt_close($update_det);
} else {
    // Insert new record
    $insert_det = mysqli_prepare($con, "INSERT INTO tbl_vaccination_details (infant_id,vaccine_name,stage,status) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($insert_det, "isss", $infant_id, $vaccine_name, $stage, $status);
    mysqli_stmt_execute($insert_det);
    mysqli_stmt_close($insert_det);
}
