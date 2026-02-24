<?php
include "db.php";
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$tahun = $_GET['tahun'] ?? date("Y");
$bulan = $_GET['bulan'] ?? "SETAHUN";

$where = "YEAR(tarikh_mohon) = '$tahun'";

if($bulan != "SETAHUN"){
    $where .= " AND MONTH(tarikh_mohon) = '$bulan'";
}

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

$totalPermohonan = 0;
$totalPetak = 0;
$totalNilai = 0;
$statusRows = "";

while($row = $result->fetch_assoc()){
    $totalPermohonan += $row['total'];
    $totalPetak += $row['jumlah_petak'];
    $totalNilai += $row['jumlah_nilai'];

    $statusRows .= "
    <tr>
        <td>{$row['status']}</td>
        <td>{$row['total']}</td>
        <td>{$row['jumlah_petak']}</td>
        <td>RM ".number_format($row['jumlah_nilai'],2)."</td>
    </tr>";
}

$responQuery = "
SELECT AVG(DATEDIFF(tarikh_periksa, tarikh_mohon)) as purata
FROM permohonan
WHERE tarikh_periksa IS NOT NULL
AND $where";

$respon = $conn->query($responQuery)->fetch_assoc()['purata'] ?? 0;

$html = "
<h2 style='text-align:center;'>LAPORAN DASHBOARD PARKING</h2>
<p><strong>Tahun:</strong> $tahun</p>
<p><strong>Bulan:</strong> $bulan</p>

<h3>Ringkasan KPI</h3>
<ul>
<li>Jumlah Permohonan: $totalPermohonan</li>
<li>Jumlah Petak: $totalPetak</li>
<li>Anggaran Nilai: RM ".number_format($totalNilai,2)."</li>
<li>Purata Respon: ".round($respon,1)." Hari</li>
</ul>

<h3>Status Permohonan</h3>
<table border='1' width='100%' cellpadding='5'>
<tr>
<th>Status</th>
<th>Jumlah</th>
<th>Jumlah Petak</th>
<th>Jumlah Nilai</th>
</tr>
$statusRows
</table>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Dashboard_$tahun.pdf", ["Attachment"=>true]);
exit;
