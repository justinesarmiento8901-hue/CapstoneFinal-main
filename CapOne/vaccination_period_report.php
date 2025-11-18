<?php
session_start();
require_once __DIR__ . '/dbForm.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: login.php');
    exit;
}

$allowedRoles = ['admin', 'healthworker', 'report'];
if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$periodOptions = [
    'week' => [
        'label' => 'Weekly',
        'select' => "CONCAT('Week ', LPAD(WEEK(date_vaccination, 1), 2, '0'), ' of ', YEAR(date_vaccination)) AS period_label, YEAR(date_vaccination) AS sort_year, WEEK(date_vaccination, 1) AS sort_period",
        'group_format' => "DATE_FORMAT(date_vaccination, '%Y-%m-%d')",
        'group' => 'YEAR(date_vaccination), WEEK(date_vaccination, 1)',
        'order' => 'sort_year ASC, sort_period ASC'
    ],
    'month' => [
        'label' => 'Monthly',
        'select' => "DATE_FORMAT(date_vaccination, '%M %Y') AS period_label, YEAR(date_vaccination) AS sort_year, MONTH(date_vaccination) AS sort_period",
        'group_format' => "DATE_FORMAT(date_vaccination, '%Y-%m-%d')",
        'group' => 'YEAR(date_vaccination), MONTH(date_vaccination)',
        'order' => 'sort_year ASC, sort_period ASC'
    ],
    'quarter' => [
        'label' => 'Quarterly',
        'select' => "CONCAT('Q', QUARTER(date_vaccination), ' ', YEAR(date_vaccination)) AS period_label, YEAR(date_vaccination) AS sort_year, QUARTER(date_vaccination) AS sort_period",
        'group_format' => "DATE_FORMAT(date_vaccination, '%Y-%m-%d')",
        'group' => 'YEAR(date_vaccination), QUARTER(date_vaccination)',
        'order' => 'sort_year ASC, sort_period ASC'
    ],
    'semiannual' => [
        'label' => 'Semi-Annual',
        'select' => "CONCAT('H', CASE WHEN MONTH(date_vaccination) <= 6 THEN 1 ELSE 2 END, ' ', YEAR(date_vaccination)) AS period_label, YEAR(date_vaccination) AS sort_year, CASE WHEN MONTH(date_vaccination) <= 6 THEN 1 ELSE 2 END AS sort_period",
        'group_format' => "DATE_FORMAT(date_vaccination, '%Y-%m-%d')",
        'group' => 'YEAR(date_vaccination), CASE WHEN MONTH(date_vaccination) <= 6 THEN 1 ELSE 2 END',
        'order' => 'sort_year ASC, sort_period ASC'
    ],
    'annual' => [
        'label' => 'Annual',
        'select' => 'YEAR(date_vaccination) AS period_label, YEAR(date_vaccination) AS sort_year, YEAR(date_vaccination) AS sort_period',
        'group_format' => "DATE_FORMAT(date_vaccination, '%Y-%m-%d')",
        'group' => 'YEAR(date_vaccination)',
        'order' => 'sort_year ASC'
    ]
];

$selectedPeriod = $_GET['period'] ?? 'month';
if (!isset($periodOptions[$selectedPeriod])) {
    $selectedPeriod = 'month';
}

$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportType = isset($_GET['export']) ? strtolower(trim((string)$_GET['export'])) : null;
$allowedExports = ['csv', 'pdf'];
if ($exportType !== null && !in_array($exportType, $allowedExports, true)) {
    $exportType = null;
}
$error = null;

if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $error = 'Invalid date range. Start date must be before end date.';
}

$data = [];
$vaccineData = [];
$topVaccine = null;
$totalCompleted = 0;
$filterMeta = [
    'period_key' => $selectedPeriod,
    'period_label' => $periodOptions[$selectedPeriod]['label'],
    'date_from' => $dateFrom ?: null,
    'date_to' => $dateTo ?: null
];

