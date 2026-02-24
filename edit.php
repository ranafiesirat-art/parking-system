<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
include "db.php";
if (!isset($_GET['id'])) {
    die("ID tidak sah");
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
    
    // Pegawai bertanggungjawab diambil dari session (nama penuh), tak ambil dari POST
    $pegawai_bertanggungjawab = $_SESSION['nama_pegawai'] ?? $data['pegawai_bertanggungjawab'] ?? 'Sistem';

    // Field tambahan
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
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ID Permohonan <strong>' . htmlspecialchars($custom_id) . '</strong> telah wujud pada rekod lain.
                Sila gunakan ID unik yang lain.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    } else {
        $update = $conn->prepare("
            UPDATE permohonan SET
                custom_id=?, status=?, syarikat=?, pemohon=?, no_tel=?,
                lokasi_jalan=?, no_petak=?, bil_petak=?, tempoh_sewa=?, nilai_sewa=?,
                kedudukan_petak=?, jumlah_petak_sedia=?, tarikh_periksa=?,
                doc_sokongan=?, jenis_bangunan=?, catatan_siasatan=?, tugasan=?,
                ulasan_siasatan=?, pegawai=?, lesen_mbjb=?, no_ssm=?,
                alamat_no=?, alamat_jalan=?, alamat_taman=?, tarikh_mohon=?,
                pegawai_bertanggungjawab=?
            WHERE id=?
        ");
        $update->bind_param(
            "sssssssisissssssssssssssssi",
            $custom_id, $status, $syarikat, $pemohon, $no_tel,
            $lokasi_jalan, $no_petak, $bil_petak, $tempoh_sewa, $nilai_sewa,
            $kedudukan_petak, $jumlah_petak_sedia, $tarikh_periksa,
            $doc_sokongan, $jenis_bangunan, $catatan_siasatan, $tugasan,
            $ulasan_siasatan, $pegawai, $lesen_mbjb, $no_ssm,
            $alamat_no, $alamat_jalan, $alamat_taman, $tarikh_mohon,
            $pegawai_bertanggungjawab,
            $id
        );
        $update->execute();
        header("Location: view.php?id=" . $id);
        exit;
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
        body { background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; }
        .card { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .card-header { background: linear-gradient(90deg, #198754 0%, #157347 100%); color: white; padding: 1.5rem 1.75rem; border-bottom: none; }
        .form-section { background: #f8f9fa; border-radius: 12px; padding: 1.75rem; margin-bottom: 1.75rem; border: 1px solid #e9ecef; }
        .section-title { font-size: 1.3rem; font-weight: 600; color: #198754; margin-bottom: 1.4rem; padding-bottom: 0.6rem; border-bottom: 2px solid #d4edda; }
        .form-floating > label { color: #6c757d; }
        .form-control, .form-select { border-radius: 10px; }
        .form-control:focus, .form-select:focus { border-color: #198754; box-shadow: 0 0 0 0.25rem rgba(25,135,84,0.15); }
        textarea.form-control { min-height: 120px; resize: vertical; }
        #nilai_sewa { font-weight: 600; color: #198754; background-color: #d4edda; border-color: #a3d7c7; }
        .btn-success { padding: 0.75rem 2rem; font-weight: 500; }
        .btn-outline-secondary { padding: 0.75rem 2rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-pencil-square fs-4 me-3"></i>
                    <h4 class="mb-0">Kemaskini Maklumat Permohonan</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <!-- SECTION 1: Maklumat Asas & Pemohon -->
                        <div class="form-section">
                            <div class="section-title">Maklumat Asas & Pemohon</div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select name="status" class="form-select" id="status">
                                            <option value="">Pilih Status</option>
                                            <?php
                                            $statuses = ["BELUM","CHECKED","ENDORSED","APPROVED","REJECTED","INCOMPLETE","ACTIVE","KIV"];
                                            foreach($statuses as $s){
                                                $sel = ($data['status'] == $s) ? "selected" : "";
                                                echo "<option value=\"$s\" $sel>$s</option>";
                                            }
                                            ?>
                                        </select>
                                        <label for="status">Status Permohonan</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="custom_id" class="form-control" id="custom_id"
                                               value="<?= htmlspecialchars($data['custom_id'] ?? '') ?>">
                                        <label for="custom_id">No ID / Custom ID</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" name="tarikh_mohon" class="form-control" id="tarikh_mohon"
                                               value="<?= htmlspecialchars($data['tarikh_mohon'] ?? '') ?>">
                                        <label for="tarikh_mohon">Tarikh Permohonan</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="syarikat" class="form-control" id="syarikat"
                                               value="<?= htmlspecialchars($data['syarikat'] ?? '') ?>">
                                        <label for="syarikat">Nama Syarikat</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="pemohon" class="form-control" id="pemohon"
                                               value="<?= htmlspecialchars($data['pemohon'] ?? '') ?>">
                                        <label for="pemohon">Nama Pemohon</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="no_tel" class="form-control" id="no_tel"
                                               value="<?= htmlspecialchars($data['no_tel'] ?? '') ?>">
                                        <label for="no_tel">No Telefon</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="lokasi_jalan" class="form-control" id="lokasi_jalan"
                                               value="<?= htmlspecialchars($data['lokasi_jalan'] ?? '') ?>">
                                        <label for="lokasi_jalan">Lokasi Jalan</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="lesen_mbjb" class="form-control" id="lesen_mbjb"
                                               value="<?= htmlspecialchars($data['lesen_mbjb'] ?? '') ?>">
                                        <label for="lesen_mbjb">Lesen MBJB</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="no_ssm" class="form-control" id="no_ssm"
                                               value="<?= htmlspecialchars($data['no_ssm'] ?? '') ?>">
                                        <label for="no_ssm">No SSM</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Kosongkan ruang -->
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: Maklumat Petak & Sewaan -->
                        <div class="form-section">
                            <div class="section-title">Maklumat Petak & Sewaan</div>
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="text" name="no_petak" class="form-control" id="no_petak"
                                               value="<?= htmlspecialchars($data['no_petak'] ?? '') ?>">
                                        <label for="no_petak">No Petak</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="number" name="bil_petak" id="bil_petak" class="form-control"
                                               value="<?= htmlspecialchars($data['bil_petak'] ?? '') ?>">
                                        <label for="bil_petak">Bilangan Petak Mohon</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <select name="tempoh_sewa" id="tempoh_sewa" class="form-select">
                                            <option value="">Pilih</option>
                                            <option value="6 BULAN" <?= ($data['tempoh_sewa'] == "6 BULAN") ? "selected" : "" ?>>6 BULAN</option>
                                            <option value="12 BULAN" <?= ($data['tempoh_sewa'] == "12 BULAN") ? "selected" : "" ?>>12 BULAN</option>
                                        </select>
                                        <label for="tempoh_sewa">Tempoh Sewaan</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="text" id="nilai_sewa" class="form-control" readonly
                                               value="RM <?= number_format($data['nilai_sewa'] ?? 0, 2) ?>">
                                        <label for="nilai_sewa">Nilai Sewaan</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select name="kedudukan_petak" class="form-select" id="kedudukan_petak">
                                            <option value="">Pilih Kedudukan</option>
                                            <?php
                                            $keds = ["MENEGAK","MELINTANG","MENYERONG"];
                                            foreach($keds as $k){
                                                $sel = ($data['kedudukan_petak'] == $k) ? "selected" : "";
                                                echo "<option value=\"$k\" $sel>$k</option>";
                                            }
                                            ?>
                                        </select>
                                        <label for="kedudukan_petak">Kedudukan Petak</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="number" name="jumlah_petak_sedia" class="form-control" id="jumlah_petak_sedia"
                                               value="<?= htmlspecialchars($data['jumlah_petak_sedia'] ?? '') ?>">
                                        <label for="jumlah_petak_sedia">Jumlah Petak Sedia Ada</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select name="jenis_bangunan" class="form-select" id="jenis_bangunan">
                                            <option value="">Pilih Tingkat</option>
                                            <option value="1 TINGKAT" <?= ($data['jenis_bangunan'] == "1 TINGKAT") ? "selected" : "" ?>>1 Tingkat</option>
                                            <option value="2 TINGKAT" <?= ($data['jenis_bangunan'] == "2 TINGKAT") ? "selected" : "" ?>>2 Tingkat</option>
                                            <option value="3 TINGKAT" <?= ($data['jenis_bangunan'] == "3 TINGKAT") ? "selected" : "" ?>>3 Tingkat</option>
                                            <option value="4 TINGKAT" <?= ($data['jenis_bangunan'] == "4 TINGKAT") ? "selected" : "" ?>>4 Tingkat</option>
                                            <option value="5 TINGKAT" <?= ($data['jenis_bangunan'] == "5 TINGKAT") ? "selected" : "" ?>>5 Tingkat</option>
                                        </select>
                                        <label for="jenis_bangunan">Jenis Bangunan</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: Alamat Lengkap -->
                        <div class="form-section">
                            <div class="section-title">Alamat Lengkap</div>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" name="alamat_no" class="form-control" placeholder="No"
                                               value="<?= htmlspecialchars($data['alamat_no'] ?? '') ?>">
                                        <label>Alamat No</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" name="alamat_jalan" class="form-control" placeholder="Jalan"
                                               value="<?= htmlspecialchars($data['alamat_jalan'] ?? '') ?>">
                                        <label>Alamat Jalan</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" name="alamat_taman" class="form-control" placeholder="Taman/Kawasan"
                                               value="<?= htmlspecialchars($data['alamat_taman'] ?? '') ?>">
                                        <label>Alamat Taman/Kawasan</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 4: Maklumat Pemeriksaan & Catatan -->
                        <div class="form-section">
                            <div class="section-title">Maklumat Pemeriksaan & Catatan</div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" name="tarikh_periksa" class="form-control" id="tarikh_periksa"
                                               value="<?= htmlspecialchars($data['tarikh_periksa'] ?? '') ?>">
                                        <label for="tarikh_periksa">Tarikh Periksa</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" name="doc_sokongan" class="form-control" id="doc_sokongan"
                                               value="<?= htmlspecialchars($data['doc_sokongan'] ?? '') ?>">
                                        <label for="doc_sokongan">Dokumen Sokongan</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="form-label fw-bold">Tugasan</label>
                                <textarea name="tugasan" class="form-control"><?= htmlspecialchars($data['tugasan'] ?? '') ?></textarea>
                            </div>
                            <div class="mt-4">
                                <label class="form-label fw-bold">Catatan Siasatan</label>
                                <textarea name="catatan_siasatan" class="form-control"><?= htmlspecialchars($data['catatan_siasatan'] ?? '') ?></textarea>
                            </div>
                            <div class="mt-4">
                                <label class="form-label fw-bold">Ulasan Siasatan</label>
                                <textarea name="ulasan_siasatan" class="form-control"><?= htmlspecialchars($data['ulasan_siasatan'] ?? '') ?></textarea>
                            </div>
                            <!-- Pegawai Bertanggungjawab (readonly, auto dari session nama penuh) -->
                            <div class="mt-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="pegawai_bertanggungjawab"
                                           value="<?= htmlspecialchars($_SESSION['nama_pegawai'] ?? $data['pegawai_bertanggungjawab'] ?? 'Sistem') ?>" readonly>
                                    <label for="pegawai_bertanggungjawab">Pegawai Bertanggungjawab (Kemaskini Terakhir)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Butang -->
                        <div class="d-flex gap-3 justify-content-end mt-5">
                            <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-printer me-2"></i>Cetak
                            </a>
                            <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-floppy me-2"></i>Simpan Kemaskini
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function kiraLive() {
    let bil = parseInt(document.getElementById("bil_petak").value) || 0;
    let tempoh = document.getElementById("tempoh_sewa").value;
    let nilai = 0;

    if (tempoh === "6 BULAN") {
        nilai = bil * 900;
    } else if (tempoh === "12 BULAN") {
        nilai = bil * 1800;
    }

    document.getElementById("nilai_sewa").value = "RM " + nilai.toLocaleString('ms-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
document.getElementById("bil_petak").addEventListener("input", kiraLive);
document.getElementById("tempoh_sewa").addEventListener("change", kiraLive);
kiraLive();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>