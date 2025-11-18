<?php
include 'dbForm.php';
require_once 'sms_queue_helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['vacc_id'])) {
    echo 'Invalid request';
    exit;
}
$id = intval($_POST['vacc_id']);

// optional: check privileges / ownership here

$info_stmt = mysqli_prepare($con, "SELECT infant_id, vaccine_name FROM tbl_vaccination_schedule WHERE vacc_id = ? LIMIT 1");
mysqli_stmt_bind_param($info_stmt, "i", $id);
mysqli_stmt_execute($info_stmt);
$info_result = mysqli_stmt_get_result($info_stmt);
$schedule = mysqli_fetch_assoc($info_result);
mysqli_stmt_close($info_stmt);

if (!$schedule) {
    echo 'Record not found';
    exit;
}

$infant_id = intval($schedule['infant_id']);
$vaccine_name = $schedule['vaccine_name'];

$stmt = mysqli_prepare($con, "DELETE FROM tbl_vaccination_schedule WHERE vacc_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
if (mysqli_stmt_execute($stmt)) {
    $stage = 'Unknown';
    $stage_stmt = mysqli_prepare($con, "SELECT age_stage FROM tbl_vaccine_reference WHERE vaccine_name = ? LIMIT 1");
    mysqli_stmt_bind_param($stage_stmt, "s", $vaccine_name);
    mysqli_stmt_execute($stage_stmt);
    $stage_result = mysqli_stmt_get_result($stage_stmt);
    if ($stage_row = mysqli_fetch_assoc($stage_result)) {
        $stage = $stage_row['age_stage'];
    }
    mysqli_stmt_close($stage_stmt);

    $detail_delete = mysqli_prepare($con, "DELETE FROM tbl_vaccination_details WHERE infant_id = ? AND vaccine_name = ? AND stage = ?");
    mysqli_stmt_bind_param($detail_delete, "iss", $infant_id, $vaccine_name, $stage);
    mysqli_stmt_execute($detail_delete);
    mysqli_stmt_close($detail_delete);
    removeFromSmsQueue($con, $id);

    echo 'deleted';
} else {
    echo 'Delete error: ' . mysqli_error($con);
}
