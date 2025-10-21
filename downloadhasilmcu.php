<?php
// Panggil library FPDF dari folder yang benar
require('fpdf185/fpdf.php');
include '../koneksi.php';

// Ambil ID jadwal dari URL
$jadwal_id = $_GET['id'] ?? 0;
if (!$jadwal_id) {
    die('⚠️ ID jadwal tidak ditemukan.');
}

// Ambil data hasil MCU dari database
$stmt = $conn->prepare("
    SELECT 
        j.jadwal_id, j.tanggal_mcu, j.status_mcu,
        p.nama_pasien, p.nik, p.jk, p.tgl_lahir, p.goldar,
        h.td, h.tb, h.bb, h.kolesterol, h.asam_urat, h.status_kelayakan, h.catatan_dokter,
        pn.tes_urin, pn.tes_hcv, pn.tes_hiv, pn.tes_bta, pn.tes_hav, pn.tes_hbv
    FROM jadwalmcu j
    JOIN pasien p ON j.pasien_id = p.pasien_id
    LEFT JOIN hasilmcu h ON j.jadwal_id = h.jadwal_id
    LEFT JOIN pempenunjang pn ON j.jadwal_id = pn.jadwal_id
    WHERE j.jadwal_id = ?
");
$stmt->bind_param("i", $jadwal_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die('⚠️ Data tidak ditemukan.');
}

// Format tanggal
$tanggal_mcu = date('d M Y', strtotime($data['tanggal_mcu']));
$tanggal_lahir = date('d M Y', strtotime($data['tgl_lahir']));

// ==========================
// Kelas PDF
// ==========================
class PDF extends FPDF
{
    function Header()
    {
        // Logo rumah sakit
        if (file_exists('../assets/img/logo_rs.png')) {
            $this->Image('../assets/img/logo_rs.png', 15, 10, 20);
        }
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'Klinik Poltekkes Kemenkes Tasikmalaya', 0, 1, 'C');
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 7, 'Jalan Cilolohan No. 35, Tasikmalaya 46115 | Tlp. 0265-340186', 0, 1, 'C');
        $this->Ln(4);
        $this->SetLineWidth(0.6);
        $this->Line(15, 32, 195, 32);
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 10, 'Dicetak pada: ' . date('d/m/Y H:i'), 0, 0, 'L');
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'R');
    }
}

// ==========================
// Cetak isi PDF
// ==========================
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

// Judul
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN HASIL MEDICAL CHECK UP', 0, 1, 'C');
$pdf->Ln(5);

// --- Data Pasien ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'DATA PASIEN', 0, 1);
$pdf->SetFont('Arial', '', 11);

$pasien = [
    'Nama Pasien' => $data['nama_pasien'],
    'NIK' => $data['nik'],
    'Jenis Kelamin' => $data['jk'],
    'Tanggal Lahir' => $tanggal_lahir,
    'Golongan Darah' => $data['goldar'],
    'Tanggal MCU' => $tanggal_mcu,
    'Status MCU' => $data['status_mcu']
];
foreach ($pasien as $label => $value) {
    $pdf->Cell(60, 8, $label, 0, 0);
    $pdf->Cell(0, 8, ': ' . $value, 0, 1);
}
$pdf->Ln(5);

// --- Hasil Pemeriksaan ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'HASIL PEMERIKSAAN', 0, 1);
$pdf->SetFont('Arial', '', 11);

$hasil = [
    'Tekanan Darah' => $data['td'] . ' mmHg',
    'Tinggi / Berat Badan' => $data['tb'] . ' cm / ' . $data['bb'] . ' kg',
    'Kolesterol' => $data['kolesterol'] . ' mg/dL',
    'Asam Urat' => $data['asam_urat'] . ' mg/dL',
    'Status Kelayakan' => $data['status_kelayakan']
];
foreach ($hasil as $label => $value) {
    $pdf->Cell(60, 8, $label, 0, 0);
    $pdf->Cell(0, 8, ': ' . $value, 0, 1);
}
$pdf->Ln(4);
$pdf->MultiCell(0, 8, 'Catatan Dokter: ' . ($data['catatan_dokter'] ?: '-'));
$pdf->Ln(8);

// --- Pemeriksaan Penunjang ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'PEMERIKSAAN PENUNJANG', 0, 1);
$pdf->SetFont('Arial', '', 11);

$penunjang = [
    'Tes Urin' => $data['tes_urin'],
    'Tes HCV' => $data['tes_hcv'],
    'Tes HIV' => $data['tes_hiv'],
    'Tes BTA' => $data['tes_bta'],
    'Tes HAV' => $data['tes_hav'],
    'Tes HBV' => $data['tes_hbv']
];
foreach ($penunjang as $label => $value) {
    $pdf->Cell(60, 8, $label, 0, 0);
    $pdf->Cell(0, 8, ': ' . ($value ?: '-'), 0, 1);
}

// --- Tanda Tangan Dokter ---
$pdf->Ln(20);
$pdf->Cell(120);
$pdf->Cell(0, 8, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(120);
$pdf->Cell(0, 8, 'Dokter Pemeriksa', 0, 1, 'R');
$pdf->Ln(25);
$pdf->Cell(120);
$pdf->Cell(0, 8, '_________________________', 0, 1, 'R');

// Output PDF
$pdf->Output('I', 'Hasil_MCU_' . $data['nama_pasien'] . '.pdf');
?>
