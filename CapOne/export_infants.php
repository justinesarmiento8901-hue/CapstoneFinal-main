<?php
// export_infants.php - generate PDF of infant list using current filters
require 'dbForm.php';
require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

// Simple auth check (reuse role checks from viewinfant)
session_start();
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'healthworker', 'parent', 'report'], true)) {
    header('Location: viewinfant.php');
    exit;
}

// Collect filters from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sex = isset($_GET['sex']) ? trim($_GET['sex']) : '';
$birth_from = isset($_GET['birth_from']) ? trim($_GET['birth_from']) : '';
$birth_to = isset($_GET['birth_to']) ? trim($_GET['birth_to']) : '';

// Build WHERE
$where = '1';
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (id LIKE '%$s%' OR firstname LIKE '%$s%' OR middlename LIKE '%$s%' OR surname LIKE '%$s%' OR placeofbirth LIKE '%$s%')";
}
if (in_array($sex, ['Male','Female'], true)) {
    $where .= " AND sex = '" . mysqli_real_escape_string($con, $sex) . "'";
}
if ($birth_from !== '') {
    $ts = strtotime($birth_from);
    if ($ts !== false) {
        $bf = date('Y-m-d', $ts);
        $where .= " AND dateofbirth >= '" . mysqli_real_escape_string($con, $bf) . "'";
    }
}
if ($birth_to !== '') {
    $ts2 = strtotime($birth_to);
    if ($ts2 !== false) {
        $bt = date('Y-m-d', $ts2);
        $where .= " AND dateofbirth <= '" . mysqli_real_escape_string($con, $bt) . "'";
    }
}

// If user is parent, restrict to their infants
if ($role === 'parent' && isset($_SESSION['user']['email'])) {
    $parentEmail = mysqli_real_escape_string($con, $_SESSION['user']['email']);
    $pres = mysqli_query($con, "SELECT id FROM parents WHERE email = '$parentEmail' LIMIT 1");
    if ($pres && mysqli_num_rows($pres) > 0) {
        $prow = mysqli_fetch_assoc($pres);
        $parentId = (int)$prow['id'];
        $where .= " AND parent_id = $parentId";
    }
}

$sql = "SELECT * FROM infantinfo WHERE $where ORDER BY id ASC";
$res = mysqli_query($con, $sql);
$rowsData = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $firstname = $r['firstname'] ?? '';
        $middlename = $r['middlename'] ?? '';
        $surname = $r['surname'] ?? '';
        $fullName = trim(preg_replace('/\s+/', ' ', $firstname . ' ' . ($middlename ?? '') . ' ' . $surname));
        $rowsData[] = [
            'full_name' => $fullName,
            'dateofbirth' => $r['dateofbirth'] ?? '',
            'placeofbirth' => $r['placeofbirth'] ?? '',
            'sex' => $r['sex'] ?? '',
            'weight' => $r['weight'] ?? '',
            'height' => $r['height'] ?? '',
            'nationality' => $r['nationality'] ?? '',
        ];
    }
}

$filtersData = [
    'search' => $search,
    'sex' => $sex,
    'birth_from' => $birth_from,
    'birth_to' => $birth_to,
];

// Decide output format: pdf (default) or csv
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'pdf';

if ($format === 'csv') {
    // Output CSV
    $filename = 'infant_list_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#', 'Full Name', 'Date of Birth', 'Place of Birth', 'Sex', 'Weight', 'Height', 'Nationality']);
    foreach ($rowsData as $i => $r) {
        fputcsv($out, [
            $i + 1,
            $r['full_name'] ?? '',
            $r['dateofbirth'] ?? '',
            $r['placeofbirth'] ?? '',
            $r['sex'] ?? '',
            $r['weight'] ?? '',
            $r['height'] ?? '',
            $r['nationality'] ?? '',
        ]);
    }
    fclose($out);
    exit;
} else {
    // Render template to HTML and produce PDF
    ob_start();
    include __DIR__ . '/templates/infant_list_pdf.php';
    $html = ob_get_clean();

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'infant_list_' . date('Ymd_His') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => 1]); // force download
    exit;
}
