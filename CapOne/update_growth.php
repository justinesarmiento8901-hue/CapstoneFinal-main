<?php
session_start();
include 'dbForm.php';
$role = $_SESSION['user']['role'] ?? '';

$growthStatusColumnCheck = $con->query("SHOW COLUMNS FROM infant_previous_records LIKE 'growth_status'");
if ($growthStatusColumnCheck && $growthStatusColumnCheck->num_rows === 0) {
    $con->query("ALTER TABLE infant_previous_records ADD COLUMN growth_status VARCHAR(255) DEFAULT NULL");
}

function normalizeSex(?string $sex): ?string
{
    if ($sex === null) {
        return null;
    }

    $sexTrimmed = strtolower(trim($sex));

    if ($sexTrimmed === '') {
        return null;
    }

    if (in_array($sexTrimmed, ['male', 'm'], true)) {
        return 'Male';
    }

    if (in_array($sexTrimmed, ['female', 'f'], true)) {
        return 'Female';
    }

    return null;
}

if (!isset($_SESSION['user']) || !in_array($role, ['admin', 'healthworker'], true)) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_growth') {
    header('Content-Type: application/json; charset=utf-8');

    $infantId = isset($_POST['infant_id']) ? intval($_POST['infant_id']) : 0;
    $currentWeightRaw = isset($_POST['current_weight']) ? trim($_POST['current_weight']) : '';
    $currentHeightRaw = isset($_POST['current_height']) ? trim($_POST['current_height']) : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    $response = [
        'success' => false,
        'message' => 'Unknown error occurred.'
    ];

    if ($infantId <= 0) {
        $response['message'] = 'Invalid infant selected.';
        echo json_encode($response);
        exit;
    }

    $decimalPattern = '/^\d+(\.\d)?$/';

    if ($currentWeightRaw === '' || !preg_match($decimalPattern, $currentWeightRaw)) {
        $response['message'] = 'Please enter a valid current weight with one decimal place (e.g., 23.3).';
        echo json_encode($response);
        exit;
    }

    if ($currentHeightRaw === '' || !preg_match($decimalPattern, $currentHeightRaw)) {
        $response['message'] = 'Please enter a valid current height with one decimal place (e.g., 75.4).';
        echo json_encode($response);
        exit;
    }

    $currentWeight = round((float) $currentWeightRaw, 1);
    $currentHeight = round((float) $currentHeightRaw, 1);

    if ($currentWeight < 0 || $currentHeight < 0) {
        $response['message'] = 'Measurements must be positive numbers.';
        echo json_encode($response);
        exit;
    }

    $remarks = mb_substr($remarks, 0, 255);

    $prevStmt = $con->prepare('SELECT weight, height, dateofbirth, sex FROM infantinfo WHERE id = ? LIMIT 1');
    if (!$prevStmt) {
        $response['message'] = 'Failed to prepare query for previous growth data.';
        echo json_encode($response);
        exit;
    }

    $prevStmt->bind_param('i', $infantId);
    $prevStmt->execute();
    $prevResult = $prevStmt->get_result();

    if (!$prevResult || $prevResult->num_rows === 0) {
        $response['message'] = 'Infant record not found.';
        echo json_encode($response);
        exit;
    }

    $previousRow = $prevResult->fetch_assoc();
    $previousWeight = isset($previousRow['weight']) ? (float) $previousRow['weight'] : 0.0;
    $previousHeight = isset($previousRow['height']) ? (float) $previousRow['height'] : 0.0;
    $dobRaw = $previousRow['dateofbirth'] ?? null;
    $sexRaw = $previousRow['sex'] ?? null;
    $prevStmt->close();

    $sexNormalized = normalizeSex($sexRaw);

    $ageInMonths = null;
    if (!empty($dobRaw)) {
        try {
            $dob = new DateTime($dobRaw);
            $today = new DateTime();
            $diff = $dob->diff($today);
            $ageInMonths = ($diff->y * 12) + $diff->m;
        } catch (Exception $e) {
            $ageInMonths = null;
        }
    }

    $referenceData = null;
    if ($ageInMonths !== null) {
        $referenceData = fetchGrowthReference($ageInMonths, $con, $sexNormalized);
        if (!$referenceData) {
            $response['message'] = 'No growth reference data available for the computed age.';
            echo json_encode($response);
            exit;
        }
    }

    $growthStatus = 'Pending';
    if ($ageInMonths !== null) {
        $growthStatus = classifyGrowth($ageInMonths, $currentWeight, $currentHeight, $con, $referenceData, $sexNormalized);
    }

    $insertStmt = $con->prepare('INSERT INTO infant_previous_records (infant_id, previous_weight, previous_height, remarks, growth_status) VALUES (?, ?, ?, ?, ?)');
    if (!$insertStmt) {
        $response['message'] = 'Failed to prepare query for saving growth data.';
        echo json_encode($response);
        exit;
    }

    $insertStmt->bind_param('iddss', $infantId, $previousWeight, $previousHeight, $remarks, $growthStatus);

    if (!$insertStmt->execute()) {
        $response['message'] = 'Failed to save growth data.';
        echo json_encode($response);
        exit;
    }

    $insertStmt->close();

    $updateInfantStmt = $con->prepare('UPDATE infantinfo SET weight = ?, height = ? WHERE id = ?');
    if ($updateInfantStmt) {
        $updateInfantStmt->bind_param('ddi', $currentWeight, $currentHeight, $infantId);
        $updateInfantStmt->execute();
        $updateInfantStmt->close();
    }

    $response['success'] = true;
    $response['message'] = 'Growth record saved successfully.';

    $response['data'] = [
        'infant_id' => $infantId,
        'current_weight' => number_format($currentWeight, 1),
        'current_height' => number_format($currentHeight, 1),
        'previous_weight' => number_format($previousWeight, 1),
        'previous_height' => number_format($previousHeight, 1),
        'remarks' => $remarks,
        'status' => $growthStatus,
        'sex' => $sexNormalized ?? ''
    ];

    echo json_encode($response);
    exit;
}

