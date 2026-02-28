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
// EXPORT PDF - dengan header HTTP untuk force download
// =============================================
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVuSans');
    $dompdf = new Dompdf($options);

    // Nama fail PDF ikut nama syarikat (sama seperti asal)
    $nama_syarikat_raw = trim($data['syarikat'] ?? 'Tiada_Nama_Syarikat');
    $nama_syarikat = preg_replace('/[^A-Za-z0-9\- ]/', '_', $nama_syarikat_raw);
    $nama_syarikat = str_replace(' ', '_', $nama_syarikat);
    $nama_syarikat = substr($nama_syarikat, 0, 50);
    $filename = "Laporan_Pemeriksaan_" . $nama_syarikat . "_" . ($data['custom_id'] ?? $id) . "_" . date('Ymd_His') . ".pdf";

    // Tambah header HTTP untuk memaksa download
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
            .stamp {
                width: 60mm;
                height: 22mm;
                border: 2px dashed #000;
                text-align: center;
                padding: 2mm 0;
                font-weight: bold;
                font-size: 8.5pt;
                display: inline-block;
            }
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
            <tr><td class="label">No ID / Custom ID</td><td class="value"><?= htmlspecialchars($data['custom_id'] ?? $id) ?></td></tr>
            <tr><td class="label">Status Permohonan</td><td class="value"><?= htmlspecialchars($status) ?></td></tr>
            <tr><td class="label">Nama Syarikat</td><td class="value"><?= htmlspecialchars($data['syarikat'] ?: '-') ?></td></tr>
            <tr><td class="label">Nama Pemohon</td><td class="value"><?= htmlspecialchars($data['pemohon'] ?: '-') ?></td></tr>
            <tr><td class="label">No Telefon</td><td class="value"><?= htmlspecialchars($data['no_tel'] ?: '-') ?></td></tr>
            <tr><td class="label">Tarikh Mohon</td><td class="value"><?= formatTarikh($data['tarikh_mohon']) ?></td></tr>
            <tr><td class="label">Bil Hari Sejak Mohon</td><td class="value"><?= $hariKe ?></td></tr>
        </table>
        <div class="section-title section-red">Maklumat Lokasi & Petak</div>
        <table class="info-table">
            <tr><td class="label">Lokasi Jalan</td><td class="value"><?= htmlspecialchars($lokasi_jalan_display) ?></td></tr>
            <tr><td class="label">Alamat Penuh</td><td class="value"><?= htmlspecialchars($alamat_penuh) ?></td></tr>
            <tr><td class="label">No Petak</td><td class="value"><?= htmlspecialchars($data['no_petak'] ?: '-') ?></td></tr>
            <tr><td class="label">Bil Petak Mohon</td><td class="value"><?= $jum_petak_mohon > 0 ? $jum_petak_mohon : '-' ?></td></tr>
            <tr><td class="label">Tempoh Sewaan</td><td class="value"><?= htmlspecialchars($tempoh_sewa ?: '-') ?></td></tr>
            <tr><td class="label">Anggaran Nilai Sewaan</td><td class="value"><?= $nilai_sewa_display ?></td></tr>
            <tr><td class="label">Kedudukan Petak</td><td class="value"><?= htmlspecialchars($data['kedudukan_petak'] ?: '-') ?></td></tr>
            <tr><td class="label">Jumlah Petak Sedia Ada</td><td class="value"><?= htmlspecialchars($data['jumlah_petak_sedia'] ?: '-') ?></td></tr>
            <tr><td class="label">Jenis Bangunan</td><td class="value"><?= htmlspecialchars($data['jenis_bangunan'] ?: '-') ?></td></tr>
        </table>
        <div class="section-title section-red">Maklumat Pemeriksaan</div>
        <table class="info-table">
            <tr><td class="label">Tarikh Periksa</td><td class="value"><?= formatTarikh($data['tarikh_periksa']) ?></td></tr>
            <tr><td class="label">Respon Hari Ke</td><td class="value"><?= $responHariKe ?></td></tr>
            <tr><td class="label">Dokumen Sokongan</td><td class="value"><?= htmlspecialchars($data['doc_sokongan'] ?: '-') ?></td></tr>
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
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary: #6c757d;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
            --bg-light: #f0f4f8;
            --shadow: rgba(0,0,0,0.08);
        }
        body {
            background: var(--bg-light);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: var(--dark);
        }
        .main-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow);
            background: white;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(13,110,253,0.1);
        }
        .detail-row {
            display: flex;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .detail-label {
            font-weight: 600;
            min-width: 220px;
            color: var(--secondary);
            flex: 0 0 220px;
        }
        .detail-value {
            flex: 1;
            color: var(--dark);
        }
        .status-badge {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
            border-radius: 30px;
        }
        .btn {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        .print-only { display: none; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="main-card card">
                <div class="card-header d-flex align-items-center justify-content-between no-print">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-earmark-text fs-4 me-3"></i>
                        <h4 class="mb-0">Maklumat Permohonan</h4>
                    </div>
                    <div>
                        <span class="status-badge badge <?= $statusClass ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5">
                    <!-- Maklumat Pemohon -->
                    <div class="section-card">
                        <div class="section-title">Maklumat Pemohon</div>
                        <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><?= htmlspecialchars($status ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">No ID</span><span class="detail-value"><?= htmlspecialchars($data['custom_id'] ?? $id) ?></span></div>
                        <div class="detail-row"><span class="detail-label">Nama Syarikat</span><span class="detail-value"><?= htmlspecialchars($data['syarikat'] ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">Nama Pemohon</span><span class="detail-value"><?= htmlspecialchars($data['pemohon'] ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">No Telefon</span><span class="detail-value"><?= htmlspecialchars($data['no_tel'] ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">Lesen MBJB</span><span class="detail-value"><?= htmlspecialchars($data['lesen_mbjb'] ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">No SSM</span><span class="detail-value"><?= htmlspecialchars($data['no_ssm'] ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">Tarikh Mohon</span><span class="detail-value"><?= formatTarikh($data['tarikh_mohon']) ?></span></div>
                        <div class="detail-row"><span class="detail-label">Bil Hari</span><span class="detail-value"><?= $hariKe ?: '(tiada)' ?></span></div>
                    </div>

                    <!-- Maklumat Lokasi & Alamat -->
                    <div class="section-card mt-5">
                        <div class="section-title">Maklumat Lokasi & Alamat</div>
                        <div class="detail-row"><span class="detail-label">Lokasi Jalan</span><span class="detail-value"><?= htmlspecialchars($lokasi_jalan_display) ?></span></div>
                        <div class="detail-row"><span class="detail-label">Alamat Penuh</span><span class="detail-value"><?= htmlspecialchars($alamat_penuh) ?></span></div>
                        <div class="detail-row"><span class="detail-label">No Petak</span><span class="detail-value"><?= htmlspecialchars($data['no_petak'] ?: '(tiada)') ?></span></div>
                    </div>

                    <!-- Maklumat Petak & Sewaan -->
                    <div class="section-card mt-5">
                        <div class="section-title">Maklumat Petak & Sewaan</div>
                        <div class="detail-row"><span class="detail-label">Bil Petak Mohon</span><span class="detail-value"><?= $jum_petak_mohon > 0 ? $jum_petak_mohon : '(tiada)' ?></span></div>
                        <div class="detail-row"><span class="detail-label">Tempoh Sewaan</span><span class="detail-value"><?= htmlspecialchars($tempoh_sewa ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">Nilai Sewaan</span><span class="detail-value"><?= $nilai_sewa_display ?></span></div>
                        <div class="detail-row"><span class="detail-label">Kedudukan Petak</span><span class="detail-value"><?= htmlspecialchars($data['kedudukan_petak'] ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">Jumlah Petak Sedia</span><span class="detail-value"><?= htmlspecialchars($data['jumlah_petak_sedia'] ?: '(tiada)') ?></span></div>
                        <div class="detail-row"><span class="detail-label">Jenis Bangunan</span><span class="detail-value"><?= htmlspecialchars($data['jenis_bangunan'] ?: '(tiada)') ?></span></div>
                    </div>

                    <!-- Maklumat Pemeriksaan -->
                    <div class="section-card mt-5">
                        <div class="section-title">Maklumat Pemeriksaan</div>
                        <div class="detail-row"><span class="detail-label">Tarikh Periksa</span><span class="detail-value"><?= formatTarikh($data['tarikh_periksa']) ?></span></div>
                        <div class="detail-row"><span class="detail-label">Respon Hari Ke</span><span class="detail-value"><?= $responHariKe ?></span></div>
                        <div class="detail-row"><span class="detail-label">Dokumen Sokongan</span><span class="detail-value"><?= htmlspecialchars($data['doc_sokongan'] ?: '(tiada)') ?></span></div>
                    </div>

                    <!-- Catatan & Ulasan -->
                    <div class="section-card mt-5">
                        <div class="section-title">Catatan & Ulasan</div>
                        <div class="detail-row"><span class="detail-label">Tugasan</span><div class="detail-value"><?= nl2br(htmlspecialchars($data['tugasan'] ?: '(tiada)')) ?></div></div>
                        <div class="detail-row"><span class="detail-label">Catatan Siasatan</span><div class="detail-value"><?= nl2br(htmlspecialchars($data['catatan_siasatan'] ?: '(tiada)')) ?></div></div>
                        <div class="detail-row"><span class="detail-label">Ulasan Siasatan</span><div class="detail-value"><?= nl2br(htmlspecialchars($data['ulasan_siasatan'] ?: '(tiada)')) ?></div></div>
                        <div class="detail-row"><span class="detail-label">Ulasan Pegawai</span><div class="detail-value"><?= nl2br(htmlspecialchars($data['ulasan_pegawai'] ?: '(tiada)')) ?></div></div>
                        <div class="detail-row"><span class="detail-label">Ulasan Pengarah</span><div class="detail-value"><?= nl2br(htmlspecialchars($data['ulasan_pengarah'] ?: '(tiada)')) ?></div></div>
                    </div>

                    <!-- Footer cetakan (hanya untuk print) -->
                    <div class="print-only stamp-footer mt-5">
                        <div class="stamp-container">
                            <div class="stamp">
                                <div class="stamp-text">(TARIKH & CAP SYARIKAT)</div>
                            </div>
                        </div>
                        <div class="footer-text mt-3">
                            Unit Letak Kereta | Jabatan Penguatkuasaan | Majlis Bandaraya Johor Bahru<br>
                            Dokumen rasmi untuk semakan di tapak sahaja.
                        </div>
                    </div>

                    <!-- Butang web (termasuk Kemaskini) -->
                    <div class="text-end mt-5 no-print">
                        <a href="index.php" class="btn btn-outline-secondary me-2">Keluar</a>
                        <a href="edit.php?id=<?= $id ?>" class="btn btn-warning me-2">Kemaskini</a>
                        <a href="?id=<?= $id ?>&export=pdf" class="btn btn-info me-2">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                        </a>
                        <button onclick="window.print()" class="btn btn-dark">
                            <i class="bi bi-printer me-1"></i> Cetak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>