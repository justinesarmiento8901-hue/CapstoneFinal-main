<?php
require 'dbForm.php';
session_start();
$role = $_SESSION['user']['role'] ?? '';

if (!isset($_SESSION['user']) || $role !== 'admin') {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>";
    exit;
}

$query = "SELECT al.id, al.user_id, u.email AS user_email, al.action, al.entity_table, al.entity_id, al.description, al.ip_address, al.created_at
           FROM audit_logs al
           LEFT JOIN users u ON al.user_id = u.id
           ORDER BY al.created_at DESC";
$result = $con->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Trail</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h3 class="mb-0"><i class="bi bi-clipboard-data"></i> Audit Trail</h3>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        <div class="card card-shadow">
            <div class="card-body table-modern table-modern-elevated">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Record ID</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php $i = 1; ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($row['user_email'] ?? 'System') ?></td>
                                        <td>
                                            <?php
                                            switch ($row['action']) {
                                                case 'add':
                                                    echo '<span class="badge bg-success">Add</span>';
                                                    break;
                                                case 'edit':
                                                    echo '<span class="badge bg-primary">Edit</span>';
                                                    break;
                                                case 'delete':
                                                    echo '<span class="badge bg-danger">Delete</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-info text-dark">View</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['entity_table']) ?></td>
                                        <td><?= htmlspecialchars($row['entity_id']) ?></td>
                                        <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['ip_address'] ?? 'N/A') ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No audit logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>
