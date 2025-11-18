<?php
require_once __DIR__ . '/dbForm.php';
header('Content-Type: application/json');

$vaccines = [];
$query = "SELECT DISTINCT vaccine_name FROM tbl_vaccination_schedule WHERE vaccine_name <> '' ORDER BY vaccine_name ASC";
$result = $con->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vaccines[] = $row['vaccine_name'];
    }
}

if (!$vaccines) {
    $fallback = $con->query("SELECT vaccine_name FROM tbl_vaccine_reference ORDER BY vaccine_name ASC");
    if ($fallback) {
        while ($row = $fallback->fetch_assoc()) {
            $vaccines[] = $row['vaccine_name'];
        }
    }
}

echo json_encode(array_values(array_unique($vaccines)));
