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
    // PAPARAN TARIKH PERIKSA: NULL / kosong / invalid → "BELUM PERIKSA"
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
        '<a href="view.php?id='.$row['id'].'" class="btn btn-sm btn-info me-1"><i class="bi bi-eye"></i></a>
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
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.06);
        }
        .search-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 1.25rem 1.5rem;
        }
        .infografik-input {
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s;
        }
        .infografik-input:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(13,110,253,0.15);
        }
        .table {
            font-size: 0.9rem;
        }
        .table th {
            font-size: 0.85rem;
            background: #0d6efd;
            color: white;
            text-align: center;
            vertical-align: middle;
        }
        .table td {
            vertical-align: middle;
        }
        .status-badge {
            padding: 0.4em 0.9em;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .bg-belum { background: #6c757d; color: white; }
        .bg-checked { background: #17a2b8; color: white; }
        .bg-endorsed { background: #6f42c1; color: white; }
        .bg-approved { background: #28a745; color: white; }
        .bg-rejected { background: #dc3545; color: white; }
        .bg-kiv { background: #ffc107; color: black; }
        .bg-incomplete { background: #fd7e14; color: white; }
        .bg-active { background: #007bff; color: white; }
        .btn-search {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            border-radius: 50px;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <!-- Card Carian Compact & Menarik -->
    <div class="card shadow border-0 rounded-4 mb-5">
        <div class="search-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="bi bi-search fs-4 me-3"></i>
                <h5 class="mb-0 fw-bold">Carian Permohonan</h5>
            </div>
            <a href="add.php" class="btn btn-light btn-md shadow rounded-pill px-4">
                <i class="bi bi-plus-circle me-2"></i> Permohonan Baru
            </a>
        </div>
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="text" name="no_id" class="form-control" id="no_id" placeholder="Custom ID (No Utama)" value="<?= htmlspecialchars($no_id) ?>">
                            <label for="no_id">Custom ID (No Utama - Exact)</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="text" name="syarikat" class="form-control" id="syarikat" placeholder="Nama Syarikat" value="<?= htmlspecialchars($syarikat) ?>">
                            <label for="syarikat">Nama Syarikat</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="text" name="jalan" class="form-control" id="jalan" placeholder="Nama Jalan" value="<?= htmlspecialchars($jalan) ?>">
                            <label for="jalan">Nama Jalan</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="text" name="taman" class="form-control" id="taman" placeholder="Nama Taman" value="<?= htmlspecialchars($taman) ?>">
                            <label for="taman">Nama Taman</label>
                        </div>
                    </div>
                </div>
                <!-- Status -->
                <div class="col-12">
                    <label class="form-label fw-bold text-muted mb-2">Status</label>
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
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="date" name="periksa_mula" class="form-control" id="periksa_mula" value="<?= htmlspecialchars($periksa_mula) ?>">
                            <label for="periksa_mula">Periksa Dari</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="date" name="periksa_tamat" class="form-control" id="periksa_tamat" value="<?= htmlspecialchars($periksa_tamat) ?>">
                            <label for="periksa_tamat">Hingga</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="date" name="mohon_mula" class="form-control" id="mohon_mula" value="<?= htmlspecialchars($mohon_mula) ?>">
                            <label for="mohon_mula">Mohon Dari</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="infografik-input">
                        <div class="form-floating">
                            <input type="date" name="mohon_tamat" class="form-control" id="mohon_tamat" value="<?= htmlspecialchars($mohon_tamat) ?>">
                            <label for="mohon_tamat">Hingga</label>
                        </div>
                    </div>
                </div>
                <!-- Butang -->
                <div class="col-12 d-flex justify-content-end gap-3 mt-4 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-md px-5 rounded-pill">
                        <i class="bi bi-search me-2"></i>Cari
                    </button>
                    <a href="senarai.php" class="btn btn-outline-secondary btn-md px-5 rounded-pill">
                        <i class="bi bi-x-circle me-2"></i>Kosongkan
                    </a>
                    <a href="index.php" class="btn btn-outline-primary btn-md px-5 rounded-pill">
                        <i class="bi bi-house-door-fill me-2"></i>Kembali ke Halaman Utama
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- Table senarai -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center p-3">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Senarai Permohonan</h5>
            <div class="d-flex align-items-center gap-3">
                <h6 class="mb-0">Jumlah Rekod: <span class="badge bg-light text-dark fs-6"><?= $total ?></span></h6>
                <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-house-door me-1"></i>Ke Dashboard
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="searchTable" class="table table-hover table-bordered table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">Bil</th>
                            <th>Custom ID (No Utama)</th>
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
                            $status = $row[2];
                            $badgeClass = match(strtoupper($status)) {
                                'APPROVED' => 'bg-approved',
                                'REJECTED' => 'bg-rejected',
                                'CHECKED' => 'bg-checked',
                                'ENDORSED' => 'bg-endorsed',
                                'KIV' => 'bg-kiv',
                                'INCOMPLETE' => 'bg-incomplete',
                                'ACTIVE' => 'bg-active',
                                default => 'bg-belum',
                            };
                            $row[2] = "<span class='status-badge $badgeClass'>$status</span>";
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
        order: [[8, 'asc']] // Urut ikut Taman
    });
});
</script>
</body>
</html>