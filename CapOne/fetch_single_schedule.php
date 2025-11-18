<?php
include 'dbForm.php';
if (!isset($_GET['vacc_id'])) {
    echo json_encode([]);
    exit;
}
$id = intval($_GET['vacc_id']);
$stmt = mysqli_prepare($con, "SELECT v.vacc_id,
                                   v.infant_id,
                                   v.vaccine_name,
                                   v.date_vaccination,
                                   v.next_dose_date,
                                   v.time,
                                   v.status,
                                   v.remarks,
                                   v.barangay,
                                   CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name
                              FROM tbl_vaccination_schedule v
                              LEFT JOIN infantinfo i ON v.infant_id = i.id
                              WHERE v.vacc_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
echo json_encode($data ?: []);
