<?php
function ensureParentBarangayColumn(mysqli $con): void
{
    $check = mysqli_query($con, "SHOW COLUMNS FROM parents LIKE 'barangay'");
    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($con, "ALTER TABLE parents ADD COLUMN barangay VARCHAR(100) DEFAULT NULL");
    }
}

function ensureScheduleBarangayColumn(mysqli $con): void
{
    $check = mysqli_query($con, "SHOW COLUMNS FROM tbl_vaccination_schedule LIKE 'barangay'");
    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($con, "ALTER TABLE tbl_vaccination_schedule ADD COLUMN barangay VARCHAR(100) DEFAULT NULL");
    }
}

function ensureSmsQueueTable(mysqli $con): void
{
    $sql = "CREATE TABLE IF NOT EXISTS sms_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vacc_id INT NOT NULL,
        infant_id INT NOT NULL,
        phone VARCHAR(30) NOT NULL,
        barangay VARCHAR(100) DEFAULT NULL,
        next_dose_date DATE DEFAULT NULL,
        schedule_time TIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_vacc (vacc_id),
        KEY idx_infant (infant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($con, $sql);

    $checkColumn = mysqli_query($con, "SHOW COLUMNS FROM sms_queue LIKE 'barangay'");
    if ($checkColumn && mysqli_num_rows($checkColumn) === 0) {
        mysqli_query($con, "ALTER TABLE sms_queue ADD COLUMN barangay VARCHAR(100) DEFAULT NULL");
    }

    ensureParentBarangayColumn($con);
    ensureScheduleBarangayColumn($con);
}

function syncSmsQueue(mysqli $con, int $vaccId, int $infantId, ?string $phone = null, ?string $barangay = null): void
{
    if ($vaccId <= 0 || $infantId <= 0) {
        return;
    }

    ensureSmsQueueTable($con);
    ensureParentBarangayColumn($con);
    ensureScheduleBarangayColumn($con);

    $phone = trim((string) $phone);
    $barangay = trim((string) $barangay);
    if ($phone === '') {
        $phoneStmt = mysqli_prepare($con, "SELECT parents.phone, parents.barangay FROM infantinfo LEFT JOIN parents ON parents.id = infantinfo.parent_id WHERE infantinfo.id=? LIMIT 1");
        mysqli_stmt_bind_param($phoneStmt, "i", $infantId);
        mysqli_stmt_execute($phoneStmt);
        $phoneResult = mysqli_stmt_get_result($phoneStmt);
        if ($phoneRow = mysqli_fetch_assoc($phoneResult)) {
            $phone = trim((string) ($phoneRow['phone'] ?? ''));
            if ($barangay === '') {
                $barangay = trim((string) ($phoneRow['barangay'] ?? ''));
            }
        }
        mysqli_stmt_close($phoneStmt);
    }

    $schedStmt = mysqli_prepare($con, "SELECT next_dose_date, `time`, barangay FROM tbl_vaccination_schedule WHERE vacc_id=? LIMIT 1");
    mysqli_stmt_bind_param($schedStmt, "i", $vaccId);
    mysqli_stmt_execute($schedStmt);
    $schedResult = mysqli_stmt_get_result($schedStmt);
    $schedRow = mysqli_fetch_assoc($schedResult);
    mysqli_stmt_close($schedStmt);

    if (!$schedRow) {
        return;
    }

    $nextDose = $schedRow['next_dose_date'] ?? null;
    $scheduleTime = $schedRow['time'] ?? null;
    if ($barangay === '') {
        $barangay = trim((string) ($schedRow['barangay'] ?? ''));
    }

    if ($phone === '') {
        return;
    }

    $existsStmt = mysqli_prepare($con, "SELECT id FROM sms_queue WHERE vacc_id=? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, "i", $vaccId);
    mysqli_stmt_execute($existsStmt);
    $existsResult = mysqli_stmt_get_result($existsStmt);
    $existsRow = mysqli_fetch_assoc($existsResult);
    mysqli_stmt_close($existsStmt);

    if ($existsRow) {
        $queueId = intval($existsRow['id']);
        $updateStmt = mysqli_prepare($con, "UPDATE sms_queue SET infant_id=?, phone=?, barangay=?, next_dose_date=?, schedule_time=? WHERE id=?");
        mysqli_stmt_bind_param($updateStmt, "issssi", $infantId, $phone, $barangay, $nextDose, $scheduleTime, $queueId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    } else {
        $insertStmt = mysqli_prepare($con, "INSERT INTO sms_queue (vacc_id, infant_id, phone, barangay, next_dose_date, schedule_time) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insertStmt, "iissss", $vaccId, $infantId, $phone, $barangay, $nextDose, $scheduleTime);
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
    }
}

function removeFromSmsQueue(mysqli $con, int $vaccId): void
{
    if ($vaccId <= 0) {
        return;
    }

    ensureSmsQueueTable($con);

    $stmt = mysqli_prepare($con, "DELETE FROM sms_queue WHERE vacc_id=?");
    mysqli_stmt_bind_param($stmt, "i", $vaccId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function clearSmsQueue(mysqli $con): void
{
    ensureSmsQueueTable($con);
    mysqli_query($con, "TRUNCATE TABLE sms_queue");
}
