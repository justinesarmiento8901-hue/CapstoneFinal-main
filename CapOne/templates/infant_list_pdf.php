<?php
if (!isset($rowsData, $filtersData)) {
    $rowsData = [];
    $filtersData = [];
}

// Try to locate header logos and convert to base64
$logoLeftSrc = '';
$logoRightSrc = '';
$baseDir = realpath(__DIR__ . '/../');
$logoDir = $baseDir . DIRECTORY_SEPARATOR . 'header logo';
$images = [];
if (is_dir($logoDir)) {
    $images = glob($logoDir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE) ?: [];
    sort($images, SORT_NATURAL | SORT_FLAG_CASE);
}
if (!empty($images)) {
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
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Infant List</title>
<style>
    body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; margin:18px; color:#222}
    .pdf-header{display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #0b5ed7; padding-bottom:8px; margin-bottom:10px}
    .pdf-header-table { width:100%; border-collapse:collapse; border:none; }
    .pdf-header-table td { vertical-align:middle; border:none; }
    .pdf-header-table .left, .pdf-header-table .right { width:15%; text-align:center; padding:6px 8px; }
    .pdf-header-table .center { width:70%; text-align:center; padding:6px 8px; }
    .pdf-logo { height:56px; max-width:120px; width:auto; display:inline-block }
    .pdf-rule {height:4px; background:#0b5ed7; margin-top:8px}
    .pdf-band {background:#f59c00; height:20px; margin-top:6px; color:#000; font-weight:700; text-align:center; line-height:20px}
    .pdf-org {font-size:14px; font-weight:700; margin:0}
    .pdf-sub {font-size:11px; margin:0; color:#2b2b2b}
    .pdf-title{font-size:15px; font-weight:700; margin:0}
    .pdf-subtitle{font-size:11px; margin:0; color:#444}
    table{width:100%; border-collapse:collapse; margin-top:8px}
    th,td{border:1px solid #666; padding:6px 8px; text-align:left; font-size:11px}
    th{background:#f5f5f5}
    .meta{font-size:11px; margin-top:6px}
</style>
</head>
<body>
    <table class="pdf-header-table">
        <tr>
            <td class="left">
                <?php if (!empty($logoLeftSrc)): ?><img class="pdf-logo" src="<?php echo $logoLeftSrc; ?>" alt="left"><?php endif; ?>
            </td>
            <td class="center">
                <p class="pdf-org">REPUBLIC OF THE PHILIPPINES</p>
                <p class="pdf-org">RHU ALIAGA</p>
                <p class="pdf-sub">Municipality of Aliaga, Nueva Ecija</p>
            </td>
            <td class="right">
                <?php if (!empty($logoRightSrc)): ?><img class="pdf-logo" src="<?php echo $logoRightSrc; ?>" alt="right"><?php endif; ?>
            </td>
        </tr>
    </table>
    <div class="pdf-rule"></div>
    <div class="pdf-band">INFANT LIST</div>

    <div class="meta">
        <div><strong>Generated:</strong> <?php echo date('m/d/Y h:i A'); ?></div>
        <?php if (!empty($filtersData['search']) || !empty($filtersData['sex']) || !empty($filtersData['birth_from']) || !empty($filtersData['birth_to'])): ?>
        <div style="margin-top:6px">
            <?php if (!empty($filtersData['search'])): ?><strong>Search:</strong> <?php echo htmlspecialchars($filtersData['search']); ?>; <?php endif; ?>
            <?php if (isset($filtersData['sex']) && $filtersData['sex'] !== ''): ?><strong>Sex:</strong> <?php echo htmlspecialchars($filtersData['sex']); ?>; <?php endif; ?>
            <?php if (!empty($filtersData['birth_from']) || !empty($filtersData['birth_to'])): ?>
                <strong>Birth range:</strong>
                <?php echo htmlspecialchars($filtersData['birth_from'] ?: 'Any'); ?> - <?php echo htmlspecialchars($filtersData['birth_to'] ?: 'Any'); ?>;
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Date of Birth</th>
                <th>Place of Birth</th>
                <th>Sex</th>
                <th>Weight</th>
                <th>Height</th>
                <th>Nationality</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rowsData)): ?>
                <tr><td colspan="8" style="text-align:center">No data for selected filters.</td></tr>
            <?php else: ?>
                <?php foreach ($rowsData as $i => $r): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo htmlspecialchars($r['full_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['dateofbirth'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['placeofbirth'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['sex'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['weight'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['height'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['nationality'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>