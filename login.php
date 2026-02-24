<?php
session_start();
// Kalau dah login, redirect ke dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

// Senarai user & password + nama penuh
$users = [
    'admin'  => [
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'nama'     => 'Big Boss'
    ],
    'nafie'  => [
        'password' => password_hash('boss123', PASSWORD_DEFAULT),
        'nama'     => 'Mohd. Ranafie'
    ],
    'raime'  => [
        'password' => password_hash('raime123', PASSWORD_DEFAULT),
        'nama'     => 'Mohd. Raime'
    ],
    'tasha'  => [
        'password' => password_hash('tasha123', PASSWORD_DEFAULT),
        'nama'     => 'Natasha Nur Afiqah'
    ],
    'putri'  => [
        'password' => password_hash('putri123', PASSWORD_DEFAULT),
        'nama'     => 'Nor Syaputri'
    ],
    'lisa'   => [
        'password' => password_hash('lisa123', PASSWORD_DEFAULT),
        'nama'     => 'Marlisa Syahirah'
    ],
];

// Proses login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['loggedin']      = true;
        $_SESSION['username']      = $username;
        $_SESSION['nama_pegawai']  = $users[$username]['nama']; // Simpan nama penuh ke session
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
    <title>Login Sistem Permohonan Petak</title>
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
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            background: #0d6efd;
            color: white;
            padding: 2.5rem 1.5rem;
            text-align: center;
        }
        .login-body {
            padding: 2.5rem;
        }
        .btn-login {
            padding: 0.75rem;
            font-size: 1.1rem;
            border-radius: 50px;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
        }
        .form-floating label {
            color: #6c757d;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h3 class="mb-0 fw-bold"><i class="bi bi-shield-lock-fill me-2"></i>Login Sistem</h3>
        <small>Geng Seksyen Petak Bermusim</small>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>