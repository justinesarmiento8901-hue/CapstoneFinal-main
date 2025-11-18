<?php
include 'dbForm.php';
require_once 'sms_queue_helpers.php';

ensureParentBarangayColumn($con);

$term = trim($_GET['q'] ?? '');

if ($term === '') {
    exit;
}

$stmt = $con->prepare("
    SELECT infantinfo.id,
           CONCAT_WS(' ', infantinfo.firstname, infantinfo.middlename, infantinfo.surname) AS name,
           parents.phone,
           parents.barangay
    FROM infantinfo
    LEFT JOIN parents ON parents.id = infantinfo.parent_id
    WHERE infantinfo.firstname LIKE CONCAT('%', ?, '%')
       OR infantinfo.middlename LIKE CONCAT('%', ?, '%')
       OR infantinfo.surname LIKE CONCAT('%', ?, '%')
    ORDER BY infantinfo.firstname ASC
    LIMIT 10
");
$stmt->bind_param("sss", $term, $term, $term);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $id = (int) $row['id'];
    $name = htmlspecialchars($row['name'] ?? '', ENT_QUOTES);
    $phone = htmlspecialchars($row['phone'] ?? '', ENT_QUOTES);
    $barangay = htmlspecialchars($row['barangay'] ?? '', ENT_QUOTES);
    echo "<button type='button' class='list-group-item list-group-item-action' data-id='{$id}' data-phone='{$phone}' data-barangay='{$barangay}'>{$name}</button>";
}
