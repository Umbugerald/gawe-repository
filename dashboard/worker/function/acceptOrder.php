<?php
session_start();
header('Content-Type: application/json');
require '../../../dbconnection/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pekerja = $_SESSION['user_id'];
    $id_order = $_POST['id_order'];

    $cek_sql = "SELECT id_order FROM orders WHERE id_users_pekerja = ? AND status IN ('diterima', 'sampai', 'proses')";
    $stmt_cek = mysqli_prepare($conn, $cek_sql);
    mysqli_stmt_bind_param($stmt_cek, "s", $id_pekerja);
    mysqli_stmt_execute($stmt_cek);
    $result_cek = mysqli_stmt_get_result($stmt_cek);

    if (mysqli_num_rows($result_cek) > 0) {
        echo json_encode(["status" => "error", "message" => "Kamu sedang mengerjakan orderan lain! Selesaikan dulu sebelum mengambil order baru."]);
        exit;
    }

    $update_sql = "UPDATE orders SET status = 'diterima', id_users_pekerja = ? WHERE id_order = ? AND status = 'mencari'";
    $stmt_up = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt_up, "ss", $id_pekerja, $id_order);
    mysqli_stmt_execute($stmt_up);

    // Cek apakah ada baris yang berhasil diupdate
    if (mysqli_stmt_affected_rows($stmt_up) > 0) {
        echo json_encode(["status" => "success", "message" => "Order berhasil diterima!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menerima. Orderan mungkin baru saja diambil oleh pekerja lain."]);
    }
}
?>