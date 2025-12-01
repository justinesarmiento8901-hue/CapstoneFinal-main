<?php
session_start(); // ensure this is at the top if not already included
include 'dbForm.php';

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

$role = $_SESSION['user']['role'] ?? '';
$showDeleteButton = ($role === 'admin');

// Check if the logged-in user is a parent
$isParent = ($role === 'parent');
$parentEmail = ($isParent && isset($_SESSION['user']['email'])) ? mysqli_real_escape_string($con, $_SESSION['user']['email']) : null;

?>



<?php
// SYNTAX FOR DELETING INFANT INFORMATION
if (isset($_GET['deleteid'])) {
    if (!$showDeleteButton) {
        header('Location: viewinfant.php');
        exit();
    }

    $id = $_GET['deleteid'];
    $sql = "DELETE FROM infantinfo WHERE id = '" . mysqli_real_escape_string($con, $id) . "'";
    $result = mysqli_query($con, $sql);
    if ($result) {
        // ✅ LOGGING DELETION
        $entityId = (int) $id;
        logAudit(
            $con,
            $_SESSION['user']['id'] ?? null,
            'delete',
            'infantinfo',
            $entityId,
            "Deleted infant record with ID $entityId"
        );

        echo "<script>
        Swal.fire({
            title: 'Success!',
            text: 'Infant information deleted successfully.',
            icon: 'success'
        }).then(() => {
            window.location.href = 'viewinfant.php';  // Redirect after success
        });
        </script>";
    } else {
        echo "<script>
        Swal.fire('Error', 'Failed to delete record.', 'error');
        </script>";
    }
}
?>

