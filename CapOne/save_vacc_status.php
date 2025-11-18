<?php
include 'dbForm.php';

// Check if infant_id is passed (you might later pass this through the form)
$infant_id = isset($_POST['infant_id']) ? intval($_POST['infant_id']) : 0;

if ($infant_id <= 0) {
    echo "Invalid infant ID";
    exit;
}

// Delete existing records for this infant before re-saving (for clean sync)
mysqli_query($con, "DELETE FROM tbl_vaccine_status WHERE infant_id = $infant_id");

// Loop through submitted vaccine data
if (isset($_POST['vaccines'])) {
    foreach ($_POST['vaccines'] as $vaccine_name => $age_stages) {
        foreach ($age_stages as $age_stage => $value) {

            // Find the vaccine_id from reference table
            $stmt = mysqli_prepare($con, "SELECT id FROM tbl_vaccine_reference WHERE vaccine_name=? AND age_stage=?");
            mysqli_stmt_bind_param($stmt, "ss", $vaccine_name, $age_stage);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $vaccine_id = $row['id'];
            } else {
                // If not existing, insert into reference table
                $insert = mysqli_prepare($con, "INSERT INTO tbl_vaccine_reference (vaccine_name, age_stage) VALUES (?, ?)");
                mysqli_stmt_bind_param($insert, "ss", $vaccine_name, $age_stage);
                mysqli_stmt_execute($insert);
                $vaccine_id = mysqli_insert_id($con);
            }

            // Save the checked vaccine status
            $query = mysqli_prepare($con, "INSERT INTO tbl_vaccine_status (infant_id, vaccine_id, status, date_given) VALUES (?, ?, 1, NOW())");
            mysqli_stmt_bind_param($query, "ii", $infant_id, $vaccine_id);
            mysqli_stmt_execute($query);
        }
    }
}

echo "success";
