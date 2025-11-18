<?php
include 'dbForm.php';
session_start();
$sql = "SELECT 
            parents.id AS parent_id,
            parents.first_name AS parent_first,
            parents.last_name AS parent_last,
            parents.email,
            parents.phone,
            infantinfo.id AS child_id,
            infantinfo.firstname AS child_first,
            infantinfo.surname AS child_last
        FROM parents
        LEFT JOIN infantinfo ON parents.id = infantinfo.parent_id
        ORDER BY parents.id";

$result = mysqli_query($con, $sql);

$parentData = [];

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['parent_id'];

        if (!isset($parentData[$id])) {
            $parentData[$id] = [
                'name' => $row['parent_first'] . ' ' . $row['parent_last'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'children' => []
            ];
        }

        if (!empty($row['child_id'])) {
            $parentData[$id]['children'][] = [
                'id' => $row['child_id'],
                'name' => $row['child_first'] . ' ' . $row['child_last']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Parent List</title>
    <style>
        table,
        th,
        td {
            border: 1px solid #999;
            border-collapse: collapse;
            padding: 8px;
        }
    </style>
</head>

<body>
    <h2>Parents and Their Children</h2>
    <table>
        <thead>
            <tr>
                <th>Parent Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Children</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parentData as $parent): ?>
                <tr>
                    <td><?= htmlspecialchars($parent['name']) ?></td>
                    <td><?= htmlspecialchars($parent['email']) ?></td>
                    <td><?= htmlspecialchars($parent['phone']) ?></td>
                    <td>
                        <?php if (count($parent['children']) > 0): ?>
                            <ul>
                                <?php foreach ($parent['children'] as $child): ?>
                                    <li>ID: <?= $child['id'] ?> - <?= htmlspecialchars($child['name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <em>No children</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>