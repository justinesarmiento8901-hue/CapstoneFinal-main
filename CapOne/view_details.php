<?php
session_start();

include 'dbForm.php'; // Include database connection file

if (isset($_GET['parent_id'])) {
    $parent_id = mysqli_real_escape_string($con, $_GET['parent_id']);

    $parentQuery = "SELECT first_name, last_name FROM parents WHERE id = '$parent_id' LIMIT 1";
    $parentResult = mysqli_query($con, $parentQuery);
    $parent = mysqli_fetch_assoc($parentResult);
    if (!$parent) {
        echo "Parent not found.";
        exit;
    }

    $vaccineReference = [];
    $referenceStageMap = [];
    $referenceQuery = "SELECT vaccine_name, disease_prevented, age_stage FROM tbl_vaccine_reference ORDER BY FIELD(age_stage,'Birth','1½ mo','2½ mo','3½ mo','9 mo','1 yr'), vaccine_name ASC";
    $referenceResult = mysqli_query($con, $referenceQuery);
    if ($referenceResult) {
        while ($refRow = mysqli_fetch_assoc($referenceResult)) {
            $vaccineReference[] = $refRow;
            $vaccineName = $refRow['vaccine_name'] ?? '';
            $ageStage = $refRow['age_stage'] ?? '';
            if ($vaccineName !== '' && $ageStage !== '') {
                $referenceStageMap[$vaccineName][] = $ageStage;
            }
        }
    }

    $infantQuery = "SELECT id, CONCAT_WS(' ', firstname, middlename, surname) AS full_name, dateofbirth, weight, height, sex FROM infantinfo WHERE parent_id = '$parent_id'";
    $infantResult = mysqli_query($con, $infantQuery);
    $infants = [];
    if ($infantResult) {
        while ($infantRow = mysqli_fetch_assoc($infantResult)) {
            $infantId = (int) $infantRow['id'];
            $fullName = trim(preg_replace('/\s+/', ' ', $infantRow['full_name']));
            $infantRow['full_name'] = $fullName;
            $statusMap = [];
            foreach ($referenceStageMap as $refVaccine => $stages) {
                foreach ($stages as $refStage) {
                    $statusMap[$refVaccine][$refStage] = [
                        'status' => 'N/A',
                        'updated_at' => '',
                        'vaccinated_by' => ''
                    ];
                }
            }
            $historyRows = [];
            $scheduledStages = [];
            $scheduleQuery = "SELECT vaccine_name, stage, status, vaccinatedby FROM tbl_vaccination_schedule WHERE infant_id = $infantId";
            $scheduleResult = mysqli_query($con, $scheduleQuery);
            if ($scheduleResult) {
                while ($scheduleRow = mysqli_fetch_assoc($scheduleResult)) {
                    $vacName = $scheduleRow['vaccine_name'] ?? '';
                    if ($vacName === '') {
                        continue;
                    }
                    $stageName = $scheduleRow['stage'] ?? '';
                    if ($stageName === '' || $stageName === null) {
                        $stageList = $referenceStageMap[$vacName] ?? [];
                        $stageName = $stageList[0] ?? '';
                    }
                    if ($stageName === '') {
                        continue;
                    }
                    $scheduledStages[$vacName][$stageName] = true;
                    $scheduleStatus = ($scheduleRow['status'] === 'Completed') ? 'Completed' : 'Pending';
                    $statusMap[$vacName][$stageName] = [
                        'status' => $scheduleStatus,
                        'updated_at' => '',
                        'vaccinated_by' => $scheduleRow['vaccinatedby'] ?? ''
                    ];
                }
            }

            $detailsQuery = "SELECT vaccine_name, stage, status, updated_at FROM tbl_vaccination_details WHERE infant_id = $infantId";
            $detailsResult = mysqli_query($con, $detailsQuery);
            if ($detailsResult) {
                while ($detailRow = mysqli_fetch_assoc($detailsResult)) {
                    $vaccineName = $detailRow['vaccine_name'] ?? '';
                    $stage = $detailRow['stage'] ?? '';
                    $statusValue = $detailRow['status'] ?? 'N/A';
                    if ($vaccineName === '' || $stage === '') {
                        continue;
                    }
                    if ($stage === '' || $stage === null) {
                        $stageList = $referenceStageMap[$vaccineName] ?? [];
                        $stage = $stageList[0] ?? '';
                    }
                    if ($stage === '') {
                        continue;
                    }
                    $normalizedStatus = 'N/A';
                    if (strcasecmp($statusValue, 'Completed') === 0) {
                        $normalizedStatus = 'Completed';
                    } elseif (strcasecmp($statusValue, 'Pending') === 0 && isset($scheduledStages[$vaccineName][$stage])) {
                        $normalizedStatus = 'Pending';
                    }
                    if ($normalizedStatus !== 'N/A') {
                        $statusMap[$vaccineName][$stage]['status'] = $normalizedStatus;
                        $statusMap[$vaccineName][$stage]['updated_at'] = $detailRow['updated_at'] ?? '';
                    }
                    if ($normalizedStatus === 'Completed') {
                        $historyRows[] = $detailRow;
                    }
                }
            }

            $vaccinationDetails = [];
            $completedCount = 0;
            $totalVaccines = count($vaccineReference);
            foreach ($vaccineReference as $referenceRow) {
                $vaccineName = $referenceRow['vaccine_name'];
                $stage = $referenceRow['age_stage'];
                $detail = $statusMap[$vaccineName][$stage] ?? null;
                $status = $detail['status'] ?? 'N/A';
                if ($status === 'Completed') {
                    $completedCount++;
                }
                $vaccinationDetails[] = [
                    'vaccine_name' => $vaccineName,
                    'disease_prevented' => $referenceRow['disease_prevented'],
                    'stage' => $stage,
                    'status' => $status,
                    'updated_at' => $detail['updated_at'] ?? '',
                    'vaccinated_by' => $detail['vaccinated_by'] ?? ''
                ];
            }
            $infantRow['vaccinations'] = $vaccinationDetails;
            $infantRow['progress_total'] = $totalVaccines;
            $infantRow['progress_completed'] = $completedCount;
            $infantRow['progress_percent'] = $totalVaccines > 0 ? round(($completedCount / $totalVaccines) * 100) : 0;
            usort($historyRows, function ($a, $b) {
                $timeA = !empty($a['updated_at']) ? strtotime($a['updated_at']) : 0;
                $timeB = !empty($b['updated_at']) ? strtotime($b['updated_at']) : 0;
                if ($timeA === $timeB) {
                    return 0;
                }
                return ($timeA < $timeB) ? 1 : -1;
            });

            $growthHistory = [];
            $growthStmt = $con->prepare("SELECT record_date, previous_weight, previous_height, growth_status FROM infant_previous_records WHERE infant_id = ? ORDER BY id DESC LIMIT 5");
            if ($growthStmt) {
                $growthStmt->bind_param('i', $infantId);
                if ($growthStmt->execute()) {
                    $growthResult = $growthStmt->get_result();
                    while ($growthRow = $growthResult->fetch_assoc()) {
                        $growthHistory[] = $growthRow;
                    }
                }
                $growthStmt->close();
            }

            $infantRow['recent_history'] = $growthHistory;
            $infants[] = $infantRow;
        }
    }
} else {
    echo "Parent ID is required.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <title>Infant Records</title>
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Parent: <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?></h2>
                <p class="text-muted mb-0">Showing infants and vaccination records linked to this parent.</p>
            </div>
            <a href="viewinfant.php" class="btn btn-primary">Back to Infant Record</a>
        </div>

        <?php if (!empty($infants)): ?>
            <?php foreach ($infants as $infant): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Infant: <?php echo htmlspecialchars($infant['full_name']); ?> (ID: <?php echo (int) $infant['id']; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $tableId = 'vaccination-table-' . (int) $infant['id'];
                        $exportBaseName = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $infant['full_name']));
                        $csvFilename = $exportBaseName . '_vaccination.csv';
                        $pdfFilename = $exportBaseName . '_vaccination.pdf';
                        $pdfTitle = $infant['full_name'] . ' Vaccination Records';
                        ?>
                        <h6>Vaccination Progress</h6>
                        <?php if ($infant['progress_total'] > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Completed Vaccines</span>
                                    <span class="text-muted"><?php echo $infant['progress_completed']; ?> / <?php echo $infant['progress_total']; ?> (<?php echo $infant['progress_percent']; ?>%)</span>
                                </div>
                                <div class="progress" style="height: 24px;">
                                    <div class="progress-bar bg-success" role="progressbar"
                                        style="width: <?php echo $infant['progress_percent']; ?>%;"
                                        aria-valuenow="<?php echo $infant['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $infant['progress_percent']; ?>%
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No vaccine reference data available.</p>
                        <?php endif; ?>

                        <h6 class="mt-4">Vaccination Records</h6>
                        <?php if (!empty($infant['vaccinations'])): ?>
                            <div class="table-responsive">
                                <?php
                                $historyPayload = [];
                                if (!empty($infant['recent_history'])) {
                                    foreach ($infant['recent_history'] as $historyItem) {
                                        $historyPayload[] = [
                                            'date' => !empty($historyItem['record_date']) ? date('M d, Y', strtotime($historyItem['record_date'])) : '—',
                                            'height' => isset($historyItem['previous_height']) && $historyItem['previous_height'] !== null ? number_format((float) $historyItem['previous_height'], 1) : '—',
                                            'weight' => isset($historyItem['previous_weight']) && $historyItem['previous_weight'] !== null ? number_format((float) $historyItem['previous_weight'], 1) : '—',
                                            'remarks' => ($historyItem['growth_status'] ?? '') !== '' ? $historyItem['growth_status'] : '—'
                                        ];
                                    }
                                }
                                $historyJson = htmlspecialchars(json_encode($historyPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                ?>
                                <div class="d-flex justify-content-end flex-wrap gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm export-table-btn"
                                        data-target="<?php echo htmlspecialchars($tableId); ?>"
                                        data-format="csv"
                                        data-filename="<?php echo htmlspecialchars($csvFilename); ?>"
                                        data-title="<?php echo htmlspecialchars($pdfTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-history="<?php echo $historyJson; ?>">
                                        Export CSV
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm export-table-btn"
                                        data-target="<?php echo htmlspecialchars($tableId); ?>"
                                        data-format="pdf"
                                        data-filename="<?php echo htmlspecialchars($pdfFilename); ?>"
                                        data-title="<?php echo htmlspecialchars($pdfTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-history="<?php echo $historyJson; ?>">
                                        Export PDF
                                    </button>
                                </div>
                                <table id="<?php echo htmlspecialchars($tableId); ?>" class="table table-bordered table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Vaccinated By</th>
                                            <th>Vaccine</th>
                                            <th>Stage</th>
                                            <th>Status</th>
                                            <th>Date Complete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($infant['vaccinations'] as $record): ?>
                                            <?php
                                            if ($record['status'] === 'Completed') {
                                                $statusClass = 'bg-success text-white';
                                            } elseif ($record['status'] === 'Pending') {
                                                $statusClass = 'bg-warning text-dark';
                                            } else {
                                                $statusClass = 'bg-secondary text-white';
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['vaccinated_by'] !== '' ? $record['vaccinated_by'] : '—'); ?></td>
                                                <td><?php echo htmlspecialchars($record['vaccine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['stage']); ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                                <td>
                                                    <?php if ($record['status'] === 'Completed' && !empty($record['updated_at'])): ?>
                                                        <?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($record['updated_at']))); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No vaccination records found for this infant.</p>
                        <?php endif; ?>

                        <h6 class="mt-4">Recent History</h6>
                        <?php if (!empty($infant['recent_history'])): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date Completed</th>
                                            <th>Height (cm)</th>
                                            <th>Weight (kg)</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($infant['recent_history'] as $history): ?>
                                            <?php
                                            $dateCompleted = !empty($history['record_date']) ? date('M d, Y', strtotime($history['record_date'])) : '—';
                                            $height = isset($history['previous_height']) && $history['previous_height'] !== null ? number_format((float) $history['previous_height'], 1) : '—';
                                            $weight = isset($history['previous_weight']) && $history['previous_weight'] !== null ? number_format((float) $history['previous_weight'], 1) : '—';
                                            $remarks = $history['growth_status'] ?? '';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dateCompleted); ?></td>
                                                <td><?php echo htmlspecialchars($height); ?></td>
                                                <td><?php echo htmlspecialchars($weight); ?></td>
                                                <td><?php echo htmlspecialchars($remarks !== '' ? $remarks : '—'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent growth history recorded.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No infants found for this parent.</div>
        <?php endif; ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function decodeHtml(str) {
                var txt = document.createElement('textarea');
                txt.innerHTML = str;
                return txt.value;
            }

            function escapeForHtml(str) {
                return str.replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function formatTimestamp(date) {
                function pad(value) {
                    return value.toString().padStart(2, '0');
                }
                var month = pad(date.getMonth() + 1);
                var day = pad(date.getDate());
                var year = date.getFullYear().toString().slice(-2);
                var hours = date.getHours();
                var period = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                if (hours === 0) {
                    hours = 12;
                }
                var minutes = pad(date.getMinutes());
                return month + '/' + day + '/' + year + ' ' + hours + ':' + minutes + ' ' + period;
            }

            var exportButtons = document.querySelectorAll('.export-table-btn');
            exportButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var tableId = button.getAttribute('data-target');
                    var format = button.getAttribute('data-format') || 'csv';
                    var table = document.getElementById(tableId);
                    if (!table) {
                        return;
                    }

                    if (format === 'pdf') {
                        var titleAttr = button.getAttribute('data-title') || 'Vaccination Records';
                        var filename = button.getAttribute('data-filename') || 'export.pdf';
                        var historyAttr = button.getAttribute('data-history') || '[]';
                        var title = decodeHtml(titleAttr);
                        var historyJson = decodeHtml(historyAttr);
                        var historyEntries = [];
                        var generatedAt = new Date();
                        var formattedTimestamp = formatTimestamp(generatedAt);
                        try {
                            historyEntries = JSON.parse(historyJson);
                        } catch (err) {
                            historyEntries = [];
                        }
                        if (!window.jspdf || !window.jspdf.jsPDF || typeof window.jspdf.jsPDF !== 'function' || !window.jspdf.jsPDF.API.autoTable) {
                            alert('PDF export library failed to load. Please try again later.');
                            return;
                        }
                        var jsPDF = window.jspdf.jsPDF;
                        var doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'letter' });
                        doc.setFontSize(14);
                        doc.text(title, 40, 40);
                        doc.setFontSize(11);
                        doc.text('Generated: ' + formattedTimestamp, 40, 60);
                        var headers = [['Vaccinated By', 'Vaccine', 'Stage', 'Status', 'Date Complete']];
                        var bodyRows = Array.from(table.querySelectorAll('tbody tr')).map(function (row) {
                            var cells = Array.from(row.querySelectorAll('td')).map(function (cell) {
                                return cell.innerText;
                            });
                            while (cells.length < 5) {
                                cells.push('');
                            }
                            return cells.slice(0, 5);
                        });
                        doc.autoTable({
                            head: headers,
                            body: bodyRows,
                            startY: 110,
                            styles: {
                                fontSize: 10,
                                cellPadding: 6
                            },
                            headStyles: {
                                fillColor: [20, 99, 196]
                            }
                        });
                        if (historyEntries.length) {
                            var baseY = doc.lastAutoTable && doc.lastAutoTable.finalY ? doc.lastAutoTable.finalY + 30 : 120;
                            doc.setFontSize(12);
                            doc.text('Recent History', 40, baseY);
                            var historyRows = historyEntries.map(function (item) {
                                return [
                                    item.date || '',
                                    item.height || '',
                                    item.weight || '',
                                    item.remarks || ''
                                ];
                            });
                            doc.autoTable({
                                head: [['Date Completed', 'Height (cm)', 'Weight (kg)', 'Remarks']],
                                body: historyRows,
                                startY: baseY + 10,
                                styles: {
                                    fontSize: 10,
                                    cellPadding: 6
                                },
                                headStyles: {
                                    fillColor: [20, 99, 196]
                                }
                            });
                        }
                        doc.save(filename);
                        return;
                    }

                    var filename = button.getAttribute('data-filename') || 'export.csv';
                    var title = decodeHtml(button.getAttribute('data-title') || 'Vaccination Records');
                    var generatedAt = new Date();
                    var formattedTimestamp = formatTimestamp(generatedAt);
                    var dataRows = Array.from(table.querySelectorAll('tbody tr')).map(function (row) {
                        var cells = Array.from(row.querySelectorAll('td')).map(function (cell) {
                            return cell.innerText;
                        });
                        while (cells.length < 4) {
                            cells.push('');
                        }
                        return cells;
                    });
                    var historyEntries = [];
                    var historyAttr = button.getAttribute('data-history') || '[]';
                    try {
                        historyEntries = JSON.parse(decodeHtml(historyAttr));
                    } catch (err) {
                        historyEntries = [];
                    }
                    var headerRow = ['Vaccinated By', 'Vaccine', 'Stage', 'Status', 'Date Complete'];
                    var csvRows = [
                        ['Report Title', title],
                        ['Generated On', formattedTimestamp],
                        [],
                        headerRow
                    ].concat(dataRows);
                    if (historyEntries.length) {
                        csvRows.push([]);
                        csvRows.push(['Recent History']);
                        csvRows.push(['Date Completed', 'Height (cm)', 'Weight (kg)', 'Remarks']);
                        historyEntries.forEach(function (item) {
                            csvRows.push([
                                item.date || '',
                                item.height || '',
                                item.weight || '',
                                item.remarks || ''
                            ]);
                        });
                    }
                    var csv = csvRows.map(function (row) {
                        return row.map(function (text) {
                            var safeText = (text || '').replace(/"/g, '""');
                            if (safeText.search(/("|,|\n)/g) >= 0) {
                                safeText = '"' + safeText + '"';
                            }
                            return safeText;
                        }).join(',');
                    }).join('\n');
                    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                    if (navigator.msSaveBlob) {
                        navigator.msSaveBlob(blob, filename);
                    } else {
                        var link = document.createElement('a');
                        var url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', filename);
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>