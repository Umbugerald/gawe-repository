<?php
session_start();
require '../../../dbconnection/dbconnection.php';
header('Content-Type: application/json');

// Pastikan session user_id sudah diset saat login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sesi tidak valid"]);
    exit;
}

// Ini adalah id_users pekerja yang sedang login (misal: 'PK-26045910')
$id_pekerja = $_SESSION['user_id'];

$sql = "SELECT 
            o.id_order, 
            o.keluhan, 
            o.detail_keluhan, 
            o.harga, 
            o.foto_keluhan,
            o.status,
            u.nama_lengkap AS nama_pelanggan,
            u.no_wa,
            a.detail_alamat, 
            a.kecamatan, 
            a.kota, 
            a.provinsi,
            a.latitude,
            a.longitude
        FROM orders o
        JOIN master_user u ON o.id_user_pelanggan = u.id_users
        JOIN master_alamat a ON o.id_alamat = a.id_alamat
        WHERE o.id_users_pekerja = ? 
          AND o.status IN ('proses', 'diterima', 'sampai')";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id_pekerja);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);

if ($order) {
    echo json_encode(["status" => "success", "data" => $order]);
} else {
    echo json_encode(["status" => "empty"]);
}
