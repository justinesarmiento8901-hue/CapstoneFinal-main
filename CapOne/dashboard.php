<?php
session_start();
include 'dbForm.php'; // Ensure this file contains the database connection
$barangays = include __DIR__ . '/config/barangays.php';
$role = $_SESSION['user']['role'] ?? '';
$userEmail = $_SESSION['user']['email'] ?? '';

// Fetch total infants
$infantCountQuery = "SELECT COUNT(*) AS total_infants FROM infantinfo";
$infantCountResult = mysqli_query($con, $infantCountQuery);
$infantCount = mysqli_fetch_assoc($infantCountResult)['total_infants'];

// Fetch total parents
$parentCountQuery = "SELECT COUNT(*) AS total_parents FROM parents";
$parentCountResult = mysqli_query($con, $parentCountQuery);
$parentCount = mysqli_fetch_assoc($parentCountResult)['total_parents'];

$newParentsRecentCount = 0;
$newParentsTodayCount = 0;
$newParentsWeekCount = 0;
$newParentsMonthCount = 0;

$newParentsSummaryQuery = "SELECT
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS recent_count,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_count,
        SUM(CASE WHEN YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) AS week_count,
        SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS month_count
    FROM parents";
$newParentsSummaryResult = mysqli_query($con, $newParentsSummaryQuery);
if ($newParentsSummaryResult) {
    $newParentsSummaryRow = mysqli_fetch_assoc($newParentsSummaryResult);
    $newParentsRecentCount = (int) ($newParentsSummaryRow['recent_count'] ?? 0);
    $newParentsTodayCount = (int) ($newParentsSummaryRow['today_count'] ?? 0);
    $newParentsWeekCount = (int) ($newParentsSummaryRow['week_count'] ?? 0);
    $newParentsMonthCount = (int) ($newParentsSummaryRow['month_count'] ?? 0);
    mysqli_free_result($newParentsSummaryResult);
}

$pendingSchedules = 0;
$completedSchedules = 0;
$upcomingWeekCount = 0;
$upcomingAppointments = [];
$parentChildrenCount = 0;
$parentPendingCount = 0;
$parentCompletedCount = 0;
$parentUpcomingAppointments = [];
$parentRecentVaccinations = [];
$parentDataLoaded = false;

if ($role !== 'parent') {
    $scheduleSummaryQuery = "SELECT status, COUNT(*) AS total FROM tbl_vaccination_schedule GROUP BY status";
    $scheduleSummaryResult = mysqli_query($con, $scheduleSummaryQuery);
    if ($scheduleSummaryResult) {
        while ($row = mysqli_fetch_assoc($scheduleSummaryResult)) {
            if ($row['status'] === 'Pending') {
                $pendingSchedules = (int) $row['total'];
            }
            if ($row['status'] === 'Completed') {
                $completedSchedules = (int) $row['total'];
            }
        }
    }

    $upcomingWeekQuery = "SELECT COUNT(*) AS upcoming FROM tbl_vaccination_schedule WHERE status = 'Pending' AND date_vaccination BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    $upcomingWeekResult = mysqli_query($con, $upcomingWeekQuery);
    if ($upcomingWeekResult) {
        $upcomingWeekCount = (int) (mysqli_fetch_assoc($upcomingWeekResult)['upcoming'] ?? 0);
    }

    $upcomingAppointmentsQuery = "SELECT v.date_vaccination, v.time, v.vaccine_name, v.stage, CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name, CONCAT_WS(' ', p.first_name, p.last_name) AS parent_name FROM tbl_vaccination_schedule v JOIN infantinfo i ON v.infant_id = i.id JOIN parents p ON i.parent_id = p.id WHERE v.status = 'Pending' AND v.date_vaccination >= CURDATE() ORDER BY v.date_vaccination ASC, v.time ASC LIMIT 5";
    $upcomingAppointmentsResult = mysqli_query($con, $upcomingAppointmentsQuery);
    if ($upcomingAppointmentsResult) {
        while ($row = mysqli_fetch_assoc($upcomingAppointmentsResult)) {
            $upcomingAppointments[] = $row;
        }
    }
}

