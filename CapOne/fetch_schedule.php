<?php
include 'dbForm.php';

function formatDateDisplay($date)
{
    if (!$date) {
        return 'N/A';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return htmlspecialchars($date);
    }
    return htmlspecialchars(date('Y-m-d', $timestamp));
}

function formatNextDoseDisplay($date)
{
    if (!$date) {
        return 'N/A';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return htmlspecialchars($date);
    }
    return htmlspecialchars(date('Y-m-d', $timestamp));
}

function formatTimeDisplay($time)
{
    if (!$time) {
        return 'N/A';
    }
    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return htmlspecialchars($time);
    }
    return date('g:i A', $timestamp);
}

$allowedSorts = [
    'infant' => 'infant_name',
    'vaccine' => 'v.vaccine_name',
    'next_dose' => 'v.next_dose_date',
    'status' => 'v.status'
];

$sort = $_GET['sort'] ?? '';
$direction = strtolower($_GET['direction'] ?? 'asc');
$direction = ($direction === 'desc') ? 'DESC' : 'ASC';

$orderClauses = [];

if ($sort && isset($allowedSorts[$sort])) {
    $orderClauses[] = $allowedSorts[$sort] . ' ' . $direction;
}

$orderClauses[] = 'infant_name ASC';
$orderClauses[] = 'v.date_vaccination DESC';

$orderSql = implode(', ', $orderClauses);

$statusFilter = $_GET['status'] ?? '';
$whereClauses = [];
$params = [];
$types = '';

