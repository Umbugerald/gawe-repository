<?php
session_start();
require '../../../dbconnection/dbconnection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_users = $_SESSION['user_id'];
    $provinsi = $_POST['provinsi'];
    $kota = $_POST['kota'];
    $kecamatan = $_POST['kecamatan'];

    // 1. Update wilayah kerja pekerja
    $update_sql = "UPDATE profile_pekerja SET provinsi = ?, kota = ?, kecamatan = ? WHERE id_users = ?";
    $stmt_up = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt_up, "ssss", $provinsi, $kota, $kecamatan, $id_users);
    mysqli_stmt_execute($stmt_up);

    // --- SISTEM CLEANUP 5 MENIT ---
    $sqlCleanup = "UPDATE `orders` 
                   SET status = 'dibatalkan_sistem' 
                   WHERE status = 'mencari' 
                   AND created_at <= (NOW() - INTERVAL 5 MINUTE)";
    mysqli_query($conn, $sqlCleanup);
    // ------------------------------

    // 3. Ambil data orderan yang masih mencari
    // PASTIKAN menambahkan o.created_at di dalam SELECT
    // ... (kode sebelumnya dari session_start sampai query cleanup tetap sama) ...

    // 3. Ambil data orderan yang masih mencari
    // KITA TAMBAHKAN TIMESTAMPDIFF DI SINI UNTUK MENGHITUNG DETIK LANGSUNG DARI DATABASE
    $sql = "SELECT 
            o.id_order, 
            o.keluhan, 
            o.detail_keluhan, 
            o.harga, 
            o.foto_keluhan,
            o.created_at, 
            TIMESTAMPDIFF(SECOND, NOW(), o.created_at + INTERVAL 5 MINUTE) AS sisa_detik,
            u.nama_lengkap AS nama_pelanggan,
            a.detail_alamat, 
            a.kecamatan, 
            a.kota, 
            a.provinsi
            FROM orders o
            JOIN master_user u ON o.id_user_pelanggan = u.id_users
            JOIN master_alamat a ON o.id_alamat = a.id_alamat
            WHERE a.provinsi = ? 
            AND a.kecamatan = ? 
            AND a.kota = ? 
            AND o.status = 'mencari'";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt === false) {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "sss", $provinsi, $kecamatan, $kota);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $orders = [];

    // Looping sekarang jadi jauh lebih bersih karena PHP tidak perlu hitung waktu lagi
    while ($row = mysqli_fetch_assoc($result)) {
        // Ambil sisa detik hasil hitungan database
        $sisa_detik = (int)$row['sisa_detik'];

        // Hanya tampilkan jika waktu masih ada (di atas 0 detik)
        if ($sisa_detik > 0) {
            $orders[] = $row;
        }
    }

    echo json_encode(["status" => "success", "data" => $orders]);
}
