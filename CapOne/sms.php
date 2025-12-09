<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$barangays = include __DIR__ . '/config/barangays.php';
if (!is_array($barangays)) {
    $barangays = [];
}
// Load server-side semaphore config (optional) to autofill API key
$semaphore = [];
if (file_exists(__DIR__ . '/config/semaphore.php')) {
    $semaphore = include __DIR__ . '/config/semaphore.php';
}
$serverApiKey = isset($semaphore['apikey']) ? $semaphore['apikey'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>

<body>
    <!-- Sidebar Menu -->
    <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-4"><i class="bi bi-baby"></i> Infant Record System</h4>
        <?php $role = $_SESSION['user']['role'] ?? ''; ?>
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
        <?php if ($role !== 'parent'): ?>
            <a href="vaccination_schedule.php"><i class="bi bi-journal-medical"></i> Vaccination Schedule</a>
            <?php if (in_array($role, ['admin', 'report', 'healthworker'], true)): ?>
                <a href="generate_report.php"><i class="bi bi-clipboard-data"></i> Reports</a>
            <?php endif; ?>
            <a href="sms.php" class="active"><i class="bi bi-chat-dots"></i> SMS Management</a>
        <?php endif; ?>
        <a href="account_settings.php"><i class="bi bi-gear"></i> Account Settings</a>
        <?php if ($role === 'admin'): ?>
            <a href="login_logs.php"><i class="bi bi-clipboard-data"></i> Logs</a>
        <?php endif; ?>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="content-area">
        <div class="container-fluid mt-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card card-shadow p-4 h-100">
                        <h3 class="dashboard-title mb-4"><i class="bi bi-chat-dots"></i>Send SMS</h3>
                        <form id="messageForm" class="row g-3">
                            <div class="col-12">
                                <label for="apiKey" class="form-label">API Key</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="apiKey" placeholder="Enter API key for SMS provider" value="<?= htmlspecialchars($serverApiKey, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="input-group-text">
                                        <input class="form-check-input mt-0" type="checkbox" id="rememberApiKey" title="Remember API key">
                                    </span>
                                </div>
                                <div class="form-help small">Check the box to remember the API key in this browser.</div>
                            </div>
                            <div class="col-12">
                                <label for="number" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="number" placeholder="09XXXXXXXXX or +63XXXXXXXXXX" required>
                                <div class="form-help">Use PH format 09XXXXXXXXX or +63XXXXXXXXXX.</div>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="4" maxlength="765" placeholder="Type your message..." required></textarea>
                                <div class="form-text"><span id="charCount">0</span>/765</div>
                            </div>
                            <div class="col-12 d-flex gap-2 justify-content-end">
                                <button type="reset" class="btn btn-outline-danger"><i class="bi bi-eraser"></i>Clear</button>
                                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-send"></i>Send</button>
                            </div>
                        </form>
                        <div class="mt-3" id="result" style="display:none;"></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-shadow p-4 h-100">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-2 mb-3">
                            <h3 class="dashboard-title mb-0"><i class="bi bi-list-ul"></i>Scheduled SMS Queue</h3>
                            <select class="form-select w-100 w-lg-auto" id="barangayFilter">
                                <option value="">Select Barangay</option>
                                <?php foreach ($barangays as $barangayOption): ?>
                                    <option value="<?= htmlspecialchars($barangayOption, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($barangayOption, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-success" id="sendBulkBtn" disabled><i class="bi bi-send-check"></i>Send Bulk Message</button>
                        </div>
                        <div id="bulkResult" class="mb-3" style="display:none;"></div>
                        <div class="table-modern">
                            <table class="table table-hover align-middle" id="smsQueueTable">
                                <thead>
                                    <tr>
                                        <th>Infant</th>
                                        <th>Phone</th>
                                        <th>Barangay</th>
                                        <th>Next Dose Date</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody id="smsQueueBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        const messageInput = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        messageInput.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });

        const queueBody = document.getElementById('smsQueueBody');
        const sendBulkBtn = document.getElementById('sendBulkBtn');
        const bulkResult = document.getElementById('bulkResult');
        const barangayFilter = document.getElementById('barangayFilter');
        let currentBarangay = '';

        function updateBulkResult(type, message) {
            if (!bulkResult) return;
            bulkResult.style.display = 'block';
            bulkResult.className = `alert alert-${type}`;
            bulkResult.textContent = message;
        }

        function renderQueue(rows) {
            queueBody.innerHTML = '';
            if (!rows || rows.length === 0) {
                const message = currentBarangay ? 'No schedules queued for the selected barangay.' : 'No schedules queued.';
                queueBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">${message}</td></tr>`;
                sendBulkBtn.disabled = true;
                return;
            }

            rows.forEach(function(row) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.infant_name ? row.infant_name : 'N/A'}</td>
                    <td>${row.phone}</td>
                    <td>${row.barangay || 'N/A'}</td>
                    <td>${row.next_dose_date || 'N/A'}</td>
                    <td>${row.schedule_time ? formatTime(row.schedule_time) : 'N/A'}</td>
                `;
                queueBody.appendChild(tr);
            });
            sendBulkBtn.disabled = currentBarangay === '';
        }

        function formatTime(timeStr) {
            if (!timeStr) return 'N/A';
            const [hour, minute] = timeStr.split(':');
            if (hour === undefined) return timeStr;
            let h = parseInt(hour, 10);
            const m = minute ? minute.substring(0, 2) : '00';
            const period = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${h}:${m} ${period}`;
        }

        function loadQueue(filterBarangay) {
            if (typeof filterBarangay === 'string') {
                currentBarangay = filterBarangay;
            }

            const query = currentBarangay ? `?barangay=${encodeURIComponent(currentBarangay)}` : '';
            fetch(`fetch_sms_queue.php${query}`)
                .then(function(res) {
                    return res.json();
                })
                .then(function(rows) {
                    renderQueue(Array.isArray(rows) ? rows : []);
                })
                .catch(function(err) {
                    console.error('Failed to load SMS queue', err);
                    updateBulkResult('danger', 'Failed to load SMS queue');
                });
        }

        if (barangayFilter) {
            barangayFilter.addEventListener('change', function() {
                const selected = this.value.trim();
                if (bulkResult) {
                    bulkResult.style.display = 'none';
                }
                loadQueue(selected);
            });
        }

        sendBulkBtn.addEventListener('click', function() {
            if (!currentBarangay) {
                updateBulkResult('warning', 'Please select a barangay to send messages.');
                return;
            }

            sendBulkBtn.disabled = true;
            updateBulkResult('info', 'Sending messages...');

            fetch('send_bulk_sms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        barangay: currentBarangay
                    })
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(response) {
                    if (response && response.success) {
                        updateBulkResult('success', response.message || 'Messages sent successfully.');
                        loadQueue(currentBarangay);
                    } else {
                        const message = response && response.error ? response.error : 'Failed to send bulk messages.';
                        updateBulkResult('danger', message);
                        sendBulkBtn.disabled = false;
                    }
                })
                .catch(function(err) {
                    console.error('Bulk send error', err);
                    updateBulkResult('danger', 'Bulk send failed.');
                    sendBulkBtn.disabled = false;
                });
        });

        loadQueue();
    </script>
    <script src="script.js"></script>
</body>

</html>