if ($role === 'parent' && $userEmail) {
    $parentEmailEsc = mysqli_real_escape_string($con, $userEmail);
    $parentIdResult = mysqli_query($con, "SELECT id FROM parents WHERE email = '$parentEmailEsc' LIMIT 1");
    if ($parentIdResult && $parentRow = mysqli_fetch_assoc($parentIdResult)) {
        $parentId = (int) $parentRow['id'];
        $parentDataLoaded = true;

        $parentChildrenResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM infantinfo WHERE parent_id = $parentId");
        if ($parentChildrenResult) {
            $parentChildrenCount = (int) (mysqli_fetch_assoc($parentChildrenResult)['total'] ?? 0);
        }

        $parentPendingResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_vaccination_schedule WHERE status = 'Pending' AND infant_id IN (SELECT id FROM infantinfo WHERE parent_id = $parentId)");
        if ($parentPendingResult) {
            $parentPendingCount = (int) (mysqli_fetch_assoc($parentPendingResult)['total'] ?? 0);
        }

        $parentCompletedResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_vaccination_schedule WHERE status = 'Completed' AND infant_id IN (SELECT id FROM infantinfo WHERE parent_id = $parentId)");
        if ($parentCompletedResult) {
            $parentCompletedCount = (int) (mysqli_fetch_assoc($parentCompletedResult)['total'] ?? 0);
        }

        $parentUpcomingQuery = "SELECT v.date_vaccination, v.time, v.vaccine_name, v.stage, CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name FROM tbl_vaccination_schedule v JOIN infantinfo i ON v.infant_id = i.id WHERE i.parent_id = $parentId AND v.status = 'Pending' AND v.date_vaccination >= CURDATE() ORDER BY v.date_vaccination ASC, v.time ASC LIMIT 5";
        $parentUpcomingResult = mysqli_query($con, $parentUpcomingQuery);
        if ($parentUpcomingResult) {
            while ($row = mysqli_fetch_assoc($parentUpcomingResult)) {
                $parentUpcomingAppointments[] = $row;
            }
        }

        $parentRecentQuery = "SELECT v.date_vaccination, v.vaccine_name, v.stage, CONCAT_WS(' ', i.firstname, i.middlename, i.surname) AS infant_name FROM tbl_vaccination_schedule v JOIN infantinfo i ON v.infant_id = i.id WHERE i.parent_id = $parentId AND v.status = 'Completed' ORDER BY v.date_vaccination DESC LIMIT 5";
        $parentRecentResult = mysqli_query($con, $parentRecentQuery);
        if ($parentRecentResult) {
            while ($row = mysqli_fetch_assoc($parentRecentResult)) {
                $parentRecentVaccinations[] = $row;
            }
        }
    }
}

// Fetch all users
$userQuery = "SELECT id, email, role FROM users";
$userResult = mysqli_query($con, $userQuery);

// Handle user deletion
if (isset($_GET['deleteid'])) {
    $id = $_GET['deleteid'];
    $sql = "DELETE FROM users WHERE id = '$id'";
    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "<script>
        Swal.fire({
            title: 'Success!',
            text: 'User deleted successfully.',
            icon: 'success'
        }).then(() => {
            window.location.href = 'dashboard.php';
        });
        </script>";
    } else {
        echo "<script>
        Swal.fire({
            title: 'Error!',
            text: 'Failed to delete user.',
            icon: 'error'
        }).then(() => {
            window.location.href = 'dashboard.php';
        });
        </script>";
    }
}

