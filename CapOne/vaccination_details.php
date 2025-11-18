<?php
include 'dbForm.php';

// 1. Get infant_id
$infant_id = 0;
if (isset($_GET['id'])) $infant_id = intval($_GET['id']);
elseif (isset($_GET['infant_id'])) $infant_id = intval($_GET['infant_id']);
if ($infant_id <= 0) exit("<script>alert('Invalid infant ID'); window.location.href='vaccination_schedule.php';</script>");

// 2. Fetch infant + parent info
$sql = "SELECT 
            i.id AS infant_id,
            CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name,
            i.dateofbirth,
            i.sex,
            CONCAT_WS(' ', p.first_name, p.last_name) AS parent_name,
            p.phone AS parent_contact
        FROM infantinfo i
        JOIN parents p ON i.parent_id = p.id
        WHERE i.id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $infant_id);
$stmt->execute();
$infant = $stmt->get_result()->fetch_assoc();
if (!$infant) exit("<script>alert('Infant not found'); window.location.href='vaccination_schedule.php';</script>");

// 3. Fetch vaccine references
$refResult = $con->query("SELECT * FROM tbl_vaccine_reference ORDER BY 
    FIELD(age_stage,'Birth','1½ mo','2½ mo','3½ mo','9 mo','1 yr'), id ASC");
$vaccine_reference = [];
$referenceStageMap = [];
while ($row = $refResult->fetch_assoc()) {
    $vaccine_reference[] = $row;
    $vaccineName = $row['vaccine_name'] ?? '';
    $ageStage = $row['age_stage'] ?? '';
    if ($vaccineName !== '' && $ageStage !== '') {
        $status_map[$vaccineName][$ageStage] = 'N/A';
        if (!isset($referenceStageMap[$vaccineName])) {
            $referenceStageMap[$vaccineName] = [];
        }
        $referenceStageMap[$vaccineName][] = $ageStage;
    }
}

// 4. Fetch vaccination status from tbl_vaccination_schedule
$schedStmt = $con->prepare("SELECT vaccine_name, stage, status FROM tbl_vaccination_schedule WHERE infant_id=?");
$schedStmt->bind_param("i", $infant_id);
$schedStmt->execute();
$scheduleResult = $schedStmt->get_result();
while ($row = $scheduleResult->fetch_assoc()) {
    $vaccineName = $row['vaccine_name'] ?? '';
    if ($vaccineName === '') {
        continue;
    }
    $stage = $row['stage'] ?? '';
    if ($stage === '' || $stage === null) {
        $stageList = $referenceStageMap[$vaccineName] ?? [];
        $stage = $stageList[0] ?? '';
    }
    if ($stage === '') {
        continue;
    }
    $statusValue = ($row['status'] === 'Completed') ? 'Completed' : 'Pending';
    $status_map[$vaccineName][$stage] = $statusValue;
}
$schedStmt->close();

// 5. Define stages
$stages = [
    'Birth' => 'Pagkapanganak',
    '1½ mo' => '1½ Buwan',
    '2½ mo' => '2½ Buwan',
    '3½ mo' => '3½ Buwan',
    '9 mo' => '9 Buwan',
    '1 yr' => '1 Taon'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Vaccination Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table thead th {
            background-color: #0d6efd;
            color: white;
            text-align: center;
            vertical-align: middle;
        }

        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .badge-status {
            font-size: 0.9rem;
            padding: 0.6em 1em;
            border-radius: 1rem;
        }
    </style>
</head>

<body class="container py-4">

    <div class="card shadow-sm p-4">
        <h4 class="mb-3 text-primary">Infant Vaccination Details</h4>

        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Infant Name:</strong> <?= htmlspecialchars($infant['infant_name']); ?><br>
                <strong>Date of Birth:</strong> <?= htmlspecialchars($infant['dateofbirth']); ?><br>
                <strong>Sex:</strong> <?= htmlspecialchars($infant['sex']); ?>
            </div>
            <div class="col-md-6">
                <strong>Parent Name:</strong> <?= htmlspecialchars($infant['parent_name']); ?><br>
                <strong>Contact No.:</strong> <?= htmlspecialchars($infant['parent_contact']); ?>
            </div>
            <div class="col-md-12">
                <a href="vaccination_schedule.php" class="btn btn-secondary mt-3">← Back to Schedule</a>
            </div>
        </div>

        <hr>

        <?php
        // Calculate progress
        $total_vaccines = count($vaccine_reference);
        $completed_count = 0;
        foreach ($vaccine_reference as $vaccine) {
            $stage = $vaccine['age_stage'];
            $status = $status_map[$vaccine['vaccine_name']][$stage] ?? 'N/A';
            if ($status === 'Completed') $completed_count++;
        }
        $progress_percent = $total_vaccines > 0 ? round(($completed_count / $total_vaccines) * 100) : 0;
        ?>

        <h5 class="text-secondary mb-3">Vaccination Progress</h5>
        <div class="mb-4">
            <div class="progress" style="height:25px;">
                <div class="progress-bar bg-success fw-bold" role="progressbar"
                    style="width:<?= $progress_percent; ?>%">
                    <?= $completed_count; ?> / <?= $total_vaccines; ?> Completed (<?= $progress_percent; ?>%)
                </div>
            </div>
        </div>

        <h5 class="text-secondary mb-3">Vaccination Checklist</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead>
                    <tr>
                        <th>Bakuna</th>
                        <th>Sakit na Maiiwasan</th>
                        <?php foreach ($stages as $label) echo "<th>$label</th>"; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vaccine_reference as $vaccine): ?>
                        <tr>
                            <td><?= htmlspecialchars($vaccine['vaccine_name']); ?></td>
                            <td><?= htmlspecialchars($vaccine['disease_prevented']); ?></td>
                            <?php
                            foreach ($stages as $db_stage => $label):
                                if ($vaccine['age_stage'] === $db_stage):
                                    $status = $status_map[$vaccine['vaccine_name']][$db_stage] ?? 'N/A';
                                    if ($status === 'Completed') {
                                        $badgeClass = 'bg-success text-white';
                                    } elseif ($status === 'Pending') {
                                        $badgeClass = 'bg-warning text-dark';
                                    } else {
                                        $status = 'N/A';
                                        $badgeClass = 'bg-secondary text-white';
                                    }
                                    $displayStatus = htmlspecialchars($status);
                                    echo "<td><span class='badge $badgeClass badge-status'>$displayStatus</span></td>";
                                else:
                                    echo "<td class='bg-light text-muted'>—</td>";
                                endif;
                            endforeach;
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>