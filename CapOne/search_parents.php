<?php
include 'dbForm.php';

session_start(); // Ensure session is started
$showDeleteButton = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
$isParent = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'parent';
$parentEmail = ($isParent && isset($_SESSION['user']['email'])) ? mysqli_real_escape_string($con, $_SESSION['user']['email']) : null;

$search = isset($_POST['search']) ? mysqli_real_escape_string($con, $_POST['search']) : '';

if ($isParent && $parentEmail) {
    $sql = "SELECT parents.*, 
               GROUP_CONCAT(infantinfo.id SEPARATOR ', ') AS infant_ids, 
               GROUP_CONCAT(infantinfo.firstname SEPARATOR ', ') AS infant_names
            FROM parents
            LEFT JOIN infantinfo ON parents.id = infantinfo.parent_id
            WHERE parents.email = '$parentEmail' AND (
                parents.id LIKE '%$search%' OR
                parents.first_name LIKE '%$search%' OR 
                parents.last_name LIKE '%$search%' OR 
                parents.email LIKE '%$search%')
            GROUP BY parents.id";
} else {
    $sql = "SELECT parents.*, 
               GROUP_CONCAT(infantinfo.id SEPARATOR ', ') AS infant_ids, 
               GROUP_CONCAT(infantinfo.firstname SEPARATOR ', ') AS infant_names
            FROM parents
            LEFT JOIN infantinfo ON parents.id = infantinfo.parent_id
            WHERE 
                parents.id LIKE '%$search%' OR
                parents.first_name LIKE '%$search%' OR 
                parents.last_name LIKE '%$search%' OR 
                parents.email LIKE '%$search%'
            GROUP BY parents.id";
}

$result = mysqli_query($con, $sql);
$output = "";

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int) $row['id'];
        $firstName = $row['first_name'] ?? '';
        $lastName = $row['last_name'] ?? '';
        $fullName = trim(preg_replace('/\s+/', ' ', $firstName . ' ' . $lastName));
        $phone = $row['phone'] ?? '';
        $barangay = $row['barangay'] ?? '';
        $address = $row['address'] ?? '';
        $infantIds = $row['infant_ids'] ?: 'N/A';
        $infantNames = $row['infant_names'] ?: 'N/A';

        $output .= '<tr>';
        $output .= '<th scope="row">' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '</th>';
        $output .= '<td>' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($infantIds, ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($infantNames, ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td class="d-flex gap-1 justify-content-center">';
        if (!$isParent) {
            $output .= '<button class="btn btn-outline-success btn-sm" onclick="confirmEdit(' . $id . ')" title="Edit"><i class="bi bi-pencil-square"></i></button>';
            if ($showDeleteButton) {
                $output .= '<button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(' . $id . ')" title="Delete"><i class="bi bi-trash"></i></button>';
            }
        }
        $output .= '</td>';
        $output .= '</tr>';
    }
} else {
    $output = "<tr><td colspan='8' class='text-center'>No records found.</td></tr>";
}

echo $output;
