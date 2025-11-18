<?php
include 'dbForm.php';
require_once 'sms_queue_helpers.php';

if (!isset($_POST['vacc_id'], $_POST['status'])) {
    echo "Missing parameters";
    exit;
}

$vacc_id = intval($_POST['vacc_id']);
$status = $_POST['status'] === 'Completed' ? 'Completed' : 'Pending';

// 1️⃣ Fetch existing schedule info first
$get_info = $con->prepare("SELECT status, infant_id, vaccine_name, stage, barangay FROM tbl_vaccination_schedule WHERE vacc_id=? LIMIT 1");
$get_info->bind_param("i", $vacc_id);
$get_info->execute();
$info = $get_info->get_result()->fetch_assoc();

if (!$info) {
    echo "Schedule record not found";
    exit;
}

$currentStatus = $info['status'];

// Prevent reverting completed schedules back to pending
if ($currentStatus === 'Completed' && $status !== 'Completed') {
    echo "Completed schedules cannot revert to pending";
    exit;
}

// Skip unnecessary update when status is unchanged
if ($currentStatus !== $status) {
    $update_sched = $con->prepare("UPDATE tbl_vaccination_schedule SET status=? WHERE vacc_id=?");
    $update_sched->bind_param("si", $status, $vacc_id);
    $update_sched->execute();
    $update_sched->close();
}

// 2️⃣ Fetch infant_id, vaccine_name, stage
$infant_id = $info['infant_id'];
$vaccine_name = $info['vaccine_name'];
$stage = $info['stage'];
$barangay = $info['barangay'] ?? '';

// 3️⃣ If stage is NULL or empty, try to get it from vaccine reference
if (empty($stage)) {
    $ref_query = $con->prepare("SELECT age_stage FROM tbl_vaccine_reference WHERE vaccine_name = ? LIMIT 1");
    $ref_query->bind_param("s", $vaccine_name);
    $ref_query->execute();
    $ref_result = $ref_query->get_result()->fetch_assoc();
    $stage = $ref_result['age_stage'] ?? 'Unknown';
}

// 4️⃣ Sync with vaccination_details - try multiple approaches
$exists = false;
$detail_id = null;

// First, try exact match
if ($stage !== 'Unknown') {
    $check = $con->prepare("SELECT id FROM tbl_vaccination_details WHERE infant_id=? AND vaccine_name=? AND stage=? LIMIT 1");
    $check->bind_param("iss", $infant_id, $vaccine_name, $stage);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    if ($result) {
        $exists = true;
        $detail_id = $result['id'];
    }
}

// If not found, try without stage constraint
if (!$exists) {
    $check2 = $con->prepare("SELECT id FROM tbl_vaccination_details WHERE infant_id=? AND vaccine_name=? LIMIT 1");
    $check2->bind_param("is", $infant_id, $vaccine_name);
    $check2->execute();
    $result2 = $check2->get_result()->fetch_assoc();
    if ($result2) {
        $exists = true;
        $detail_id = $result2['id'];
    }
}

if ($exists && $detail_id) {
    // Update existing record
    $update_det = $con->prepare("UPDATE tbl_vaccination_details SET status=?, updated_at=NOW() WHERE id=?");
    $update_det->bind_param("si", $status, $detail_id);
    $update_det->execute();
    $update_det->close();
} else {
    // Insert new record
    $insert_det = $con->prepare("INSERT INTO tbl_vaccination_details (infant_id,vaccine_name,stage,status) VALUES (?,?,?,?)");
    $insert_det->bind_param("isss", $infant_id, $vaccine_name, $stage, $status);
    $insert_det->execute();
}

if ($status === 'Completed') {
    removeFromSmsQueue($con, $vacc_id);
} else {
    syncSmsQueue($con, $vacc_id, $infant_id, null, $barangay);
}

echo "ok";
