<?php
session_start();

require_once __DIR__ . '/dbForm.php';
$barangays = include __DIR__ . '/config/barangays.php';

$userRole = $_SESSION['user']['role'] ?? '';
$isHealthWorkerRole = ($userRole === 'healthworker');

if (!isset($_SESSION['user']) || !in_array($userRole, ['admin', 'healthworker'], true)) {
    header('Location: index.php');
    exit();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_healthworker'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $barangayAssigned = $_POST['barangay_assigned'] ?? '';
    $licenseNumber = trim($_POST['license_number'] ?? '');
    $position = trim($_POST['position'] ?? '');

    if ($userId <= 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid health worker record.'];
    } elseif ($firstname === '' || $lastname === '') {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'First and last name are required.'];
    } elseif (!in_array($barangayAssigned, $barangays ?? [], true)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please select a valid barangay assignment.'];
    } else {
        $firstnameEsc = mysqli_real_escape_string($con, $firstname);
        $middlenameEsc = $middlename !== '' ? "'" . mysqli_real_escape_string($con, $middlename) . "'" : 'NULL';
        $lastnameEsc = mysqli_real_escape_string($con, $lastname);
        $contactEsc = $contact !== '' ? "'" . mysqli_real_escape_string($con, $contact) . "'" : 'NULL';
        $barangayEsc = mysqli_real_escape_string($con, $barangayAssigned);
        $licenseEsc = $licenseNumber !== '' ? "'" . mysqli_real_escape_string($con, $licenseNumber) . "'" : 'NULL';
        $positionEsc = $position !== '' ? "'" . mysqli_real_escape_string($con, $position) . "'" : 'NULL';

        $updateQuery = "
            UPDATE healthworker
            SET firstname = '$firstnameEsc',
                middlename = $middlenameEsc,
                lastname = '$lastnameEsc',
                contact_number = $contactEsc,
                barangay_assigned = '$barangayEsc',
                license_number = $licenseEsc,
                position = $positionEsc
            WHERE user_id = $userId
        ";

        if (mysqli_query($con, $updateQuery)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Health worker updated successfully.'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update health worker.'];
        }
    }

    header('Location: healthworker.php');
    exit();
}

$healthWorkers = [];
$userId = (int) ($_SESSION['user']['id'] ?? 0);

$whereClause = '';
if ($isHealthWorkerRole && $userId > 0) {
    $whereClause = 'WHERE hw.user_id = ' . $userId;
}

$query = "SELECT hw.user_id,
                 hw.firstname,
                 hw.middlename,
                 hw.lastname,
                 TRIM(CONCAT_WS(' ', hw.firstname, hw.middlename, hw.lastname)) AS full_name,
                 hw.contact_number,
                 hw.barangay_assigned,
                 hw.license_number,
                 hw.position
          FROM healthworker hw
          $whereClause
          ORDER BY hw.created_at DESC";

$result = mysqli_query($con, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $healthWorkers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Workers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>

<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-people"></i> Registered Health Workers</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="card card-shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">User ID</th>
                                <th scope="col">Full Name</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Barangay Assigned</th>
                                <th scope="col">License Number</th>
                                <th scope="col">Position</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($healthWorkers)): ?>
                                <?php foreach ($healthWorkers as $worker): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($worker['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['contact_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($worker['barangay_assigned']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['license_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($worker['position'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary edit-healthworker-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editHealthWorkerModal"
                                                    data-user-id="<?php echo htmlspecialchars($worker['user_id']); ?>"
                                                    data-firstname="<?php echo htmlspecialchars($worker['firstname'] ?? ''); ?>"
                                                    data-middlename="<?php echo htmlspecialchars($worker['middlename'] ?? ''); ?>"
                                                    data-lastname="<?php echo htmlspecialchars($worker['lastname'] ?? ''); ?>"
                                                    data-contact="<?php echo htmlspecialchars($worker['contact_number'] ?? ''); ?>"
                                                    data-barangay="<?php echo htmlspecialchars($worker['barangay_assigned'] ?? ''); ?>"
                                                    data-license="<?php echo htmlspecialchars($worker['license_number'] ?? ''); ?>"
                                                    data-position="<?php echo htmlspecialchars($worker['position'] ?? ''); ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No health workers registered yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editHealthWorkerModal" tabindex="-1" aria-labelledby="editHealthWorkerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editHealthWorkerModalLabel">Edit Health Worker</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="edit_user_id_display" class="form-label">User ID</label>
                                <input type="text" class="form-control" id="edit_user_id_display" readonly>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_firstname" name="firstname" <?php echo $isHealthWorkerRole ? 'readonly' : ''; ?> required>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_middlename" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="edit_middlename" name="middlename" <?php echo $isHealthWorkerRole ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-3">
                                <label for="edit_lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_lastname" name="lastname" <?php echo $isHealthWorkerRole ? 'readonly' : ''; ?> required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="edit_contact_number" name="contact_number" placeholder="09xxxxxxxxx" <?php echo $isHealthWorkerRole ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_barangay_assigned" class="form-label">Barangay Assigned <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_barangay_assigned" name="barangay_assigned" required>
                                    <option value="" disabled>Select barangay</option>
                                    <?php foreach (($barangays ?? []) as $barangay): ?>
                                        <option value="<?php echo htmlspecialchars($barangay); ?>"><?php echo htmlspecialchars($barangay); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_license_number" class="form-label">License Number</label>
                                <input type="text" class="form-control" id="edit_license_number" name="license_number" <?php echo $isHealthWorkerRole ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="edit_position" name="position" <?php echo $isHealthWorkerRole ? 'readonly' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_healthworker" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editHealthWorkerModal = document.getElementById('editHealthWorkerModal');
        if (editHealthWorkerModal) {
            editHealthWorkerModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                if (!button) {
                    return;
                }
                const form = editHealthWorkerModal.querySelector('form');
                const userId = button.getAttribute('data-user-id') || '';
                form.querySelector('#edit_user_id').value = userId;
                form.querySelector('#edit_user_id_display').value = userId;
                form.querySelector('#edit_firstname').value = button.getAttribute('data-firstname') || '';
                form.querySelector('#edit_middlename').value = button.getAttribute('data-middlename') || '';
                form.querySelector('#edit_lastname').value = button.getAttribute('data-lastname') || '';
                form.querySelector('#edit_contact_number').value = button.getAttribute('data-contact') || '';
                form.querySelector('#edit_license_number').value = button.getAttribute('data-license') || '';
                form.querySelector('#edit_position').value = button.getAttribute('data-position') || '';
                const barangaySelect = form.querySelector('#edit_barangay_assigned');
                const barangayValue = button.getAttribute('data-barangay') || '';
                if (barangaySelect) {
                    barangaySelect.value = barangayValue;
                }
            });
        }
    </script>
</body>

</html>
