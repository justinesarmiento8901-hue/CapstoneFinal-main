<?php
use Dompdf\Dompdf;
use Dompdf\Options;

function buildReportQuery(array $filters, bool $withPagination = true): array
{
    $baseSelect = "SELECT 
            v.vacc_id,
            v.infant_id,
            CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name,
            CONCAT_WS(' ', p.first_name, p.last_name) AS parent_name,
            p.phone AS parent_contact,
            v.barangay,
            v.vaccine_name,
            v.date_vaccination,
            v.next_dose_date,
            v.time,
            v.status,
            v.remarks";

    $baseFrom = " FROM tbl_vaccination_schedule v
            JOIN infantinfo i ON v.infant_id = i.id
            JOIN parents p ON i.parent_id = p.id";

    $where = [];
    $params = [];
    $types = '';

    if (!empty($filters['barangays']) && is_array($filters['barangays'])) {
        $barangays = array_filter(array_map('trim', $filters['barangays']));
        if ($barangays) {
            $placeholders = implode(',', array_fill(0, count($barangays), '?'));
            $where[] = "v.barangay IN ($placeholders)";
            foreach ($barangays as $barangay) {
                $params[] = $barangay;
                $types .= 's';
            }
        }
    }

    if (!empty($filters['date_from'])) {
        $where[] = 'v.date_vaccination >= ?';
        $params[] = $filters['date_from'];
        $types .= 's';
    }

    if (!empty($filters['date_to'])) {
        $where[] = 'v.date_vaccination <= ?';
        $params[] = $filters['date_to'];
        $types .= 's';
    }

    if (!empty($filters['vaccine'])) {
        $where[] = 'v.vaccine_name = ?';
        $params[] = $filters['vaccine'];
        $types .= 's';
    }

    if (!empty($filters['status']) && $filters['status'] !== 'All') {
        $where[] = 'v.status = ?';
        $params[] = $filters['status'];
        $types .= 's';
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $groupOrder = " ORDER BY v.date_vaccination DESC, v.time DESC";
    $allowedGroups = [
        'barangay' => 'v.barangay',
        'status' => 'v.status',
        'vaccine' => 'v.vaccine_name'
    ];

    if (!empty($filters['group_by']) && isset($allowedGroups[$filters['group_by']])) {
        $groupOrder = " ORDER BY {$allowedGroups[$filters['group_by']]}, v.date_vaccination DESC, v.time DESC";
    }

    $sql = $baseSelect . $baseFrom . $whereSql . $groupOrder;
    $countSql = 'SELECT COUNT(*)' . $baseFrom . $whereSql;

    if ($withPagination) {
        return [$sql, $params, $types, $countSql, $params, $types];
    }

    return [$sql, $params, $types];
}

function logReportRun(mysqli $con, int $userId, array $filters, string $type): void
{
    $sql = 'INSERT INTO tbl_report_logs (user_id, run_type, filters_json) VALUES (?, ?, ?)';
    $stmt = $con->prepare($sql);
    $filtersJson = json_encode($filters);
    $stmt->bind_param('iss', $userId, $type, $filtersJson);
    $stmt->execute();
    $stmt->close();
}

function exportToCsv(array $rows, string $filename): void
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
    } else {
        fputcsv($out, ['No data available for selected filters.']);
    }
    fclose($out);
    exit;
}

function exportToPdf(array $rows, array $filters, string $filename): void
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['error' => 'PDF export unavailable. Please install dompdf/dompdf via Composer.']);
        exit;
    }

    require_once $autoload;

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $dompdf = new Dompdf($options);

    $rowsData = $rows; // expose to template
    $filtersData = $filters;

    ob_start();
    include __DIR__ . '/../templates/report_pdf_template.php';
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}
