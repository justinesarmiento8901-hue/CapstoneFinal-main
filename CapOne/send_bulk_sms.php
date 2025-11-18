<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

include 'dbForm.php';
require_once 'sms_queue_helpers.php';

ensureSmsQueueTable($con);
ensureParentBarangayColumn($con);
ensureScheduleBarangayColumn($con);

$input = json_decode(file_get_contents('php://input'), true);
$barangayFilter = '';
if (is_array($input) && isset($input['barangay'])) {
    $barangayFilter = trim((string) $input['barangay']);
}

if ($barangayFilter === '') {
    echo json_encode(['success' => false, 'error' => 'Barangay selection is required.']);
    exit;
}

$rows = [];
$stmt = mysqli_prepare($con, "SELECT id, infant_id, phone, barangay, next_dose_date, schedule_time FROM sms_queue WHERE barangay = ? ORDER BY id ASC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $barangayFilter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $phone = trim((string) ($row['phone'] ?? ''));
            if ($phone !== '') {
                $rows[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'infant_id' => (int) ($row['infant_id'] ?? 0),
                    'phone' => $phone,
                    'barangay' => $row['barangay'] ?? null,
                    'next_dose_date' => $row['next_dose_date'] ?? null,
                    'schedule_time' => $row['schedule_time'] ?? null,
                ];
            }
        }
    }
    mysqli_stmt_close($stmt);
}

if (empty($rows)) {
    echo json_encode(['success' => false, 'error' => 'No queued SMS to send for the selected barangay.']);
    exit;
}

$apiKey = '';
if (isset($_SESSION['sms_api_key']) && $_SESSION['sms_api_key']) {
    $apiKey = trim($_SESSION['sms_api_key']);
} else {
    $configPaths = [
        __DIR__ . '/config/semaphore.php',
        __DIR__ . '/sms_config.php',
    ];

    foreach ($configPaths as $configPath) {
        if (is_readable($configPath)) {
            $config = include $configPath;
            if (is_array($config) && isset($config['apikey'])) {
                $apiKey = trim($config['apikey']);
                if ($apiKey !== '') {
                    break;
                }
            }
        }
    }
}

if ($apiKey === '') {
    echo json_encode(['success' => false, 'error' => 'Missing Semaphore API key.']);
    exit;
}

$messageTemplate = "Dear Parent/Guardian,\nThis is a friendly reminder that your {{infant_name}} is scheduled for their next vaccination on:\nDate: {{next_dose_date}}\nTime: {{schedule_time}}\nPlace: {{barangay}}\nPlease bring your child’s vaccination record and arrive a few minutes early to avoid delays.\n\nThank you for keeping your child protected and healthy!";

$sentIds = [];
$errors = [];

foreach ($rows as $row) {
    $number = $row['phone'];

    $infantName = 'child';
    $infantId = (int) $row['infant_id'];
    if ($infantId > 0) {
        $infantStmt = mysqli_prepare($con, "SELECT firstname, middlename, surname FROM infantinfo WHERE id=? LIMIT 1");
        if ($infantStmt) {
            mysqli_stmt_bind_param($infantStmt, "i", $infantId);
            mysqli_stmt_execute($infantStmt);
            $infantResult = mysqli_stmt_get_result($infantStmt);
            if ($infantRow = mysqli_fetch_assoc($infantResult)) {
                $parts = [];
                $first = trim((string) ($infantRow['firstname'] ?? ''));
                $middle = trim((string) ($infantRow['middlename'] ?? ''));
                $last = trim((string) ($infantRow['surname'] ?? ''));
                if ($first !== '') {
                    $parts[] = $first;
                }
                if ($middle !== '') {
                    $parts[] = $middle;
                }
                if ($last !== '') {
                    $parts[] = $last;
                }
                if (!empty($parts)) {
                    $infantName = implode(' ', $parts);
                }
            }
            mysqli_stmt_close($infantStmt);
        }
    }

    $nextDoseRaw = $row['next_dose_date'];
    $nextDose = 'TBD';
    if ($nextDoseRaw) {
        $timestamp = strtotime((string) $nextDoseRaw);
        if ($timestamp !== false) {
            $nextDose = date('m/d/y', $timestamp);
        }
    }

    $scheduleTimeRaw = $row['schedule_time'];
    $scheduleTime = 'TBD';
    if ($scheduleTimeRaw) {
        $timeTimestamp = strtotime((string) $scheduleTimeRaw);
        if ($timeTimestamp !== false) {
            $scheduleTime = date('h:i A', $timeTimestamp);
        }
    }

    $barangayRaw = trim((string) ($row['barangay'] ?? ''));
    $barangay = $barangayRaw !== '' ? $barangayRaw : 'TBD';

    $placeholders = [
        '{{infant_name}}' => $infantName,
        '{{next_dose_date}}' => $nextDose,
        '{{schedule_time}}' => $scheduleTime,
        '{{barangay}}' => $barangay,
    ];

    $message = strtr($messageTemplate, $placeholders);

    $postFields = http_build_query([
        'apikey' => $apiKey,
        'number' => $number,
        'message' => $message,
    ]);

    $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError || ($status !== 200 && $status !== 201)) {
        $errors[] = [
            'id' => $row['id'],
            'phone' => $number,
            'error' => $curlError ?: $response,
        ];
        continue;
    }

    $sentIds[] = $row['id'];
}

if (!empty($sentIds)) {
    $ids = implode(',', array_map('intval', $sentIds));
    mysqli_query($con, "DELETE FROM sms_queue WHERE id IN ($ids)");
}

if (!empty($errors)) {
    echo json_encode([
        'success' => empty($sentIds) ? false : true,
        'message' => empty($sentIds) ? null : 'Some messages sent successfully.',
        'errors' => $errors,
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Messages sent successfully.',
]);