if (!$error) {
    $conditions = ["status = 'Completed'"];
    $params = [];
    $types = '';

    if ($dateFrom !== '') {
        $conditions[] = 'date_vaccination >= ?';
        $params[] = $dateFrom;
        $types .= 's';
    }

    if ($dateTo !== '') {
        $conditions[] = 'date_vaccination <= ?';
        $params[] = $dateTo;
        $types .= 's';
    }

    $whereSql = ' WHERE ' . implode(' AND ', $conditions);
    $config = $periodOptions[$selectedPeriod];
    $dateListSelect = "GROUP_CONCAT(DISTINCT {$config['group_format']} ORDER BY date_vaccination SEPARATOR ', ') AS date_list";
    $sql = "SELECT {$config['select']}, COUNT(*) AS total_completed, {$dateListSelect} FROM tbl_vaccination_schedule{$whereSql} GROUP BY {$config['group']} ORDER BY {$config['order']}";

    $stmt = $con->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $label = $row['period_label'];
        $count = (int) $row['total_completed'];
        $dateList = [];
        if (!empty($row['date_list'])) {
            $dateList = array_filter(array_map('trim', explode(',', $row['date_list'])));
        }
        $data[] = [
            'label' => $label,
            'count' => $count,
            'dates' => $dateList
        ];
        $totalCompleted += $count;
    }
    $stmt->close();

    $vaccineSql = "SELECT COALESCE(vaccine_name, 'Unknown') AS vaccine_label, COUNT(*) AS total_completed FROM tbl_vaccination_schedule{$whereSql} GROUP BY vaccine_name ORDER BY total_completed DESC, vaccine_label ASC";

    $vaccineStmt = $con->prepare($vaccineSql);
    if ($types !== '') {
        $vaccineStmt->bind_param($types, ...$params);
    }
    $vaccineStmt->execute();
    $vaccineResult = $vaccineStmt->get_result();
    while ($row = $vaccineResult->fetch_assoc()) {
        $label = $row['vaccine_label'];
        $count = (int) $row['total_completed'];
        $vaccineData[] = [
            'label' => $label,
            'count' => $count
        ];
        if ($topVaccine === null) {
            $topVaccine = [
                'label' => $label,
                'count' => $count
            ];
        }
    }
    $vaccineStmt->close();

    if ($exportType !== null) {
        $exportPayload = [
            'summary' => [
                'period_label' => $filterMeta['period_label'],
                'date_from' => $filterMeta['date_from'],
                'date_to' => $filterMeta['date_to'],
                'total_completed' => $totalCompleted
            ],
            'periods' => $data,
            'vaccines' => $vaccineData
        ];

        if ($exportType === 'csv') {
            exportPeriodUsageCsv($exportPayload);
        } else {
            exportPeriodUsagePdf($exportPayload);
        }
    }
}