$infants = [];
$sql = "
    SELECT
        i.id,
        i.firstname,
        i.middlename,
        i.surname,
        i.sex,
        i.dateofbirth,
        latest.previous_weight AS previous_weight,
        latest.previous_height AS previous_height,
        i.weight AS current_weight,
        i.height AS current_height,
        latest.remarks,
        latest.growth_status
    FROM infantinfo i
    LEFT JOIN (
        SELECT ipr1.*
        FROM infant_previous_records ipr1
        INNER JOIN (
            SELECT infant_id, MAX(id) AS max_id
            FROM infant_previous_records
            GROUP BY infant_id
        ) latest_ipr ON latest_ipr.infant_id = ipr1.infant_id AND latest_ipr.max_id = ipr1.id
    ) latest ON latest.infant_id = i.id
    ORDER BY i.id ASC
";

$result = $con->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $infants[] = $row;
    }
}

function formatMeasurement($value)
{
    if ($value === null || $value === '') {
        return '--';
    }

    return number_format((float) $value, 1);
}

function fetchGrowthReference(int $ageInMonths, mysqli $con, ?string $sex = null): ?array
{
    $reference = null;

    if ($sex !== null) {
        $stmt = $con->prepare('SELECT weight_min, weight_max, height_min, height_max FROM growth_reference WHERE age_in_months = ? AND sex = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('is', $ageInMonths, $sex);
            $stmt->execute();
            $result = $stmt->get_result();
            $reference = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }
    }

    if ($reference) {
        return $reference;
    }

    $stmt = $con->prepare('SELECT weight_min, weight_max, height_min, height_max FROM growth_reference WHERE age_in_months = ? AND (sex IS NULL OR sex = "") LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $ageInMonths);
        $stmt->execute();
        $result = $stmt->get_result();
        $reference = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }

    return $reference ?: null;
}

