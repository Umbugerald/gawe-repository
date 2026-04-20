<?php
session_start();
require '../../../../dbconnection/dbconnection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $id_order = mysqli_real_escape_string($conn, $data['id_order']);
    $rating_baru = isset($data['rating']) ? (int)$data['rating'] : 0;
    if (empty($id_order) || $rating_baru == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap!']);
        exit;
    }

    $sql = "SELECT o.is_rated, p.id_users, p.rating, p.jml_penilai
            FROM orders o
            JOIN profile_pekerja p ON o.id_users_pekerja = p.id_users
            WHERE o.id_order = '$id_order'";

    $query = mysqli_query($conn, $sql);
    $pekerja = mysqli_fetch_assoc($query);

    if (!$pekerja) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        exit;
    }

    if ($pekerja['is_rated'] == 1) {
        echo json_encode(['status' => 'error', 'message' => 'Order ini sudah pernah dinilai!']);
        exit;
    }

    $id_pekerja = $pekerja['id_users'];
    $rating_sekarang = ($pekerja['rating'] == NULL) ? 5.0 : (float)$pekerja['rating'];
    $jml_penilai_asli = (int)$pekerja['jml_penilai'];

    $beban_n = 25;

    $total_orang_lama = $beban_n + $jml_penilai_asli;
    $rating_update = (($rating_sekarang * $total_orang_lama) + $rating_baru) / ($total_orang_lama + 1);

    $rating_final = number_format($rating_update, 1);
    $jml_penilai_baru = $jml_penilai_asli + 1;

    mysqli_begin_transaction($conn);
    try {

        $sqlUpdatePekerja = "UPDATE profile_pekerja
                         SET rating = ?, jml_penilai = ?
                         WHERE id_users = ?";
        $stmt1 = mysqli_prepare($conn, $sqlUpdatePekerja);

        mysqli_stmt_bind_param($stmt1, "dii", $rating_final, $jml_penilai_baru, $id_pekerja);
        mysqli_stmt_execute($stmt1);

        $sqlUpdateOrder = "UPDATE orders SET is_rated = 1, rating = ? WHERE id_order = ?";
        $stmt2 = mysqli_prepare($conn, $sqlUpdateOrder);

        mysqli_stmt_bind_param($stmt2, "ds", $rating_baru, $id_order);

        if (!mysqli_stmt_execute($stmt2)) {
            throw new Exception("Gagal update tabel orders");
        }

        mysqli_commit($conn);
        echo json_encode(['status' => 'success', 'message' => 'Rating berhasil dikirim!']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}