<?php
require 'dbForm.php';
session_start();
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Infant Management System - Login</title>
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
      background: linear-gradient(135deg, #f2f8ff 0%, #fef6ff 55%, #ffffff 100%);
      color: var(--text-primary);
      min-height: 100vh;
    }
    a {
      color: var(--primary-start);
    }
    a:hover {
      color: var(--primary-end);
    }
    .auth-layout {
      min-height: 100vh;
    }
    .image-panel {
      position: relative;
      background: url('https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat;
    }
    .image-panel::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(160deg, rgba(20, 46, 95, 0.75), rgba(43, 126, 193, 0.55));
    }
    .image-panel .panel-content {
      position: relative;
      z-index: 1;
      max-width: 420px;
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
      margin-bottom: 1.5rem;
    }
    .panel-text {
      font-size: 1.05rem;
      line-height: 1.7;
      opacity: 0.95;
    }
    .form-panel {
      background: transparent;
    }
    .form-card {
      width: 100%;
      max-width: 460px;
      background: var(--surface);
      border: 1px solid var(--border-soft);
      box-shadow: 0 20px 45px rgba(38, 70, 107, 0.16);
      border-radius: 1.5rem;
    }
    .form-card h2 {
      font-weight: 600;
      font-size: 1.85rem;
    }
    .form-card p {
      color: var(--text-muted);
      margin-bottom: 0;
    }
    .form-label {
      font-weight: 500;
      color: var(--text-primary);
    }
    .form-control {
      border-radius: 0.85rem;
      border: 1px solid #d8e2f1;
      padding: 0.7rem 1rem;
      font-size: 0.95rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-control:focus {
      border-color: rgba(43, 126, 193, 0.6);
      box-shadow: 0 0 0 0.2rem rgba(43, 126, 193, 0.15);
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
      width: 28%;
      height: 1px;
      background: #dbe4f3;
    }
    .divider-text::before {
      left: 0;
    }
    .divider-text::after {
      right: 0;
    }
    @media (max-width: 1199.98px) {
      .panel-heading {
        font-size: 2.3rem;
      }
    }
    @media (max-width: 991.98px) {
      .auth-layout {
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
      .auth-layout {
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
  <div class="container-fluid auth-layout d-flex align-items-center">
    <div class="row w-100 align-items-stretch g-0">
      <div class="col-lg-6 d-none d-lg-flex image-panel justify-content-center align-items-center px-5">
        <div class="panel-content text-center text-lg-start">
          <div class="panel-pill mb-4">
            <strong>Infant Management System</strong>
            <span>Hospital Care</span>
          </div>
          <h1 class="panel-heading">Trusted records for every little milestone</h1>
          <p class="panel-text">Stay connected with pediatric teams, track vital updates, and coordinate infant care securely across your hospital network.</p>
        </div>
      </div>
      <div class="col-lg-6 d-flex align-items-center justify-content-center form-panel py-5 py-lg-0">
        <div class="form-card p-4 p-md-5 mx-3 mx-md-0">
          <div class="mb-4 text-center text-lg-start">
            <h2 class="mb-2">Welcome back</h2>
            <p>Sign in to access infant records and care insights.</p>
          </div>

          <?php if (!empty($errors['login'])): ?>
            <div class="alert alert-danger mb-4"><?= $errors['login'] ?></div>
          <?php endif; ?>

          <form method="POST" action="user-account.php" class="needs-validation" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label">Email address</label>
              <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
            </div>
            <div class="mb-4">
              <div class="g-recaptcha" data-sitekey="6Lc3-QsrAAAAACr7zkgRS1HuGV-sew0EE5tzE4pF"></div>
            </div>
            <div class="d-grid">
              <button type="submit" name="signin" class="btn btn-primary btn-lg">Sign in</button>
            </div>
          </form>

          <div class="divider-text my-4">New to the system?</div>

          <div class="text-center">
            <span class="text-muted">Create an account to manage records.</span>
            <a href="register.php" class="text-decoration-none ms-1 fw-semibold">Register now</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>