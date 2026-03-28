<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
include "db.php";

$duplicate_error = false;
$success = false;

if (isset($_POST['submit'])) {
    $custom_id = trim($_POST['custom_id'] ?? '');
    $status = $_POST['status'] ?? 'BELUM';
    $syarikat = trim($_POST['syarikat'] ?? '');
    $pemohon = trim($_POST['pemohon'] ?? '');
    $no_tel = trim($_POST['no_tel'] ?? '');
    $lokasi_jalan = trim($_POST['lokasi_jalan'] ?? '');
    $no_petak = trim($_POST['no_petak'] ?? '');
    $bil_petak = (int)($_POST['bil_petak'] ?? 0);
    $tempoh = $_POST['tempoh'] ?? '';
    $tarikh_mohon = $_POST['tarikh_mohon'] ?: null;
    $lesen = trim($_POST['lesen'] ?? '');
    $ssm = trim($_POST['ssm'] ?? '');
    $doc = trim($_POST['doc'] ?? '');
    $alamat_no = trim($_POST['alamat_no'] ?? '');
    $alamat_jalan = trim($_POST['alamat_jalan'] ?? '');
    $alamat_taman = trim($_POST['alamat_taman'] ?? '');
  
    // Pegawai bertanggungjawab diambil dari session
    $pegawai_bertanggungjawab = $_SESSION['nama_pegawai'] ?? 'Sistem';

    // Kira nilai sewa
    $nilai = 0;
    if ($bil_petak > 0 && $tempoh) {
        if ($tempoh === "6 BULAN") $nilai = $bil_petak * 900;
        elseif ($tempoh === "12 BULAN") $nilai = $bil_petak * 1800;
    }

    // Semak duplicate custom_id
    if ($custom_id !== '') {
        $check = $conn->prepare("SELECT id FROM permohonan WHERE custom_id = ?");
        $check->bind_param("s", $custom_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $duplicate_error = true;
        }
        $check->close();
    }

    // Simpan jika tiada duplicate
    if (!$duplicate_error) {
        $stmt = $conn->prepare("
            INSERT INTO permohonan
            (custom_id, status, syarikat, pemohon, no_tel, lokasi_jalan, no_petak,
             bil_petak, tempoh_sewa, nilai_sewa, tarikh_mohon,
             lesen_mbjb, no_ssm, doc_sokongan,
             alamat_no, alamat_jalan, alamat_taman,
             pegawai_bertanggungjawab)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssisissssssss",
            $custom_id, $status, $syarikat, $pemohon, $no_tel, $lokasi_jalan, $no_petak,
            $bil_petak, $tempoh, $nilai, $tarikh_mohon,
            $lesen, $ssm, $doc,
            $alamat_no, $alamat_jalan, $alamat_taman,
            $pegawai_bertanggungjawab
        );
        if ($stmt->execute()) {
            $success = true;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Permohonan Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #4338ca;
        }
        body {
            background: #f8fafc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .main-content {
            padding: 2rem 1.5rem;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }
        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .info-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.75rem;
            margin-bottom: 1.75rem;
            border: 1px solid #e2e8f0;
        }
        .form-control, .form-select {
            border-radius: 10px;
        }
        #nilai_sewa {
            font-weight: 700;
            color: #10b981;
            background-color: #f0fdf4;
            border-color: #86efac;
        }
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">

                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-semibold text-dark mb-1">
                            <i class="bi bi-plus-circle-fill me-3 text-primary"></i>
                            Tambah Permohonan Baru
                        </h3>
                        <p class="text-muted mb-0">Isi maklumat di bawah untuk mendaftarkan permohonan petak bermusim</p>
                    </div>
                    <a href="senarai.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Senarai
                    </a>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <i class="bi bi-journal-plus fs-3 me-3"></i>
                        <h4 class="mb-0">Borang Permohonan Petak Bermusim</h4>
                    </div>

                    <div class="card-body p-4 p-lg-5">

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Rekod berjaya didaftarkan!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <script>
                                setTimeout(() => { window.location.href = 'senarai.php'; }, 1800);
                            </script>
                        <?php endif; ?>

                        <?php if ($duplicate_error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                ID Permohonan telah wujud. Sila gunakan ID lain.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="addForm">

                            <!-- Maklumat Asas -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="custom_id" class="form-control" id="custom_id" placeholder="ID" required>
                                        <label for="custom_id">ID Permohonan (unik) *</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select name="status" class="form-select" id="status" required>
                                            <option value="BELUM" selected>BELUM</option>
                                            <option value="CHECKED">CHECKED</option>
                                            <option value="ENDORSED">ENDORSED</option>
                                            <option value="APPROVED">APPROVED</option>
                                            <option value="REJECTED">REJECTED</option>
                                            <option value="INCOMPLETE">INCOMPLETE</option>
                                            <option value="ACTIVE">ACTIVE</option>
                                            <option value="KIV">KIV</option>
                                        </select>
                                        <label for="status">Status Awal</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Maklumat Pemohon -->
                            <div class="info-card">
                                <div class="section-title">Maklumat Pemohon</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" name="syarikat" class="form-control" id="syarikat" required>
                                            <label for="syarikat">Nama Syarikat / Organisasi *</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" name="alamat_no" class="form-control" id="alamat_no" placeholder="No">
                                            <label for="alamat_no">No</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="alamat_jalan" class="form-control" id="alamat_jalan">
                                            <label for="alamat_jalan">Jalan</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="alamat_taman" class="form-control" id="alamat_taman">
                                            <label for="alamat_taman">Taman / Kawasan</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="pemohon" class="form-control" id="pemohon">
                                            <label for="pemohon">Nama Pemohon</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="no_tel" class="form-control" id="no_tel">
                                            <label for="no_tel">No Telefon</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="lesen" class="form-control" id="lesen">
                                            <label for="lesen">Lesen MBJB</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="ssm" class="form-control" id="ssm">
                                            <label for="ssm">No SSM</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" name="doc" class="form-control" id="doc">
                                            <label for="doc">Dokumen Sokongan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Maklumat Petak & Sewaan -->
                            <div class="info-card">
                                <div class="section-title">Maklumat Petak & Sewaan</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" name="lokasi_jalan" class="form-control" id="lokasi_jalan">
                                            <label for="lokasi_jalan">Lokasi Jalan</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="no_petak" class="form-control" id="no_petak">
                                            <label for="no_petak">No Petak</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="bil_petak" id="bil_petak" class="form-select" required>
                                                <option value="0">Pilih Bilangan</option>
                                                <?php for($i=1; $i<=20; $i++): ?>
                                                    <option value="<?= $i ?>"><?= $i ?> Petak</option>
                                                <?php endfor; ?>
                                            </select>
                                            <label>Bilangan Petak Dimohon *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="tempoh" id="tempoh" class="form-select" required>
                                                <option value="">Pilih Tempoh</option>
                                                <option value="6 BULAN">6 BULAN</option>
                                                <option value="12 BULAN">12 BULAN</option>
                                            </select>
                                            <label>Tempoh Sewa *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" name="tarikh_mohon" class="form-control" id="tarikh_mohon">
                                            <label for="tarikh_mohon">Tarikh Mohon</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" id="nilai_sewa" class="form-control" readonly value="RM 0.00">
                                            <label>Anggaran Nilai Sewaan (RM)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pegawai Bertanggungjawab -->
                            <div class="info-card">
                                <div class="section-title">Pegawai Bertanggungjawab</div>
                                <div class="col-md-8">
                                    <div class="form-floating">
                                        <input type="text" class="form-control bg-light"
                                               value="<?= htmlspecialchars($_SESSION['nama_pegawai'] ?? 'Sistem') ?>" readonly>
                                        <label>Nama Pegawai</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Butang -->
                            <div class="d-flex flex-wrap gap-3 justify-content-end mt-5 pt-4 border-top">
                                <a href="senarai.php" class="btn btn-outline-secondary btn-lg px-5">
                                    <i class="bi bi-x-circle me-2"></i>Batal
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="bi bi-save me-2"></i>Daftar Permohonan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Pengiraan Nilai Sewa secara automatik
function kiraNilai() {
    let petak = parseInt(document.getElementById('bil_petak').value) || 0;
    let tempoh = document.getElementById('tempoh').value;
    let nilai = 0;
    if (tempoh === "6 BULAN") nilai = petak * 900;
    else if (tempoh === "12 BULAN") nilai = petak * 1800;

    const nilaiField = document.getElementById('nilai_sewa');
    nilaiField.value = nilai > 0
        ? "RM " + nilai.toLocaleString('ms-MY', {minimumFractionDigits: 2})
        : "RM 0.00";
}

document.getElementById('bil_petak').addEventListener('change', kiraNilai);
document.getElementById('tempoh').addEventListener('change', kiraNilai);
kiraNilai(); // Jalankan sekali pada load
</script>
</body>
</html>