function getInputValue(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function exportPeriodUsageCsv(array $payload): void
{
    $summary = $payload['summary'] ?? [];
    $periods = $payload['periods'] ?? [];
    $vaccines = $payload['vaccines'] ?? [];

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=completed_vaccination_report.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Completed Vaccination Summary']);
    fputcsv($out, ['Period', $summary['period_label'] ?? '']);
    fputcsv($out, ['Date From', $summary['date_from'] ?? '']);
    fputcsv($out, ['Date To', $summary['date_to'] ?? '']);
    fputcsv($out, ['Total Completed', $summary['total_completed'] ?? 0]);

    fputcsv($out, []);
    fputcsv($out, ['Period Breakdown']);
    fputcsv($out, ['Period', 'Dates', 'Completed Vaccinations']);
    foreach ($periods as $row) {
        $dates = !empty($row['dates']) ? implode(' | ', $row['dates']) : '';
        fputcsv($out, [
            $row['label'] ?? '',
            $dates,
            $row['count'] ?? 0
        ]);
    }

    fputcsv($out, []);
    fputcsv($out, ['Vaccine Usage']);
    fputcsv($out, ['Vaccine', 'Completed Vaccinations']);
    foreach ($vaccines as $row) {
        fputcsv($out, [
            $row['label'] ?? '',
            $row['count'] ?? 0
        ]);
    }

    fclose($out);
    exit;
}

function exportPeriodUsagePdf(array $payload): void
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        header('Content-Type: text/plain', true, 500);
        echo 'PDF export unavailable. Please install dependencies by running composer install.';
        exit;
    }

    require_once $autoload;

    $summary = $payload['summary'] ?? [];
    $periods = $payload['periods'] ?? [];
    $vaccines = $payload['vaccines'] ?? [];

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; }
            h1 { font-size: 20px; margin-bottom: 8px; }
            h2 { font-size: 16px; margin-top: 24px; margin-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            th, td { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 12px; }
            th { background: #f3f4f6; text-align: left; }
            .summary-table td { border: none; padding: 4px 0; }
        </style>
    </head>
    <body>
        <h1>Completed Vaccination Summary</h1>
        <table class="summary-table">
            <tr><td><strong>Period:</strong></td><td><?php echo htmlspecialchars($summary['period_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td><strong>Date From:</strong></td><td><?php echo htmlspecialchars($summary['date_from'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td><strong>Date To:</strong></td><td><?php echo htmlspecialchars($summary['date_to'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td><strong>Total Completed:</strong></td><td><?php echo htmlspecialchars((string)($summary['total_completed'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        </table>

        <h2>Period Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Dates</th>
                    <th style="text-align:right;">Completed Vaccinations</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($periods)): ?>
                    <?php foreach ($periods as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(!empty($row['dates']) ? implode(', ', $row['dates']) : '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="text-align:right;"><?php echo htmlspecialchars((string)($row['count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>Vaccine Usage</h2>
        <table>
            <thead>
                <tr>
                    <th>Vaccine</th>
                    <th style="text-align:right;">Completed Vaccinations</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($vaccines)): ?>
                    <?php foreach ($vaccines as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="text-align:right;"><?php echo htmlspecialchars((string)($row['count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2">No data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('completed_vaccination_report.pdf', ['Attachment' => true]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>
        body {
            background: radial-gradient(circle at top left, #f5f5ff, #f0f9ff);
            font-family: 'Inter', sans-serif;
            color: #1f2937;
        }

        .report-card {
            border-radius: 24px;
            overflow: hidden;
        }

        .report-card .card-header {
            background: linear-gradient(135deg, #4f46e5, #9333ea);
            border: none;
        }

        .report-card .card-header h1 {
            font-weight: 700;
        }

        .report-card .card-header p {
            opacity: 0.85;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1.25rem;
        }

        .modern-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #4b5563;
            margin-bottom: 0.35rem;
        }

        .modern-input, .modern-select {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 0.65rem 0.9rem;
            background: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .modern-input:focus, .modern-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.18);
        }

        .btn-modern {
            border-radius: 12px;
            padding: 0.75rem;
            font-weight: 600;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            border: none;
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-modern:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            box-shadow: 0 15px 30px -20px rgba(79, 70, 229, 0.65);
        }

        .metric-tiles {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
        }

        .metric-tile {
            flex: 1 1 260px;
            background: linear-gradient(145deg, rgba(99, 102, 241, 0.08), rgba(14, 165, 233, 0.08));
            border: 1px solid rgba(99, 102, 241, 0.15);
            border-radius: 18px;
            padding: 1.5rem;
            backdrop-filter: blur(6px);
            position: relative;
        }

        .metric-tile::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            box-shadow: 0 25px 35px -25px rgba(79, 70, 229, 0.45);
            opacity: 0;
            transition: opacity 0.25s ease-in-out;
        }

        .metric-tile:hover::after {
            opacity: 1;
        }

        .metric-label {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .metric-value {
            font-size: 2.1rem;
            font-weight: 700;
            color: #1f2937;
        }

        .metric-subtext {
            font-size: 0.9rem;
            color: #4b5563;
        }

        .section-title {
            font-weight: 600;
            font-size: 1.05rem;
            color: #1f2937;
        }

        .table-modern {
            border-radius: 14px;
            overflow: hidden;
        }

        .table-modern thead th {
            background: #f4f4ff;
            border-bottom: none;
            color: #4338ca;
            font-weight: 600;
        }

        .table-modern tbody tr {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .table-modern tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -20px rgba(79, 70, 229, 0.45);
        }

        .table-modern td {
            vertical-align: middle;
        }

        .date-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .date-list li {
            background: rgba(99, 102, 241, 0.12);
            color: #312e81;
            border-radius: 999px;
            padding: 0.3rem 0.75rem;
            font-size: 0.82rem;
        }

        .badge-most-used {
            background: rgba(6, 182, 212, 0.15);
            color: #0e7490;
            border-radius: 999px;
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            font-weight: 600;
        }

        .empty-state {
            color: #6b7280;
            font-style: italic;
        }

        @media (max-width: 576px) {
            .report-card .card-header {
                text-align: center;
            }

            .metric-tile {
                flex: 1 1 100%;
            }
        }
    </style>
    <title>Completed Vaccination Summary</title>
</head>
<body>
    <div class="container py-5">
        <div class="card report-card shadow-lg border-0">
            <div class="card-header text-white p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h1 class="h3 mb-1">Completed Vaccination Summary</h1>
                        <p class="mb-0">Monitor vaccination performance across time periods and identify the most utilized vaccines.</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-light btn-lg shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-speedometer2"></i>
                            <span>Back to Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-4 p-lg-5">
                <?php if ($error): ?>
                    <div class="alert alert-danger mb-4" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form method="get" class="mb-4" id="reportFilterForm">
                    <input type="hidden" name="export" id="exportInput" value="">
                    <div class="filter-grid">
                        <div>
                            <label class="modern-label" for="periodSelect">Period</label>
                            <select class="form-select modern-select" id="periodSelect" name="period">
                                <?php foreach ($periodOptions as $key => $option): ?>
                                    <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedPeriod === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="modern-label" for="dateFrom">Date from</label>
                            <input type="date" class="form-control modern-input" id="dateFrom" name="date_from" value="<?php echo getInputValue($dateFrom); ?>">
                        </div>
                        <div>
                            <label class="modern-label" for="dateTo">Date to</label>
                            <input type="date" class="form-control modern-input" id="dateTo" name="date_to" value="<?php echo getInputValue($dateTo); ?>">
                        </div>
                        <div class="d-flex flex-column justify-content-end">
                            <button type="submit" class="btn btn-modern w-100">Generate report</button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <button type="button" class="btn btn-outline-secondary px-4" data-export="csv">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export CSV
                        </button>
                        <button type="button" class="btn btn-outline-secondary px-4" data-export="pdf">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                        </button>
                    </div>
                </form>
                <div class="metric-tiles mb-4">
                    <div class="metric-tile metric-tile-primary">
                        <div class="metric-label">Total Vaccines Used</div>
                        <div class="metric-value"><?php echo number_format($totalCompleted); ?></div>
                        <div class="metric-subtext">Completed doses within the selected filters</div>
                    </div>
                    <div class="metric-tile metric-tile-accent">
                        <div class="metric-label">Most Used Vaccine</div>
                        <?php if ($topVaccine): ?>
                            <div class="metric-value fs-3"><?php echo htmlspecialchars($topVaccine['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="metric-subtext"><?php echo number_format($topVaccine['count']); ?> completed</div>
                        <?php else: ?>
                            <div class="metric-subtext">No vaccine usage data available.</div>
                        <?php endif; ?>
                    </div>
                    <div class="metric-tile metric-tile-secondary">
                        <div class="metric-label">Periods Covered</div>
                        <div class="metric-value"><?php echo number_format(count($data)); ?></div>
                        <div class="metric-subtext">Distinct period groups in the report</div>
                    </div>
                </div>
                <div>
                    <div class="section-title mb-3">Period Breakdown</div>
                    <?php if (empty($data)): ?>
                        <p class="empty-state">No completed vaccinations found for the selected filters.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Period</th>
                                        <th scope="col">Dates</th>
                                        <th scope="col" class="text-end">Completed Vaccinations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if (!empty($row['dates'])): ?>
                                                    <ul class="date-list">
                                                        <?php foreach ($row['dates'] as $date): ?>
                                                            <li><?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="empty-state">No dates</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($row['count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-5">
                    <div class="section-title mb-3">Vaccine Usage</div>
                    <?php if (empty($vaccineData)): ?>
                        <p class="empty-state">No vaccine usage data found for the selected filters.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Vaccine</th>
                                        <th scope="col" class="text-end">Completed Vaccinations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vaccineData as $index => $row): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if ($index === 0): ?>
                                                    <span class="badge-most-used ms-2">Most Used</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($row['count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const form = document.getElementById('reportFilterForm');
            if (!form) {
                return;
            }

            const periodSelect = document.getElementById('periodSelect');
            const exportField = document.getElementById('exportInput');
            const exportButtons = form.querySelectorAll('button[data-export]');

            if (periodSelect) {
                periodSelect.addEventListener('change', function() {
                    if (exportField) {
                        exportField.value = '';
                    }
                    form.requestSubmit();
                });
            }

            if (exportButtons.length && exportField) {
                exportButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        const type = button.getAttribute('data-export');
                        if (!type) {
                            return;
                        }
                        exportField.value = type;
                        form.requestSubmit();
                    });
                });
            }

            form.addEventListener('submit', function() {
                if (exportField) {
                    // Reset export flag after submission to avoid unintended repeated exports
                    setTimeout(function() {
                        exportField.value = '';
                    }, 0);
                }
            });
        })();
    </script>
</body>
</html>
