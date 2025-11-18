<?php
include 'dbForm.php';
header('Content-Type: application/json');

$infantId = isset($_GET['infant_id']) ? intval($_GET['infant_id']) : 0;
if ($infantId <= 0) {
    echo json_encode(['completed' => []]);
    exit;
}

$stmt = $con->prepare("SELECT DISTINCT vaccine_name FROM tbl_vaccination_schedule WHERE infant_id = ? AND status = 'Completed'");
$stmt->bind_param('i', $infantId);
$stmt->execute();
$result = $stmt->get_result();
$completed = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['vaccine_name'])) {
        $completed[] = $row['vaccine_name'];
    }
}
$stmt->close();

echo json_encode(['completed' => $completed]);
