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
                    <h3 class="dashboard-title"><i class="bi bi-baby"></i>Infant Information</h3>
                </div>
                <div class="card-body">
                    <?php if (!$isParent): ?>
                        <form method="GET" action="viewinfant.php" class="mb-4">
                            <div class="input-group search-bar search-bar-elevated">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="search" class="form-control" placeholder="Search by ID, Name, or Birthplace...">
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
                                    // FOR VIEWING INFANT INFORMATION

                                    // $sql = "SELECT * FROM infantinfo";
                                    $search = (!$isParent && isset($_GET['search'])) ? mysqli_real_escape_string($con, $_GET['search']) : '';

                                    if ($isParent && $parentEmail) {
                                        // Resolve parent id from email
                                        $parentRes = mysqli_query($con, "SELECT id FROM parents WHERE email = '$parentEmail' LIMIT 1");
                                        $parentIdFilter = 0;
                                        if ($parentRes && mysqli_num_rows($parentRes) > 0) {
                                            $parentRow = mysqli_fetch_assoc($parentRes);
                                            $parentIdFilter = intval($parentRow['id']);
                                        }
                                        if (!empty($search)) {
                                            $sql = "SELECT * FROM infantinfo WHERE parent_id = '$parentIdFilter' AND (
                                    id LIKE '%$search%' OR
                                    firstname LIKE '%$search%' OR 
                                    middlename LIKE '%$search%' OR 
                                    surname LIKE '%$search%' OR 
                                    placeofbirth LIKE '%$search%')";
                                        } else {
                                            $sql = "SELECT * FROM infantinfo WHERE parent_id = '$parentIdFilter'";
                                        }
                                    } else {
                                        if (!empty($search)) {
                                            $sql = "SELECT * FROM infantinfo WHERE 
                                    id LIKE '%$search%' OR
                                    firstname LIKE '%$search%' OR 
                                    middlename LIKE '%$search%' OR 
                                    surname LIKE '%$search%' OR 
                                    placeofbirth LIKE '%$search%'";
                                        } else {
                                            $sql = "SELECT * FROM infantinfo";
                                        }
                                    }

                                    $result = mysqli_query($con, $sql);
                                    if ($result) {
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
                                        } // Close the while loop
                                    } // Close the if block
                                    ?>
                                </tbody>
                            </table>
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

    // Search function AJAX

    $(document).ready(function() {
        $("#search").on("keyup", function() {
            let searchText = $(this).val(); // Get input value
            $.ajax({
                url: "search_infant.php", // PHP file that will handle search
                method: "GET",
                data: {
                    search: searchText
                },
                success: function(response) {
                    $("tbody").html(response); // Update table with results
                }
            });
        });
    });
</script>
<script src="assets/js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>