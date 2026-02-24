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
    
    // Pegawai bertanggungjawab diambil dari session (nama penuh), tak ambil dari POST
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
    <title>Tambah Permohonan Petak Bermusim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; }
        .card { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: white; }
        .card-header { background: linear-gradient(90deg, #0d6efd 0%, #0b5ed7 100%); color: white; padding: 1.5rem 1.75rem; border-bottom: none; }
        .form-section { background: #f8f9fa; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .section-title { font-size: 1.25rem; font-weight: 600; color: #0d6efd; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e9ecef; }
        .form-control, .form-select { border-radius: 10px; }
        #nilai_sewa { font-weight: 600; color: #198754; background-color: #e8f5e9; }
        .error-msg { color: #dc3545; font-weight: 500; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-journal-text fs-4 me-3"></i>
                    <h4 class="mb-0">Tambah Permohonan Petak Bermusim</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Rekod berjaya didaftarkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <script>setTimeout(() => window.location.href = 'index.php', 1500);</script>
                    <?php endif; ?>
                    <?php if ($duplicate_error): ?>
                        <div class="alert alert-danger">ID Permohonan telah wujud. Sila gunakan ID lain.</div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="custom_id" class="form-control" id="custom_id" placeholder="ID">
                                    <label for="custom_id">ID Permohonan (unik)</label>
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
                                    <label for="status">Status</label>
                                </div>
                            </div>
                        </div>
                        <!-- Maklumat Pemohon -->
                        <div class="form-section">
                            <div class="section-title">Maklumat Pemohon</div>
                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="text" name="syarikat" class="form-control" id="syarikat" required>
                                    <label for="syarikat">Nama Syarikat *</label>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6"><div class="form-floating"><input type="text" name="pemohon" class="form-control" id="pemohon"><label>Nama Pemohon</label></div></div>
                                <div class="col-md-6"><div class="form-floating"><input type="text" name="no_tel" class="form-control" id="no_tel"><label>No Telefon</label></div></div>
                            </div>
                        </div>
                        <!-- Maklumat Petak & Sewa -->
                        <div class="form-section">
                            <div class="section-title">Maklumat Petak & Sewa</div>
                            <div class="row g-4">
                                <div class="col-md-6"><div class="form-floating"><input type="text" name="lokasi_jalan" class="form-control" id="lokasi_jalan"><label>Lokasi Jalan</label></div></div>
                                <div class="col-md-6"><div class="form-floating"><input type="text" name="no_petak" class="form-control" id="no_petak"><label>No Petak</label></div></div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select name="bil_petak" id="bil_petak" class="form-select">
                                            <option value="0">Pilih</option>
                                            <?php for($i=1; $i<=10; $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <label>Bilangan Petak Dimohon</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select name="tempoh" id="tempoh" class="form-select">
                                            <option value="">Pilih</option>
                                            <option value="6 BULAN">6 BULAN</option>
                                            <option value="12 BULAN">12 BULAN</option>
                                        </select>
                                        <label>Tempoh Sewa</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="date" name="tarikh_mohon" class="form-control" id="tarikh_mohon">
                                        <label>Tarikh Mohon</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="form-floating">
                                    <input type="text" id="nilai_sewa" class="form-control" readonly value="RM 0.00">
                                    <label>Anggaran Nilai Sewaan</label>
                                </div>
                            </div>
                        </div>
                        <!-- Lesen & Sokongan -->
                        <div class="form-section">
                            <div class="section-title">Maklumat Lesen & Sokongan</div>
                            <div class="row g-4">
                                <div class="col-md-4"><div class="form-floating"><input type="text" name="lesen" class="form-control" id="lesen"><label>Lesen MBJB</label></div></div>
                                <div class="col-md-4"><div class="form-floating"><input type="text" name="ssm" class="form-control" id="ssm"><label>No SSM</label></div></div>
                                <div class="col-md-4"><div class="form-floating"><input type="text" name="doc" class="form-control" id="doc"><label>Dokumen Sokongan</label></div></div>
                            </div>
                        </div>
                        <!-- Alamat -->
                        <div class="form-section">
                            <div class="section-title">Alamat Pemohon</div>
                            <div class="row g-4">
                                <div class="col-md-4"><div class="form-floating"><input type="text" name="alamat_no" class="form-control" id="alamat_no"><label>No</label></div></div>
                                <div class="col-md-4"><div class="form-floating"><input type="text" name="alamat_jalan" class="form-control" id="alamat_jalan"><label>Jalan</label></div></div>
                                <div class="col-md-4"><div class="form-floating"><input type="text" name="alamat_taman" class="form-control" id="alamat_taman"><label>Taman / Kawasan</label></div></div>
                            </div>
                        </div>
                        <!-- Pegawai Bertanggungjawab (readonly, auto dari session nama penuh) -->
                        <div class="form-section">
                            <div class="section-title">Pegawai Bertanggungjawab</div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="pegawai_bertanggungjawab"
                                               value="<?= htmlspecialchars($_SESSION['nama_pegawai'] ?? 'Sistem') ?>" readonly>
                                        <label for="pegawai_bertanggungjawab">Nama Pegawai</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 justify-content-end mt-5">
                            <a href="index.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-x-circle me-2"></i>Keluar</a>
                            <button type="submit" name="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle me-2"></i>Daftar Permohonan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function kiraNilai() {
    let petak = parseInt(document.getElementById('bil_petak').value) || 0;
    let tempoh = document.getElementById('tempoh').value;
    let nilai = 0;
    if (tempoh === "6 BULAN") nilai = petak * 900;
    else if (tempoh === "12 BULAN") nilai = petak * 1800;
    document.getElementById('nilai_sewa').value = nilai > 0
        ? "RM " + nilai.toLocaleString('ms-MY', {minimumFractionDigits: 2})
        : "RM 0.00";
}
document.getElementById('bil_petak').addEventListener('change', kiraNilai);
document.getElementById('tempoh').addEventListener('change', kiraNilai);
kiraNilai();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>