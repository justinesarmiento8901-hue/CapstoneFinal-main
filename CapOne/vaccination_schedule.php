<?php
// vaccination_schedule.php
include 'dbForm.php'; // must create/include this; provides $con (MySQLi)
session_start();
$role = $_SESSION['user']['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$barangays = [];
$barangayConfig = __DIR__ . '/config/barangays.php';
if (is_readable($barangayConfig)) {
    $loadedBarangays = include $barangayConfig;
    if (is_array($loadedBarangays)) {
        $barangays = $loadedBarangays;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Vaccination Schedule Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .pointer {
            cursor: pointer;
        }

        .sortable {
            cursor: pointer;
            user-select: none;
        }

        .sortable .sort-icon {
            font-size: 0.75rem;
            margin-left: 4px;
        }

        .small-col {
            width: 80px;
            text-align: center;
        }

        /* Modern table styling */
        .table {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px 12px;
        }

        .table tbody tr {
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table tbody td {
            border: none;
            padding: 12px;
            vertical-align: middle;
        }

        /* Infant group styling */
        .infant-group-header {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            font-weight: 600;
            color: #1565c0;
        }

        .infant-group-header td {
            border-top: 2px solid #2196f3 !important;
            padding: 15px 12px;
        }

        .infant-group-header:hover {
            background: linear-gradient(135deg, #bbdefb 0%, #90caf9 100%);
        }

        /* Vaccine rows styling */
        .vaccine-row {
            background-color: #fafafa;
            border-left: 3px solid #e0e0e0;
        }

        .vaccine-row td:first-child {
            padding-left: 25px;
            position: relative;
        }

        .vaccine-row td:first-child::before {
            content: "→";
            position: absolute;
            left: 10px;
            color: #9e9e9e;
            font-weight: bold;
        }

        .vaccine-row:hover {
            background-color: #f0f0f0;
            border-left-color: #2196f3;
        }

        /* Status badges */
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%) !important;
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%) !important;
            color: white !important;
        }

        /* Action buttons */
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
            border-radius: 4px;
            margin: 0 2px;
            transition: all 0.2s ease;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            border: none;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Checkbox styling */
        .form-check-input {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid #dee2e6;
        }

        .form-check-input:checked {
            background-color: #4caf50;
            border-color: #4caf50;
        }

        /* Infant link styling */
        .infantLink {
            color: #1976d2;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .infantLink:hover {
            color: #0d47a1;
            text-decoration: underline;
        }

        /* Container styling */
        .container-fluid {
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        /* Header styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
    </style>
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
        <?php if ($role === 'admin' || $role === 'healthworker'): ?>
            <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
        <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
        <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
        <?php if ($role !== 'parent'): ?>
            <a href="vaccination_schedule.php" class="active"><i class="bi bi-journal-medical"></i> Vaccination Schedule</a>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="dashboard-title"><i class="bi bi-syringe"></i>Vaccination Schedule Management</h3>
                    <small class="text-muted">Vaccines are grouped by infant to reduce redundancy</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sort-alpha-down"></i> Sort
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item sort-option" href="#" data-sort="infant" data-default-direction="asc">Infant (A-Z)</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="vaccine" data-default-direction="asc">Vaccine (A-Z)</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="next_dose" data-default-direction="asc">Next Dose (Earliest First)</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="status" data-default-direction="asc">Status (Pending First)</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" id="resetStatusFilter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                    </button>
                    <?php if ($role !== 'parent'): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEditModal" id="openAddModal">
                            <i class="fas fa-plus"></i> Add New Schedule
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table -->
            <div class="card card-shadow">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="mainScheduleTable">
                            <thead class="align-middle">
                                <tr>
                                    <th>#</th>
                                    <th>Infant</th>
                                    <th>Parent</th>
                                    <th>Contact</th>
                                    <th>Today Date</th>
                                    <th class="sortable" data-sort="vaccine">Vaccine<span class="sort-icon"></span></th>
                                    <th class="sortable" data-sort="next_dose">Next Dose<span class="sort-icon"></span></th>
                                    <th>Time</th>
                                    <th class="small-col sortable" data-sort="status">Status<span class="sort-icon"></span></th>
                                    <th>Remarks</th>
                                    <th class="small-col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleBody">
                                <!-- filled by AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <div class="modal fade" id="addEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="addEditForm">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTitle">Add Vaccination Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="vacc_id" id="vacc_id" value="">
                        <div class="mb-2 position-relative" id="infantSearchWrapper">
                            <label class="form-label">Search Infant</label>
                            <input type="text" id="modalsearch_infant" class="form-control" placeholder="Type infant name..." autocomplete="off" required>
                            <div id="infantResults" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                            <input type="hidden" name="infant_id" id="infant_id">
                            <input type="hidden" name="parent_phone" id="parent_phone">
                        </div>

                        <div class="mb-2 d-none" id="infantNameDisplayWrapper">
                            <label class="form-label">Infant</label>
                            <input type="text" id="infant_name_display" class="form-control" readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Parent Contact</label>
                            <input type="text" id="parent_phone_display" class="form-control" placeholder="Select an infant to load contact" readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Barangay</label>
                            <input type="text" class="form-control" name="barangay" id="barangay" placeholder="Barangay will load from selected parent" readonly required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Vaccine Name</label>
                            <select class="form-select" name="vaccine_name" id="vaccine_name" required>
                                <option value="">-- Select Vaccine --</option>
                                <?php
                                $vaccine_query = $con->query("SELECT vaccine_name FROM tbl_vaccine_reference ORDER BY id ASC");
                                while ($v = $vaccine_query->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($v['vaccine_name']) . '">' . htmlspecialchars($v['vaccine_name']) . '</option>';
                                }
                                ?>
                            </select>

                        </div>

                        <div class="mb-2">
                            <label class="form-label">Today Date</label>
                            <input type="date" name="date_vaccination" id="date_vaccination" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Next Dose Date</label>
                            <input type="date" name="next_dose_date" id="next_dose_date" class="form-control">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Vaccination Time</label>
                            <input type="time" name="time" id="vaccination_time" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" id="saveBtn">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vaccGrowthModal" tabindex="-1" aria-labelledby="vaccGrowthModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vaccGrowthModalLabel">Update Growth</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="vaccGrowthForm">
                        <input type="hidden" name="vacc_id" id="vaccGrowthVaccId">
                        <input type="hidden" name="infant_id" id="vaccGrowthInfantId">
                        <div class="mb-3">
                            <label class="form-label">Infant Name</label>
                            <input type="text" class="form-control" id="vaccGrowthInfantName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vaccine</label>
                            <input type="text" class="form-control" id="vaccGrowthVaccineName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vaccinated By</label>
                            <input type="text" class="form-control" id="vaccGrowthVaccinatedBy" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sex</label>
                            <input type="text" class="form-control" id="vaccGrowthSex" readonly>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Reference Min Weight (kg)</label>
                                <input type="text" class="form-control" id="vaccGrowthWeightMin" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Max Weight (kg)</label>
                                <input type="text" class="form-control" id="vaccGrowthWeightMax" readonly>
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Reference Min Height (cm)</label>
                                <input type="text" class="form-control" id="vaccGrowthHeightMin" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Max Height (cm)</label>
                                <input type="text" class="form-control" id="vaccGrowthHeightMax" readonly>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Previous Weight (kg)</label>
                                <input type="text" class="form-control" id="vaccGrowthPreviousWeight" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Previous Height (cm)</label>
                                <input type="text" class="form-control" id="vaccGrowthPreviousHeight" readonly>
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Current Weight (kg)</label>
                                <input type="number" class="form-control" id="vaccGrowthCurrentWeight" name="current_weight" step="0.1" pattern="^\d+(\.\d)?$" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Current Height (cm)</label>
                                <input type="number" class="form-control" id="vaccGrowthCurrentHeight" name="current_height" step="0.1" pattern="^\d+(\.\d)?$" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Growth Status</label>
                            <input type="text" class="form-control" id="vaccGrowthStatus" readonly>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="vaccGrowthSaveBtn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>

    <script>
        var statusFilter = '<?php echo htmlspecialchars($statusFilter); ?>';

        function resetVaccineSelect() {
            $('#vaccine_name option').prop('disabled', false).removeAttr('title');
        }

        function updateVaccineSelectState() {
            var disableSelect = $('#status').val() === 'Completed';
            $('#vaccine_name').prop('disabled', disableSelect);
        }

        function applyCompletedVaccines(infantId) {
            resetVaccineSelect();
            if (!infantId) {
                updateVaccineSelectState();
                return;
            }
            $.getJSON('get_completed_vaccines.php', {
                infant_id: infantId
            }, function(resp) {
                if (resp && Array.isArray(resp.completed)) {
                    var completedSet = new Set(resp.completed);
                    resp.completed.forEach(function(name) {
                        $('#vaccine_name option').filter(function() {
                            return $(this).val() === name;
                        }).prop('disabled', true).attr('title', 'Already completed');
                    });
                    var selected = $('#vaccine_name').val();
                    if (selected && completedSet.has(selected)) {
                        $('#vaccine_name').val('');
                    }
                }
                updateVaccineSelectState();
            });
        }

        $(function() {
            var currentSort = '';
            var currentDirection = 'asc';

            function getTodayDate() {
                var today = new Date();
                var year = today.getFullYear();
                var month = String(today.getMonth() + 1).padStart(2, '0');
                var day = String(today.getDate()).padStart(2, '0');
                return year + '-' + month + '-' + day;
            }

            function updateSortIndicators() {
                $('.sortable .sort-icon').text('');
                if (!currentSort) {
                    return;
                }
                var indicator = currentDirection === 'asc' ? '▲' : '▼';
                $(".sortable[data-sort='" + currentSort + "'] .sort-icon").text(indicator);
            }

            // load table
            function loadTable() {
                var requestData = {};
                if (statusFilter) {
                    requestData.status = statusFilter;
                }
                if (currentSort) {
                    requestData.sort = currentSort;
                    requestData.direction = currentDirection;
                }
                $.ajax({
                    url: 'fetch_schedule.php',
                    method: 'GET',
                    data: requestData,
                    success: function(data) {
                        $('#scheduleBody').html(data);
                        updateSortIndicators();
                    },
                    error: function() {
                        $('#scheduleBody').html('<tr><td colspan="11" class="text-center text-danger">Error loading data</td></tr>');
                    }
                });

                if (statusFilter) {
                    $('#status').val(statusFilter);
                }
            }
            loadTable();
            updateSortIndicators();

            $('.sortable').on('click', function() {
                var newSort = $(this).data('sort');
                if (currentSort === newSort) {
                    currentDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort = newSort;
                    currentDirection = 'asc';
                }
                updateSortIndicators();
                loadTable();
            });

            $('#resetStatusFilter').on('click', function() {
                statusFilter = '';
                window.history.replaceState({}, document.title, 'vaccination_schedule.php');
                loadTable();
            });

            $('.sort-option').on('click', function(e) {
                e.preventDefault();
                var selectedSort = $(this).data('sort');
                var defaultDirection = $(this).data('default-direction') === 'desc' ? 'desc' : 'asc';
                if (currentSort === selectedSort) {
                    currentDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort = selectedSort;
                    currentDirection = defaultDirection;
                }
                updateSortIndicators();
                loadTable();
            });

            // open add modal: reset form
            $('#openAddModal').on('click', function() {
                $('#modalTitle').text('Add Vaccination Schedule');
                $('#addEditForm')[0].reset();
                $('#vacc_id').val('');
                $('#parent_phone').val('');
                $('#parent_phone_display').val('');
                $('#barangay').val('');
                $('#date_vaccination').val(getTodayDate());
                $('#vaccine_name').prop('disabled', false);
                resetVaccineSelect();
                $('#status').val('Pending');
                updateVaccineSelectState();
                $('#infantNameDisplayWrapper').addClass('d-none');
                $('#infantSearchWrapper').removeClass('d-none');
                $('#modalsearch_infant').prop('required', true).prop('readonly', false);
                $('#modalsearch_infant').val('');
                $('#infant_name_display').val('');
            });

            $('#status').on('change', updateVaccineSelectState);

            // submit add/edit
            $('#addEditForm').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                $('#saveBtn').attr('disabled', true);

                $.ajax({
                    url: 'add_edit_vaccine.php',
                    method: 'POST',
                    data: form.serialize(),
                    success: function(resp) {
                        $('#saveBtn').attr('disabled', false);
                        var res = resp.trim();
                        if (res === 'success_add' || res === 'success_update') {
                            $('#addEditModal').modal('hide');
                            loadTable();
                            Swal.fire({
                                icon: 'success',
                                title: 'Saved',
                                text: (res === 'success_add') ? 'Schedule added.' : 'Schedule updated.'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res
                            });
                        }
                    },
                    error: function() {
                        $('#saveBtn').attr('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Request failed.'
                        });
                    }
                });
            });

            // delegate edit button
            $(document).on('click', '.editBtn', function() {
                var id = $(this).data('id');
                // fetch single record via AJAX to populate form
                $.ajax({
                    url: 'fetch_single_schedule.php',
                    method: 'GET',
                    data: {
                        vacc_id: id
                    },
                    dataType: 'json',
                    success: function(d) {
                        if (d && d.vacc_id) {
                            $('#modalTitle').text('Edit Vaccination Schedule');
                            $('#vacc_id').val(d.vacc_id);
                            $('#infant_id').val(d.infant_id);
                            resetVaccineSelect();
                            $('#vaccine_name').val(d.vaccine_name);
                            $('#date_vaccination').val(d.date_vaccination);
                            if (!d.date_vaccination) {
                                $('#date_vaccination').val(getTodayDate());
                            }
                            $('#next_dose_date').val(d.next_dose_date);
                            var timeValue = d.time ? d.time.substring(0, 5) : '';
                            $('#vaccination_time').val(timeValue);
                            $('#status').val(d.status);
                            updateVaccineSelectState();
                            $('#remarks').val(d.remarks);
                            $('#parent_phone').val('');
                            $('#parent_phone_display').val('');
                            $('#barangay').val(d.barangay || '');
                            $('#infant_name_display').val(d.infant_name || '');
                            $('#infantNameDisplayWrapper').removeClass('d-none');
                            $('#infantSearchWrapper').addClass('d-none');
                            $('#modalsearch_infant').prop('required', false).prop('readonly', true).val('');
                            if (d.infant_id) {
                                $.getJSON('get_parent_phone.php', {
                                    infant_id: d.infant_id
                                }, function(resp) {
                                    if (resp && resp.phone) {
                                        $('#parent_phone').val(resp.phone);
                                        $('#parent_phone_display').val(resp.phone);
                                    }
                                    if (resp && typeof resp.barangay !== 'undefined') {
                                        $('#barangay').val(resp.barangay || '');
                                    }
                                });
                            }
                            var modal = new bootstrap.Modal(document.getElementById('addEditModal'));
                            modal.show();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Record not found.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not fetch record.'
                        });
                    }
                });
            });

            // delegate delete button
            $(document).on('click', '.deleteBtn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete this schedule?',
                    text: "This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'delete_vaccine.php',
                            method: 'POST',
                            data: {
                                vacc_id: id
                            },
                            success: function(resp) {
                                var r = resp.trim();
                                if (r === 'deleted') {
                                    loadTable();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted',
                                        text: 'Record removed.'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: r
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Delete request failed.'
                                });
                            }
                        });
                    }
                });
            });

            var vaccGrowthModalEl = document.getElementById('vaccGrowthModal');
            var vaccGrowthModalInstance = vaccGrowthModalEl ? new bootstrap.Modal(vaccGrowthModalEl) : null;
            var pendingStatusCheckbox = null;
            var pendingVaccId = null;
            var pendingInfantId = null;

            function enforceSingleDecimal(input) {
                input.addEventListener('blur', function() {
                    var value = parseFloat(input.value);
                    if (!isNaN(value)) {
                        input.value = value.toFixed(1);
                    }
                });
            }

            if (vaccGrowthModalEl) {
                enforceSingleDecimal(document.getElementById('vaccGrowthCurrentWeight'));
                enforceSingleDecimal(document.getElementById('vaccGrowthCurrentHeight'));
            }

            function openVaccGrowthModal(checkbox) {
                if (!vaccGrowthModalInstance) {
                    return;
                }
                pendingStatusCheckbox = checkbox;
                pendingVaccId = checkbox.data('id');
                pendingInfantId = checkbox.data('infant-id');
                $('#vaccGrowthVaccId').val(pendingVaccId);
                $('#vaccGrowthInfantId').val(pendingInfantId);
                $('#vaccGrowthInfantName').val(checkbox.data('infant-name') || '');
                $('#vaccGrowthVaccineName').val(checkbox.data('vaccine-name') || '');
                $('#vaccGrowthVaccinatedBy').val('');
                $('#vaccGrowthSex').val('');
                $('#vaccGrowthWeightMin').val('');
                $('#vaccGrowthWeightMax').val('');
                $('#vaccGrowthHeightMin').val('');
                $('#vaccGrowthHeightMax').val('');
                $('#vaccGrowthPreviousWeight').val('');
                $('#vaccGrowthPreviousHeight').val('');
                $('#vaccGrowthCurrentWeight').val('').prop('readonly', false);
                $('#vaccGrowthCurrentHeight').val('').prop('readonly', false);
                $('#vaccGrowthStatus').val('');
                $('#vaccGrowthSaveBtn').prop('disabled', true).text('Loading...');
                vaccGrowthModalInstance.show();
                $.ajax({
                    url: 'get_growth_modal_data.php',
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        infant_id: pendingInfantId
                    },
                    success: function(resp) {
                        if (!resp || !resp.success) {
                            var msg = resp && resp.message ? resp.message : 'Failed to load growth data.';
                            vaccGrowthModalInstance.hide();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg
                            });
                            pendingStatusCheckbox = null;
                            pendingVaccId = null;
                            pendingInfantId = null;
                            return;
                        }
                        var data = resp.data;
                        $('#vaccGrowthVaccinatedBy').val(data.vaccinated_by || '--');
                        $('#vaccGrowthSex').val(data.sex || '--');
                        $('#vaccGrowthWeightMin').val(data.weight_min || '--');
                        $('#vaccGrowthWeightMax').val(data.weight_max || '--');
                        $('#vaccGrowthHeightMin').val(data.height_min || '--');
                        $('#vaccGrowthHeightMax').val(data.height_max || '--');
                        $('#vaccGrowthPreviousWeight').val(data.previous_weight || '--');
                        $('#vaccGrowthPreviousHeight').val(data.previous_height || '--');
                        $('#vaccGrowthCurrentWeight').val(data.current_weight || '');
                        $('#vaccGrowthCurrentHeight').val(data.current_height || '');
                        $('#vaccGrowthStatus').val(data.status || '');
                        if (data.status === 'Completed') {
                            $('#vaccGrowthCurrentWeight').prop('readonly', true);
                            $('#vaccGrowthCurrentHeight').prop('readonly', true);
                            $('#vaccGrowthSaveBtn').prop('disabled', true).text('Completed');
                        } else {
                            $('#vaccGrowthCurrentWeight').prop('readonly', false);
                            $('#vaccGrowthCurrentHeight').prop('readonly', false);
                            $('#vaccGrowthSaveBtn').prop('disabled', false).text('Save');
                        }
                    },
                    error: function() {
                        vaccGrowthModalInstance.hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load growth data.'
                        });
                        pendingStatusCheckbox = null;
                        pendingVaccId = null;
                        pendingInfantId = null;
                    }
                });
            }

            $(document).on('change', '.statusCheckbox', function() {
                var checkbox = $(this);
                var isChecked = checkbox.is(':checked');
                if (isChecked) {
                    checkbox.prop('checked', false);
                    openVaccGrowthModal(checkbox);
                    return;
                }
                $.ajax({
                    url: 'update_status.php',
                    method: 'POST',
                    data: {
                        vacc_id: checkbox.data('id'),
                        status: 'Pending'
                    },
                    success: function(resp) {
                        var r = resp.trim();
                        if (r !== 'ok') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: r
                            });
                            checkbox.prop('checked', true);
                        } else {
                            loadTable();
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Status update failed.'
                        });
                        checkbox.prop('checked', true);
                    }
                });
            });

            $('#vaccGrowthForm').on('submit', function(e) {
                e.preventDefault();
                if (!pendingVaccId || !pendingInfantId) {
                    return;
                }
                $('#vaccGrowthSaveBtn').prop('disabled', true).text('Saving...');
                $.ajax({
                    url: 'save_vaccine_growth.php',
                    method: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(resp) {
                        if (!resp || !resp.success) {
                            var msg = resp && resp.message ? resp.message : 'Failed to save growth data.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg
                            });
                            return;
                        }
                        if (vaccGrowthModalInstance) {
                            vaccGrowthModalInstance.hide();
                        }
                        if (pendingStatusCheckbox) {
                            pendingStatusCheckbox.prop('checked', true);
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: resp.message || 'Growth data saved.'
                        });
                        loadTable();
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to save growth data.'
                        });
                    },
                    complete: function() {
                        $('#vaccGrowthSaveBtn').prop('disabled', false).text('Save');
                    }
                });
            });

            $('#vaccGrowthModal').on('hidden.bs.modal', function() {
                pendingStatusCheckbox = null;
                pendingVaccId = null;
                pendingInfantId = null;
                $('#vaccGrowthSaveBtn').prop('disabled', false).text('Save');
            });

            // Clickable infant name => go to details page
            $(document).on('click', '.infantLink', function(e) {
                e.prevent
                window.open('vaccination_details.php?infant_id=' + id, '_blank');
            });
        });

        // Infant live search
        $(document).on('input', '#modalsearch_infant', function() {
            var query = $(this).val().trim();
            $('#infant_id').val('');
            $('#parent_phone').val('');
            $('#parent_phone_display').val('');
            $('#barangay').val('');
            resetVaccineSelect();
            if (query.length < 2) {
                $('#infantResults').hide();
                return;
            }

            $.get('modalsearch_infant.php', {
                q: query
            }, function(data) {
                $('#infantResults').html(data).show();
            });
        });

        // When a user clicks a search result
        $(document).on('click', '#infantResults button', function() {
            var name = $(this).text();
            var id = $(this).data('id');
            var phone = $(this).data('phone') || '';
            var barangay = $(this).data('barangay') || '';
            $('#modalsearch_infant').val(name);
            $('#infant_id').val(id);
            $('#parent_phone').val(phone);
            $('#parent_phone_display').val(phone);
            $('#infant_name_display').val(name);
            if (barangay) {
                $('#barangay').val(barangay);
            }
            applyCompletedVaccines(id);
            $('#infantResults').hide();
        });

        // Hide search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#modalsearch_infant, #infantResults').length) {
                $('#infantResults').hide();
            }
        });
    </script>

    <!-- Font Awesome (optional if you use icons) -->
    <script src="https://kit.fontawesome.com/a2e0b3b6ab.js" crossorigin="anonymous"></script>
</body>

</html>