function classifyGrowth(int $age, float $weight, float $height, mysqli $con, ?array $referenceData = null, ?string $sex = null): string
{
    if ($referenceData === null) {
        $referenceData = fetchGrowthReference($age, $con, $sex);
    }

    if (!$referenceData) {
        return 'No reference data';
    }

    $statuses = [];

    if ($weight < (float) $referenceData['weight_min']) {
        $statuses[] = 'Underweight';
    } elseif ($weight > (float) $referenceData['weight_max']) {
        $statuses[] = 'Overweight';
    } else {
        $statuses[] = 'Normal Weight';
    }

    if ($height < (float) $referenceData['height_min']) {
        $statuses[] = 'Stunted';
    } elseif ($height > (float) $referenceData['height_max']) {
        $statuses[] = 'Tall';
    } else {
        $statuses[] = 'Normal Height';
    }

    return implode(', ', $statuses);
}

function computeStatus($growthStatus, $previousWeight, $previousHeight, $currentWeight, $currentHeight)
{
    if (!empty($growthStatus)) {
        return $growthStatus;
    }

    if ($currentWeight === null || $currentHeight === null) {
        return 'Pending';
    }

    $previousWeight = (float) $previousWeight;
    $previousHeight = (float) $previousHeight;
    $currentWeight = (float) $currentWeight;
    $currentHeight = (float) $currentHeight;

    $weightDiff = $currentWeight - $previousWeight;
    $heightDiff = $currentHeight - $previousHeight;

    if ($weightDiff > 0.0 || $heightDiff > 0.0) {
        return 'Improving';
    }

    if ($weightDiff < 0.0 || $heightDiff < 0.0) {
        return 'Needs Attention';
    }

    if ($currentWeight === 0.0 && $currentHeight === 0.0) {
        return 'Pending';
    }

    return 'Maintained';
}

