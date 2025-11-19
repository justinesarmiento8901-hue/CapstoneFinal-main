<?php
session_start(); // Ensure session is started
include 'dbForm.php'; // Include database connection file

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

$isParent = ($role === 'parent');
$parentEmail = ($isParent && isset($_SESSION['user']['email'])) ? mysqli_real_escape_string($con, $_SESSION['user']['email']) : null;

// Handle deletion
if (isset($_GET['deleteid'])) {
    if (!$showDeleteButton) {
        header('Location: view_parents.php');
        exit();
    }

    $id = mysqli_real_escape_string($con, $_GET['deleteid']);
    $sql = "DELETE FROM parents WHERE id = '$id'";
    $result = mysqli_query($con, $sql);
    if ($result) {
        // Log deletion
        $entityId = (int) $id;
        logAudit(
            $con,
            $_SESSION['user']['id'] ?? null,
            'delete',
            'parents',
            $entityId,
            "Deleted parent record with ID $entityId"
        );

        echo "<script>
        Swal.fire({
            title: 'Success!',
            text: 'Parent information deleted successfully.',
            icon: 'success'
        }).then(() => {
            window.location.href = 'view_parents.php';
        });
        </script>";
    } else {
        echo "<script>
        Swal.fire('Error', 'Failed to delete record.', 'error');
        </script>";
    }
}

