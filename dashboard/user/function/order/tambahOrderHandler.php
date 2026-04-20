<?php
session_start();
require '../../../../dbconnection/dbconnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil data dari POST
    $id_pelanggan = $_POST['id_user_pelanggan'] ?? '';
    $id_alamat    = $_POST['id_alamat'] ?? '';
    $keluhan      = $_POST['keluhan'] ?? '';
    $detail       = $_POST['detail_keluhan'] ?? '';
    $harga        = $_POST['harga'] ?? 0;

    // 2. LOGIKA GENERATE ID: OR-YYMMXXXX
    $prefix = "OR-" . date('ym'); // Contoh Hasil: OR-2604 (Tahun 2026, Bulan 04)

    // Kita cek ke tabel 'orders' untuk mendapatkan nomor urut terakhir
    // Gunakan backtick `` pada nama tabel `orders` karena di beberapa versi MySQL ini termasuk reserved word
    $sqlCek = "SELECT id_order FROM `orders` WHERE id_order LIKE '$prefix%' ORDER BY id_order DESC LIMIT 1";
    $queryCek = mysqli_query($conn, $sqlCek);

    if ($queryCek && mysqli_num_rows($queryCek) > 0) {
        $row = mysqli_fetch_assoc($queryCek);
        // Ambil 4 digit terakhir, lalu tambah 1
        $lastNumber = (int)substr($row['id_order'], -4);
        $newNumber = str_pad($lastNumber + 1, 4, "0", STR_PAD_LEFT);
    } else {
        // Jika belum ada pesanan sama sekali di bulan ini
        $newNumber = "0001";
    }

    $id_order = $prefix . $newNumber; // Gabung jadi OR-26040001

    // 3. Proses Upload Foto
    $foto_name = "";
    if (isset($_FILES['foto_keluhan']) && $_FILES['foto_keluhan']['error'] === 0) {
        $ext = pathinfo($_FILES['foto_keluhan']['name'], PATHINFO_EXTENSION);
        $foto_name = "ORD_" . time() . "_" . rand(100, 999) . "." . $ext;
        $target = "../../../../assets/img/uploads/keluhan/" . $foto_name;

        move_uploaded_file($_FILES['foto_keluhan']['tmp_name'], $target);
    }

    if (empty($_FILES['foto_keluhan']['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'File tidak terdeteksi oleh server. Periksa enctype form Anda.']);
        exit;
    }

    // 4. Query Insert ke tabel 'orders'
    $sql = "INSERT INTO `orders` (id_order, id_user_pelanggan, id_alamat, keluhan, detail_keluhan, harga, foto_keluhan, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'mencari')";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query: ' . mysqli_error($conn)]);
        exit;
    }

    // "sssssds" artinya: string, string, string, string, string, double(decimal/number), string
    mysqli_stmt_bind_param(
        $stmt,
        "sssssds",
        $id_order,
        $id_pelanggan,
        $id_alamat,
        $keluhan,
        $detail,
        $harga,
        $foto_name
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Pesanan berhasil dibuat!',
            'id_order' => $id_order
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke tabel orders: ' . mysqli_error($conn)]);
    }

    mysqli_stmt_close($stmt);
}
