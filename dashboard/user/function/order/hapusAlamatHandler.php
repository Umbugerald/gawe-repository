<?php
session_start();
require '../../../../dbconnection/dbconnection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login ulang.']);
    exit;
}

$id_user_login = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $id_alamat = trim($_GET['id']);

    // 1. CEK DULU: Apakah alamat ini memang ada di database?
    $check_sql = "SELECT id_users FROM master_alamat WHERE id_alamat = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "s", $id_alamat);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['status' => 'error', 'message' => "Data tidak ditemukan (ID: $id_alamat tidak ada di database)."]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);

    // 2. CEK KEPEMILIKAN: Apakah alamat ini milik user yang sedang login?
    if ($row['id_users'] !== $id_user_login) {
        echo json_encode([
            'status' => 'error',
            'message' => "Akses ditolak: Alamat ini milik user lain (ID User di DB: " . $row['id_users'] . ", ID Anda: $id_user_login)."
        ]);
        exit;
    }

    $sql = "UPDATE master_alamat SET is_deleted = 1 WHERE id_alamat = ? AND id_users = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $id_alamat, $id_user_login);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Alamat berhasil dihapus.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID Alamat tidak diberikan.']);
}

mysqli_close($conn);