<?php
// SYNTAX FOR EDITING INFANT INFORMATION
if (isset($_POST['new_submit'])) {
    $id = $_POST['new_id'];
    $new_firstname = $_POST['new_firstname'];
    $new_middlename = $_POST['new_middle'];
    $new_surname = $_POST['new_surname'];
    $new_dateofbirth = $_POST['new_dateofbirth'];
    $new_placeofbirth = $_POST['new_birthplace'];
    $new_nationality = $_POST['new_nationality'];
    $new_weight = $_POST['new_weight'];
    $new_height = $_POST['new_height'];

    $sql = "UPDATE infantinfo SET 
                firstname = '$new_firstname', 
                middlename = '$new_middlename', 
                surname = '$new_surname', 
                dateofbirth = '$new_dateofbirth',
                placeofbirth = '$new_placeofbirth', 
                nationality = '$new_nationality', 
                weight = '$new_weight', 
                height = '$new_height'
            WHERE id = '$id'";

    $result = mysqli_query($con, $sql);
    if ($result) {
        // ✅ LOGGING UPDATE
        $entityId = (int) $id;
        logAudit(
            $con,
            $_SESSION['user']['id'] ?? null,
            'edit',
            'infantinfo',
            $entityId,
            "Updated infant record with ID $entityId"
        );

        echo "<script>
        Swal.fire({
            title: 'Success!',
            text: 'Infant information updated successfully.',
            icon: 'success'
        }).then(() => {
            window.location.href = 'viewinfant.php';  // Redirect after success
        });
        </script>";
    } else {
        echo "<script>
        Swal.fire('Error', 'Failed to update record.', 'error');
        </script>";
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
    <title>Infant Records</title>
</head>

<body>
    <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-4"><i class="bi bi-baby"></i> Infant Record System</h4>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="addinfant.php"><i class="bi bi-person-fill-add"></i> Add Infant</a>
        <?php if (!$isParent && isset($_SESSION['user']['role']) && ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'healthworker')): ?>
            <a href="add_parents.php"><i class="bi bi-person-plus"></i> Add Parent</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['user']['role']) && ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'healthworker' || $_SESSION['user']['role'] === 'parent')): ?>
            <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
        <?php endif; ?>
        <a href="viewinfant.php" class="active"><i class="bi bi-journal-medical"></i> Infant Records</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
        <?php if (!$isParent): ?>
            <a href="vaccination_schedule.php"><i class="bi bi-journal-medical"></i> Vaccination Schedule</a>
            <?php if (isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], ['admin', 'report'], true)): ?>
                <a href="generate_report.php"><i class="bi bi-clipboard-data"></i> Reports</a>
            <?php endif; ?>
            <a href="sms.php"><i class="bi bi-chat-dots"></i> SMS Management</a>
        <?php endif; ?>
        <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
        <?php if ($role === 'admin'): ?>
            <a href="login_logs.php"><i class="bi bi-clipboard-data"></i> Logs</a>
        <?php endif; ?>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    <div class="content-area">
        <div class="container-fluid mt-4">
            <div class="card card-shadow">
                <div class="card-header bg-white border-0 py-3">
                    <h3 class="dashboard-title"><i class="bi bi-baby"></i>Infant Information</h3>
                </div>
                <div class="card-body">
                    <?php if (!$isParent): ?>
                        <form id="searchForm" method="GET" action="viewinfant.php" class="mb-4">
                            <div class="d-flex align-items-center" style="gap: 0.5rem; max-width: 720px;">
                                <div class="input-group search-bar search-bar-elevated" style="max-width:380px;">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" id="search" name="search" class="form-control" placeholder="Search by ID, Name, or Birthplace..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    <button class="btn btn-outline-secondary" type="button" title="Clear" onclick="clearSearch()"><i class="bi bi-x-lg"></i></button>
                                    <button class="btn btn-primary" type="submit">Search</button>
                                </div>
                                <div class="ms-2 d-flex align-items-center" style="gap:0.75rem;">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="sex" id="sexAll" value="" <?php if (!isset($_GET['sex']) || $_GET['sex'] === '') echo 'checked'; ?> onchange="this.form.submit()">
                                        <label class="form-check-label" for="sexAll">All</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="sex" id="sexMale" value="Male" <?php if (isset($_GET['sex']) && $_GET['sex'] === 'Male') echo 'checked'; ?> onchange="this.form.submit()">
                                        <label class="form-check-label" for="sexMale">Male</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="sex" id="sexFemale" value="Female" <?php if (isset($_GET['sex']) && $_GET['sex'] === 'Female') echo 'checked'; ?> onchange="this.form.submit()">
                                        <label class="form-check-label" for="sexFemale">Female</label>
                                    </div>
                                
                                </div>
                            </div>
                            <div class="w-100"></div>
                            <div class="mt-2" style="max-width:720px;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center" style="gap:0.5rem;">
                                        <label class="mb-0 text-muted" style="font-size:0.9rem;">Birth range:</label>
                                        <input type="date" name="birth_from" class="form-control form-control-sm" style="width:170px;" value="<?php echo isset($_GET['birth_from']) ? htmlspecialchars($_GET['birth_from']) : ''; ?>">
                                        <span class="text-muted">to</span>
                                        <input type="date" name="birth_to" class="form-control form-control-sm" style="width:170px;" value="<?php echo isset($_GET['birth_to']) ? htmlspecialchars($_GET['birth_to']) : ''; ?>">
                                    </div>
                                    <div class="d-flex" style="gap:0.5rem;">
                                        <?php
                                        // Build export query preserving current filters (use raw GET so variables defined later won't be undefined)
                                        $exportParams = [];
                                        $search_for_export = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
                                        $sex_for_export = isset($_GET['sex']) ? trim((string)$_GET['sex']) : '';
                                        $birth_from_for_export = isset($_GET['birth_from']) ? trim((string)$_GET['birth_from']) : '';
                                        $birth_to_for_export = isset($_GET['birth_to']) ? trim((string)$_GET['birth_to']) : '';
                                        if ($search_for_export !== '') $exportParams['search'] = $search_for_export;
                                        if ($sex_for_export !== '') $exportParams['sex'] = $sex_for_export;
                                        if ($birth_from_for_export !== '') $exportParams['birth_from'] = $birth_from_for_export;
                                        if ($birth_to_for_export !== '') $exportParams['birth_to'] = $birth_to_for_export;
                                        $csvQuery = http_build_query(array_merge($exportParams, ['format' => 'csv']));
                                        $pdfQuery = http_build_query(array_merge($exportParams, ['format' => 'pdf']));
                                        $csvHref = 'export_infants.php?' . $csvQuery;
                                        $pdfHref = 'export_infants.php?' . $pdfQuery;
                                        ?>
                                        <a class="btn btn-outline-success btn-sm" href="<?php echo htmlspecialchars($csvHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Export CSV"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
                                        <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($pdfHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Export PDF"><i class="bi bi-printer"></i> PDF</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                    <!-- Table -->
                    <div class="table-modern table-modern-elevated">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Full Name</th>
                                        <th scope="col">Date of Birth</th>
                                        <th scope="col">Place of Birth</th>
                                        <th scope="col">Sex</th>
                                        <th scope="col">Weight</th>
                                        <th scope="col">Height</th>
                                        <th scope="col">BloodType</th>
                                        <th scope="col">Nationality</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // FOR VIEWING INFANT INFORMATION WITH PAGINATION
                                    $search = (!$isParent && isset($_GET['search'])) ? mysqli_real_escape_string($con, $_GET['search']) : '';
                                    $sex_filter = (!$isParent && isset($_GET['sex']) && in_array($_GET['sex'], ['Male', 'Female'])) ? $_GET['sex'] : '';

                                    // Birthdate range filters (YYYY-MM-DD)
                                    $birth_from = '';
                                    $birth_to = '';
                                    if (!$isParent) {
                                        if (!empty($_GET['birth_from'])) {
                                            $ts = strtotime($_GET['birth_from']);
                                            if ($ts !== false) {
                                                $birth_from = date('Y-m-d', $ts);
                                            }
                                        }
                                        if (!empty($_GET['birth_to'])) {
                                            $ts2 = strtotime($_GET['birth_to']);
                                            if ($ts2 !== false) {
                                                $birth_to = date('Y-m-d', $ts2);
                                            }
                                        }
                                    }

                                    // Pagination settings
                                    $per_page = 10;
                                    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                                    if ($page < 1) $page = 1;
                                    $offset = ($page - 1) * $per_page;

                                    // If parent, resolve parent id first
                                    $parentIdFilter = 0;
                                    if ($isParent && $parentEmail) {
                                        $parentRes = mysqli_query($con, "SELECT id FROM parents WHERE email = '$parentEmail' LIMIT 1");
                                        if ($parentRes && mysqli_num_rows($parentRes) > 0) {
                                            $parentRow = mysqli_fetch_assoc($parentRes);
                                            $parentIdFilter = intval($parentRow['id']);
                                        }
                                    }

                                    // Build WHERE clause
                                    if ($isParent && $parentIdFilter > 0) {
                                        $where = "parent_id = $parentIdFilter";
                                        if (!empty($search)) {
                                            $where .= " AND (id LIKE '%$search%' OR firstname LIKE '%$search%' OR middlename LIKE '%$search%' OR surname LIKE '%$search%' OR placeofbirth LIKE '%$search%')";
                                        }
                                        if (!empty($sex_filter)) {
                                            $where .= " AND sex = '" . mysqli_real_escape_string($con, $sex_filter) . "'";
                                        }
                                        if (!empty($birth_from)) {
                                            $where .= " AND dateofbirth >= '" . mysqli_real_escape_string($con, $birth_from) . "'";
                                        }
                                        if (!empty($birth_to)) {
                                            $where .= " AND dateofbirth <= '" . mysqli_real_escape_string($con, $birth_to) . "'";
                                        }
                                    } else {
                                        $where = '1';
                                        if (!empty($search)) {
                                            $where .= " AND (id LIKE '%$search%' OR firstname LIKE '%$search%' OR middlename LIKE '%$search%' OR surname LIKE '%$search%' OR placeofbirth LIKE '%$search%')";
                                        }
                                        if (!empty($sex_filter)) {
                                            $where .= " AND sex = '" . mysqli_real_escape_string($con, $sex_filter) . "'";
                                        }
                                        if (!empty($birth_from)) {
                                            $where .= " AND dateofbirth >= '" . mysqli_real_escape_string($con, $birth_from) . "'";
                                        }
                                        if (!empty($birth_to)) {
                                            $where .= " AND dateofbirth <= '" . mysqli_real_escape_string($con, $birth_to) . "'";
                                        }
                                    }

                                    // Total count for pagination
                                    $count_sql = "SELECT COUNT(*) AS total FROM infantinfo WHERE $where";
                                    $count_res = mysqli_query($con, $count_sql);
                                    $total = 0;
                                    if ($count_res) {
                                        $row_count = mysqli_fetch_assoc($count_res);
                                        $total = (int) ($row_count['total'] ?? 0);
                                    }
                                    $total_pages = ($total > 0) ? (int) ceil($total / $per_page) : 1;

                                    // Fetch paginated rows
                                    $sql = "SELECT * FROM infantinfo WHERE $where ORDER BY id ASC LIMIT $offset, $per_page";
                                    $result = mysqli_query($con, $sql);
                                    if ($result) {
                                        if ($total === 0) {
                                            $colspan = 10; // number of table columns
                                            $msg = 'No results found.';
                                            if (!empty($search)) {
                                                $msg = 'No results found for "' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '".';
                                            }
                                            echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted">' . $msg . '</td></tr>';
                                        } else {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $id = $row['id'];
                                                $firstname = $row['firstname'];
                                                $middlename = $row['middlename'];
                                                $surname = $row['surname'];
                                                $fullName = trim(preg_replace('/\s+/', ' ', $firstname . ' ' . ($middlename ?? '') . ' ' . $surname));
                                                $dateofbirth = $row['dateofbirth'];
                                                $placeofbirth = $row['placeofbirth'];
                                                $sex = $row['sex'];
                                                $weight = $row['weight'];
                                                $height = $row['height'];
                                                $bloodtype = $row['bloodtype'];
                                                $nationality = $row['nationality'];
                                                $parentIdForInfant = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
                                                $viewDetailsUrl = 'view_details.php?parent_id=' . $parentIdForInfant;
                                                ?>

                                                <tr>
                                                    <th scope="row"><?php echo $id; ?></th>
                                                    <td><?php echo $fullName; ?></td>
                                                    <td><?php echo $dateofbirth; ?></td>
                                                    <td><?php echo $placeofbirth; ?></td>
                                                    <td><?php echo $sex; ?></td>
                                                    <td><?php echo $weight; ?></td>
                                                    <td><?php echo $height; ?></td>
                                                    <td><?php echo $bloodtype; ?></td>
                                                    <td><?php echo $nationality; ?></td>
                                                    <td class="d-flex gap-1 justify-content-center action-icons">
                                                        <a href="<?php echo htmlspecialchars($viewDetailsUrl); ?>" class="btn btn-outline-info btn-sm" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if (!$isParent): ?>
                                                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#formModal_<?php echo $id; ?>" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                                            <?php if ($showDeleteButton): ?>
                                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?php echo $id; ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- Modal for EDIT -->
                                                <div class="modal fade" id="formModal_<?php echo $id; ?>" tabindex="-1" aria-labelledby="formModalLabel_<?php echo $id; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title text-primary" id="formModalLabel_<?php echo $id; ?>">Edit Infant Information</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST" action="viewinfant.php">
                                                                    <input type="hidden" name="new_id" value="<?php echo $id; ?>">

                                                                    <div class="mb-3">
                                                                        <label class="form-label">First Name</label>
                                                                        <input type="text" class="form-control" name="new_firstname" value="<?php echo $firstname; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Middle Name</label>
                                                                        <input type="text" class="form-control" name="new_middle" value="<?php echo $middlename; ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Surname</label>
                                                                        <input type="text" class="form-control" name="new_surname" value="<?php echo $surname; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Date of Birth</label>
                                                                        <input type="date" class="form-control" name="new_dateofbirth" value="<?php echo $dateofbirth; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Place of Birth</label>
                                                                        <input type="text" class="form-control" name="new_birthplace" value="<?php echo $placeofbirth; ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Nationality</label>
                                                                        <input type="text" class="form-control" name="new_nationality" value="<?php echo $nationality; ?>">
                                                                    </div>
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">Weight (kg)</label>
                                                                            <input type="number" class="form-control" name="new_weight" value="<?php echo $weight; ?>" step="0.01" required>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">Height (cm)</label>
                                                                            <input type="number" class="form-control" name="new_height" value="<?php echo $height; ?>" step="0.1" required>
                                                                        </div>
                                                                    </div>

                                                                    <div class="text-center">
                                                                        <button type="submit" name="new_submit" class="btn btn-primary w-50">Update</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            } // end while
                                        } // end else
                                    } // end if result
                                    ?>
                                </tbody>
                            </table>
                            <?php if (isset($total_pages) && $total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mt-3">
                                        <?php
                                        $search_param = $search !== '' ? '&search=' . urlencode($search) : '';
                                        $sex_param = (isset($_GET['sex']) && in_array($_GET['sex'], ['Male', 'Female'])) ? '&sex=' . urlencode($_GET['sex']) : (isset($_GET['sex']) && $_GET['sex'] === '' ? '&sex=' : '');
                                        $birth_from_param = (!empty($birth_from)) ? '&birth_from=' . urlencode($birth_from) : (isset($_GET['birth_from']) && $_GET['birth_from'] === '' ? '&birth_from=' : '');
                                        $birth_to_param = (!empty($birth_to)) ? '&birth_to=' . urlencode($birth_to) : (isset($_GET['birth_to']) && $_GET['birth_to'] === '' ? '&birth_to=' : '');
                                        $prev_page = $page - 1;
                                        $next_page = $page + 1;
                                        ?>
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="viewinfant.php?page=<?php echo max(1, $prev_page) . $search_param . $sex_param . $birth_from_param . $birth_to_param; ?>" aria-label="Previous">Previous</a>
                                        </li>
                                        <?php
                                        $start = max(1, $page - 2);
                                        $end = min($total_pages, $page + 2);
                                        for ($p = $start; $p <= $end; $p++):
                                        ?>
                                            <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>"><a class="page-link" href="viewinfant.php?page=<?php echo $p . $search_param . $sex_param . $birth_from_param . $birth_to_param; ?>"><?php echo $p; ?></a></li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="viewinfant.php?page=<?php echo min($total_pages, $next_page) . $search_param . $sex_param . $birth_from_param . $birth_to_param; ?>" aria-label="Next">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
</body>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `viewinfant.php?deleteid=${id}`;
            }
        });
    }

    function confirmEdit(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to edit this entry?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, edit it!'
        }).then((result) => {
            if (result.isConfirmed) {
                var myModal = new bootstrap.Modal(document.getElementById(`formModal_${id}`));
                myModal.show();
            }
        });
    }

    // Search now uses the form submit button and GET parameter `search`.
    // Live (keyup) AJAX search has been removed.

    function clearSearch() {
        var form = document.getElementById('searchForm');
        var input = document.getElementById('search');
        if (input) input.value = '';
        if (form) form.submit();
    }
</script>
<script src="assets/js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>