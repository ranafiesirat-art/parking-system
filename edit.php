<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
include "db.php";

if (!isset($_GET['id'])) {
    header("Location: senarai.php");
    exit;
}

$id = intval($_GET['id']);

/* ================= FETCH DATA ================= */
$stmt = $conn->prepare("SELECT * FROM permohonan WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) {
    die("Data tidak dijumpai");
}

/* ================= FUNCTION AUTO KIRA ================= */
function kiraNilaiSewa($bil, $tempoh) {
    if ($tempoh == "6 BULAN") {
        return $bil * 900;
    } elseif ($tempoh == "12 BULAN") {
        return $bil * 1800;
    }
    return 0;
}

/* ================= SIMPAN ================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $custom_id = $_POST['custom_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $syarikat = $_POST['syarikat'] ?? '';
    $pemohon = $_POST['pemohon'] ?? '';
    $no_tel = $_POST['no_tel'] ?? '';
    $lokasi_jalan = $_POST['lokasi_jalan'] ?? '';
    $no_petak = $_POST['no_petak'] ?? '';
    $bil_petak = intval($_POST['bil_petak'] ?? 0);
    $tempoh_sewa = $_POST['tempoh_sewa'] ?? '';
    $nilai_sewa = kiraNilaiSewa($bil_petak, $tempoh_sewa);
    $kedudukan_petak = $_POST['kedudukan_petak'] ?? '';
    $jumlah_petak_sedia = intval($_POST['jumlah_petak_sedia'] ?? 0);
    $tarikh_periksa = $_POST['tarikh_periksa'] ?? null;
    $doc_sokongan = $_POST['doc_sokongan'] ?? '';
    $jenis_bangunan = $_POST['jenis_bangunan'] ?? '';
    $catatan_siasatan = $_POST['catatan_siasatan'] ?? '';
    $tugasan = $_POST['tugasan'] ?? '';
    $ulasan_siasatan = $_POST['ulasan_siasatan'] ?? '';
    $ulasan_pegawai = $_POST['ulasan_pegawai'] ?? '';
    $ulasan_pengarah = $_POST['ulasan_pengarah'] ?? '';

    $pegawai_bertanggungjawab = $_SESSION['nama_pegawai'] ?? $data['pegawai_bertanggungjawab'] ?? 'Sistem';

    $lesen_mbjb = $_POST['lesen_mbjb'] ?? '';
    $no_ssm = $_POST['no_ssm'] ?? '';
    $alamat_no = $_POST['alamat_no'] ?? '';
    $alamat_jalan = $_POST['alamat_jalan'] ?? '';
    $alamat_taman = $_POST['alamat_taman'] ?? '';
    $tarikh_mohon = $_POST['tarikh_mohon'] ?? null;

    // Check duplicate custom_id
    $duplicate_error = false;
    if ($custom_id !== '') {
        $check = $conn->prepare("SELECT id FROM permohonan WHERE custom_id = ? AND id != ?");
        $check->bind_param("si", $custom_id, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $duplicate_error = true;
        }
        $check->close();
    }

    if ($duplicate_error) {
        $error_msg = "ID Permohonan <strong>" . htmlspecialchars($custom_id) . "</strong> telah wujud pada rekod lain.";
    } else {
        $update = $conn->prepare("
            UPDATE permohonan SET
                custom_id=?, status=?, syarikat=?, pemohon=?, no_tel=?,
                lokasi_jalan=?, no_petak=?, bil_petak=?, tempoh_sewa=?, nilai_sewa=?,
                kedudukan_petak=?, jumlah_petak_sedia=?, tarikh_periksa=?,
                doc_sokongan=?, jenis_bangunan=?, catatan_siasatan=?, tugasan=?,
                ulasan_siasatan=?, ulasan_pegawai=?, ulasan_pengarah=?,
                lesen_mbjb=?, no_ssm=?,
                alamat_no=?, alamat_jalan=?, alamat_taman=?, tarikh_mohon=?,
                pegawai_bertanggungjawab=?
            WHERE id=?
        ");

        $update->bind_param(
            "sssssssisisssssssssssssssssi",
            $custom_id, $status, $syarikat, $pemohon, $no_tel,
            $lokasi_jalan, $no_petak, $bil_petak, $tempoh_sewa, $nilai_sewa,
            $kedudukan_petak, $jumlah_petak_sedia, $tarikh_periksa,
            $doc_sokongan, $jenis_bangunan, $catatan_siasatan, $tugasan,
            $ulasan_siasatan, $ulasan_pegawai, $ulasan_pengarah,
            $lesen_mbjb, $no_ssm,
            $alamat_no, $alamat_jalan, $alamat_taman, $tarikh_mohon,
            $pegawai_bertanggungjawab,
            $id
        );

        if ($update->execute()) {
            header("Location: view.php?id=" . $id);
            exit;
        } else {
            $error_msg = "Ralat semasa kemaskini: " . $update->error;
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kemaskini Permohonan #<?= htmlspecialchars($data['custom_id'] ?? $id) ?></title>
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
        }
        /* Auto-resize textarea dengan UI moden */
        .auto-resize {
            min-height: 120px;
            resize: none;
            overflow: hidden;
            transition: height 0.2s ease;
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-semibold text-dark mb-1">
                            <i class="bi bi-pencil-square me-3 text-primary"></i>
                            Kemaskini Permohonan
                        </h3>
                        <p class="text-muted mb-0">ID: <strong><?= htmlspecialchars($data['custom_id'] ?? $id) ?></strong></p>
                    </div>
                    <a href="senarai.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Senarai
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Kemaskini Butiran Permohonan</h4>
                    </div>

                    <div class="card-body p-4 p-lg-5">

                        <?php if (isset($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?= $error_msg ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">

                            <!-- Maklumat Pemohon -->
                            <div class="info-card">
                                <div class="section-title">Maklumat Pemohon</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="custom_id" class="form-control" 
                                                   value="<?= htmlspecialchars($data['custom_id'] ?? '') ?>" required>
                                            <label>No ID / Custom ID *</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="status" class="form-select" required>
                                                <?php
                                                $statuses = ["BELUM","CHECKED","ENDORSED","APPROVED","REJECTED","INCOMPLETE","ACTIVE","KIV"];
                                                foreach($statuses as $s){
                                                    $sel = ($data['status'] == $s) ? "selected" : "";
                                                    echo "<option value=\"$s\" $sel>$s</option>";
                                                }
                                                ?>
                                            </select>
                                            <label>Status Permohonan</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" name="syarikat" class="form-control" 
                                                   value="<?= htmlspecialchars($data['syarikat'] ?? '') ?>" required>
                                            <label>Nama Syarikat / Organisasi *</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" name="alamat_no" class="form-control" 
                                                   value="<?= htmlspecialchars($data['alamat_no'] ?? '') ?>">
                                            <label>No</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="alamat_jalan" class="form-control" 
                                                   value="<?= htmlspecialchars($data['alamat_jalan'] ?? '') ?>">
                                            <label>Jalan</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="alamat_taman" class="form-control" 
                                                   value="<?= htmlspecialchars($data['alamat_taman'] ?? '') ?>">
                                            <label>Taman / Kawasan</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="pemohon" class="form-control" 
                                                   value="<?= htmlspecialchars($data['pemohon'] ?? '') ?>">
                                            <label>Nama Pemohon</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="no_tel" class="form-control" 
                                                   value="<?= htmlspecialchars($data['no_tel'] ?? '') ?>">
                                            <label>No Telefon</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="lesen_mbjb" class="form-control" 
                                                   value="<?= htmlspecialchars($data['lesen_mbjb'] ?? '') ?>">
                                            <label>Lesen MBJB</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="no_ssm" class="form-control" 
                                                   value="<?= htmlspecialchars($data['no_ssm'] ?? '') ?>">
                                            <label>No SSM</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" name="doc_sokongan" class="form-control" 
                                                   value="<?= htmlspecialchars($data['doc_sokongan'] ?? '') ?>">
                                            <label>Dokumen Sokongan</label>
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
                                            <input type="text" name="lokasi_jalan" class="form-control" 
                                                   value="<?= htmlspecialchars($data['lokasi_jalan'] ?? '') ?>">
                                            <label>Lokasi Jalan</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="no_petak" class="form-control" 
                                                   value="<?= htmlspecialchars($data['no_petak'] ?? '') ?>">
                                            <label>No Petak</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" name="bil_petak" id="bil_petak" class="form-control" 
                                                   value="<?= htmlspecialchars($data['bil_petak'] ?? '') ?>">
                                            <label>Bilangan Petak Mohon</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="tempoh_sewa" id="tempoh_sewa" class="form-select">
                                                <option value="">Pilih Tempoh</option>
                                                <option value="6 BULAN" <?= ($data['tempoh_sewa'] == "6 BULAN") ? "selected" : "" ?>>6 BULAN</option>
                                                <option value="12 BULAN" <?= ($data['tempoh_sewa'] == "12 BULAN") ? "selected" : "" ?>>12 BULAN</option>
                                            </select>
                                            <label>Tempoh Sewaan</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" name="tarikh_mohon" class="form-control" 
                                                   value="<?= htmlspecialchars($data['tarikh_mohon'] ?? '') ?>">
                                            <label>Tarikh Mohon</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" id="nilai_sewa" class="form-control" readonly 
                                                   value="RM <?= number_format($data['nilai_sewa'] ?? 0, 2) ?>">
                                            <label>Anggaran Nilai Sewaan (RM)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="kedudukan_petak" class="form-select">
                                                <option value="">Pilih Kedudukan</option>
                                                <?php
                                                $keds = ["MENEGAK","MELINTANG","MENYERONG"];
                                                foreach($keds as $k){
                                                    $sel = ($data['kedudukan_petak'] == $k) ? "selected" : "";
                                                    echo "<option value=\"$k\" $sel>$k</option>";
                                                }
                                                ?>
                                            </select>
                                            <label>Kedudukan Petak</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" name="jumlah_petak_sedia" class="form-control" 
                                                   value="<?= htmlspecialchars($data['jumlah_petak_sedia'] ?? '') ?>">
                                            <label>Jumlah Petak Sedia Ada</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select name="jenis_bangunan" class="form-select">
                                                <option value="">Pilih Jenis Bangunan</option>
                                                <option value="1 TINGKAT" <?= ($data['jenis_bangunan'] == "1 TINGKAT") ? "selected" : "" ?>>1 Tingkat</option>
                                                <option value="2 TINGKAT" <?= ($data['jenis_bangunan'] == "2 TINGKAT") ? "selected" : "" ?>>2 Tingkat</option>
                                                <option value="3 TINGKAT" <?= ($data['jenis_bangunan'] == "3 TINGKAT") ? "selected" : "" ?>>3 Tingkat</option>
                                                <option value="4 TINGKAT" <?= ($data['jenis_bangunan'] == "4 TINGKAT") ? "selected" : "" ?>>4 Tingkat</option>
                                                <option value="5 TINGKAT" <?= ($data['jenis_bangunan'] == "5 TINGKAT") ? "selected" : "" ?>>5 Tingkat</option>
                                            </select>
                                            <label>Jenis Bangunan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tugasan/Siasatan -->
                            <div class="info-card">
                                <div class="section-title">Tugasan/Siasatan</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea name="tugasan" class="form-control auto-resize"><?= htmlspecialchars($data['tugasan'] ?? '') ?></textarea>
                                            <label>Tugasan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Maklumat Pemeriksaan -->
                            <div class="info-card">
                                <div class="section-title">Maklumat Pemeriksaan Pegawai di Tapak</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" name="tarikh_periksa" class="form-control" 
                                                   value="<?= htmlspecialchars($data['tarikh_periksa'] ?? '') ?>">
                                            <label>Tarikh Periksa</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea name="catatan_siasatan" class="form-control auto-resize"><?= htmlspecialchars($data['catatan_siasatan'] ?? '') ?></textarea>
                                            <label>Catatan Siasatan</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea name="ulasan_siasatan" class="form-control auto-resize"><?= htmlspecialchars($data['ulasan_siasatan'] ?? '') ?></textarea>
                                            <label>Ulasan Siasatan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Semakan Pegawai -->
                            <div class="info-card">
                                <div class="section-title">Semakan Pegawai</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea name="ulasan_pegawai" class="form-control auto-resize"><?= htmlspecialchars($data['ulasan_pegawai'] ?? '') ?></textarea>
                                            <label>Ulasan Pegawai</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Semakan Pengarah -->
                            <div class="info-card">
                                <div class="section-title">Semakan Pengarah</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea name="ulasan_pengarah" class="form-control auto-resize"><?= htmlspecialchars($data['ulasan_pengarah'] ?? '') ?></textarea>
                                            <label>Ulasan Pengarah</label>
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
                                               value="<?= htmlspecialchars($_SESSION['nama_pegawai'] ?? $data['pegawai_bertanggungjawab'] ?? 'Sistem') ?>" readonly>
                                        <label>Pegawai Bertanggungjawab (Kemaskini Terakhir)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Butang -->
                            <div class="d-flex flex-wrap gap-3 justify-content-end mt-5 pt-4 border-top">
                                <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary px-5">
                                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Paparan
                                </a>
                                <a href="senarai.php" class="btn btn-outline-secondary px-5">Batal</a>
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="bi bi-save me-2"></i>Simpan Kemaskini
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
// Auto-resize textarea (moden & smooth)
function autoResizeTextarea() {
    const textareas = document.querySelectorAll('.auto-resize');
    textareas.forEach(textarea => {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
}

// Jalankan auto-resize selepas page load
window.addEventListener('load', autoResizeTextarea);

// Pengiraan Nilai Sewa
function kiraLive() {
    let bil = parseInt(document.getElementById("bil_petak").value) || 0;
    let tempoh = document.getElementById("tempoh_sewa").value;
    let nilai = 0;
    if (tempoh === "6 BULAN") nilai = bil * 900;
    else if (tempoh === "12 BULAN") nilai = bil * 1800;

    document.getElementById("nilai_sewa").value = "RM " + nilai.toLocaleString('ms-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

document.getElementById("bil_petak").addEventListener("input", kiraLive);
document.getElementById("tempoh_sewa").addEventListener("change", kiraLive);
kiraLive();
</script>
</body>
</html>