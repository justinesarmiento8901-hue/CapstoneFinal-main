<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

$barangays = [];
$barangayConfig = __DIR__ . '/config/barangays.php';
if (is_readable($barangayConfig)) {
    $configBarangays = include $barangayConfig;
    if (is_array($configBarangays)) {
        $barangays = $configBarangays;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Infant Management System - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        :root {
            --primary-start: #2b7ec1;
            --primary-end: #38c1ba;
            --surface: rgba(255, 255, 255, 0.95);
            --text-primary: #26466a;
            --text-muted: #5f6f89;
            --border-soft: #e4ebf5;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f1f7ff 0%, #fef5ff 55%, #ffffff 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }
        a {
            color: var(--primary-start);
        }
        a:hover {
            color: var(--primary-end);
        }
        .register-layout {
            min-height: 100vh;
        }
        .image-panel {
            position: relative;
            background: url('https://images.unsplash.com/photo-1526256262350-7da7584cf5eb?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat;
        }
        .image-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(170deg, rgba(18, 44, 92, 0.8), rgba(56, 193, 186, 0.55));
        }
        .image-panel .panel-content {
            position: relative;
            z-index: 1;
            max-width: 440px;
            color: #ffffff;
        }
        .panel-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.55rem 1.1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(3px);
            font-weight: 500;
            font-size: 0.95rem;
        }
        .panel-pill span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.25);
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .panel-heading {
            font-size: 2.6rem;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 1.4rem;
        }
        .panel-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .panel-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .panel-list li::before {
            content: "";
            flex-shrink: 0;
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 50%;
            margin-top: 0.45rem;
            background: #a4f5ef;
            box-shadow: 0 0 0 6px rgba(164, 245, 239, 0.35);
        }
        .form-panel {
            background: transparent;
        }
        .form-card {
            width: 100%;
            max-width: 540px;
            background: var(--surface);
            border: 1px solid var(--border-soft);
            box-shadow: 0 20px 45px rgba(38, 70, 107, 0.16);
            border-radius: 1.5rem;
        }
        .form-card h2 {
            font-weight: 600;
            font-size: 1.9rem;
        }
        .form-card p {
            color: var(--text-muted);
            margin-bottom: 0;
        }
        .section-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
        }
        .form-label {
            font-weight: 500;
            color: var(--text-primary);
        }
        .form-control,
        .form-select {
            border-radius: 0.85rem;
            border: 1px solid #d8e2f1;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: rgba(43, 126, 193, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(43, 126, 193, 0.15);
        }
        .form-text {
            margin-top: 0.35rem;
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--primary-start), var(--primary-end));
            border: none;
            border-radius: 999px;
            padding: 0.75rem 1.2rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            box-shadow: 0 14px 28px rgba(43, 126, 193, 0.25);
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #256fae, #30b0aa);
        }
        .divider-text {
            position: relative;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .divider-text::before,
        .divider-text::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 32%;
            height: 1px;
            background: #dbe4f3;
        }
        .divider-text::before { left: 0; }
        .divider-text::after { right: 0; }
        @media (max-width: 1199.98px) {
            .panel-heading {
                font-size: 2.3rem;
            }
        }
        @media (max-width: 991.98px) {
            .register-layout {
                padding: 3rem 1.5rem;
            }
            .image-panel {
                display: none;
            }
            .form-panel {
                padding: 0;
            }
            .form-card {
                max-width: 100%;
            }
        }
        @media (max-width: 575.98px) {
            body {
                background: #f5f8ff;
            }
            .register-layout {
                padding: 2.5rem 1.2rem;
            }
            .form-card {
                border-radius: 1.2rem;
                box-shadow: 0 16px 30px rgba(38, 70, 107, 0.12);
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid register-layout d-flex align-items-center">
        <div class="row w-100 align-items-stretch g-0">
            <div class="col-lg-6 d-none d-lg-flex image-panel justify-content-center align-items-center px-5">
                <div class="panel-content text-center text-lg-start">
                    <div class="panel-pill mb-4">
                        <strong>Infant Management System</strong>
                        <span>Neonatal care</span>
                    </div>
                    <h1 class="panel-heading">Create a trusted space for infant wellness</h1>
                    <ul class="panel-list">
                        <li>Coordinate securely with nurses, pediatricians, and guardians.</li>
                        <li>Track immunizations, milestones, and hospital visits in real time.</li>
                        <li>Access records from any device with medical-grade security.</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 d-flex align-items-center justify-content-center form-panel py-5 py-lg-0">
                <div class="form-card p-4 p-md-5 mx-3 mx-md-0">
                    <div class="mb-4 text-center text-lg-start">
                        <h2 class="mb-2">Guardian registration</h2>
                        <p>Share a few details to begin managing infant care records.</p>
                    </div>

                    <?php if (!empty($errors['user_exist'])): ?>
                        <div class="alert alert-warning mb-4"><?= $errors['user_exist'] ?></div>
                    <?php endif; ?>

                    <form method="POST" action="user-account.php" class="needs-validation" novalidate>
                        <div class="section-label">Contact information</div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" placeholder="Enter your first name" required>
                                <?php if (!empty($errors['firstname'])): ?>
                                    <small class="text-danger form-text"><?= $errors['firstname'] ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" placeholder="Enter your last name" required>
                                <?php if (!empty($errors['lastname'])): ?>
                                    <small class="text-danger form-text"><?= $errors['lastname'] ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" placeholder="Enter your phone number" required>
                                <?php if (!empty($errors['phone'])): ?>
                                    <small class="text-danger form-text"><?= $errors['phone'] ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                                <?php if (!empty($errors['email'])): ?>
                                    <small class="text-danger form-text"><?= $errors['email'] ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Barangay</label>
                                <select class="form-select" name="barangay" required>
                                    <option value="" disabled selected>Select your barangay</option>
                                    <?php foreach ($barangays as $barangay): ?>
                                        <option value="<?= htmlspecialchars($barangay) ?>"><?= htmlspecialchars($barangay) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errors['barangay'])): ?>
                                    <small class="text-danger form-text"><?= $errors['barangay'] ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" placeholder="Enter your address" rows="3" required></textarea>
                                <?php if (!empty($errors['address'])): ?>
                                    <small class="text-danger form-text"><?= $errors['address'] ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="section-label mt-4">Security</div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Create a password" required>
                                <?php if (!empty($errors['password'])): ?>
                                    <small class="text-danger form-text"><?= $errors['password'] ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Re-enter password" required>
                                <?php if (!empty($errors['confirm_password'])): ?>
                                    <small class="text-danger form-text"><?= $errors['confirm_password'] ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="section-label mt-4">Role</div>
                        <div class="mb-3">
                            <select class="form-select" name="role" required>
                                <option value="" disabled selected>Select your role</option>
                                <option value="parent">Parent</option>
                            </select>
                            <?php if (!empty($errors['role'])): ?>
                                <small class="text-danger form-text"><?= $errors['role'] ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4">
                            <div class="g-recaptcha" data-sitekey="6Lc3-QsrAAAAACr7zkgRS1HuGV-sew0EE5tzE4pF"></div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" name="signup" class="btn btn-primary btn-lg">Create account</button>
                        </div>
                    </form>

                    <div class="divider-text my-4">Already registered?</div>

                    <div class="text-center">
                        <span class="text-muted">Return to your dashboard.</span>
                        <a href="index.php" class="text-decoration-none ms-1 fw-semibold">Sign in instead</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>