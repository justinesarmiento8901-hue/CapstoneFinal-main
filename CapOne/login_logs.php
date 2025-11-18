<?php
require 'dbForm.php';
session_start();
$role = $_SESSION['user']['role'] ?? '';

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  http_response_code(403); // Forbidden
  echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>";
  exit;
}

// Fetch all login logs (both successful and failed)
$query = "SELECT * FROM user_logins ORDER BY timestamp DESC";
$result = $con->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login Logs</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
  <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
  <div class="sidebar" id="sidebar">
    <h4 class="mb-4"><i class="bi bi-baby"></i> Infant Record System</h4>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="addinfant.php"><i class="bi bi-person-fill-add"></i> Add Infant</a>
    <?php if ($role === 'admin' || $role === 'healthworker'): ?>
      <a href="add_parents.php"><i class="bi bi-person-plus"></i> Add Parent</a>
    <?php endif; ?>
    <a href="viewinfant.php"><i class="bi bi-journal-medical"></i> Infant Records</a>
    <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
    <?php if ($role === 'admin'): ?>
      <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
    <?php endif; ?>
    <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
    <?php if ($role !== 'parent'): ?>
      <a href="vaccination_schedule.php"><i class="bi bi-journal-medical"></i> Vaccination Schedule</a>
      <?php if (in_array($role, ['admin', 'report'], true)): ?>
        <a href="generate_report.php"><i class="bi bi-clipboard-data"></i> Reports</a>
      <?php endif; ?>
      <a href="sms.php"><i class="bi bi-chat-dots"></i> SMS Management</a>
      <a href="login_logs.php" class="active"><i class="bi bi-clipboard-data"></i> Logs</a>
    <?php endif; ?>
    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
  <div class="content-area">
    <div class="container-fluid mt-4">
      <div class="card card-shadow">
        <div class="card-header bg-white border-0 py-3">
          <h3 class="dashboard-title mb-0"><i class="bi bi-clipboard-data"></i>User Login Logs</h3>
        </div>
        <div class="card-body table-modern table-modern-elevated">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>User ID</th>
              <th>Email</th>
              <th>IP Address</th>
              <th>Status</th>
              <th>Reason</th>
              <th>Timestamp</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php $i = 1; ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="<?= $row['success'] ? 'table-success' : 'table-danger' ?>">
                  <td><?= $i++ ?></td>
                  <td><?= $row['user_id'] ?? 'N/A' ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><?= htmlspecialchars($row['ip_address']) ?></td>
                  <td>
                    <?= $row['success'] ? '<span class="badge bg-success">Success</span>' : '<span class="badge bg-danger">Failed</span>' ?>
                  </td>
                  <td><?= htmlspecialchars($row['reason']) ?></td>
                  <td><?= date("Y-m-d H:i:s", strtotime($row['timestamp'])) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No login logs found.</td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('active');
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/theme.js"></script>

</body>

</html>