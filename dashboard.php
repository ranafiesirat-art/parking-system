<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
include "db.php";
$tahun = $_GET['tahun'] ?? date("Y");
$bulan = $_GET['bulan'] ?? "SETAHUN";

// =======================
// Label dinamik (kekal)
// =======================
$labelBulan = ($bulan == "SETAHUN") ? "Setahun" : date("F", mktime(0,0,0,$bulan,1));
$labelKeseluruhan = "Jumlah Keseluruhan Premis yang diperiksa pada $labelBulan";
$labelPermohonanBulan = "Jumlah Premis yang diperiksa (Permohonan $labelBulan)";
$labelPermohonanLain = "Jumlah Premis yang diperiksa (Permohonan Selain $labelBulan)";

// =======================
// WHERE CLAUSE UTAMA
// =======================
$where = "YEAR(tarikh_mohon) = '$tahun'";
if ($bulan != "SETAHUN") {
    $where .= " AND MONTH(tarikh_mohon) = '$bulan'";
}

// =======================
// STATUS + PETAK + NILAI (KPI asal)
// =======================
$query = "
SELECT
    status,
    COUNT(*) as total,
    SUM(bil_petak) as jumlah_petak,
    SUM(nilai_sewa) as jumlah_nilai
FROM permohonan
WHERE $where
GROUP BY status";
$result = $conn->query($query);
$statusData = [];
$totalPermohonan = 0;
$totalPetak = 0;
$totalNilai = 0;
while($row = $result->fetch_assoc()){
    $statusData[$row['status']] = $row;
    $totalPermohonan += $row['total'];
    $totalPetak += $row['jumlah_petak'] ?? 0;
    $totalNilai += $row['jumlah_nilai'] ?? 0;
}

// =======================
// PURATA RESPON
// =======================
$responQuery = "
SELECT AVG(DATEDIFF(tarikh_periksa, tarikh_mohon)) as purata
FROM permohonan
WHERE tarikh_periksa IS NOT NULL
AND $where";
$respon = $conn->query($responQuery)->fetch_assoc()['purata'] ?? 0;

// =======================
// KPI 5,6,7
// =======================
$where_same = "YEAR(tarikh_mohon) = '$tahun'
               AND YEAR(tarikh_periksa) = '$tahun'
               AND tarikh_periksa IS NOT NULL";
if ($bulan != "SETAHUN") {
    $where_same .= " AND MONTH(tarikh_mohon) = '$bulan'
                    AND MONTH(tarikh_periksa) = '$bulan'";
}
$sameQuery = "SELECT COUNT(*) as jumlah FROM permohonan WHERE $where_same";
$jumlahSame = $conn->query($sameQuery)->fetch_assoc()['jumlah'] ?? 0;

$where_lain = "YEAR(tarikh_periksa) = '$tahun'
               AND tarikh_periksa IS NOT NULL";
if ($bulan != "SETAHUN") {
    $where_lain .= " AND MONTH(tarikh_periksa) = '$bulan'
                    AND MONTH(tarikh_mohon) != '$bulan'";
} else {
    $where_lain .= " AND 1=0";
}
$lainQuery = "SELECT COUNT(*) as jumlah FROM permohonan WHERE $where_lain";
$jumlahLain = $conn->query($lainQuery)->fetch_assoc()['jumlah'] ?? 0;

$where_keseluruhan = "YEAR(tarikh_periksa) = '$tahun' AND tarikh_periksa IS NOT NULL";
if ($bulan != "SETAHUN") {
    $where_keseluruhan .= " AND MONTH(tarikh_periksa) = '$bulan'";
}
$keseluruhanQuery = "SELECT COUNT(*) as jumlah FROM permohonan WHERE $where_keseluruhan";
$jumlahKeseluruhan = $conn->query($keseluruhanQuery)->fetch_assoc()['jumlah'] ?? 0;

// =======================
// Baki Premis Belum Diperiksa
// =======================
$bakiBelumQuery = "
    SELECT COUNT(*) as jumlah FROM permohonan
    WHERE $where
    AND (tarikh_periksa IS NULL
         OR tarikh_periksa = ''
         OR tarikh_periksa = '0000-00-00'
         OR tarikh_periksa = '0000-00-00 00:00:00')
