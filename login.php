<?php
session_start();
include "db.php";

// Kalau dah login, terus ke dashboard parking
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

// Senarai user (kekal sama seperti asal boss)
$users = [
    'admin' => [
        'password' => '$2y$10$T4Z5iH9iDhZB/N/dYIfP9.YQ.1ugRU0eqwxBm.dSH75cHiacDkrC2', // ganti dengan hash betul
        'nama' => 'Big Boss'
    ],
    'nafie' => [
        'password' => '$2y$10$YV4MvJPLDv.l72Datep67exbaYP8dfAW9rOcQJKnOUnNflbvu4Ssm',
        'nama' => 'Mohd. Ranafie'
    ],
    'raime' => [
        'password' => '$2y$10$RNOlWK91KlIHtan4DvZi1OzNriAkIQ8HUqTDFWw6DB.3ekLvvauXa',
        'nama' => 'Mohd. Raime'
    ],
    'tasha' => [
        'password' => '$2y$10$bsokZWydpIVAb1TCqEquH.BIfhix5Rc6LcNcBZ/DEFPbfMN.Dx/Du',
        'nama' => 'Natasha Nur Afiqah'
    ],
    'putri' => [
        'password' => '$2y$10$FJdPwdDYXU1RPqq7VCubBON3w6KJdFf6tNEehXuW9NjszPYA8qL7m',
        'nama' => 'Nor Syaputri'
    ],
    'lisa' => [
        'password' => '$2y$10$lKA2Nwtmw6Mwd5LsrJ1wreYAhgxiE.NpQGi10rbu2MeVzQPrFqgTy',
        'nama' => 'Marlisa Syahirah'
    ],
    // ... user lain kekal ...
];

// Proses login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['nama_pegawai'] = $users[$username]['nama'];
        $_SESSION['user_id'] = $username;
        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Parking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s;
        }
        .login-card:hover {
            transform: translateY(-10px);
        }
        .login-header {
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            color: white;
            padding: 2.5rem 1.5rem;
            text-align: center;
        }
        .login-body {
            padding: 2.5rem 2rem;
        }
        .btn-login {
            padding: 0.85rem;
            font-size: 1.15rem;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(13,110,253,0.3);
        }
        .form-floating label {
            color: #6c757d;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        .error-alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <h3 class="mb-0 fw-bold">
            <i class="bi bi-shield-lock-fill me-2"></i>Login Parking System
        </h3>
        <small>Sistem Pengurusan Kawalan & Log Kenderaan</small>
    </div>
    
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert alert-danger error-alert alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-floating mb-4">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                <label for="username">Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i> Log Masuk
            </button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">Sistem Parking & Mileage Seksyen</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>