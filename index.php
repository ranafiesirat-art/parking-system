<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
// index.php - Landing Page / Home
$tahun_semasa = date("Y");
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengurusan Petak Bermusim - Majlis Bandaraya Johor Bahru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #0dcaf0;
            --dark: #212529;
            --light: #f8f9fa;
        }
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .hero {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 6rem 0 4rem;
            text-align: center;
            position: relative;
        }
        .hero h1 { font-size: 3.5rem; font-weight: 700; }
        .hero p { font-size: 1.3rem; opacity: 0.9; }
        .feature-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-lg-custom {
            padding: 1rem 2.5rem;
            font-size: 1.25rem;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .btn-lg-custom:hover {
            transform: translateY(-2px);
        }
        footer {
            background: var(--dark);
            color: white;
            padding: 2.5rem 0;
            margin-top: 6rem;
            font-size: 0.95rem;
        }
        .navbar-custom {
            background: rgba(13,110,253,0.95) !important;
            backdrop-filter: blur(10px);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>

<!-- Navbar Atas (dengan Log Keluar) -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-parking-fill me-2"></i> Sistem Petak Bermusim
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php?tahun=<?= $tahun_semasa ?>&bulan=SETAHUN">Dashboard Analitik</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="senarai.php">Senarai Permohonan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add.php">Permohonan Baru</a>
                </li>
                <li class="nav-item ms-3">
                    <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-4">
                        <i class="bi bi-box-arrow-right me-1"></i> Log Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section (dengan padding atas sebab navbar fixed) -->
<section class="hero" style="padding-top: 120px;">
    <div class="container">
        <h1>Sistem Bapak Napie (DARI PAGOH)</h1>
        <p class="lead mb-5">Unit Letak Kereta | Jabatan Penguatkuasaan | Majlis Bandaraya Johor Bahru</p>
       
        <div class="d-flex justify-content-center gap-4 flex-wrap">
            <a href="dashboard.php?tahun=<?= $tahun_semasa ?>&bulan=SETAHUN" class="btn btn-light btn-lg-custom shadow">
                <i class="bi bi-speedometer2 me-2"></i> Masuk Dashboard Analitik
            </a>
            <a href="add.php" class="btn btn-outline-light btn-lg-custom shadow">
                <i class="bi bi-plus-circle me-2"></i> Permohonan Baru
            </a>
        </div>
    </div>
</section>

<!-- Features / Quick Access -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold text-dark">Akses Pantas ke Modul Utama</h2>
        <div class="row g-4 justify-content-center">
            <!-- Dashboard Analitik -->
            <div class="col-lg-3 col-md-6">
                <div class="card feature-card h-100 text-center bg-white p-4">
                    <i class="bi bi-graph-up-arrow feature-icon text-primary"></i>
                    <h4 class="fw-bold">Dashboard Analitik</h4>
                    <p class="text-muted mb-4">Lihat statistik status, petak, nilai sewaan & purata respon masa sebenar.</p>
                    <a href="dashboard.php?tahun=<?= $tahun_semasa ?>&bulan=SETAHUN" class="btn btn-primary btn-lg-custom mt-2">
                        Pergi ke Dashboard
                    </a>
                </div>
            </div>
            <!-- Senarai Permohonan -->
            <div class="col-lg-3 col-md-6">
                <div class="card feature-card h-100 text-center bg-white p-4">
                    <i class="bi bi-list-ul feature-icon text-success"></i>
                    <h4 class="fw-bold">Senarai Permohonan</h4>
                    <p class="text-muted mb-4">Semak, carian & urus semua rekod permohonan dengan mudah.</p>
                    <a href="senarai.php" class="btn btn-success btn-lg-custom mt-2">
                        Lihat Senarai
                    </a>
                </div>
            </div>
            <!-- Tambah Permohonan Baru -->
            <div class="col-lg-3 col-md-6">
                <div class="card feature-card h-100 text-center bg-white p-4">
                    <i class="bi bi-file-earmark-plus feature-icon text-info"></i>
                    <h4 class="fw-bold">Tambah Permohonan Baru</h4>
                    <p class="text-muted mb-4">Daftar permohonan baharu dengan borang yang selamat & pantas.</p>
                    <a href="add.php" class="btn btn-info btn-lg-custom mt-2">
                        Tambah Baru
                    </a>
                </div>
            </div>
            <!-- Export Laporan PDF -->
            <div class="col-lg-3 col-md-6">
                <div class="card feature-card h-100 text-center bg-white p-4">
                    <i class="bi bi-file-earmark-pdf feature-icon text-danger"></i>
                    <h4 class="fw-bold">Export Laporan PDF</h4>
                    <p class="text-muted mb-4">Jana & muat turun laporan analitik dalam format PDF siap cetak.</p>
                    <a href="dashboard_pdf.php?tahun=<?= $tahun_semasa ?>&bulan=SETAHUN" class="btn btn-danger btn-lg-custom mt-2">
                        Export PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="text-center">
    <div class="container">
        <p class="mb-1">© <?= date("Y") ?> NR Innovations. Hak cipta terpelihara.</p>
        <small>Sistem Nak Urus Petak Bermusim | Dibangunkan untuk kemudahan Geng Seksyen Petak Bermusim</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

