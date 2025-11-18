<?php
include 'dbForm.php';

session_start(); // Ensure session is started
$showDeleteButton = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
$showEditButton = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'parent';

$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

$sql = "SELECT * FROM infantinfo WHERE 
        id LIKE '%$search%' OR
        firstname LIKE '%$search%' OR 
        middlename LIKE '%$search%' OR 
        surname LIKE '%$search%' OR 
        placeofbirth LIKE '%$search%'";

$result = mysqli_query($con, $sql);
$output = "";

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int) $row['id'];
        $firstName = $row['firstname'] ?? '';
        $middleName = $row['middlename'] ?? '';
        $lastName = $row['surname'] ?? '';
        $fullName = trim(preg_replace('/\s+/', ' ', $firstName . ' ' . $middleName . ' ' . $lastName));

        $output .= '<tr>';
        $output .= '<th scope="row">' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '</th>';
        $output .= '<td>' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($row['dateofbirth'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($row['placeofbirth'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($row['sex'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($row['weight'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($row['height'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($row['bloodtype'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        $output .= '<td>' . htmlspecialchars($row['nationality'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        $parentIdForInfant = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
        $viewDetailsUrl = 'view_details.php?parent_id=' . $parentIdForInfant;

        $output .= '<td class="d-flex gap-1 justify-content-center">';
        $output .= '<a href="' . htmlspecialchars($viewDetailsUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-info btn-sm" title="View Details"><i class="bi bi-eye"></i></a>';
        if ($showEditButton) {
            $output .= '<button class="btn btn-outline-success btn-sm" onclick="confirmEdit(' . $id . ')" title="Edit"><i class="bi bi-pencil-square"></i></button>';
        }
        if ($showDeleteButton) {
            $output .= '<button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(' . $id . ')" title="Delete"><i class="bi bi-trash"></i></button>';
        }
        $output .= '</td>';
        $output .= '</tr>';
    }
} else {
    $output = "<tr><td colspan='10' class='text-center'>No records found.</td></tr>";
}

echo $output;