function computeAgeInMonths(?string $dateOfBirth): ?int
{
    if ($dateOfBirth === null || trim($dateOfBirth) === '') {
        return null;
    }

    try {
        $dob = new DateTime($dateOfBirth);
        $today = new DateTime();
        $diff = $dob->diff($today);
        return ($diff->y * 12) + $diff->m;
    } catch (Exception $e) {
        return null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Growth Update</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-4"><i class="bi bi-baby"></i> Infant Record System</h4>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="addinfant.php"><i class="bi bi-person-fill-add"></i> Add Infant</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="add_parents.php"><i class="bi bi-person-plus"></i> Add Parent</a>
        <?php endif; ?>
        <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
        <a href="viewinfant.php"><i class="bi bi-journal-medical"></i> Infant Records</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="update_growth.php" class="active"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
        <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
        <?php if ($role !== 'parent'): ?>
            <a href="vaccination_schedule.php"><i class="bi bi-journal-medical"></i> Vaccination Schedule</a>
            <?php if (in_array($role, ['admin', 'report'], true)): ?>
                <a href="generate_report.php"><i class="bi bi-clipboard-data"></i> Reports</a>
            <?php endif; ?>
            <a href="sms.php"><i class="bi bi-chat-dots"></i> SMS Management</a>
            <?php if ($role === 'admin'): ?>
                <a href="login_logs.php"><i class="bi bi-clipboard-data"></i> Logs</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="content-area">
        <div class="container-fluid mt-4">
            <div class="card card-shadow">
                <div class="card-header bg-white border-0 py-3">
                    <h3 class="dashboard-title"><i class="bi bi-activity"></i> Growth Tracking</h3>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-5 col-lg-4 ms-auto">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" class="form-control" id="infantSearchInput" placeholder="Search infant name..." aria-label="Search infant name">
                            </div>
                        </div>
                    </div>
                    <div class="table-modern table-modern-elevated">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="growthTable">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Infant Name</th>
                                        <th scope="col">Sex</th>
                                        <th scope="col">Previous Weight (kg)</th>
                                        <th scope="col">Previous Height (cm)</th>
                                        <th scope="col">Current Weight (kg)</th>
                                        <th scope="col">Current Height (cm)</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Remarks</th>
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="growthTableBody">
                                    <?php if (empty($infants)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No infant records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($infants as $infant): ?>
                                            <?php
                                            $infantId = (int) $infant['id'];
                                            $fullName = trim(preg_replace('/\s+/', ' ', $infant['firstname'] . ' ' . ($infant['middlename'] ?? '') . ' ' . $infant['surname']));
                                            $prevWeightFormatted = formatMeasurement($infant['previous_weight']);
                                            $prevHeightFormatted = formatMeasurement($infant['previous_height']);
                                            $currentWeightFormatted = formatMeasurement($infant['current_weight']);
                                            $currentHeightFormatted = formatMeasurement($infant['current_height']);
                                            $remarksText = $infant['remarks'] ?? '';
                                            $statusLabel = computeStatus($infant['growth_status'] ?? '', $infant['previous_weight'], $infant['previous_height'], $infant['current_weight'], $infant['current_height']);

                                            $sexNormalizedRow = normalizeSex($infant['sex'] ?? null);
                                            $sexDisplay = $sexNormalizedRow ?? (trim((string) ($infant['sex'] ?? '')) !== '' ? ucfirst(strtolower((string) $infant['sex'])) : '--');

                                            $ageInMonthsDisplay = computeAgeInMonths($infant['dateofbirth'] ?? null);
                                            $referenceForDisplay = null;
                                            if ($ageInMonthsDisplay !== null) {
                                                $referenceForDisplay = fetchGrowthReference($ageInMonthsDisplay, $con, $sexNormalizedRow);
                                            }

                                            $weightMinDisplay = $referenceForDisplay ? number_format((float) $referenceForDisplay['weight_min'], 1) : '--';
                                            $weightMaxDisplay = $referenceForDisplay ? number_format((float) $referenceForDisplay['weight_max'], 1) : '--';
                                            $heightMinDisplay = $referenceForDisplay ? number_format((float) $referenceForDisplay['height_min'], 1) : '--';
                                            $heightMaxDisplay = $referenceForDisplay ? number_format((float) $referenceForDisplay['height_max'], 1) : '--';

                                            $weightMinAttr = $referenceForDisplay ? number_format((float) $referenceForDisplay['weight_min'], 1) : '';
                                            $weightMaxAttr = $referenceForDisplay ? number_format((float) $referenceForDisplay['weight_max'], 1) : '';
                                            $heightMinAttr = $referenceForDisplay ? number_format((float) $referenceForDisplay['height_min'], 1) : '';
                                            $heightMaxAttr = $referenceForDisplay ? number_format((float) $referenceForDisplay['height_max'], 1) : '';
                                            ?>
                                            <tr data-infant-id="<?php echo $infantId; ?>">
                                                <td><?php echo $infantId; ?></td>
                                                <td><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td data-column="sex"><?php echo htmlspecialchars($sexDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td data-column="previous-weight"><?php echo $prevWeightFormatted; ?></td>
                                                <td data-column="previous-height"><?php echo $prevHeightFormatted; ?></td>
                                                <td data-column="current-weight"><?php echo $currentWeightFormatted; ?></td>
                                                <td data-column="current-height"><?php echo $currentHeightFormatted; ?></td>
                                                <td data-column="status"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td data-column="remarks"><?php echo htmlspecialchars($remarksText !== '' ? $remarksText : '--', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-center">
                                                    <button
                                                        type="button"
                                                        class="btn btn-success btn-sm d-inline-flex align-items-center justify-content-center"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#growthModal"
                                                        data-infant-id="<?php echo $infantId; ?>"
                                                        data-infant-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-sex="<?php echo htmlspecialchars($sexDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-weight-min="<?php echo htmlspecialchars($weightMinAttr, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-weight-max="<?php echo htmlspecialchars($weightMaxAttr, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-height-min="<?php echo htmlspecialchars($heightMinAttr, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-height-max="<?php echo htmlspecialchars($heightMaxAttr, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-prev-weight="<?php echo $prevWeightFormatted; ?>"
                                                        data-prev-height="<?php echo $prevHeightFormatted; ?>"
                                                        data-current-weight="<?php echo $currentWeightFormatted !== '--' ? $currentWeightFormatted : ''; ?>"
                                                        data-current-height="<?php echo $currentHeightFormatted !== '--' ? $currentHeightFormatted : ''; ?>"
                                                        data-remarks="<?php echo htmlspecialchars($remarksText, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-status="<?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                        <span class="visually-hidden">Edit</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr id="noMatchesRow" class="d-none">
                                        <td colspan="10" class="text-center text-muted">No matching records found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="growthModal" tabindex="-1" aria-labelledby="growthModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="growthModalLabel">Update Growth</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="growthForm">
                        <input type="hidden" name="action" value="save_growth">
                        <input type="hidden" name="infant_id" id="modalInfantId">
                        <div class="mb-3">
                            <label class="form-label">Infant Name</label>
                            <input type="text" class="form-control" id="modalInfantName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sex</label>
                            <input type="text" class="form-control" id="modalSex" readonly>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Reference Min Weight (kg)</label>
                                <input type="text" class="form-control" id="modalWeightMin" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Max Weight (kg)</label>
                                <input type="text" class="form-control" id="modalWeightMax" readonly>
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Reference Min Height (cm)</label>
                                <input type="text" class="form-control" id="modalHeightMin" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Max Height (cm)</label>
                                <input type="text" class="form-control" id="modalHeightMax" readonly>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Previous Weight (kg)</label>
                                <input type="text" class="form-control" id="modalPreviousWeight" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Previous Height (cm)</label>
                                <input type="text" class="form-control" id="modalPreviousHeight" readonly>
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Current Weight (kg)</label>
                                <input type="number" class="form-control" id="modalCurrentWeight" name="current_weight" step="0.1" pattern="^\d+(\.\d)?$" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Current Height (cm)</label>
                                <input type="number" class="form-control" id="modalCurrentHeight" name="current_height" step="0.1" pattern="^\d+(\.\d)?$" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" id="modalRemarks" name="remarks" rows="3" maxlength="255" placeholder="Enter remarks"></textarea>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="growthSubmitBtn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        const growthModal = document.getElementById('growthModal');
        const growthForm = document.getElementById('growthForm');
        const submitBtn = document.getElementById('growthSubmitBtn');
        const currentWeightInput = document.getElementById('modalCurrentWeight');
        const currentHeightInput = document.getElementById('modalCurrentHeight');
        const modalSexInput = document.getElementById('modalSex');
        const modalWeightMinInput = document.getElementById('modalWeightMin');
        const modalWeightMaxInput = document.getElementById('modalWeightMax');
        const modalHeightMinInput = document.getElementById('modalHeightMin');
        const modalHeightMaxInput = document.getElementById('modalHeightMax');
        const searchInput = document.getElementById('infantSearchInput');
        const tableBody = document.getElementById('growthTableBody');
        const noMatchesRow = document.getElementById('noMatchesRow');

        function enforceSingleDecimal(input) {
            input.addEventListener('blur', () => {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    input.value = value.toFixed(1);
                }
            });
        }

        enforceSingleDecimal(currentWeightInput);
        enforceSingleDecimal(currentHeightInput);

        if (searchInput && tableBody) {
            const handleSearch = () => {
                const query = searchInput.value.trim().toLowerCase();
                const rows = tableBody.querySelectorAll('tr[data-infant-id]');
                let matches = 0;

                rows.forEach(row => {
                    const nameCell = row.querySelector('td:nth-child(2)');
                    const nameText = nameCell ? nameCell.textContent.toLowerCase() : '';
                    const shouldShow = query === '' || nameText.includes(query);
                    row.classList.toggle('d-none', !shouldShow);
                    if (shouldShow) {
                        matches++;
                    }
                });

                if (noMatchesRow) {
                    if (rows.length === 0) {
                        noMatchesRow.classList.add('d-none');
                    } else if (matches === 0) {
                        noMatchesRow.classList.remove('d-none');
                    } else {
                        noMatchesRow.classList.add('d-none');
                    }
                }
            };

            searchInput.addEventListener('input', handleSearch);
            handleSearch();
        }

        growthModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) {
                return;
            }

            const infantId = button.getAttribute('data-infant-id');
            const infantName = button.getAttribute('data-infant-name');
            const sex = button.getAttribute('data-sex') || '--';
            const weightMin = button.getAttribute('data-weight-min') || '';
            const weightMax = button.getAttribute('data-weight-max') || '';
            const heightMin = button.getAttribute('data-height-min') || '';
            const heightMax = button.getAttribute('data-height-max') || '';
            const prevWeight = button.getAttribute('data-prev-weight');
            const prevHeight = button.getAttribute('data-prev-height');
            const currentWeight = button.getAttribute('data-current-weight');
            const currentHeight = button.getAttribute('data-current-height');
            const remarks = button.getAttribute('data-remarks');
            const status = button.getAttribute('data-status');

            document.getElementById('modalInfantId').value = infantId;
            document.getElementById('modalInfantName').value = infantName;
            if (modalSexInput) {
                modalSexInput.value = sex || '--';
            }
            if (modalWeightMinInput) {
                modalWeightMinInput.value = weightMin !== '' ? weightMin : '--';
            }
            if (modalWeightMaxInput) {
                modalWeightMaxInput.value = weightMax !== '' ? weightMax : '--';
            }
            if (modalHeightMinInput) {
                modalHeightMinInput.value = heightMin !== '' ? heightMin : '--';
            }
            if (modalHeightMaxInput) {
                modalHeightMaxInput.value = heightMax !== '' ? heightMax : '--';
            }
            document.getElementById('modalPreviousWeight').value = prevWeight;
            document.getElementById('modalPreviousHeight').value = prevHeight;
            document.getElementById('modalCurrentWeight').value = currentWeight || '';
            document.getElementById('modalCurrentHeight').value = currentHeight || '';
            document.getElementById('modalRemarks').value = remarks ? remarks.replace(/&quot;/g, '"') : '';
            document.getElementById('modalRemarks').placeholder = status ? `Status: ${status}` : 'Enter remarks';
        });

        growthForm.addEventListener('submit', event => {
            event.preventDefault();

            const formData = new FormData(growthForm);

            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire('Error', data.message || 'Failed to save growth data.', 'error');
                        return;
                    }

                    const updated = data.data;
                    const row = document.querySelector(`#growthTable tr[data-infant-id="${updated.infant_id}"]`);
                    if (row) {
                        const previousWeightCell = row.querySelector('[data-column="previous-weight"]');
                        const previousHeightCell = row.querySelector('[data-column="previous-height"]');
                        const currentWeightCell = row.querySelector('[data-column="current-weight"]');
                        const currentHeightCell = row.querySelector('[data-column="current-height"]');
                        const statusCell = row.querySelector('[data-column="status"]');
                        const remarksCell = row.querySelector('[data-column="remarks"]');
                        const sexCell = row.querySelector('[data-column="sex"]');
                        const editButton = row.querySelector('button[data-bs-target="#growthModal"]');

                        if (previousWeightCell) {
                            previousWeightCell.textContent = updated.previous_weight || '--';
                        }
                        if (previousHeightCell) {
                            previousHeightCell.textContent = updated.previous_height || '--';
                        }
                        if (currentWeightCell) {
                            currentWeightCell.textContent = updated.current_weight;
                        }
                        if (currentHeightCell) {
                            currentHeightCell.textContent = updated.current_height;
                        }
                        if (statusCell) {
                            statusCell.textContent = updated.status || '';
                        }
                        if (remarksCell) {
                            remarksCell.textContent = updated.remarks && updated.remarks !== '' ? updated.remarks : '--';
                        }
                        if (sexCell && updated.sex) {
                            sexCell.textContent = updated.sex;
                        }
                        if (editButton) {
                            editButton.setAttribute('data-prev-weight', updated.previous_weight || '--');
                            editButton.setAttribute('data-prev-height', updated.previous_height || '--');
                            editButton.setAttribute('data-current-weight', updated.current_weight || '--');
                            editButton.setAttribute('data-current-height', updated.current_height || '--');
                            editButton.setAttribute('data-remarks', updated.remarks || '');
                            editButton.setAttribute('data-status', updated.status || '');
                            if (updated.sex) {
                                editButton.setAttribute('data-sex', updated.sex);
                            }
                        }
                    }

                    const modalInstance = bootstrap.Modal.getInstance(growthModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    const statusMessage = updated.status ? `Status: ${updated.status}` : '';
                    Swal.fire('Success', statusMessage || data.message || 'Growth data saved.', 'success');
                })
                .catch(() => {
                    Swal.fire('Error', 'An unexpected error occurred.', 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save';
                });
        });
    </script>
</body>

</html>