// Handle editing
if (isset($_POST['update_submit'])) {
    $id = $_POST['update_id'];
    $first_name = $_POST['update_first_name'];
    $last_name = $_POST['update_last_name'];
    $phone = $_POST['update_phone_number'];
    $barangay = $_POST['update_barangay'];
    $address = $_POST['update_address'];

    $sql = "UPDATE parents SET 
                first_name = '$first_name', 
                last_name = '$last_name', 
                phone = '$phone', 
                barangay = '$barangay', 
                address = '$address'
            WHERE id = '$id'";

    $result = mysqli_query($con, $sql);
    if ($result) {
        // Log update
        $entityId = (int) $id;
        logAudit(
            $con,
            $_SESSION['user']['id'] ?? null,
            'edit',
            'parents',
            $entityId,
            "Updated parent record with ID $entityId"
        );

        echo "<script>
        Swal.fire({
            title: 'Success!',
            text: 'Parent information updated successfully.',
            icon: 'success'
        }).then(() => {
            window.location.href = 'view_parents.php';
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
        <h4 class="mb-4"> Infant Record System</h4>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="addinfant.php"><i class="bi bi-person-fill-add"></i> Add Infant</a>
        <?php if (!$isParent && isset($_SESSION['user']['role']) && ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'healthworker')): ?>
            <a href="add_parents.php"><i class="bi bi-person-plus"></i> Add Parent</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['user']['role']) && ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'healthworker' || $_SESSION['user']['role'] === 'parent')): ?>
            <a href="view_parents.php" class="active"><i class="bi bi-people"></i> Parent Records</a>
        <?php endif; ?>
        <a href="viewinfant.php"><i class="bi bi-journal-medical"></i> Infant Records</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
        <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
        <?php if (!$isParent): ?>
            <a href="vaccination_schedule.php"><i class="bi bi-journal-medical"></i> Vaccination Schedule</a>
            <?php if (isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], ['admin', 'report'], true)): ?>
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
            <div class="card card-shadow">
                <div class="card-header bg-white border-0 py-3">
                    <h3 class="dashboard-title"><i class="bi bi-people"></i>Parent Information</h3>
                </div>
                <div class="card-body">
                    <!-- Add Infant Button -->
                    <div class="mb-3 text-start">
                        <a href="addinfant.php" class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> Add Infant</a>
                    </div>
                    <?php if (!$isParent): ?>
                        <form id="searchForm" method="GET" action="view_parents.php" class="mb-4">
                            <div class="input-group search-bar search-bar-elevated" style="max-width:420px;">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Search by ID, Name, or Email...">
                                <button class="btn btn-outline-secondary" type="button" title="Clear" onclick="clearSearch()"><i class="bi bi-x-lg"></i></button>
                                <button class="btn btn-primary" type="submit">Search</button>
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
                                        <th scope="col">Phone Number</th>
                                        <th scope="col">Barangay</th>
                                        <th scope="col">Address</th>
                                        <th scope="col">Infant IDs</th>
                                        <th scope="col">Infant Names</th>
                                        <?php if (!$isParent): ?>
                                            <th scope="col">Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $search = (!$isParent && isset($_GET['search'])) ? mysqli_real_escape_string($con, $_GET['search']) : '';

                                    // Pagination settings
                                    $per_page = 10;
                                    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                                    if ($page < 1) $page = 1;
                                    $offset = ($page - 1) * $per_page;

                                    // Build WHERE clause depending on role and search
                                    $where = '';
                                    if ($isParent && $parentEmail) {
                                        $where = "parents.email = '$parentEmail'";
                                        if (!empty($search)) {
                                            $where .= " AND (parents.id LIKE '%$search%' OR parents.first_name LIKE '%$search%' OR parents.last_name LIKE '%$search%' OR parents.email LIKE '%$search%')";
                                        }
                                    } else {
                                        if (!empty($search)) {
                                            $where = "(parents.id LIKE '%$search%' OR parents.first_name LIKE '%$search%' OR parents.last_name LIKE '%$search%' OR parents.email LIKE '%$search%')";
                                        } else {
                                            $where = '1';
                                        }
                                    }

                                    // Get total distinct parent count for pagination
                                    $count_sql = "SELECT COUNT(DISTINCT parents.id) AS total FROM parents LEFT JOIN infantinfo ON parents.id = infantinfo.parent_id WHERE $where";
                                    $count_res = mysqli_query($con, $count_sql);
                                    $total = 0;
                                    if ($count_res) {
                                        $row_count = mysqli_fetch_assoc($count_res);
                                        $total = (int) ($row_count['total'] ?? 0);
                                    }
                                    $total_pages = ($total > 0) ? (int) ceil($total / $per_page) : 1;

                                    // Fetch paginated data
                                    $sql = "SELECT parents.*, 
                                               GROUP_CONCAT(infantinfo.id SEPARATOR ', ') AS infant_ids, 
                                               GROUP_CONCAT(infantinfo.firstname SEPARATOR ', ') AS infant_names
                                            FROM parents
                                            LEFT JOIN infantinfo ON parents.id = infantinfo.parent_id
                                            WHERE $where
                                            GROUP BY parents.id
                                            ORDER BY parents.id ASC
                                            LIMIT $offset, $per_page";

                                    $result = mysqli_query($con, $sql);
                                    if ($result) {
                                        if (!empty($search) && $total === 0) {
                                            $colspan = $isParent ? 7 : 8;
                                            echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted">No results found for "' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '".</td></tr>';
                                        } else {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $id = $row['id'];
                                                $first_name = $row['first_name'];
                                                $last_name = $row['last_name'];
                                                $fullName = trim(preg_replace('/\s+/', ' ', $first_name . ' ' . $last_name));
                                                $phone = $row['phone'];
                                                $barangay = $row['barangay'];
                                                $address = $row['address'];
                                                ?>

                                                <tr>
                                                    <th scope="row"><?php echo $id; ?></th>
                                                    <td><?php echo $fullName; ?></td>
                                                    <td><?php echo $phone; ?></td>
                                                    <td><?php echo $barangay; ?></td>
                                                    <td><?php echo $address; ?></td>
                                                    <td><?php echo $row['infant_ids'] ?: 'N/A'; ?></td>
                                                    <td><?php echo $row['infant_names'] ?: 'N/A'; ?></td>
                                                    <?php if (!$isParent): ?>
                                                        <td class="d-flex gap-1 justify-content-center action-icons">
                                                            <button class="btn btn-outline-success btn-sm" onclick="confirmEdit(<?php echo $id; ?>)" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                                            <?php if ($showDeleteButton): ?>
                                                                <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?php echo $id; ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>

                                                <!-- Modal for EDIT -->
                                                <div class="modal fade" id="formModal_<?php echo $id; ?>" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title text-primary" id="formModalLabel">Edit Parent Information</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST" action="view_parents.php">
                                                                    <input type="hidden" name="update_id" value="<?php echo $id; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="first_name" class="form-label">First Name</label>
                                                                        <input type="text" class="form-control" name="update_first_name" value="<?php echo $first_name; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="last_name" class="form-label">Last Name</label>
                                                                        <input type="text" class="form-control" name="update_last_name" value="<?php echo $last_name; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="phone_number" class="form-label">Phone Number</label>
                                                                        <input type="text" class="form-control" name="update_phone_number" value="<?php echo $phone; ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="barangay" class="form-label">Barangay</label>
                                                                        <input type="text" class="form-control" name="update_barangay" value="<?php echo $barangay; ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="address" class="form-label">Address</label>
                                                                        <textarea class="form-control" name="update_address"><?php echo $address; ?></textarea>
                                                                    </div>
                                                                    <div class="text-center">
                                                                        <button type="submit" name="update_submit" class="btn btn-primary w-50">Submit</button>
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
                                        $prev_page = $page - 1;
                                        $next_page = $page + 1;
                                        ?>
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="view_parents.php?page=<?php echo max(1, $prev_page) . $search_param; ?>" aria-label="Previous">Previous</a>
                                        </li>
                                        <?php
                                        $start = max(1, $page - 2);
                                        $end = min($total_pages, $page + 2);
                                        for ($p = $start; $p <= $end; $p++):
                                        ?>
                                            <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>"><a class="page-link" href="view_parents.php?page=<?php echo $p . $search_param; ?>"><?php echo $p; ?></a></li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="view_parents.php?page=<?php echo min($total_pages, $next_page) . $search_param; ?>" aria-label="Next">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                    window.location.href = `view_parents.php?deleteid=${id}`;
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
        // Live (keyup) AJAX search has been removed per request.

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