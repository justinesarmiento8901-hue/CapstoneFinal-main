<?php
include 'dbForm.php';
header('Content-Type: application/json');

$infantId = isset($_GET['infant_id']) ? intval($_GET['infant_id']) : 0;
if ($infantId <= 0) {
    echo json_encode(['phone' => null]);
    exit;
}

$stmt = $con->prepare("SELECT parents.phone, parents.barangay FROM infantinfo LEFT JOIN parents ON parents.id = infantinfo.parent_id WHERE infantinfo.id = ? LIMIT 1");
$stmt->bind_param('i', $infantId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

$phone = $row['phone'] ?? null;
$barangay = $row['barangay'] ?? null;

echo json_encode(['phone' => $phone, 'barangay' => $barangay]);
