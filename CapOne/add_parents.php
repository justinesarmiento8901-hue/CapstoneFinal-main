<?php
include 'dbForm.php';
session_start();

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

$role = $_SESSION['user']['role'] ?? '';
$barangays = [];
$barangayConfig = __DIR__ . '/config/barangays.php';
if (is_readable($barangayConfig)) {
    $loadedBarangays = include $barangayConfig;
    if (is_array($loadedBarangays)) {
        $barangays = $loadedBarangays;
    }
}

$purokOptions = [
    'Purok 1',
    'Purok 2',
    'Purok 3',
    'Purok 4',
    'Purok 5',
    'Purok 6',
    'Purok 7'
];

$municipalities = [
    'Aliaga',
    'Bongabon',
    'Cabiao',
    'Carranglan',
    'Gabaldon',
    'General Mamerto Natividad',
    'General Tinio',
    'Guimba',
    'Jaen',
    'Laur',
    'Licab',
    'Llanera',
    'Lupao',
    'Nampicuan',
    'Pantabangan',
    'Peñaranda',
    'Quezon',
    'Rizal',
    'San Antonio',
    'San Isidro',
    'San Leonardo',
    'Santa Rosa',
    'Santo Domingo',
    'Talavera',
    'Talugtug',
    'Zaragoza',
    'Cabanatuan City',
    'Gapan City',
    'Science City of Muñoz',
    'Palayan City',
    'San Jose City'
];

$provinceOptions = [
    'Aurora',
    'Bataan',
    'Bulacan',
    'Nueva Ecija',
    'Pampanga',
    'Tarlac',
    'Zambales'
];

