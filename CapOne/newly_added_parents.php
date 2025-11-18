<?php
session_start();
require_once __DIR__ . '/dbForm.php';

$allowedFilters = ['recent', 'barangay', 'day', 'week', 'month'];
$filter = isset($_GET['sort']) ? strtolower($_GET['sort']) : 'recent';
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'recent';
}

$filterLabels = [
    'recent' => 'All recent parents',
    'barangay' => 'Sorted by barangay (A-Z)',
    'day' => 'Added today',
    'week' => 'Added this week',
    'month' => 'Added this month',
];

$whereClause = '';
$orderClause = 'created_at DESC, id DESC';

switch ($filter) {
    case 'barangay':
        $orderClause = 'barangay_name ASC, created_at DESC, id DESC';
        break;
    case 'day':
        $whereClause = 'WHERE DATE(created_at) = CURDATE()';
        break;
    case 'week':
        $whereClause = 'WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)';
        break;
    case 'month':
        $whereClause = 'WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())';
        break;
    default:
        break;
}

$sql = "SELECT id, first_name, last_name, email, COALESCE(NULLIF(barangay, ''), NULLIF(baranggay, ''), 'N/A') AS barangay_name, created_at FROM parents $whereClause ORDER BY $orderClause";
$result = mysqli_query($con, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newly Added Parents</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
<div class="container-fluid mt-4">
    <div class="card card-shadow">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h3 class="dashboard-title mb-0"><i class="bi bi-person-plus"></i> Newly Added Parents</h3>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-speedometer2"></i> Back to Dashboard
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <span class="text-muted small">Showing: <?php echo htmlspecialchars($filterLabels[$filter] ?? $filterLabels['recent']); ?></span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Filter parents">
                    <a href="?sort=recent" class="btn btn-outline-primary<?php echo $filter === 'recent' ? ' active' : ''; ?>">All</a>
                    <a href="?sort=barangay" class="btn btn-outline-primary<?php echo $filter === 'barangay' ? ' active' : ''; ?>">Barangay</a>
                    <a href="?sort=day" class="btn btn-outline-primary<?php echo $filter === 'day' ? ' active' : ''; ?>">Today</a>
                    <a href="?sort=week" class="btn btn-outline-primary<?php echo $filter === 'week' ? ' active' : ''; ?>">This Week</a>
                    <a href="?sort=month" class="btn btn-outline-primary<?php echo $filter === 'month' ? ' active' : ''; ?>">This Month</a>
                </div>
            </div>
            <div class="table-modern table-modern-elevated">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Full Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Barangay</th>
                            <th scope="col">Created At</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['barangay_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at'] ? date('M d, Y h:i A', strtotime($row['created_at'])) : 'N/A'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td class="text-center" colspan="5">No parent records found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
if ($result) {
    mysqli_free_result($result);
}
mysqli_close($con);
?>
