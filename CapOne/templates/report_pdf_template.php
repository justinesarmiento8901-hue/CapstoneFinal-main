<?php
if (!isset($rowsData, $filtersData)) {
    $rowsData = [];
    $filtersData = [];
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
    <h1>RHU Aliaga Vaccination Report</h1>
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
