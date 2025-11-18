<?php
include 'dbForm.php';

// This endpoint returns HTML result items for the live search in addinfant.php
// Admin/healthworker can search by users.name; we map it to parents.id via email

if (isset($_POST['query'])) {
    $q = mysqli_real_escape_string($con, $_POST['query']);
    $sql = "SELECT p.id AS parent_id, u.name AS user_name
            FROM users u
            INNER JOIN parents p ON p.email = u.email
            WHERE u.role = 'parent' AND u.name LIKE '%$q%'
            ORDER BY u.name ASC
            LIMIT 10";
    $result = mysqli_query($con, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $pid = htmlspecialchars($row['parent_id']);
            $pname = htmlspecialchars($row['user_name']);
            echo "<div class=\"search-result\" data-id=\"$pid\">$pname</div>";
        }
    } else {
        echo "<div class=\"px-2 py-1 text-muted\">No results</div>";
    }
}
