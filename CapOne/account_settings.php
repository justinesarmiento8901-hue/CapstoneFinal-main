<?php
session_start();
include 'dbForm.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit();
}

$userId = intval($_SESSION['user']['id']);
$currentUserEmail = $_SESSION['user']['email'] ?? '';
$role = $_SESSION['user']['role'] ?? '';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Fetch current user
    $stmt = $con->prepare("SELECT email, password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;

    if (!$user) {
        $errorMessage = 'User not found.';
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $errorMessage = 'Current password is incorrect.';
    } else {
        // Validate email if changing
        $willChangeEmail = $newEmail !== '' && strcasecmp($newEmail, $user['email']) !== 0;
        if ($willChangeEmail) {
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errorMessage = 'Invalid email format.';
            } else {
                $check = $con->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
                $check->bind_param('si', $newEmail, $userId);
                $check->execute();
                if ($check->get_result()->fetch_assoc()) {
                    $errorMessage = 'Email is already in use.';
                }
            }
        }

        // Validate password if changing
        $willChangePassword = $newPassword !== '' || $confirmPassword !== '';
        if (!$errorMessage && $willChangePassword) {
            if ($newPassword !== $confirmPassword) {
                $errorMessage = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 8) {
                $errorMessage = 'Password must be at least 8 characters.';
            }
        }

        if (!$errorMessage) {
            // Build update
            $fields = [];
            $params = [];
            $types = '';

            if ($willChangeEmail) {
                $fields[] = 'email = ?';
                $params[] = $newEmail;
                $types .= 's';
            }
            if ($willChangePassword) {
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $fields[] = 'password = ?';
                $params[] = $hashed;
                $types .= 's';
            }

            if (!empty($fields)) {
                $params[] = $userId;
                $types .= 'i';
                $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
                $up = $con->prepare($sql);
                $up->bind_param($types, ...$params);
                if ($up->execute()) {
                    // Sync parents email if parent role and email changed
                    if ($role === 'parent' && $willChangeEmail) {
                        $oldEmailEsc = mysqli_real_escape_string($con, $user['email']);
                        $newEmailEsc = mysqli_real_escape_string($con, $newEmail);
                        mysqli_query($con, "UPDATE parents SET email = '$newEmailEsc' WHERE email = '$oldEmailEsc'");
                    }
                    if ($willChangeEmail) {
                        $_SESSION['user']['email'] = $newEmail;
                    }
                    $successMessage = 'Account updated successfully.';
                } else {
                    $errorMessage = 'Failed to update account.';
                }
            } else {
                $successMessage = 'No changes to update.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
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
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
        <a href="account_settings.php" class="active"><i class="bi bi-gear"></i> Account Settings</a>
        <?php if ($role !== 'parent'): ?>
            <a href="vaccination_schedule.php"><i class="bi bi-journal-medical"></i> Vaccination Schedule</a>
            <?php if (in_array($role, ['admin', 'report'], true)): ?>
                <a href="generate_report.php"><i class="bi bi-clipboard-data"></i> Reports</a>
            <?php endif; ?>
            <a href="sms.php"><i class="bi bi-chat-dots"></i> SMS Management</a>
            <?php if ($role === 'admin'): ?>
                <a href="login_logs.php"><i class="bi bi-clipboard-data"></i> Logs</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="content-area">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card card-shadow p-4 p-md-5">
                        <h3 class="mb-3">Account Settings</h3>
                        <form method="POST" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Email</label>
                                <input type="email" class="form-control" name="new_email" placeholder="<?php echo htmlspecialchars($currentUserEmail); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" placeholder="Leave blank to keep current">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" placeholder="Leave blank to keep current">
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="dashboard.php" class="btn btn-secondary">Back</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($successMessage): ?>
        <script>
            Swal.fire({
                title: 'Success!',
                text: <?php echo json_encode($successMessage); ?>,
                icon: 'success'
            });
            <?php if (!empty($newEmail)): ?>
                // Optional: we could force reload to reflect session email change if needed
            <?php endif; ?>
        </script>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <script>
            Swal.fire({
                title: 'Error',
                text: <?php echo json_encode($errorMessage); ?>,
                icon: 'error'
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>