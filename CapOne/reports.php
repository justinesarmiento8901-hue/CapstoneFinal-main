<?php
require_once __DIR__ . '/dbForm.php';
require_once __DIR__ . '/lib/ReportHelpers.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json', true, 401);
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$allowedRoles = ['admin', 'report'];
if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
    header('Content-Type: application/json', true, 403);
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'preview';

switch ($action) {
    case 'preview':
        handlePreview($con, $user);
        break;
    case 'export_csv':
        handleExport($con, $user, 'csv');
        break;
    case 'export_pdf':
        handleExport($con, $user, 'pdf');
        break;
    default:
        header('Content-Type: application/json', true, 400);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handlePreview(mysqli $con, array $user): void
{
    [$filters, $validationError] = extractFilters();
    if ($validationError) {
        header('Content-Type: application/json', true, 400);
        http_response_code(400);
        echo json_encode(['error' => $validationError]);
        return;
    }

    header('Content-Type: application/json');

    [$sql, $params, $types, $countSql, $countParams, $countTypes] = buildReportQuery($filters);

    $page = max(1, (int)($_GET['page'] ?? $_POST['page'] ?? 1));
    $perPage = max(5, min(50, (int)($_GET['perPage'] ?? $_POST['perPage'] ?? 10)));
    $offset = ($page - 1) * $perPage;

    $sql .= ' LIMIT ? OFFSET ?';
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $con->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $countStmt = $con->prepare($countSql);
    if ($countTypes !== '') {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRow = $countResult ? $countResult->fetch_row() : null;
    $total = $totalRow ? (int)$totalRow[0] : 0;
    $countStmt->close();

    logReportRun($con, (int)$user['id'], $filters, 'preview');

    echo json_encode([
        'data' => $rows,
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
        ],
    ]);
}

function handleExport(mysqli $con, array $user, string $format): void
{
    [$filters, $validationError] = extractFilters();
    if ($validationError) {
        header('Content-Type: application/json', true, 400);
        http_response_code(400);
        echo json_encode(['error' => $validationError]);
        return;
    }

    [$sql, $params, $types] = buildReportQuery($filters, false);

    $stmt = $con->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    logReportRun($con, (int)$user['id'], $filters, $format);

    if ($format === 'csv') {
        exportToCsv($rows, 'vaccination_report.csv');
    } else {
        exportToPdf($rows, $filters, 'vaccination_report.pdf');
    }
}

function extractFilters(): array
{
    $barangays = $_REQUEST['barangays'] ?? [];
    $dateFrom = $_REQUEST['date_from'] ?? null;
    $dateTo = $_REQUEST['date_to'] ?? null;
    $status = $_REQUEST['status'] ?? 'All';
    $vaccine = $_REQUEST['vaccine'] ?? null;
    $groupBy = $_REQUEST['group_by'] ?? 'none';

    if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
        return [[], 'Invalid date range: start date is after end date.'];
    }

    $filters = [
        'barangays' => is_array($barangays) ? $barangays : [$barangays],
        'date_from' => $dateFrom ?: null,
        'date_to' => $dateTo ?: null,
        'status' => $status ?: 'All',
        'vaccine' => $vaccine ?: null,
        'group_by' => $groupBy ?: 'none',
    ];

    $allowedGroups = ['none', 'barangay', 'status', 'vaccine'];
    if (!in_array($filters['group_by'], $allowedGroups, true)) {
        return [[], 'Invalid group by value.'];
    }

    $allowedStatuses = ['All', 'Pending', 'Completed'];
    if (!in_array($filters['status'], $allowedStatuses, true)) {
        return [[], 'Invalid status value.'];
    }

    return [$filters, null];
}
