<?php
include 'dbForm.php';
require_once 'sms_queue_helpers.php';

ensureSmsQueueTable($con);
clearSmsQueue($con);

echo json_encode(['success' => true]);