";
$bakiBelum = $conn->query($bakiBelumQuery)->fetch_assoc()['jumlah'] ?? 0;

// =======================
// DATA UNTUK CHART & STATUS COUNT
// =======================
$statusCounts = [];

// BELUM (kekal)
$statusCounts['BELUM'] = $conn->query("
    SELECT COUNT(*) as jumlah FROM permohonan
    WHERE $where
    AND status = 'BELUM'
    AND (tarikh_periksa IS NULL
         OR tarikh_periksa = ''
         OR tarikh_periksa = '0000-00-00'
         OR tarikh_periksa = '0000-00-00 00:00:00')
")->fetch_assoc()['jumlah'] ?? 0;

// Base query untuk status diproses
$base_query = "
    SELECT COUNT(*) as jumlah FROM permohonan
    WHERE $where
    AND tarikh_periksa IS NOT NULL";

// CHECKED
$statusCounts['CHECKED'] = $conn->query($base_query . " AND status = 'CHECKED'")->fetch_assoc()['jumlah'] ?? 0;

// ENDORSED
$statusCounts['ENDORSED'] = $conn->query($base_query . " AND status = 'ENDORSED'")->fetch_assoc()['jumlah'] ?? 0;

// APPROVED
$statusCounts['APPROVED'] = $conn->query($base_query . " AND status = 'APPROVED'")->fetch_assoc()['jumlah'] ?? 0;

// REJECTED
$statusCounts['REJECTED'] = $conn->query($base_query . " AND status = 'REJECTED'")->fetch_assoc()['jumlah'] ?? 0;

// ACTIVE
$statusCounts['ACTIVE'] = $conn->query($base_query . " AND status = 'ACTIVE'")->fetch_assoc()['jumlah'] ?? 0;

// KIV
$statusCounts['KIV'] = $conn->query($base_query . " AND status = 'KIV'")->fetch_assoc()['jumlah'] ?? 0;

// INCOMPLETED - total keseluruhan (kekal untuk rujukan)
$incompleteTotal = $conn->query("
    SELECT COUNT(*) as jumlah FROM permohonan
    WHERE $where
    AND status = 'INCOMPLETE'
")->fetch_assoc()['jumlah'] ?? 0;

// INCOMPLETED dengan tarikh_periksa NULL
$incompleteNull = $conn->query("
    SELECT COUNT(*) as jumlah FROM permohonan
    WHERE $where
    AND status = 'INCOMPLETE'
    AND (tarikh_periksa IS NULL
         OR tarikh_periksa = ''
         OR tarikh_periksa = '0000-00-00'
         OR tarikh_periksa = '0000-00-00 00:00:00')
")->fetch_assoc()['jumlah'] ?? 0;

// INCOMPLETED dengan tarikh_periksa ADA
$incompleteAda = $incompleteTotal - $incompleteNull;

// Masukkan ke statusCounts (untuk label & total keseluruhan)
$statusCounts['INCOMPLETE'] = $incompleteTotal;

// =======================
// WARNA STATUS (kekal, tapi INCOMPLETE tak guna lagi sebab pecah dua)
// =======================
$statusColors = [
    "APPROVED"    => "#10b981",
    "REJECTED"    => "#ef4444",
    "CHECKED"     => "#0ea5e9",
    "KIV"         => "#f59e0b",
    "BELUM"       => "#64748b",
    "ACTIVE"      => "#3b82f6",
    "ENDORSED"    => "#8b5cf6",
    "INCOMPLETE"  => "#f97316" // warna default, tapi tak guna lagi sebab dataset pecah
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sistem Permohonan Petak Bermusim</title>
  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
    <style>
        :root { --primary:#0d6efd; --success:#198754; --info:#0dcaf0; --warning:#ffc107; --danger:#dc3545; --dark:#212529; --light-bg:#f0f4f8; }
        body { background:var(--light-bg); min-height:100vh; font-family:'Segoe UI',system-ui,sans-serif; }
        .sidebar { background:white; border-right:1px solid #dee2e6; height:100vh; position:fixed; width:250px; padding-top:1rem; }
        .main-content { margin-left:250px; padding:2rem 1.5rem; }
        .card-kpi { border:none; border-radius:16px; box-shadow:0 8px 20px rgba(0,0,0,0.06); transition:transform 0.3s; }
        .card-kpi:hover { transform:translateY(-5px); }
        .counter { font-size:2.5rem; font-weight:700; color:var(--primary); }
        .chart-container { background:white; border-radius:16px; box-shadow:0 8px 20px rgba(0,0,0,0.06); padding:1.5rem; }
        @media (max-width:992px) { .sidebar { position:relative; height:auto; width:100%; } .main-content { margin-left:0; } }
    </style>
</head>
<body>

<!-- Sidebar (kekal) -->
<nav class="sidebar d-none d-lg-block">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-primary"><i class="bi bi-parking-fill me-2"></i>Parking Admin</h4>
    </div>
    <ul class="nav flex-column px-3">
        <li class="nav-item"><a href="index.php" class="nav-link active"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li class="nav-item"><a href="add.php" class="nav-link"><i class="bi bi-journal-plus me-2"></i>Permohonan Baru</a></li>
        <li class="nav-item"><a href="senarai.php" class="nav-link"><i class="bi bi-list-check me-2"></i>Senarai Permohonan</a></li>
        <li class="nav-item"><a href="#" class="nav-link"><i class="bi bi-person-badge me-2"></i>Pegawai</a></li>
        
        <li class="nav-item mt-auto">
            <a href="index.php" class="nav-link text-primary fw-bold">
                <i class="bi bi-arrow-left-circle me-2"></i>Kembali ke Halaman Utama
            </a>
        </li>
    </ul>
</nav>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark mb-3 mb-md-0">
                <i class="bi bi-graph-up me-2 text-primary"></i>Dashboard Analitik Permohonan
            </h3>
            <div class="d-flex gap-3 align-items-center flex-wrap">
                <form class="d-flex gap-2 flex-wrap">
                    <select name="tahun" class="form-select" style="width:auto;">
                        <?php for($y=2023; $y<=2030; $y++): ?>
                            <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="bulan" class="form-select" style="width:auto;">
                        <option value="SETAHUN" <?= $bulan=='SETAHUN'?'selected':'' ?>>Setahun</option>
                        <?php for($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= date("F", mktime(0,0,0,$m,1)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat me-2"></i>Jana Laporan</button>
                    <a href="dashboard_pdf.php?tahun=<?= $tahun ?>&bulan=<?= $bulan ?>" class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                    </a>
                </form>
                <a href="index.php" class="btn btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-house-door-fill me-1"></i>Ke Halaman Utama
                </a>
            </div>
        </div>

        <!-- KPI Cards - 8 card (kekal) -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text fs-1 text-primary mb-2"></i>
                        <h6 class="text-muted">Jumlah Permohonan</h6>
                        <div class="counter" data-target="<?= $totalPermohonan ?>"><?= $totalPermohonan ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-grid-3x3-gap fs-1 text-success mb-2"></i>
                        <h6 class="text-muted">Jumlah Petak Dimohon</h6>
                        <div class="counter" data-target="<?= $totalPetak ?>"><?= number_format($totalPetak) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-currency-dollar fs-1 text-warning mb-2"></i>
                        <h6 class="text-muted">Anggaran Nilai Sewaan (RM)</h6>
                        <div class="counter" data-target="<?= $totalNilai ?>">RM <?= number_format($totalNilai, 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-clock-history fs-1 text-info mb-2"></i>
                        <h6 class="text-muted">Purata Masa Respon (Hari)</h6>
                        <div class="counter" data-target="<?= round($respon, 1) ?>"><?= round($respon, 1) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-building-check fs-1 text-primary mb-2"></i>
                        <h6 class="text-muted"><?= $labelKeseluruhan ?></h6>
                        <div class="counter" data-target="<?= $jumlahKeseluruhan ?>"><?= number_format($jumlahKeseluruhan) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-building-check fs-1 text-success mb-2"></i>
                        <h6 class="text-muted"><?= $labelPermohonanBulan ?></h6>
                        <div class="counter" data-target="<?= $jumlahSame ?>"><?= number_format($jumlahSame) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-building-exclamation fs-1 text-warning mb-2"></i>
                        <h6 class="text-muted"><?= $labelPermohonanLain ?></h6>
                        <div class="counter" data-target="<?= $jumlahLain ?>"><?= number_format($jumlahLain) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card card-kpi bg-white">
                    <div class="card-body text-center">
                        <i class="bi bi-hourglass-split fs-1 text-danger mb-2"></i>
                        <h6 class="text-muted">Baki Premis Belum Diperiksa (<?= $labelBulan ?>)</h6>
                        <div class="counter text-danger" data-target="<?= $bakiBelum ?>"><?= number_format($bakiBelum) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="card-title mb-4">Taburan Status Permohonan</h5>
                    <canvas id="statusBarChart" height="200"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5 class="card-title mb-4">Pecahan Petak Mengikut Status</h5>
                    <canvas id="petakPieChart" height="220"></canvas>
                </div>
            </div>
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="card-title mb-4">Trend Permohonan (Contoh Simulasi Bulanan)</h5>
                    <canvas id="trendLineChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Data untuk chart
const statusLabels = <?= json_encode(array_keys($statusCounts)) ?>;
const statusValues = <?= json_encode(array_values($statusCounts)) ?>;
const colors = <?= json_encode(array_values($statusColors)) ?>;

// Khusus untuk INCOMPLETE - pecah dua dataset
const incompleteNull = <?= $incompleteNull ?>;
const incompleteAda = <?= $incompleteAda ?>;

// Chart 1: Horizontal Bar - Status (dengan INCOMPLETE pecah dua warna)
new Chart(document.getElementById('statusBarChart'), {
    type: 'bar',
    data: {
        labels: statusLabels,
        datasets: [
            {
                label: 'Jumlah Permohonan',
                data: statusValues.map((val, idx) => idx === statusLabels.indexOf('INCOMPLETE') ? incompleteNull : val),
                backgroundColor: colors.map((c, idx) => idx === statusLabels.indexOf('INCOMPLETE') ? '#f97316' : c),
                borderWidth: 0,
                borderRadius: 8,
                stack: 'incomplete' // stack untuk INCOMPLETE
            },
            {
                label: 'INCOMPLETE (Ada Tarikh Periksa)',
                data: statusValues.map((val, idx) => idx === statusLabels.indexOf('INCOMPLETE') ? incompleteAda : 0),
                backgroundColor: '#ffc107', // kuning cerah
                borderWidth: 0,
                borderRadius: 8,
                stack: 'incomplete' // stack sama supaya jadi satu bar
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false } // sembunyi legend supaya tak keliru
        },
        scales: {
            x: {
                beginAtZero: true,
                stacked: true // aktifkan stacking
            },
            y: {
                stacked: true
            }
        }
    }
});

// Chart 2: Pie - Pecahan Petak (kekal)
new Chart(document.getElementById('petakPieChart'), {
    type: 'pie',
    data: {
        labels: statusLabels,
        datasets: [{
            data: <?= json_encode(array_column($statusData, 'jumlah_petak')) ?>,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw} petak` } }
        }
    }
});

// Chart 3: Line - Trend (kekal)
new Chart(document.getElementById('trendLineChart'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mac','Apr','Mei','Jun','Jul','Ogos','Sep','Okt','Nov','Dis'],
        datasets: [{
            label: 'Permohonan Bulanan',
            data: [12,19,15,25,30,22,18,28,35,40,32,45],
            borderColor: 'var(--primary)',
            tension: 0.4,
            fill: false
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Counter animation (kekal)
document.querySelectorAll('.counter').forEach(el => {
    const target = parseFloat(el.getAttribute('data-target'));
    let count = 0; const duration = 1500; const step = target / (duration / 16);
    function update() {
        count += step;
        if (count < target) { el.textContent = Math.ceil(count).toLocaleString('ms-MY'); requestAnimationFrame(update); }
        else { el.textContent = target.toLocaleString('ms-MY'); }
    }
    update();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>