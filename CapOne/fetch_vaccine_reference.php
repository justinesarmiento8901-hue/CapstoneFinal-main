<?php
// fetch_vaccine_reference.php
include('dbForm.php');

$infant_id = isset($_GET['infant_id']) ? intval($_GET['infant_id']) : 0;

// get reference vaccines (you may have pre-populated this table)
$ref_q = "SELECT id, vaccine_name, disease_prevented, age_stage FROM tbl_vaccine_reference ORDER BY id";
$ref_res = mysqli_query($con, $ref_q);

if (!$ref_res) {
    echo "<tr><td colspan='7' class='text-danger'>Reference not found.</td></tr>";
    exit;
}

// We will assume age_stage holds one of the columns we render (e.g., 'Birth', '1½ mo', etc.)
// To determine which age columns apply for each vaccine, this simple layout expects age_stage to be a comma-separated list
// e.g., 'Birth' or '1½ mo,2½ mo,3½ mo'
while ($ref = mysqli_fetch_assoc($ref_res)) {
    $vname = htmlspecialchars($ref['vaccine_name']);
    $stages = array_map('trim', explode(',', $ref['age_stage']));

    // For each stage, check if there's a corresponding schedule row for this infant and this vaccine+stage (simplified)
    // This requires your tbl_vaccination_schedule to store something that maps to reference (here we check by vaccine_name and infant_id)
    // You can refine by adding reference_id in tbl_vaccination_schedule for more precise mapping.
    $row = "<tr>";
    $row .= "<td class='text-start'>{$vname}</td>";

    $columns = ['Birth', '1½ mo', '2½ mo', '3½ mo', '9 mo', '12 mo'];
    foreach ($columns as $col) {
        $is_applicable = in_array($col, $stages);
        if (!$is_applicable) {
            $row .= "<td class='text-center'>—</td>";
            continue;
        }

        // check if a corresponding record exists (vaccine_name match + infant_id + maybe date_vaccination not null)
        $chk_sql = "SELECT vacc_id, status FROM tbl_vaccination_schedule 
                    WHERE infant_id = ? AND vaccine_name = ? 
                    LIMIT 1";
        $chk_stmt = $con->prepare($chk_sql);
        $chk_stmt->bind_param('is', $infant_id, $ref['vaccine_name']);
        $chk_stmt->execute();
        $chk_res = $chk_stmt->get_result();
        $checked = '';
        $vacc_id = 0;
        if ($r = $chk_res->fetch_assoc()) {
            if ($r['status'] === 'Completed') {
                $checked = 'checked';
            }
            $vacc_id = intval($r['vacc_id']);
        }
        $chk_stmt->close();

        // checkbox includes data-vaccid so change updates can point to a specific schedule row (if exists)
        if ($vacc_id > 0) {
            $row .= "<td><input type='checkbox' class='form-check-input checkDose' data-vaccid='{$vacc_id}' {$checked}></td>";
        } else {
            // no existing schedule record — show unchecked checkbox that when checked should create a schedule row
            // set data-vname so client can POST to create a schedule if needed
            $vname_attr = htmlspecialchars($ref['vaccine_name'], ENT_QUOTES);
            $row .= "<td><input type='checkbox' class='form-check-input checkDose' data-vaccid='' data-vname=\"{$vname_attr}\"></td>";
        }
    }

    $row .= "</tr>";
    echo $row;
}
