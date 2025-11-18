<?php
session_start();
include 'dbForm.php';

if (!function_exists('logAudit')) {
    function logAudit(mysqli $con, ?int $userId, string $action, string $entityTable, int $entityId, string $description): void
    {
        $stmt = $con->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_table, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$stmt) {
            error_log('Failed to prepare audit log statement: ' . $con->error);
            return;
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt->bind_param('ississ', $userId, $action, $entityTable, $entityId, $description, $ipAddress);
        $stmt->execute();
        $stmt->close();
    }
}

// Debug session role
error_log("Session Role: " . (isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'Not Set'));

// --- AUTO-FETCH PARENT INFO BASED ON LOGGED-IN USER ---
$parent_info = null;
if (isset($_SESSION['user']['email']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'parent') {
    $email = mysqli_real_escape_string($con, $_SESSION['user']['email']);
    // Use parents table to resolve the current parent's record
    $query = "SELECT p.id AS parent_id, CONCAT(p.first_name, ' ', p.last_name) AS parent_name FROM parents p WHERE p.email = '$email' LIMIT 1";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $parent_info = mysqli_fetch_assoc($result);
    }
}

// --- INSERT INFANT ---
if (isset($_POST['submit'])) {
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'parent') {
        // Ensure we link to parents.id (not users.id)
        $parent_id = isset($parent_info['parent_id']) ? intval($parent_info['parent_id']) : 0;
        if ($parent_id === 0 && isset($_SESSION['user']['email'])) {
            $emailLookup = mysqli_real_escape_string($con, $_SESSION['user']['email']);
            $parentRes = mysqli_query($con, "SELECT id FROM parents WHERE email = '$emailLookup' LIMIT 1");
            if ($parentRes && mysqli_num_rows($parentRes) > 0) {
                $parentRow = mysqli_fetch_assoc($parentRes);
                $parent_id = intval($parentRow['id']);
            }
        }
    } else {
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    }

    $firstname = mysqli_real_escape_string($con, $_POST['firstname'] ?? '');
    $middlename = mysqli_real_escape_string($con, $_POST['middlename'] ?? '');
    $surname = mysqli_real_escape_string($con, $_POST['surname'] ?? '');
    $dateofbirth = mysqli_real_escape_string($con, $_POST['dateofbirth'] ?? '');
    $placeofbirth = mysqli_real_escape_string($con, $_POST['placeofbirth'] ?? '');
    $sex = mysqli_real_escape_string($con, $_POST['sex'] ?? '');
    $weight = mysqli_real_escape_string($con, $_POST['weight'] ?? '');
    $height = mysqli_real_escape_string($con, $_POST['height'] ?? '');
    $bloodtype = mysqli_real_escape_string($con, $_POST['bloodtype'] ?? '');
    $nationality = mysqli_real_escape_string($con, $_POST['nationality'] ?? '');

    $sql = "INSERT INTO Infantinfo
            (parent_id, firstname, middlename, surname, dateofbirth, placeofbirth, sex, weight, height, bloodtype, nationality)
            VALUES
            ('$parent_id', '$firstname', '$middlename', '$surname', '$dateofbirth', '$placeofbirth', '$sex', '$weight', '$height', '$bloodtype', '$nationality')";
    $result = mysqli_query($con, $sql);

    if ($result) {
        $newInfantId = mysqli_insert_id($con);
        logAudit(
            $con,
            $_SESSION['user']['id'] ?? null,
            'add',
            'infantinfo',
            (int) $newInfantId,
            "Added infant record with ID $newInfantId"
        );

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Success!',
                    text: 'Infant information added successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((res) => {
                    if (res.isConfirmed) {
                        window.location.href = 'viewinfant.php';
                    }
                });
            });
        </script>";
    } else {
        error_log('Database Error: ' . mysqli_error($con));
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to add infant information!',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infant Record System - Add Infant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-4"><i class="bi bi-baby"></i> Infant Record System</h4>
        <?php $role = $_SESSION['user']['role'] ?? ''; ?>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="addinfant.php" class="active"><i class="bi bi-person-fill-add"></i> Add Infant</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="add_parents.php"><i class="bi bi-person-plus"></i> Add Parent</a>
        <?php endif; ?>
        <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
        <a href="viewinfant.php"><i class="bi bi-journal-medical"></i> Infant Records</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
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
        <div class="container-fluid mt-4">
            <div class="card card-shadow p-4">
                <h3 class="dashboard-title mb-4"><i class="bi bi-clipboard-plus"></i>Infant Information Form</h3>

                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'parent'): ?>
                    <?php
                    $resolved_parent_id = $parent_info['parent_id'] ?? 0;
                    $resolved_parent_name = $parent_info['parent_name'] ?? 'Parent';
                    ?>
                    <div class="alert alert-info">
                        Logged-in Parent: <strong><?php echo htmlspecialchars($resolved_parent_name); ?></strong><br>
                        <small>Parent ID: <?php echo htmlspecialchars($resolved_parent_id); ?></small>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-3">
                    <div class="col-12 position-relative">
                        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'parent'): ?>
                            <label for="searchParent" class="form-label">Search Parent </label>
                            <div class="input-group search-bar">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="searchParent" placeholder="Type parent name..." autocomplete="off" required>
                            </div>
                            <div id="searchResults" class="live-search-results mt-2"></div>
                            <input type="hidden" name="parent_id" id="parent_id" required>
                        <?php else: ?>
                            <input type="hidden" name="parent_id" value="<?php echo htmlspecialchars($resolved_parent_id); ?>">
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="firstname" placeholder="Enter first name" style="text-transform:capitalize" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" name="middlename" placeholder="Enter middle name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="surname" placeholder="Enter last name" style="text-transform:capitalize" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dateofbirth" min="1900-01-01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" class="form-control" name="placeofbirth" placeholder="Enter place of birth" style="text-transform:capitalize" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sex</label>
                        <select class="form-select" name="sex" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Birth Weight (kg)</label>
                        <input type="number" class="form-control" name="weight" placeholder="Enter weight" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Birth Height (cm)</label>
                        <input type="number" class="form-control" name="height" placeholder="Enter height" step="0.1" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Blood Type</label>
                        <select class="form-select" name="bloodtype" required>
                            <option value="">Select</option>
                            <option>A+</option>
                            <option>A-</option>
                            <option>B+</option>
                            <option>B-</option>
                            <option>AB+</option>
                            <option>AB-</option>
                            <option>O+</option>
                            <option>O-</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nationality</label>
                        <input type="text" class="form-control" name="nationality" placeholder="Enter nationality" style="text-transform:capitalize" required>
                    </div>
                    <div class="col-md-8 d-flex align-items-end justify-content-end gap-2">
                        <button type="button" id="clearBtn" class="btn btn-outline-danger"><i class="bi bi-eraser"></i>Clear</button>
                        <button type="submit" name="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i>Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#searchParent').on('keyup', function() {
                const query = $(this).val().trim();
                if (query.length === 0) {
                    $('#searchResults').html('');
                    return;
                }
                $.ajax({
                    url: 'addinfantsearch.php',
                    method: 'POST',
                    data: {
                        query: query
                    },
                    success: function(data) {
                        $('#searchResults').html(data);
                    }
                });
            });

            $(document).on('click', '.search-result', function() {
                const userId = $(this).data('id');
                const userName = $(this).text();
                $('#searchParent').val(userName);
                $('#parent_id').val(userId);
                $('#searchResults').html('');
            });

            $('#clearBtn').click(function() {
                $('input[type="text"], input[type="date"], input[type="number"], select').val('');
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>