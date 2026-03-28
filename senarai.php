<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
include "db.php";

// =======================
// AMBIL NILAI GET
// =======================
$no_id = trim($_GET['no_id'] ?? '');
$syarikat = $_GET['syarikat'] ?? '';
$jalan = $_GET['jalan'] ?? '';
$taman = $_GET['taman'] ?? '';
$status = $_GET['status'] ?? [];
$periksa_mula = $_GET['periksa_mula'] ?? '';
$periksa_tamat = $_GET['periksa_tamat'] ?? '';
$mohon_mula = $_GET['mohon_mula'] ?? '';
$mohon_tamat = $_GET['mohon_tamat'] ?? '';

// =======================
// FILTER
// =======================
$where = [];
if ($no_id != '') {
    $escaped = $conn->real_escape_string($no_id);
    $where[] = "custom_id = '$escaped'";
}
if($syarikat != ''){
    $where[] = "syarikat LIKE '%".$conn->real_escape_string($syarikat)."%'";
}
if($jalan != ''){
    $where[] = "lokasi_jalan LIKE '%".$conn->real_escape_string($jalan)."%'";
}
if($taman != ''){
    $where[] = "alamat_taman LIKE '%".$conn->real_escape_string($taman)."%'";
}
if(!empty($status)){
    $statusClean = array_map(function($s) use ($conn){
        return "'".$conn->real_escape_string($s)."'";
    }, $status);
    $where[] = "status IN (".implode(",", $statusClean).")";
}
if($periksa_mula != '' && $periksa_tamat != ''){
    $where[] = "tarikh_periksa BETWEEN '$periksa_mula' AND '$periksa_tamat'";
}
if($mohon_mula != '' && $mohon_tamat != ''){
    $where[] = "tarikh_mohon BETWEEN '$mohon_mula' AND '$mohon_tamat'";
}

// =======================
// BUILD QUERY
// =======================
$sql = "SELECT * FROM permohonan";
if(count($where) > 0){
    $sql .= " WHERE ".implode(" AND ", $where);
}
$sql .= " ORDER BY alamat_taman ASC";
$result = $conn->query($sql);
$total = $result->num_rows;