if (isset($_POST['submit'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $purok = trim($_POST['purok'] ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $province = trim($_POST['province'] ?? '');

    if (!in_array($barangay, $barangays, true)) {
        $barangay = '';
    }
    if (!in_array($purok, $purokOptions, true)) {
        $purok = '';
    }
    if (!in_array($municipality, $municipalities, true)) {
        $municipality = '';
    }
    if (!in_array($province, $provinceOptions, true)) {
        $province = '';
    }

    if ($barangay === '' || $purok === '' || $municipality === '' || $province === '') {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please select a barangay, purok, municipality, and province.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    } else {
        $stmt = $con->prepare("INSERT INTO parents (first_name, last_name, phone, email, address, barangay, purok, Municipality, Province) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssssss', $first_name, $last_name, $phone, $email, $address, $barangay, $purok, $municipality, $province);

        if ($stmt->execute()) {
            $newParentId = $stmt->insert_id;
            $stmt->close();

            // Log creation in audit trail
            logAudit(
                $con,
                $_SESSION['user']['id'] ?? null,
                'add',
                'parents',
                (int) $newParentId,
                "Added parent record with ID $newParentId"
            );

            // Also create a corresponding users account with role 'parent' if missing
            $fullName = mysqli_real_escape_string($con, trim($first_name . ' ' . $last_name));
            $emailEsc = mysqli_real_escape_string($con, $email);
            $accountNote = '';
            $tempPassword = null;

            $check = mysqli_query($con, "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
            if ($check && mysqli_num_rows($check) === 0) {
                // Generate temporary password
                try {
                    $tempPassword = bin2hex(random_bytes(4)); // 8-char hex
                } catch (Exception $e) {
                    $tempPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 8);
                }
                $hashed = password_hash($tempPassword, PASSWORD_BCRYPT);
                $createdAt = date('Y-m-d');
                $insertUserSql = "INSERT INTO users (email, password, name, role, created_at) VALUES ('$emailEsc', '$hashed', '$fullName', 'parent', '$createdAt')";
                if (mysqli_query($con, $insertUserSql)) {
                    $accountNote = "\nLogin Email: $email\nTemporary Password: $tempPassword";
                } else {
                    $accountNote = "\n(Note: Failed to auto-create login account.)";
                    $tempPassword = null;
                }
            } else {
                $accountNote = "\n(Account already exists for this email.)";
            }

            $successMsg = "Parent added successfully!" . $accountNote;
            $tableTempPassword = $tempPassword ?? 'N/A';

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const newParentData = " . json_encode([
                'id' => $newParentId,
                'email' => $email,
                'tempPassword' => $tableTempPassword,
                'accountNote' => $accountNote,
            ]) . ";
                    Swal.fire({
                        title: 'Success!',
                        text: " . json_encode($successMsg) . ",
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (typeof window.addParentRow === 'function') {
                                window.addParentRow(newParentData);
                            }
                        }
                    });
                });
            </script>";
        } else {
            $stmt->close();
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to add parent information!',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infant Record System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <!-- Sidebar Menu -->
    <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-4">Infant Record System</h4>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="addinfant.php"><i class="bi bi-person-fill-add"></i> Add Infant</a>
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="add_parents.php" class="active"><i class="bi bi-person-plus"></i> Add Parent</a>
            <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
        <?php endif; ?>
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
                <h3 class="dashboard-title mb-4"><i class="bi bi-person-plus"></i>Parent Information Form</h3>
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" placeholder="Enter first name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" placeholder="Enter last name" required>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" placeholder="09XXXXXXXXX">
                        <div class="form-help">Use PH format 09XXXXXXXXX or +63XXXXXXXXXX.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter email" required>
                    </div>

                    <div class="col-md-6">
                        <label for="barangay" class="form-label">Barangay</label>
                        <select class="form-select" name="barangay" required>
                            <option value="">-- Select Barangay --</option>
                            <?php foreach ($barangays as $barangayOption): ?>
                                <option value="<?php echo htmlspecialchars($barangayOption); ?>"><?php echo htmlspecialchars($barangayOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="purok" class="form-label">Purok</label>
                        <select class="form-select" name="purok" required>
                            <option value="">-- Select Purok --</option>
                            <?php foreach ($purokOptions as $purokOption): ?>
                                <option value="<?php echo htmlspecialchars($purokOption); ?>"><?php echo htmlspecialchars($purokOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="municipality" class="form-label">Municipality</label>
                        <select class="form-select" name="municipality" required>
                            <option value="">-- Select Municipality --</option>
                            <?php foreach ($municipalities as $municipalityOption): ?>
                                <option value="<?php echo htmlspecialchars($municipalityOption); ?>"><?php echo htmlspecialchars($municipalityOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="province" class="form-label">Province</label>
                        <select class="form-select" name="province" required>
                            <option value="">-- Select Province --</option>
                            <?php foreach ($provinceOptions as $provinceOption): ?>
                                <option value="<?php echo htmlspecialchars($provinceOption); ?>"><?php echo htmlspecialchars($provinceOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="Enter address"></textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-outline-danger"><i class="bi bi-eraser"></i>Clear</button>
                        <button type="submit" name="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i>Submit</button>
                    </div>
                </form>
            </div>

            <div class="card card-shadow p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="dashboard-title mb-0"><i class="bi bi-table"></i> Newly Added Parents</h4>
                    <button type="button" id="clear-new-parents" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-trash"></i> Reset Table
                    </button>
                </div>
                <div class="table-modern">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Temporary Pass</th>
                                </tr>
                            </thead>
                            <tbody id="new-parents-table-body">
                                <tr class="text-center text-muted" data-placeholder="true">
                                    <td colspan="3">No parent added in this session yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        (function() {
            const tableBody = document.getElementById('new-parents-table-body');
            const resetButton = document.getElementById('clear-new-parents');
            const barangaySelect = document.querySelector('select[name="barangay"]');
            const purokSelect = document.querySelector('select[name="purok"]');
            const municipalitySelect = document.querySelector('select[name="municipality"]');
            const provinceSelect = document.querySelector('select[name="province"]');
            const addressField = document.querySelector('textarea[name="address"]');
            let addressDirty = false;

            function createPlaceholderRow() {
                const row = document.createElement('tr');
                row.className = 'text-center text-muted';
                row.setAttribute('data-placeholder', 'true');
                row.innerHTML = '<td colspan="3">No parent added in this session yet.</td>';
                return row;
            }

            function buildAddress() {
                const parts = [
                    barangaySelect?.value || '',
                    purokSelect?.value || '',
                    municipalitySelect?.value || '',
                    provinceSelect?.value || ''
                ].filter(Boolean);
                return parts.join(', ');
            }

            function updateAddressIfClean() {
                if (!addressField || addressDirty) {
                    return;
                }
                addressField.value = buildAddress();
            }

            function attachAutoFill(selectEl) {
                if (!selectEl) {
                    return;
                }
                selectEl.addEventListener('change', updateAddressIfClean);
            }

            if (addressField) {
                addressField.addEventListener('input', function() {
                    addressDirty = addressField.value.trim().length > 0 && addressField.value !== buildAddress();
                });
            }

            attachAutoFill(barangaySelect);
            attachAutoFill(purokSelect);
            attachAutoFill(municipalitySelect);
            attachAutoFill(provinceSelect);
            updateAddressIfClean();

            window.addParentRow = function(data) {
                if (!tableBody) {
                    return;
                }

                const placeholderRow = tableBody.querySelector('[data-placeholder="true"]');
                if (placeholderRow) {
                    tableBody.removeChild(placeholderRow);
                }

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${data.id}</td>
                    <td>${data.email}</td>
                    <td>${data.tempPassword}</td>
                `;
                tableBody.appendChild(row);
            };

            if (resetButton && tableBody) {
                resetButton.addEventListener('click', function() {
                    tableBody.innerHTML = '';
                    tableBody.appendChild(createPlaceholderRow());
                });
            }
        })();
    </script>
</body>

</html>