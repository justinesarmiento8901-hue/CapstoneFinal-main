<?php
session_start();
if(isset($_SESSION['user'])){
    $user = $_SESSION['user'];

}else{
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>

    <!-- <div class="user-details">
        <p>Logged in user</p>
        <?php
        echo '<p>Email: ' . $user['email'] . '</p><br>';

        echo '<p> Name: ' . $user['name'] . '</p>';

        ?>
        <a href="logout.php">Logout</a>

    </div> -->
             <!-- sidebar -->
    <div class="sidebar">
        <div class="nav flex-column">
            <a class="nav-link" href="#">
                <span class="icon">
                    <i class="bi bi-grid"></i>
                </span>
                <span class="description">Dashboard</span>
            </a>
            <a class="nav-link" href="#">
                <span class="icon">
                    <i class="bi bi-clipboard-check"></i>
                </span>
                <span class="description">Post</span>
            </a>
            <a class="nav-link" href="#">
                <span class="icon">
                    <i class="bi bi-bell"></i>
                </span>
                <span class="description">Notification</span>
            </a>
            <!-- menu dropdown -->
            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#submenu"
             aria-expanded="false" aria-controls="submenu">
                <span class="icon">
                    <i class="bi bi-box-seam"></i>
                </span>
                <span class="description">Project<i class="bi bi-caret-down-fill"></i></span>
            </a>
            <!-- submenu for project -->
             <div class="sub-menu collapse" id="submenu">
             <a class="nav-link" href="#">
                <span class="icon">
                    <i class="bi bi-file-earmark-check"></i>
                </span>
                <span class="description">Project 1</span>
            </a>
            <a class="nav-link" href="#">
                <span class="icon">
                    <i class="bi bi-file-earmark-check"></i>
                </span>
                <span class="description">Project 2</span>
            </a>
            <a class="nav-link" href="#">
                <span class="icon">
                    <i class="bi bi-file-earmark-check"></i>
                </span>
                <span class="description">Project 3</span>
            </a>
             </div>
             <a class="nav-link" href="#">
                <span class="icon">
                    <i class="bi bi-gear"></i>
                </span>
                <span class="description">Setting</span>
            </a>

            <!-- main content -->
             <div class="main-content">
                <h2>Infant Record Management System</h2>
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis 
                tenetur provident veniam dolorum quas ut id iste error suscipit at quos voluptate adipisci, ullam dis
                Cum a modi dolor esse quia praesentium, eaque aliquam beatae, rerum qui ullam dolorem itaque nihil dicta, accusantium eligendi enim quibusdam atque libero assumenda? Et cupiditate ipsa modi sapiente at?
             </div>
        </div>
    </div>














    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

</body>

</html>