// Collect rows for DataTables
$rows = [];
$bil = 1;
while($row = $result->fetch_assoc()){
    $tarikh_periksa_raw = trim($row['tarikh_periksa'] ?? '');
    $tarikh_periksa_display = '';
    if ($tarikh_periksa_raw && $tarikh_periksa_raw !== '0000-00-00' && $tarikh_periksa_raw !== '0000-00-00 00:00:00') {
        $tarikh_periksa_display = date('d/m/Y', strtotime($tarikh_periksa_raw));
    } else {
        $tarikh_periksa_display = '<strong class="text-muted">BELUM PERIKSA</strong>';
    }
    $rows[] = [
        $bil++,
        htmlspecialchars($row['custom_id'] ?? $row['id']),
        $row['status'],
        htmlspecialchars($row['syarikat'] ?: '(tiada)'),
        $row['tarikh_mohon'] ? date('d/m/Y', strtotime($row['tarikh_mohon'])) : '(tiada)',
        $tarikh_periksa_display,
        htmlspecialchars($row['no_petak'] ?: '(tiada)'),
        htmlspecialchars($row['lokasi_jalan'] ?: '(tiada)'),
        htmlspecialchars($row['alamat_taman'] ?: '(tiada)'),
        '<a href="view.php?id='.$row['id'].'" class="btn btn-sm btn-primary me-1"><i class="bi bi-eye"></i></a>
         <a href="delete.php?id='.$row['id'].'" class="btn btn-sm btn-danger" onclick="return confirm(\'Anda pasti mahu padam rekod ini?\')"><i class="bi bi-trash"></i></a>'
    ];
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai Permohonan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.css" rel="stylesheet">
    
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
            max-width: 100%;
        }
        .top-navbar {
            background: white;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border-radius: 16px;
            margin-bottom: 2rem;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.07);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }
        .table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .status-badge {
            padding: 0.4em 0.95em;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .bg-belum { background: #64748b; color: white; }
        .bg-checked { background: #06b6d4; color: white; }
        .bg-endorsed { background: #7c3aed; color: white; }
        .bg-approved { background: #10b981; color: white; }
        .bg-rejected { background: #ef4444; color: white; }
        .bg-kiv { background: #f59e0b; color: black; }
        .bg-incomplete { background: #f97316; color: white; }
        .bg-active { background: #3b82f6; color: white; }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
        }
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }
    </style>
</head>
<body>

<div class="main-content">
    <!-- Top Navbar -->
    <nav class="top-navbar navbar navbar-expand-lg navbar-light px-4 py-3">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <h4 class="mb-0 fw-semibold text-dark">
                    <i class="bi bi-clipboard-data me-3"></i>Senarai Permohonan
                </h4>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary fs-6 px-3 py-2">
                    Jumlah Rekod: <strong><?= $total ?></strong>
                </span>
                
                <div class="dropdown">
                    <button class="btn btn-light d-flex align-items-center gap-2 dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span><?= htmlspecialchars($_SESSION['username'] ?? 'Pengguna') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Card -->
    <div class="card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold text-secondary">
                <i class="bi bi-funnel-fill me-2"></i>Carian Permohonan
            </h6>
            <a href="add.php" class="btn btn-primary rounded-pill px-4 py-2">
                <i class="bi bi-plus-circle me-2"></i>Permohonan Baru
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text" name="no_id" class="form-control" id="no_id" placeholder="Custom ID" value="<?= htmlspecialchars($no_id) ?>">
                        <label for="no_id">Custom ID (No Utama - Exact)</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text" name="syarikat" class="form-control" id="syarikat" placeholder="Nama Syarikat" value="<?= htmlspecialchars($syarikat) ?>">
                        <label for="syarikat">Nama Syarikat</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text" name="jalan" class="form-control" id="jalan" placeholder="Nama Jalan" value="<?= htmlspecialchars($jalan) ?>">
                        <label for="jalan">Nama Jalan</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text" name="taman" class="form-control" id="taman" placeholder="Nama Taman" value="<?= htmlspecialchars($taman) ?>">
                        <label for="taman">Nama Taman</label>
                    </div>
                </div>

                <!-- Status -->
                <div class="col-12 mt-2">
                    <label class="form-label fw-semibold text-muted mb-2">Status</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $statusArr = [
                            "BELUM" => "bg-belum",
                            "CHECKED" => "bg-checked",
                            "ENDORSED" => "bg-endorsed",
                            "APPROVED" => "bg-approved",
                            "REJECTED" => "bg-rejected",
                            "KIV" => "bg-kiv",
                            "INCOMPLETE" => "bg-incomplete",
                            "ACTIVE" => "bg-active"
                        ];
                        foreach($statusArr as $s => $cls){
                            $checked = in_array($s, $status) ? "checked" : "";
                            echo "<div class='form-check form-check-inline'>
                                    <input class='form-check-input' type='checkbox' name='status[]' value='$s' id='status_$s' $checked>
                                    <label class='form-check-label status-badge $cls px-3 py-1' for='status_$s'>$s</label>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Tarikh -->
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date" name="periksa_mula" class="form-control" id="periksa_mula" value="<?= htmlspecialchars($periksa_mula) ?>">
                        <label for="periksa_mula">Periksa Dari</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date" name="periksa_tamat" class="form-control" id="periksa_tamat" value="<?= htmlspecialchars($periksa_tamat) ?>">
                        <label for="periksa_tamat">Hingga</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date" name="mohon_mula" class="form-control" id="mohon_mula" value="<?= htmlspecialchars($mohon_mula) ?>">
                        <label for="mohon_mula">Mohon Dari</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date" name="mohon_tamat" class="form-control" id="mohon_tamat" value="<?= htmlspecialchars($mohon_tamat) ?>">
                        <label for="mohon_tamat">Hingga</label>
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-3 pt-3">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-search me-2"></i>Cari
                    </button>
                    <a href="senarai.php" class="btn btn-outline-secondary px-5">Kosongkan Carian</a>
                    <a href="index.php" class="btn btn-outline-primary px-5">
                        <i class="bi bi-house-door me-2"></i>Kembali Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">Hasil Carian</h6>
            <span class="text-muted small">Jumlah rekod: <strong class="text-dark"><?= $total ?></strong></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="searchTable" class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">Bil</th>
                            <th>Custom ID</th>
                            <th>Status</th>
                            <th>Nama Syarikat</th>
                            <th>Tarikh Mohon</th>
                            <th>Tarikh Periksa</th>
                            <th>No Petak</th>
                            <th>Jalan</th>
                            <th>Taman</th>
                            <th class="text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rows as $row): ?>
                        <tr>
                            <?php
                            $status_val = $row[2];
                            $badgeClass = match(strtoupper($status_val)) {
                                'APPROVED' => 'bg-approved',
                                'REJECTED' => 'bg-rejected',
                                'CHECKED' => 'bg-checked',
                                'ENDORSED' => 'bg-endorsed',
                                'KIV' => 'bg-kiv',
                                'INCOMPLETE' => 'bg-incomplete',
                                'ACTIVE' => 'bg-active',
                                default => 'bg-belum',
                            };
                            $row[2] = "<span class='status-badge $badgeClass'>$status_val</span>";
                            foreach($row as $cell) echo "<td>$cell</td>";
                            ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.bootstrap5.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#searchTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', text: '<i class="bi bi-copy"></i> Copy' },
            { extend: 'csv', text: '<i class="bi bi-filetype-csv"></i> CSV' },
            { extend: 'excel', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
            { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf"></i> PDF' },
            { extend: 'print', text: '<i class="bi bi-printer"></i> Print' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/ms.json'
        },
        responsive: true,
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        order: [[8, 'asc']]
    });
});
</script>
</body>
</html>