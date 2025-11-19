<?php
if (!isset($rowsData, $filtersData)) {
    $rowsData = [];
    $filtersData = [];
}

// Try to locate header logos and convert to base64 so dompdf can embed them reliably.
$logoLeftSrc = '';
$logoRightSrc = '';
// Paths relative to this template file
$baseDir = realpath(__DIR__ . '/../'); // CapOne directory
$logoDir = $baseDir . DIRECTORY_SEPARATOR . 'header logo';
// Find image files in the header logo directory and pick first two (sorted)
$images = [];
if (is_dir($logoDir)) {
    $images = glob($logoDir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE) ?: [];
    sort($images, SORT_NATURAL | SORT_FLAG_CASE);
}
if (!empty($images)) {
    // Left logo = first image
    $leftPath = $images[0];
    $data = @file_get_contents($leftPath);
    if ($data !== false) {
        $mime = mime_content_type($leftPath) ?: 'image/jpeg';
        $logoLeftSrc = 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
if (isset($images[1])) {
    $rightPath = $images[1];
    $data2 = @file_get_contents($rightPath);
    if ($data2 !== false) {
        $mime2 = mime_content_type($rightPath) ?: 'image/jpeg';
        $logoRightSrc = 'data:' . $mime2 . ';base64,' . base64_encode($data2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vaccination Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }
        .pdf-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0b5ed7;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .pdf-header-table { width:100%; border-collapse:collapse; border:none; }
        .pdf-header-table td { vertical-align:middle; border:none; }
        .pdf-header-table .left, .pdf-header-table .right { width:15%; text-align:center; padding:6px 8px; }
        .pdf-header-table .center { width:70%; text-align:center; padding:6px 8px; }
        .pdf-logo { height:56px; max-width:120px; width:auto; display:inline-block }
        .pdf-rule {height:4px; background:#0b5ed7; margin-top:8px}
        .pdf-band {background:#f59c00; height:20px; margin-top:6px; color:#000; font-weight:700; text-align:center; line-height:20px}
        .pdf-org {font-size:14px; font-weight:700; margin:0}
        .pdf-sub {font-size:11px; margin:0; color:#2b2b2b}
        .pdf-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            line-height: 1.1;
        }
        .pdf-subtitle {
            font-size: 12px;
            margin: 0;
            color: #444;
        }
        h1 {
            text-align: center;
            margin-bottom: 10px;
        }
        .meta {
            margin-bottom: 15px;
        }
        .meta span {
            display: inline-block;
            margin-right: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #666;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="pdf-header-table">
        <tr>
            <td class="left">
                <?php if (!empty($logoLeftSrc)): ?>
                    <img class="pdf-logo" src="<?php echo $logoLeftSrc; ?>" alt="Logo left">
                <?php endif; ?>
            </td>
            <td class="center">
                <p class="pdf-org">REPUBLIC OF THE PHILIPPINES</p>
                <p class="pdf-org">RHU ALIAGA</p>
                <p class="pdf-sub">Municipality of Aliaga, Nueva Ecija</p>
            </td>
            <td class="right">
                <?php if (!empty($logoRightSrc)): ?>
                    <img class="pdf-logo" src="<?php echo $logoRightSrc; ?>" alt="Logo right">
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <div class="pdf-rule"></div>
    <div class="pdf-band">INFANT VACCINATION REPORT</div>

    <div class="meta">
        <span><strong>Generated:</strong> <?php echo date('m/d/y h:i A'); ?></span>
        <?php if (!empty($filtersData['date_from']) || !empty($filtersData['date_to'])): ?>
            <span><strong>Date Range:</strong>
                <?php echo htmlspecialchars($filtersData['date_from'] ?? 'Any'); ?>
                &ndash;
                <?php echo htmlspecialchars($filtersData['date_to'] ?? 'Any'); ?>
            </span>
        <?php endif; ?>
        <?php if (!empty($filtersData['status']) && $filtersData['status'] !== 'All'): ?>
            <span><strong>Status:</strong> <?php echo htmlspecialchars($filtersData['status']); ?></span>
        <?php endif; ?>
        <?php if (!empty($filtersData['vaccine'])): ?>
            <span><strong>Vaccine:</strong> <?php echo htmlspecialchars($filtersData['vaccine']); ?></span>
        <?php endif; ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Infant</th>
                <th>Parent</th>
                <th>Barangay</th>
                <th>Vaccine</th>
                <th>Date</th>
                <th>Next Dose</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rowsData)): ?>
                <tr>
                    <td colspan="9" class="text-center">No data for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rowsData as $index => $row): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($row['infant_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['parent_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['barangay'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['vaccine_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['date_vaccination'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['next_dose_date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
