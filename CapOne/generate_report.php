<?php
session_start();
include 'dbForm.php';
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'report'], true)) {
    header('Location: dashboard.php');
    exit;
}
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
    <title>Vaccination Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        .card-shadow { box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
        .filters .form-label { font-weight: 600; }
        .badge-status { padding: 0.4rem 0.75rem; border-radius: 999px; font-size: 0.75rem; }
        .badge-status.pending { background: linear-gradient(135deg,#ff9800,#f57c00); color:#fff; }
        .badge-status.completed { background: linear-gradient(135deg,#4caf50,#388e3c); color:#fff; }
    </style>
</head>
<body class="bg-light">
    <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-4"><i class="bi bi-baby"></i> Infant Record System</h4>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="addinfant.php"><i class="bi bi-person-fill-add"></i> Add Infant</a>
        <a href="viewinfant.php"><i class="bi bi-journal-medical"></i> Infant Records</a>
        <a href="view_parents.php"><i class="bi bi-people"></i> Parent Records</a>
        <?php if ($role === 'admin'): ?>
            <a href="update_growth.php"><i class="bi bi-activity"></i> Growth Tracking</a>
        <?php endif; ?>
        <a href="vaccination_schedule.php"><i class="bi bi-list-check"></i> Vaccination Schedule</a>
        <a href="sms.php"><i class="bi bi-chat-dots"></i> SMS Management</a>
        <a href="generate_report.php" class="active"><i class="bi bi-clipboard-data"></i> Reports</a>
        <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    <div class="content-area">
        <div class="container-fluid py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="mb-0"><i class="bi bi-clipboard-data"></i> Vaccination Reports</h2>
                    <small class="text-muted">Generate, export, and print schedule summaries.</small>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-success" id="exportCsv"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
                    <button class="btn btn-outline-primary" id="exportPdf"><i class="bi bi-printer"></i> Export / Print</button>
                </div>
            </div>
            <form id="reportFilters" class="card card-shadow p-3 filters mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Barangay</label>
                        <select class="form-select" name="barangays[]" id="filterBarangay" multiple>
                            <?php foreach ($barangays as $barangay): ?>
                                <option value="<?php echo htmlspecialchars($barangay); ?>"><?php echo htmlspecialchars($barangay); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="date_from">
                            <input type="date" class="form-control" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="All">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Vaccine</label>
                        <select class="form-select" name="vaccine" id="filterVaccine">
                            <option value="">Any</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Group By</label>
                        <select class="form-select" name="group_by">
                            <option value="none">None</option>
                            <option value="barangay">Barangay</option>
                            <option value="status">Status</option>
                            <option value="vaccine">Vaccine</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rows per page</label>
                        <select class="form-select" name="perPage" id="perPage">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="col-md-12 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="resetFilters">Reset</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Generate</button>
                    </div>
                </div>
            </form>
            <div class="card card-shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="reportTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Infant</th>
                                    <th>Parent</th>
                                    <th>Barangay</th>
                                    <th>Vaccine</th>
                                    <th>Date</th>
                                    <th>Next Dose</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Apply filters and click Generate to view results.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <nav>
                        <ul class="pagination justify-content-end" id="reportPagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        const reportState = { page: 1 };
        let loadTimer = null;

        function showLoading() {
            $('#reportTable tbody').html('<tr><td colspan="9" class="text-center text-muted">Loading report...</td></tr>');
            $('#reportPagination').empty();
        }

        function fetchVaccines() {
            $.getJSON('fetch_vaccines.php', function(data) {
                const $vaccine = $('#filterVaccine');
                $vaccine.empty();
                $vaccine.append('<option value="">Any</option>');
                (data || []).forEach(function(v) {
                    $vaccine.append('<option value="' + v + '">' + v + '</option>');
                });
            });
        }

        function buildQueryParams(extra) {
            const formData = $('#reportFilters').serializeArray();
            const params = {};
            formData.forEach(function(item) {
                if (params[item.name]) {
                    if (!Array.isArray(params[item.name])) {
                        params[item.name] = [params[item.name]];
                    }
                    params[item.name].push(item.value);
                } else {
                    params[item.name] = item.value;
                }
            });
            return Object.assign(params, extra || {});
        }

        function renderTable(rows) {
            const $tbody = $('#reportTable tbody');
            $tbody.empty();
            if (!rows || !rows.length) {
                $tbody.append('<tr><td colspan="9" class="text-center text-muted">No data for selected filters.</td></tr>');
                return;
            }
            const perPage = parseInt($('#perPage').val(), 10) || 10;
            rows.forEach(function(row, idx) {
                const statusClass = row.status === 'Completed' ? 'completed' : 'pending';
                const statusLabel = row.status ? row.status : '';
                const nextDose = row.next_dose_date ? row.next_dose_date : 'N/A';
                const remarks = row.remarks ? row.remarks : '';
                const rowNumber = (reportState.page - 1) * perPage + idx + 1;
                $tbody.append(
                    '<tr>' +
                        '<td>' + rowNumber + '</td>' +
                        '<td>' + (row.infant_name || '') + '</td>' +
                        '<td>' + (row.parent_name || '') + '</td>' +
                        '<td>' + (row.barangay || '') + '</td>' +
                        '<td>' + (row.vaccine_name || '') + '</td>' +
                        '<td>' + (row.date_vaccination || '') + '</td>' +
                        '<td>' + nextDose + '</td>' +
                        '<td><span class="badge-status ' + statusClass + '">' + statusLabel + '</span></td>' +
                        '<td>' + remarks + '</td>' +
                    '</tr>'
                );
            });
        }

        function renderPagination(meta) {
            const $pagination = $('#reportPagination');
            $pagination.empty();
            if (!meta || meta.pages <= 1) {
                return;
            }
            for (let i = 1; i <= meta.pages; i++) {
                const active = i === meta.page ? ' active' : '';
                $pagination.append('<li class="page-item' + active + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
            }
        }

        function loadReport(page) {
            reportState.page = page || 1;
            showLoading();
            const params = buildQueryParams({ page: reportState.page, action: 'preview' });
            $.ajax({
                url: 'reports.php',
                method: 'GET',
                data: params,
                dataType: 'json',
                success: function(resp) {
                    renderTable(resp.data || []);
                    renderPagination(resp.pagination || {});
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Unable to load report.';
                    $('#reportTable tbody').html('<tr><td colspan="9" class="text-center text-danger">' + message + '</td></tr>');
                    $('#reportPagination').empty();
                }
            });
        }

        function scheduleLoad(page = 1) {
            clearTimeout(loadTimer);
            loadTimer = setTimeout(function() {
                loadReport(page);
            }, 250);
        }

        $('#reportFilters').on('submit', function(e) {
            e.preventDefault();
            loadReport(1);
        });

        $('#filterBarangay').on('change', function() {
            scheduleLoad(1);
        });

        $('input[name="date_from"], input[name="date_to"]').on('change', function() {
            scheduleLoad(1);
        });

        $('select[name="status"], #filterVaccine, select[name="group_by"], #perPage').on('change', function() {
            scheduleLoad(1);
        });

        $('#reportPagination').on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'), 10);
            if (!isNaN(page)) {
                loadReport(page);
            }
        });

        $('#exportCsv').on('click', function() {
            const params = buildQueryParams({ action: 'export_csv' });
            const query = $.param(params, true);
            window.location = 'reports.php?' + query;
        });

        $('#exportPdf').on('click', function() {
            const params = buildQueryParams({ action: 'export_pdf' });
            const query = $.param(params, true);
            window.open('reports.php?' + query, '_blank');
        });

        $('#resetFilters').on('click', function() {
            $('#reportFilters')[0].reset();
            $('#filterBarangay').val([]);
            scheduleLoad(1);
        });

        $(function() {
            fetchVaccines();
            loadReport(1);
        });
    </script>
</body>
</html>
