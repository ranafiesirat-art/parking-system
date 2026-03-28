<?php
require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
include "db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID tidak sah.");

$result = $conn->query("SELECT * FROM permohonan WHERE id = $id");
if (!$result || $result->num_rows === 0) die("Rekod tidak dijumpai.");

$data = $result->fetch_assoc();

$status = strtoupper(trim($data['status'] ?? 'BELUM ADA STATUS'));
$statusClass = 'bg-secondary text-white';
if (in_array($status, ['LULUS','APPROVED'])) $statusClass = 'bg-success text-white';
elseif (in_array($status, ['DITOLAK','REJECTED'])) $statusClass = 'bg-danger text-white';
elseif (strpos($status,'PROSES') !== false || in_array($status,['CHECKED','ENDORSED'])) $statusClass = 'bg-warning text-dark';
elseif (in_array($status,['BARU','BELUM','INCOMPLETE'])) $statusClass = 'bg-info text-white';
elseif (in_array($status,['KIV','ACTIVE'])) $statusClass = 'bg-primary text-white';

function formatTarikh($date) {
    return ($date && $date != "0000-00-00") ? date("d/m/Y", strtotime($date)) : "(tiada)";
}

$responHariKe = (!empty($data['tarikh_periksa']) && !empty($data['tarikh_mohon']) && $data['tarikh_periksa'] != "0000-00-00")
    ? (int)((strtotime($data['tarikh_periksa']) - strtotime($data['tarikh_mohon'])) / 86400) + 1
    : "(tiada)";

$currentDate = date('Y-m-d');
$hariKe = !empty($data['tarikh_mohon']) ? (int)((strtotime($currentDate) - strtotime($data['tarikh_mohon'])) / 86400) + 1 : "(tiada)";

$alamat_penuh = implode(", ", array_filter([$data['alamat_no'] ?? '', $data['alamat_jalan'] ?? '', $data['alamat_taman'] ?? ''])) ?: '(tiada)';
$lokasi_jalan_display = implode(", ", array_filter([$data['lokasi_jalan'] ?? '', $data['alamat_taman'] ?? ''])) ?: '(tiada)';

$jum_petak_mohon = (int)($data['bil_petak'] ?? 0);
$tempoh_sewa = strtoupper(trim($data['tempoh_sewa'] ?? ''));
$nilai_sewa = 0;
if ($jum_petak_mohon > 0) {
    if ($tempoh_sewa === "6 BULAN") $nilai_sewa = $jum_petak_mohon * 900;
    elseif ($tempoh_sewa === "12 BULAN") $nilai_sewa = $jum_petak_mohon * 1800;
}
$nilai_sewa_display = $nilai_sewa > 0 ? "RM " . number_format($nilai_sewa, 2) : '(tiada)';