// Handle health worker registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_healthworker'])) {
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $barangayAssigned = $_POST['barangay_assigned'] ?? '';
    $licenseNumber = trim($_POST['license_number'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $alertScript = function (string $message, string $icon = 'error') {
        echo "<script>Swal.fire({title: 'Error!', text: " . json_encode($message) . ", icon: '" . $icon . "'});</script>";
    };

    if ($firstname === '' || $lastname === '') {
        $alertScript('Please provide the health worker\'s first and last name.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alertScript('Please enter a valid email address.');
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $alertScript('Please select a valid gender option.');
    } elseif ($barangayAssigned === '' || !in_array($barangayAssigned, $barangays ?? [], true)) {
        $alertScript('Please select a valid barangay assignment.');
    } elseif ($password !== $confirmPassword) {
        $alertScript('Passwords do not match.');
    } else {
        $firstnameEsc = mysqli_real_escape_string($con, $firstname);
        $middlenameEsc = mysqli_real_escape_string($con, $middlename);
        $lastnameEsc = mysqli_real_escape_string($con, $lastname);
        $emailEsc = mysqli_real_escape_string($con, $email);
        $genderEsc = mysqli_real_escape_string($con, $gender);
        $addressEsc = mysqli_real_escape_string($con, $address);
        $contactEsc = mysqli_real_escape_string($con, $contactNumber);
        $barangayEsc = mysqli_real_escape_string($con, $barangayAssigned);
        $licenseEsc = mysqli_real_escape_string($con, $licenseNumber);
        $positionEsc = mysqli_real_escape_string($con, $position);
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $hashedPasswordEsc = mysqli_real_escape_string($con, $hashedPassword);
        $roleValue = 'healthworker';
        $roleEsc = mysqli_real_escape_string($con, $roleValue);
        $fullName = trim($firstname . ' ' . ($middlename !== '' ? $middlename . ' ' : '') . $lastname);
        $fullNameEsc = mysqli_real_escape_string($con, $fullName);

        $existingUserQuery = "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1";
        $existingUserResult = mysqli_query($con, $existingUserQuery);

        if ($existingUserResult && mysqli_num_rows($existingUserResult) > 0) {
            $alertScript('Email is already registered.');
        } else {
            $registerQuery = "INSERT INTO users (name, email, password, role, created_at) VALUES ('$fullNameEsc', '$emailEsc', '$hashedPasswordEsc', '$roleEsc', NOW())";
            $registerResult = mysqli_query($con, $registerQuery);

            if ($registerResult) {
                $userId = mysqli_insert_id($con);
                $middlenameValue = $middlename !== '' ? "'$middlenameEsc'" : 'NULL';
                $addressValue = $address !== '' ? "'$addressEsc'" : 'NULL';
                $contactValue = $contactNumber !== '' ? "'$contactEsc'" : 'NULL';
                $licenseValue = $licenseNumber !== '' ? "'$licenseEsc'" : 'NULL';
                $positionValue = $position !== '' ? "'$positionEsc'" : 'NULL';

                $healthWorkerQuery = "INSERT INTO healthworker (user_id, firstname, middlename, lastname, gender, address, contact_number, barangay_assigned, license_number, position)
                    VALUES ($userId, '$firstnameEsc', $middlenameValue, '$lastnameEsc', '$genderEsc', $addressValue, $contactValue, '$barangayEsc', $licenseValue, $positionValue)";
                $healthWorkerResult = mysqli_query($con, $healthWorkerQuery);

                if ($healthWorkerResult) {
                    echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Health worker registered successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'dashboard.php';
                        });
                    });
                    </script>";
                } else {
                    mysqli_query($con, "DELETE FROM users WHERE id = $userId");
                    $alertScript('Failed to save health worker details. Please try again.');
                }
            } else {
                $alertScript('Failed to register health worker. Please try again.');
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Dashboard</title>
</head>
<!-- sidebar -->

<body>
    <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-4"><i class="bi bi-baby"></i> Infant Record System</h4>
        <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="addinfant.php"><i class="bi bi-person-fill-add"></i> Add Infant</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="add_parents.php"><i class="bi bi-person-plus"></i> Add Parent</a>
            <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
        <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
        <a href="viewinfant.php"><i class="bi bi-journal-medical"></i> Infant Records</a>
        <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
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
        <div class="container-fluid">
            <?php if ($role !== 'parent'): ?>
                <div class="card card-shadow mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <h3 class="dashboard-title text-primary mb-0"><i class="bi bi-speedometer2"></i> Dashboard</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <?php if ($role === 'admin'): ?>
                                    <a href="viewinfant.php" class="summary-card summary-infants d-block text-decoration-none">
                                        <div class="summary-icon"><i class="bi bi-people-fill"></i></div>
                                        <div>
                                            <p class="summary-label">Total Infants</p>
                                            <h4 class="summary-value"><?php echo $infantCount; ?></h4>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="summary-card summary-infants">
                                        <div class="summary-icon"><i class="bi bi-people-fill"></i></div>
                                        <div>
                                            <p class="summary-label">Total Infants</p>
                                            <h4 class="summary-value"><?php echo $infantCount; ?></h4>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <?php if ($role === 'admin'): ?>
                                    <a href="view_parents.php" class="summary-card summary-parents d-block text-decoration-none">
                                        <div class="summary-icon"><i class="bi bi-person-bounding-box"></i></div>
                                        <div>
                                            <p class="summary-label">Total Parents</p>
                                            <h4 class="summary-value"><?php echo $parentCount; ?></h4>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="summary-card summary-parents">
                                        <div class="summary-icon"><i class="bi bi-person-bounding-box"></i></div>
                                        <div>
                                            <p class="summary-label">Total Parents</p>
                                            <h4 class="summary-value"><?php echo $parentCount; ?></h4>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <?php if ($role === 'admin'): ?>
                                    <a href="vaccination_schedule.php?status=Pending" class="summary-card summary-pending d-block text-decoration-none">
                                        <div class="summary-icon"><i class="bi bi-exclamation-octagon"></i></div>
                                        <div>
                                            <p class="summary-label">Pending Vaccinations</p>
                                            <h4 class="summary-value"><?php echo $pendingSchedules; ?></h4>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="summary-card summary-pending">
                                        <div class="summary-icon"><i class="bi bi-exclamation-octagon"></i></div>
                                        <div>
                                            <p class="summary-label">Pending Vaccinations</p>
                                            <h4 class="summary-value"><?php echo $pendingSchedules; ?></h4>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <?php if ($role === 'admin'): ?>
                                    <a href="vaccination_schedule.php?status=Completed" class="summary-card summary-completed d-block text-decoration-none">
                                        <div class="summary-icon"><i class="bi bi-check-circle"></i></div>
                                        <div>
                                            <p class="summary-label">Completed Vaccinations</p>
                                            <h4 class="summary-value"><?php echo $completedSchedules; ?></h4>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="summary-card summary-completed">
                                        <div class="summary-icon"><i class="bi bi-check-circle"></i></div>
                                        <div>
                                            <p class="summary-label">Completed Vaccinations</p>
                                            <h4 class="summary-value"><?php echo $completedSchedules; ?></h4>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row g-3 mb-4 align-items-stretch">
                            <div class="col-sm-6 col-lg-4">
                                <div class="summary-card summary-infants h-100">
                                    <div class="summary-icon"><i class="bi bi-calendar-event"></i></div>
                                    <div>
                                        <p class="summary-label">Upcoming Vaccinations</p>
                                        <h4 class="summary-value"><?php echo $upcomingWeekCount; ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-4">
                                <a href="newly_added_parents.php" class="summary-card summary-parents d-block text-decoration-none h-100">
                                    <div class="summary-icon"><i class="bi bi-person-plus"></i></div>
                                    <div>
                                        <p class="summary-label mb-2">New Parents Overview</p>
                                        <div class="row g-2 text-center small">
                                            <div class="col-6">
                                                <p class="mb-1 summary-subtext">Last 30 Days</p>
                                                <h5 class="summary-value mb-0"><?php echo $newParentsRecentCount; ?></h5>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 summary-subtext">Today</p>
                                                <h5 class="summary-value mb-0"><?php echo $newParentsTodayCount; ?></h5>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 summary-subtext">This Week</p>
                                                <h5 class="summary-value mb-0"><?php echo $newParentsWeekCount; ?></h5>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1 summary-subtext">This Month</p>
                                                <h5 class="summary-value mb-0"><?php echo $newParentsMonthCount; ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php if ($role === 'admin'): ?>
                                <div class="col-sm-6 col-lg-4">
                                    <a href="vaccination_period_report.php" class="summary-card summary-reports d-block text-decoration-none h-100">
                                        <div class="summary-icon"><i class="bi bi-clipboard-data"></i></div>
                                        <div>
                                            <p class="summary-label mb-2">Vaccination Reports</p>
                                            <p class="summary-subtext mb-1">Generate coverage by barangay and schedule period.</p>
                                            <p class="summary-link mb-0">View Report &raquo;</p>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="row g-3 mb-4 quick-actions">
                            <div class="col-sm-6 col-lg-4 col-xl-3">
                                <a href="login_logs.php" class="summary-card summary-logs d-block text-decoration-none h-100">
                                    <div class="summary-icon"><i class="bi bi-journal-check"></i></div>
                                    <div>
                                        <p class="summary-label mb-1">Login Logs</p>
                                        <p class="summary-text mb-0">Review system access history.</p>
                                    </div>
                                </a>
                            </div>
                            <?php if (in_array($role, ['admin', 'healthworker'], true)): ?>
                                <div class="col-sm-6 col-lg-4 col-xl-3">
                                    <a href="healthworker.php" class="summary-card summary-healthworkers d-block text-decoration-none h-100">
                                        <div class="summary-icon"><i class="bi bi-people"></i></div>
                                        <div>
                                            <p class="summary-label mb-1">Health Workers</p>
                                            <p class="summary-text mb-0">Manage assigned staff details.</p>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($role === 'admin'): ?>
                                <div class="col-sm-6 col-lg-4 col-xl-3">
                                    <a href="update_growth.php" class="summary-card summary-growth d-block text-decoration-none h-100">
                                        <div class="summary-icon"><i class="bi bi-activity"></i></div>
                                        <div>
                                            <p class="summary-label mb-1">Growth Tracking</p>
                                            <p class="summary-text mb-0">Update infant measurements.</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-6 col-lg-4 col-xl-3">
                                    <a href="javascript:void(0)" class="summary-card summary-register d-block text-decoration-none h-100" data-bs-toggle="modal" data-bs-target="#registerHealthWorkerModal">
                                        <div class="summary-icon"><i class="bi bi-person-plus"></i></div>
                                        <div>
                                            <p class="summary-label mb-1">Register Worker</p>
                                            <p class="summary-text mb-0">Add new health personnel.</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-6 col-lg-4 col-xl-3">
                                    <a href="javascript:void(0)" class="summary-card summary-manage d-block text-decoration-none h-100" data-bs-toggle="modal" data-bs-target="#manageUsersModal">
                                        <div class="summary-icon"><i class="bi bi-people-fill"></i></div>
                                        <div>
                                            <p class="summary-label mb-1">Manage Users</p>
                                            <p class="summary-text mb-0">Modify system user accounts.</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-6 col-lg-4 col-xl-3">
                                    <a href="edit_del_viewlogs.php" class="summary-card summary-audit d-block text-decoration-none h-100">
                                        <div class="summary-icon"><i class="bi bi-clipboard-data"></i></div>
                                        <div>
                                            <p class="summary-label mb-1">Audit Trail</p>
                                            <p class="summary-text mb-0">View activity history logs.</p>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive table-modern">
                            <h5 class="section-heading"><i class="bi bi-clock-history"></i>Upcoming Appointments</h5>
                            <?php if (!empty($upcomingAppointments)): ?>
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Infant</th>
                                            <th>Parent</th>
                                            <th>Vaccine</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingAppointments as $appointment): ?>
                                            <?php
                                            $dateDisplay = htmlspecialchars($appointment['date_vaccination']);
                                            $timeDisplay = $appointment['time'] ? date('g:i A', strtotime($appointment['time'])) : 'N/A';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($appointment['infant_name']); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['parent_name']); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['vaccine_name']); ?></td>
                                                <td><?php echo $dateDisplay; ?></td>
                                                <td><?php echo htmlspecialchars($timeDisplay); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted mb-0">No upcoming appointments scheduled.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card card-shadow">
                    <div class="card-header bg-white border-0 py-3">
                        <h3 class="dashboard-title text-primary"><i class="bi bi-house-heart"></i>Parent Dashboard</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($parentDataLoaded): ?>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="summary-card summary-infants">
                                        <div class="summary-icon"><i class="bi bi-people"></i></div>
                                        <div>
                                            <p class="summary-label">Children Registered</p>
                                            <h4 class="summary-value"><?php echo $parentChildrenCount; ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-card summary-pending">
                                        <div class="summary-icon"><i class="bi bi-hourglass-split"></i></div>
                                        <div>
                                            <p class="summary-label">Pending Vaccinations</p>
                                            <h4 class="summary-value"><?php echo $parentPendingCount; ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-card summary-completed">
                                        <div class="summary-icon"><i class="bi bi-shield-check"></i></div>
                                        <div>
                                            <p class="summary-label">Completed Vaccinations</p>
                                            <h4 class="summary-value"><?php echo $parentCompletedCount; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="table-responsive table-modern panel-upcoming">
                                        <h5 class="section-heading"><i class="bi bi-calendar-check"></i>Upcoming Vaccinations</h5>
                                        <?php if (!empty($parentUpcomingAppointments)): ?>
                                            <table class="table table-hover align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Child</th>
                                                        <th>Vaccine</th>
                                                        <th>Date</th>
                                                        <th>Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($parentUpcomingAppointments as $appointment): ?>
                                                        <?php
                                                        $dateDisplay = htmlspecialchars($appointment['date_vaccination']);
                                                        $timeDisplay = $appointment['time'] ? date('g:i A', strtotime($appointment['time'])) : 'N/A';
                                                        ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($appointment['infant_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($appointment['vaccine_name']); ?></td>
                                                            <td><?php echo $dateDisplay; ?></td>
                                                            <td><?php echo htmlspecialchars($timeDisplay); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No upcoming vaccinations scheduled.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="table-responsive table-modern panel-recent">
                                        <h5 class="section-heading"><i class="bi bi-activity"></i>Recent Vaccinations</h5>
                                        <?php if (!empty($parentRecentVaccinations)): ?>
                                            <table class="table table-hover align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Child</th>
                                                        <th>Vaccine</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($parentRecentVaccinations as $record): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($record['infant_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($record['vaccine_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($record['date_vaccination']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No completed vaccinations recorded yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No parent profile is linked to this account yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="modal fade" id="registerHealthWorkerModal" tabindex="-1" aria-labelledby="registerHealthWorkerModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="registerHealthWorkerModalLabel">Register Health Worker</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="firstname" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="firstname" name="firstname" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="middlename" class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" id="middlename" name="middlename">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="lastname" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="lastname" name="lastname" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="contact_number" class="form-label">Contact Number</label>
                                            <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-select" id="gender" name="gender" required>
                                                <option value="" selected disabled>Select gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="barangay_assigned" class="form-label">Barangay Assigned</label>
                                            <select class="form-select" id="barangay_assigned" name="barangay_assigned" required>
                                                <option value="" selected disabled>Select barangay</option>
                                                <?php foreach (($barangays ?? []) as $barangay): ?>
                                                    <option value="<?php echo htmlspecialchars($barangay); ?>"><?php echo htmlspecialchars($barangay); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="2" placeholder="Street / Sitio, Barangay, Municipality" required></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="license_number" class="form-label">License Number</label>
                                            <input type="text" class="form-control" id="license_number" name="license_number">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="position" class="form-label">Position</label>
                                            <input type="text" class="form-control" id="position" name="position">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <button type="submit" name="register_healthworker" class="btn btn-success">Register</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="manageUsersModal" tabindex="-1" aria-labelledby="manageUsersModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="manageUsersModalLabel">Manage Users</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = mysqli_fetch_assoc($userResult)) { ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo $user['email']; ?></td>
                                                <td><?php echo $user['role']; ?></td>
                                                <td>
                                                    <button class="btn btn-danger btn-sm" onclick="return confirmDelete(<?php echo $user['id']; ?>)">Delete</button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to the deletion URL
                    window.location.href = `dashboard.php?deleteid=${id}`;
                }
            });
            return false; // Prevent default link behavior
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>

</body>

</html>