if ($statusFilter && in_array($statusFilter, ['Pending', 'Completed'], true)) {
    $whereClauses[] = 'v.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSql = $whereClauses ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

$sql = "SELECT 
            v.vacc_id,
            v.infant_id,
            CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name,
            CONCAT_WS(' ', p.first_name, p.last_name) AS parent_name,
            p.phone AS parent_contact,
            v.vaccine_name,
            v.date_vaccination,
            v.next_dose_date,
            v.time,
            v.status,
            v.remarks
        FROM tbl_vaccination_schedule v
        JOIN infantinfo i ON v.infant_id = i.id
        JOIN parents p ON i.parent_id = p.id
        $whereSql
        ORDER BY $orderSql";

if ($types !== '') {
    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = mysqli_query($con, $sql);
}
$rows = '';
$grouped_data = [];
$count = 1;

if ($res) {
    // Group data by infant_id
    while ($r = mysqli_fetch_assoc($res)) {
        $grouped_data[$r['infant_id']][] = $r;
    }

    // Display grouped data
    foreach ($grouped_data as $infant_id => $vaccines) {
        $first_vaccine = $vaccines[0]; // Get infant info from first vaccine
        $infantEsc = htmlspecialchars($first_vaccine['infant_name']);
        $parentEsc = htmlspecialchars($first_vaccine['parent_name']);

        // Add infant header row
        $firstTime = formatTimeDisplay($first_vaccine['time']);
        $firstDate = formatDateDisplay($first_vaccine['date_vaccination']);
        $firstNextDose = formatNextDoseDisplay($first_vaccine['next_dose_date']);
        $firstVaccineName = htmlspecialchars($first_vaccine['vaccine_name']);
        $firstRemarks = htmlspecialchars($first_vaccine['remarks']);
        $contactEsc = htmlspecialchars($first_vaccine['parent_contact']);

        $firstVaccineAttr = htmlspecialchars($first_vaccine['vaccine_name'], ENT_QUOTES, 'UTF-8');
        $infantNameAttr = htmlspecialchars($first_vaccine['infant_name'], ENT_QUOTES, 'UTF-8');

        $firstStatusCompleted = ($first_vaccine['status'] === 'Completed');
        $firstDisabledAttr = $firstStatusCompleted ? "disabled title='Completed schedules cannot revert to pending'" : '';

        $rows .= "<tr class='infant-group-header'>
            <td rowspan='" . count($vaccines) . "'>{$count}</td>
            <td rowspan='" . count($vaccines) . "'><a href='#' class='infantLink' data-id='{$infant_id}'>{$infantEsc}</a></td>
            <td rowspan='" . count($vaccines) . "'>{$parentEsc}</td>
            <td rowspan='" . count($vaccines) . "'>{$contactEsc}</td>
            <td>{$firstDate}</td>
            <td>{$firstVaccineName}</td>
            <td>{$firstNextDose}</td>
            <td>{$firstTime}</td>
            <td class='text-center'>
                <input type='checkbox' class='form-check-input statusCheckbox' data-id='{$first_vaccine['vacc_id']}' data-infant-id='{$infant_id}' data-infant-name='{$infantNameAttr}' data-vaccine-name='{$firstVaccineAttr}' " . ($firstStatusCompleted ? "checked {$firstDisabledAttr}" : '') . ">
                <div style='margin-top:4px'>" . (($first_vaccine['status'] === 'Completed') ? "<span class='badge bg-success'>Completed</span>" : "<span class='badge bg-warning'>Pending</span>") . "</div>
            </td>
            <td>{$firstRemarks}</td>
            <td class='text-center'>
                <div class='d-flex gap-1 justify-content-center'>
                    <button class='btn btn-outline-success btn-sm editBtn' data-id='{$first_vaccine['vacc_id']}' title='Edit'><i class='bi bi-pencil-square'></i></button>
                    <button class='btn btn-outline-danger btn-sm deleteBtn' data-id='{$first_vaccine['vacc_id']}' title='Delete'><i class='bi bi-trash'></i></button>
                </div>
            </td>
        </tr>";

        // Add remaining vaccines for this infant
        for ($i = 1; $i < count($vaccines); $i++) {
            $vaccine = $vaccines[$i];
            $vaccineEsc = htmlspecialchars($vaccine['vaccine_name']);
            $remarksEsc = htmlspecialchars($vaccine['remarks']);
            $statusBadge = ($vaccine['status'] === 'Completed') ? "<span class='badge bg-success'>Completed</span>" : "<span class='badge bg-warning'>Pending</span>";
            $checked = ($vaccine['status'] === 'Completed') ? 'checked' : '';

            $vaccineNameAttr = htmlspecialchars($vaccine['vaccine_name'], ENT_QUOTES, 'UTF-8');
            $rowStatusCompleted = ($vaccine['status'] === 'Completed');
            $rowDisabledAttr = $rowStatusCompleted ? "disabled title='Completed schedules cannot revert to pending'" : '';
            $rows .= "<tr class='vaccine-row'>
                <td>" . formatDateDisplay($vaccine['date_vaccination']) . "</td>
                <td>{$vaccineEsc}</td>
                <td>" . formatNextDoseDisplay($vaccine['next_dose_date']) . "</td>
                <td>" . formatTimeDisplay($vaccine['time']) . "</td>
                <td class='text-center'>
                    <input type='checkbox' class='form-check-input statusCheckbox' data-id='{$vaccine['vacc_id']}' data-infant-id='{$infant_id}' data-infant-name='{$infantNameAttr}' data-vaccine-name='{$vaccineNameAttr}' " . ($rowStatusCompleted ? "checked {$rowDisabledAttr}" : $checked) . ">
                    <div style='margin-top:4px'>{$statusBadge}</div>
                </td>
                <td>{$remarksEsc}</td>
                <td class='text-center'>
                    <div class='d-flex gap-1 justify-content-center'>
                        <button class='btn btn-outline-success btn-sm editBtn' data-id='{$vaccine['vacc_id']}' title='Edit'><i class='bi bi-pencil-square'></i></button>
                        <button class='btn btn-outline-danger btn-sm deleteBtn' data-id='{$vaccine['vacc_id']}' title='Delete'><i class='bi bi-trash'></i></button>
                    </div>
                </td>
            </tr>";
        }
        $count++;
    }
} else {
    $rows = "<tr><td colspan='10' class='text-center text-danger'>Query error: " . mysqli_error($con) . "</td></tr>";
}
echo $rows;