// =============================================
// EXPORT PDF
// =============================================
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVuSans');
    $dompdf = new Dompdf($options);

    $nama_syarikat_raw = trim($data['syarikat'] ?? 'Tiada_Nama_Syarikat');
    $nama_syarikat = preg_replace('/[^A-Za-z0-9\- ]/', '_', $nama_syarikat_raw);
    $nama_syarikat = str_replace(' ', '_', $nama_syarikat);
    $nama_syarikat = substr($nama_syarikat, 0, 50);

    $filename = "Laporan_Pemeriksaan_" . $nama_syarikat . "_" . ($data['custom_id'] ?? $id) . "_" . date('Ymd_His') . ".pdf";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="ms">
    <head>
        <meta charset="UTF-8">
        <title>LAPORAN PEMERIKSAAN</title>
        <style>
            @page { margin: 10mm; size: A4 portrait; }
            body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; line-height: 1.18; color: #000; margin: 0; }
            .header { text-align: center; margin-bottom: 3mm; }
            .header h1 { font-size: 13pt; margin: 0; color: #003087; font-weight: bold; }
            .header .jabatan { font-size: 9.5pt; color: #333; margin: 0.5mm 0; }
            .header .tajuk { font-size: 10.5pt; font-weight: bold; margin: 2mm 0 1mm; border-bottom: 2px solid #003087; padding-bottom: 1mm; }
            .info-table { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
            .info-table td { padding: 1.2mm 1.5mm; border-bottom: 1px solid #999; vertical-align: top; }
            .label { width: 32%; font-weight: bold; color: #003087; }
            .section-title { font-size: 9.8pt; font-weight: bold; margin: 3.5mm 0 1.5mm; border-bottom: 1px solid #003087; padding-bottom: 1mm; }
            .section-red { color: #dc3545; }
            .catatan { border: 1px solid #999; padding: 2mm; background: #f9f9f9; min-height: 16mm; font-size: 8.5pt; }
            .catatan strong { color: #003087; }
            .stamp-container { text-align: right; margin-top: 4mm; }
            .stamp { width: 60mm; height: 22mm; border: 2px dashed #000; text-align: center; padding: 2mm 0; font-weight: bold; font-size: 8.5pt; display: inline-block; }
            .stamp-text { margin-top: -1mm; }
            .footer { margin-top: 3mm; text-align: center; font-size: 7.5pt; color: #555; border-top: 1px solid #ccc; padding-top: 2mm; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>TEAM SEKSYEN PETAK BERMUSIM</h1>
            <div class="jabatan">JABATAN PENGUATKUASAAN - UNIT LETAK KERETA</div>
            <div class="tajuk">LAPORAN PEMERIKSAAN & SIASATAN TAPAK</div>
        </div>
        <table class="info-table">
            <tr><td class="label">No ID / Custom ID</td><td><?= htmlspecialchars($data['custom_id'] ?? $id) ?></td></tr>
            <tr><td class="label">Status Permohonan</td><td><?= htmlspecialchars($status) ?></td></tr>
            <tr><td class="label">Nama Syarikat</td><td><?= htmlspecialchars($data['syarikat'] ?: '-') ?></td></tr>
            <tr><td class="label">Nama Pemohon</td><td><?= htmlspecialchars($data['pemohon'] ?: '-') ?></td></tr>
            <tr><td class="label">No Telefon</td><td><?= htmlspecialchars($data['no_tel'] ?: '-') ?></td></tr>
            <tr><td class="label">Tarikh Mohon</td><td><?= formatTarikh($data['tarikh_mohon']) ?></td></tr>
            <tr><td class="label">Bil Hari Sejak Mohon</td><td><?= $hariKe ?></td></tr>
        </table>
        <div class="section-title section-red">Maklumat Lokasi & Petak</div>
        <table class="info-table">
            <tr><td class="label">Lokasi Jalan</td><td><?= htmlspecialchars($lokasi_jalan_display) ?></td></tr>
            <tr><td class="label">Alamat Penuh</td><td><?= htmlspecialchars($alamat_penuh) ?></td></tr>
            <tr><td class="label">No Petak</td><td><?= htmlspecialchars($data['no_petak'] ?: '-') ?></td></tr>
            <tr><td class="label">Bil Petak Mohon</td><td><?= $jum_petak_mohon > 0 ? $jum_petak_mohon : '-' ?></td></tr>
            <tr><td class="label">Tempoh Sewaan</td><td><?= htmlspecialchars($tempoh_sewa ?: '-') ?></td></tr>
            <tr><td class="label">Anggaran Nilai Sewaan</td><td><?= $nilai_sewa_display ?></td></tr>
            <tr><td class="label">Kedudukan Petak</td><td><?= htmlspecialchars($data['kedudukan_petak'] ?: '-') ?></td></tr>
            <tr><td class="label">Jumlah Petak Sedia Ada</td><td><?= htmlspecialchars($data['jumlah_petak_sedia'] ?: '-') ?></td></tr>
            <tr><td class="label">Jenis Bangunan</td><td><?= htmlspecialchars($data['jenis_bangunan'] ?: '-') ?></td></tr>
        </table>
        <div class="section-title section-red">Maklumat Pemeriksaan</div>
        <table class="info-table">
            <tr><td class="label">Tarikh Periksa</td><td><?= formatTarikh($data['tarikh_periksa']) ?></td></tr>
            <tr><td class="label">Respon Hari Ke</td><td><?= $responHariKe ?></td></tr>
            <tr><td class="label">Dokumen Sokongan</td><td><?= htmlspecialchars($data['doc_sokongan'] ?: '-') ?></td></tr>
        </table>
        <div class="section-title section-red">Catatan & Ulasan</div>
        <div class="catatan">
            <strong>TUGASAN:</strong><br><?= nl2br(htmlspecialchars($data['tugasan'] ?: '-')) ?><br><br>
            <strong>CATATAN SIASTAN:</strong><br><?= nl2br(htmlspecialchars($data['catatan_siasatan'] ?: '-')) ?><br><br>
            <strong>ULASAN SIASTAN:</strong><br><?= nl2br(htmlspecialchars($data['ulasan_siasatan'] ?: '-')) ?><br><br>
            <strong>ULASAN PEGAWAI:</strong><br><?= nl2br(htmlspecialchars($data['ulasan_pegawai'] ?: '-')) ?><br><br>
            <strong>ULASAN PENGARAH:</strong><br><?= nl2br(htmlspecialchars($data['ulasan_pengarah'] ?: '-')) ?>
        </div>
        <div class="stamp-container">
            <div class="stamp">
                <div class="stamp-text">(TARIKH & CAP SYARIKAT)</div>
            </div>
        </div>
        <div class="footer">
            Unit Letak Kereta | Jabatan Penguatkuasaan | Majlis Bandaraya Johor Bahru<br>
            Dokumen rasmi untuk semakan di tapak sahaja.
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, ["Attachment" => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maklumat Permohonan #<?= htmlspecialchars($data['custom_id'] ?? $id) ?></title>
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
        .detail-label {
            font-weight: 600;
            color: #64748b;
            min-width: 220px;
        }
        .detail-value {
            color: #1e2937;
            font-weight: 500;
        }
        .status-badge {
            font-size: 1.1rem;
            padding: 0.55rem 1.25rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        .info-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.75rem;
            margin-bottom: 1.75rem;
            border: 1px solid #e2e8f0;
        }
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
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
                            <i class="bi bi-file-earmark-text me-3 text-primary"></i>
                            Maklumat Permohonan
                        </h3>
                        <p class="text-muted mb-0">ID: <strong><?= htmlspecialchars($data['custom_id'] ?? $id) ?></strong></p>
                    </div>
                    <a href="senarai.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Senarai
                    </a>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle fs-3 me-3"></i>
                            <h4 class="mb-0">Butiran Lengkap Permohonan</h4>
                        </div>
                        <span class="status-badge badge <?= $statusClass ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </div>

                    <div class="card-body p-4 p-lg-5">

                        <!-- Maklumat Pemohon -->
                        <div class="info-card">
                            <div class="section-title">Maklumat Pemohon</div>
                            <div class="row g-3">
                                <div class="col-md-6"><span class="detail-label">No ID / Custom ID</span><br><span class="detail-value"><?= htmlspecialchars($data['custom_id'] ?? $id) ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Status</span><br><span class="detail-value"><?= htmlspecialchars($status) ?></span></div>
                                <div class="col-12"><span class="detail-label">Nama Syarikat</span><br><span class="detail-value"><?= htmlspecialchars($data['syarikat'] ?: '(tiada)') ?></span></div>
                                <div class="col-12"><span class="detail-label">Alamat Penuh</span><br><span class="detail-value"><?= htmlspecialchars($alamat_penuh) ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Nama Pemohon</span><br><span class="detail-value"><?= htmlspecialchars($data['pemohon'] ?: '(tiada)') ?></span></div>
                                <div class="col-md-6"><span class="detail-label">No Telefon</span><br><span class="detail-value"><?= htmlspecialchars($data['no_tel'] ?: '(tiada)') ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Tarikh Mohon</span><br><span class="detail-value"><?= formatTarikh($data['tarikh_mohon']) ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Bil Hari Sejak Mohon</span><br><span class="detail-value"><?= $hariKe ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Lesen MBJB</span><br><span class="detail-value"><?= htmlspecialchars($data['lesen_mbjb'] ?: '(tiada)') ?></span></div>
                                <div class="col-md-6"><span class="detail-label">No SSM</span><br><span class="detail-value"><?= htmlspecialchars($data['no_ssm'] ?: '(tiada)') ?></span></div>
                                <div class="col-12"><span class="detail-label">Dokumen Sokongan</span><br><span class="detail-value"><?= htmlspecialchars($data['doc_sokongan'] ?: '(tiada)') ?></span></div>
                            </div>
                        </div>

                        <!-- Maklumat Petak & Sewaan -->
                        <div class="info-card">
                            <div class="section-title">Maklumat Petak & Sewaan</div>
                            <div class="row g-3">
                                <div class="col-12"><span class="detail-label">Lokasi Jalan</span><br><span class="detail-value"><?= htmlspecialchars($lokasi_jalan_display) ?></span></div>
                                <div class="col-md-6"><span class="detail-label">No Petak</span><br><span class="detail-value"><?= htmlspecialchars($data['no_petak'] ?: '(tiada)') ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Bil Petak Mohon</span><br><span class="detail-value"><?= $jum_petak_mohon > 0 ? $jum_petak_mohon : '(tiada)' ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Tempoh Sewaan</span><br><span class="detail-value"><?= htmlspecialchars($tempoh_sewa ?: '(tiada)') ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Nilai Sewaan</span><br><span class="detail-value fw-bold text-success"><?= $nilai_sewa_display ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Kedudukan Petak</span><br><span class="detail-value"><?= htmlspecialchars($data['kedudukan_petak'] ?: '(tiada)') ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Jumlah Petak Sedia Ada</span><br><span class="detail-value"><?= htmlspecialchars($data['jumlah_petak_sedia'] ?: '(tiada)') ?></span></div>
                                <div class="col-12"><span class="detail-label">Jenis Bangunan</span><br><span class="detail-value"><?= htmlspecialchars($data['jenis_bangunan'] ?: '(tiada)') ?></span></div>
                            </div>
                        </div>

                        <!-- Maklumat Pemeriksaan -->
                        <div class="info-card">
                            <div class="section-title">Maklumat Pemeriksaan</div>
                            <div class="row g-3">
                                <div class="col-md-6"><span class="detail-label">Tarikh Periksa</span><br><span class="detail-value"><?= formatTarikh($data['tarikh_periksa']) ?></span></div>
                                <div class="col-md-6"><span class="detail-label">Respon Hari Ke</span><br><span class="detail-value"><?= $responHariKe ?></span></div>
                            </div>
                        </div>

                        <!-- Catatan & Ulasan -->
                        <div class="info-card">
                            <div class="section-title">Catatan & Ulasan</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <span class="detail-label">Tugasan</span><br>
                                    <span class="detail-value"><?= nl2br(htmlspecialchars($data['tugasan'] ?: '(tiada)')) ?></span>
                                </div>
                                <div class="col-12">
                                    <span class="detail-label">Catatan Siasatan</span><br>
                                    <span class="detail-value"><?= nl2br(htmlspecialchars($data['catatan_siasatan'] ?: '(tiada)')) ?></span>
                                </div>
                                <div class="col-12">
                                    <span class="detail-label">Ulasan Siasatan</span><br>
                                    <span class="detail-value"><?= nl2br(htmlspecialchars($data['ulasan_siasatan'] ?: '(tiada)')) ?></span>
                                </div>
                                <div class="col-12">
                                    <span class="detail-label">Ulasan Pegawai</span><br>
                                    <span class="detail-value"><?= nl2br(htmlspecialchars($data['ulasan_pegawai'] ?: '(tiada)')) ?></span>
                                </div>
                                <div class="col-12">
                                    <span class="detail-label">Ulasan Pengarah</span><br>
                                    <span class="detail-value"><?= nl2br(htmlspecialchars($data['ulasan_pengarah'] ?: '(tiada)')) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Butang Tindakan -->
                        <div class="d-flex flex-wrap gap-3 justify-content-end mt-5 pt-4 border-top">
                            <a href="senarai.php" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-arrow-left me-2"></i>Kembali ke Senarai
                            </a>
                            <a href="edit.php?id=<?= $id ?>" class="btn btn-warning px-4">
                                <i class="bi bi-pencil-square me-2"></i>Kemaskini
                            </a>
                            <a href="?id=<?= $id ?>&export=pdf" class="btn btn-danger px-4">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                            </a>
                            <button onclick="window.print()" class="btn btn-dark px-4">
                                <i class="bi bi-printer me-2"></i>Cetak
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>