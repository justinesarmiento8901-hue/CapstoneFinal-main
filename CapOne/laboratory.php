<?php
$con = new mysqli("localhost", "root", "", "Laboratory");
if ($con) {
    echo 'success';
} else {
    echo 'not success';
}
?>

<?php

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $username = $_POST['user'];
    $password = $_POST['pass'];


    $sql = "INSERT INTO `tbltest` (`email`,`username`,`password`)
    values ('$email','$username','$password')";

    $result = mysqli_query($con, $sql);
    if ($result) {
        echo 'Inserted Successfully';
    } else {
        echo 'Not Inserted';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <title>Laboratory</title>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 p-4 bg-primary text-white">
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="">Email</label>
                            <input type="text" placeholder="Enter Email" name="email">
                        </div>
                        <div class="col-md-2">
                            <label for="">hey you</label>
                            <input type="text" placeholder="Enter Username" name="user">
                        </div>
                        <div class="col-md-2">
                            <label for="">Password</label>
                            <input type="text" placeholder="Enter Password" name="pass">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" name="submit">Submit</button>
                </form>
            </div>
            <div class="col-md-6 p-4 bg-success text-white">
                <h3>Right Column</h3>
                <p>This is the content of the right column. Customize it as needed for your project.</p>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>