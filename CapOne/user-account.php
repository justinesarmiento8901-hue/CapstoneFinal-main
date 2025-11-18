<?php
require_once 'dbForm.php';
session_start();

function verifyRecaptcha($response)
{
    $secret = "6Lc3-QsrAAAAAKN6_UabC1nqshJkWypOaCpeONCt";
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        'secret' => $secret,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result)->success;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';

    if (!verifyRecaptcha($recaptcha)) {
        $_SESSION['errors']['login'] = 'reCAPTCHA failed. Please try again.';
        header('Location: ' . ($_POST['signin'] ? 'index.php' : 'register.php'));
        exit();
    }

    if (isset($_POST['signup'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        $role = $_POST['role'] ?? 'parent'; // Default to 'parent' if no role is selected

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }
        if (empty($first_name)) {
            $errors['firstname'] = 'First name is required.';
        }
        if (empty($last_name)) {
            $errors['lastname'] = 'Last name is required.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
            $errors['password'] = 'Password must include uppercase, lowercase, number, and special character.';
        }
        if (empty($confirm)) {
            $errors['confirm_password'] = 'Confirm password is required.';
        }
        if ($password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // Check if email already exists
        $stmt = $con->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors['user_exist'] = 'Email is already registered.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: register.php');
            exit();
        }

        //  Passed all checks — now safe to insert
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $created = date('Y-m-d H:i:s');

        // Insert into users table
        $stmt = $con->prepare("INSERT INTO users (email, password, name, role, created_at) 
                               VALUES (?, ?, CONCAT(?, ' ', ?), ?, ?)");
        $stmt->bind_param("ssssss", $email, $hashed, $first_name, $last_name, $role, $created);
        if (!$stmt->execute()) {
            error_log("Error inserting into users table: " . $stmt->error);
            $_SESSION['errors']['database'] = 'An error occurred while creating the user.';
            header('Location: register.php');
            exit();
        }

        // Insert into parents table
        $parentStmt = $con->prepare("INSERT INTO parents (first_name, last_name, phone, address, email) 
                                     VALUES (?, ?, ?, ?, ?)");
        $parentStmt->bind_param("sssss", $first_name, $last_name, $phone, $address, $email);
        if (!$parentStmt->execute()) {
            error_log("Error inserting into parents table: " . $parentStmt->error);
            $_SESSION['errors']['database'] = 'An error occurred while creating the parent record.';
            header('Location: register.php');
            exit();
        }

        header('Location: index.php');
        exit();
    }

    if (isset($_POST['signin'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $ip = $_SERVER['REMOTE_ADDR'];

        // Check failed attempts in last 15 minutes
        $attemptStmt = $con->prepare("SELECT COUNT(*) AS attempts FROM login_attempts 
                              WHERE email = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
        $attemptStmt->bind_param("s", $email);
        $attemptStmt->execute();
        $attemptResult = $attemptStmt->get_result();
        $attempts = $attemptResult->fetch_assoc()['attempts'] ?? 0;

        if ($attempts >= 5) {
            $errors['login'] = 'Too many failed login attempts. Please try again later.';
            $_SESSION['errors'] = $errors;

            // Optional: log this too
            $reason = 'Rate limited: Too many attempts';
            $logStmt = $con->prepare("INSERT INTO user_logins (user_id, email, ip_address, success, reason)
                              VALUES (NULL, ?, ?, 0, ?)");
            $logStmt->bind_param("sss", $email, $ip, $reason);
            $logStmt->execute();

            header('Location: index.php');
            exit();
        }

        $stmt = $con->prepare("SELECT id, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // ✅ SUCCESS
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'email' => $email,
                    'role' => $user['role']
                ];

                // Debugging to confirm role
                error_log("User Role: " . $_SESSION['user']['role']);

                // Log success
                $logStmt = $con->prepare("INSERT INTO user_logins (user_id, email, ip_address, success, reason)
                                  VALUES (?, ?, ?, 1, 'Login successful')");
                $logStmt->bind_param("iss", $user['id'], $email, $ip);
                $logStmt->execute();

                // Redirect user based on role
                if ($user['role'] === 'admin') {
                    header('Location: dashboard.php'); // Admin dashboard
                } elseif ($user['role'] === 'healthworker') {
                    header('Location: dashboard.php'); // Healthworker dashboard
                } elseif ($user['role'] === 'parent') {
                    header('Location: dashboard.php'); // Parent dashboard
                } else {
                    header('Location: dashboard.php'); // Regular user dashboard
                }
                exit();
            } else {
                $errors['login'] = 'Incorrect password.';
                $reason = 'Incorrect password';

                // Log failed attempt
                $failStmt = $con->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
                $failStmt->bind_param("ss", $email, $ip);
                $failStmt->execute();
            }
        } else {
            $errors['login'] = 'No user found with that email.';
            $reason = 'User not found';

            // Log failed attempt
            $failStmt = $con->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
            $failStmt->bind_param("ss", $email, $ip);
            $failStmt->execute();
        }

        // Log to audit trail
        $logStmt = $con->prepare("INSERT INTO user_logins (user_id, email, ip_address, success, reason)
                          VALUES (NULL, ?, ?, 0, ?)");
        $logStmt->bind_param("sss", $email, $ip, $reason);
        $logStmt->execute();

        $_SESSION['errors'] = $errors;
        header('Location: index.php');
        exit();
    }
}
