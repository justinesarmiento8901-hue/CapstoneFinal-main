<?php
include 'dbForm.php';
require_once 'sms_queue_helpers.php';

header('Content-Type: application/json');

ensureSmsQueueTable($con);
ensureScheduleBarangayColumn($con);

$barangayFilter = '';
if (isset($_GET['barangay'])) {
    $barangayFilter = trim((string) $_GET['barangay']);
}

$sqlBase = "SELECT q.id, q.vacc_id, q.phone, q.barangay, q.next_dose_date, q.schedule_time,
               CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name
        FROM sms_queue q
        LEFT JOIN tbl_vaccination_schedule v ON v.vacc_id = q.vacc_id
        LEFT JOIN infantinfo i ON i.id = q.infant_id";
$orderClause = " ORDER BY q.next_dose_date IS NULL, q.next_dose_date ASC, q.schedule_time ASC";

$rows = [];

if ($barangayFilter !== '') {
    $stmt = mysqli_prepare($con, $sqlBase . " WHERE q.barangay = ?" . $orderClause);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $barangayFilter);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = false;
    }
} else {
    $result = mysqli_query($con, $sqlBase . $orderClause);
}

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'vacc_id' => (int) ($row['vacc_id'] ?? 0),
            'infant_name' => trim((string) ($row['infant_name'] ?? '')),
            'phone' => $row['phone'] ?? '',
            'barangay' => $row['barangay'] ?? '',
            'next_dose_date' => $row['next_dose_date'] ?? null,
            'schedule_time' => $row['schedule_time'] ?? null,
        ];
    }
}

if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    mysqli_stmt_close($stmt);
}

echo json_encode($rows);
