<?php
include 'dbForm.php';

// Handle Create Operation
if (isset($_POST['submit'])) {
    $name_of_child = $_POST['name_of_child'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $name_of_mother = $_POST['name_of_mother'];
    $bcg = $_POST['bcg'];
    $opv = $_POST['opv'];
    $pcv = $_POST['pcv'];
    $ipv = $_POST['ipv'];

    $sql = "INSERT INTO `ChildRecords`(`name_of_child`, `dob`, `age`, `name_of_mother`, `bcg`, `opv`, `pcv`, `ipv`)
            VALUES ('$name_of_child', '$dob', '$age', '$name_of_mother', '$bcg', '$opv', '$pcv', '$ipv')";
    mysqli_query($con, $sql);
}

// Handle Delete Operation
if (isset($_GET['deleteid'])) {
    $id = $_GET['deleteid'];
    $sql = "DELETE FROM ChildRecords WHERE id = '$id'";
    mysqli_query($con, $sql);
}

// Handle Update Operation
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name_of_child = $_POST['name_of_child'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $name_of_mother = $_POST['name_of_mother'];
    $bcg = $_POST['bcg'];
    $opv = $_POST['opv'];
    $pcv = $_POST['pcv'];
    $ipv = $_POST['ipv'];

    $sql = "UPDATE ChildRecords SET 
            name_of_child = '$name_of_child', 
            dob = '$dob', 
            age = '$age', 
            name_of_mother = '$name_of_mother', 
            bcg = '$bcg', 
            opv = '$opv', 
            pcv = '$pcv', 
            ipv = '$ipv' 
            WHERE id = '$id'";
    mysqli_query($con, $sql);
}

// Fetch records from the database
$sql = "SELECT * FROM ChildRecords";
$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Improved Form with Modal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5 text-center">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal">
            Launch Form
        </button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="formModalLabel">Personal Information Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="test.php">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="name_of_child" class="form-label">Name of Child</label>
                                <input type="text" class="form-control" id="name_of_child" name="name_of_child" placeholder="Enter name of child" required>
                            </div>
                            <div class="col-md-4">
                                <label for="dob" class="form-label">B-Day</label>
                                <input type="date" class="form-control" id="dob" name="dob" required>
                            </div>
                            <div class="col-md-4">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" class="form-control" id="age" name="age" placeholder="Enter age" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name_of_mother" class="form-label">Name of Mother</label>
                                <input type="text" class="form-control" id="name_of_mother" name="name_of_mother" placeholder="Enter name of mother" required>
                            </div>
                            <div class="col-md-6">
                                <label for="bcg" class="form-label">BCG</label>
                                <input type="text" class="form-control" id="bcg" name="bcg" placeholder="Enter BCG details" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="opv" class="form-label">OPV</label>
                                <input type="text" class="form-control" id="opv" name="opv" placeholder="Enter OPV details" required>
                            </div>
                            <div class="col-md-4">
                                <label for="pcv" class="form-label">PCV</label>
                                <input type="text" class="form-control" id="pcv" name="pcv" placeholder="Enter PCV details" required>
                            </div>
                            <div class="col-md-4">
                                <label for="ipv" class="form-label">IPV</label>
                                <input type="text" class="form-control" id="ipv" name="ipv" placeholder="Enter IPV details" required>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="submit" class="btn btn-primary w-50">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Table to Display Records -->
    <div class="container mt-4">
        <table class="table table-bordered table-striped text-center">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name of Child</th>
                    <th>B-Day</th>
                    <th>Age</th>
                    <th>Name of Mother</th>
                    <th>BCG</th>
                    <th>OPV</th>
                    <th>PCV</th>
                    <th>IPV</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['name_of_child']}</td>
                        <td>{$row['dob']}</td>
                        <td>{$row['age']}</td>
                        <td>{$row['name_of_mother']}</td>
                        <td>{$row['bcg']}</td>
                        <td>{$row['opv']}</td>
                        <td>{$row['pcv']}</td>
                        <td>{$row['ipv']}</td>
                        <td>
                            <button class='btn btn-success btn-sm' data-bs-toggle='modal' data-bs-target='#editModal_{$row['id']}'>Edit</button>
                            <a href='test.php?deleteid={$row['id']}' class='btn btn-danger btn-sm'>Delete</a>
                        </td>
                    </tr>";

                    // Edit Modal
                    echo "<div class='modal fade' id='editModal_{$row['id']}' tabindex='-1'>
                        <div class='modal-dialog'>
                            <div class='modal-content'>
                                <div class='modal-header'>
                                    <h5 class='modal-title'>Edit Record</h5>
                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                </div>
                                <div class='modal-body'>
                                    <form method='POST' action='test.php'>
                                        <input type='hidden' name='id' value='{$row['id']}'>
                                        <div class='mb-3'>
                                            <label>Name of Child</label>
                                            <input type='text' class='form-control' name='name_of_child' value='{$row['name_of_child']}' required>
                                        </div>
                                        <div class='mb-3'>
                                            <label>B-Day</label>
                                            <input type='date' class='form-control' name='dob' value='{$row['dob']}' required>
                                        </div>
                                        <div class='mb-3'>
                                            <label>Age</label>
                                            <input type='number' class='form-control' name='age' value='{$row['age']}' required>
                                        </div>
                                        <div class='mb-3'>
                                            <label>Name of Mother</label>
                                            <input type='text' class='form-control' name='name_of_mother' value='{$row['name_of_mother']}' required>
                                        </div>
                                        <div class='mb-3'>
                                            <label>BCG</label>
                                            <input type='text' class='form-control' name='bcg' value='{$row['bcg']}' required>
                                        </div>
                                        <div class='mb-3'>
                                            <label>OPV</label>
                                            <input type='text' class='form-control' name='opv' value='{$row['opv']}' required>
                                        </div>
                                        <div class='mb-3'>
                                            <label>PCV</label>
                                            <input type='text' class='form-control' name='pcv' value='{$row['pcv']}' required>
                                        </div>
                                        <div class='mb-3'>
                                            <label>IPV</label>
                                            <input type='text' class='form-control' name='ipv' value='{$row['ipv']}' required>
                                        </div>
                                        <button type='submit' name='update' class='btn btn-primary'>Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>