<?php
session_start();
header('Content-Type: application/json');
require '../../dbconnection/dbconnection.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "Sesi tidak valid!"]);
        exit;
    }

    $id_users = $_SESSION['user_id'];
    $role = $_SESSION['role'] ?? 'pelanggan';

    // 1. VALIDASI FILE - Perbaikan Logika
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "error", "message" => "Harap pilih foto profil!"]);
        exit;
    }

    $max_size = 2 * 1024 * 1024; // 2 MB
    if ($_FILES['profile_photo']['size'] > $max_size) {
        echo json_encode(["status" => "error", "message" => "Ukuran file terlalu besar! Maksimal 2 MB."]);
        exit;
    }

    $file_tmp = $_FILES['profile_photo']['tmp_name'];
    $file_name = $_FILES['profile_photo']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png'];

    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(["status" => "error", "message" => "Format harus JPG/PNG!"]);
        exit;
    }

    // 2. GENERATE ID
    $tahun = date('y');
    $bulan = date('m');
    $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $id_role = ($role === 'pekerja') ? "PK-$tahun$bulan$random" : "PL-$tahun$bulan$random";

    // 3. UPLOAD FILE
    $upload_dir = '../../assets/img/uploads/profile/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $new_file_name = "prof_" . $id_users . "_" . time() . "." . $file_ext;
    if (!move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan file ke server"]);
        exit;
    }

    // 4. TRANSACTION
    mysqli_begin_transaction($conn);
    try {
        // Cek apakah data profile sudah ada (agar tidak error duplicate entry)
        $table = ($role === 'pekerja') ? 'profile_pekerja' : 'profile_pelanggan';
        $id_col = ($role === 'pekerja') ? 'id_pekerja' : 'id_pelanggan';

        $cek = mysqli_prepare($conn, "SELECT $id_col FROM $table WHERE id_users = ?");
        mysqli_stmt_bind_param($cek, "s", $id_users);
        mysqli_stmt_execute($cek);
        $res = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($res) == 0) {
            $sql = "INSERT INTO $table ($id_col, id_users) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $id_role, $id_users);
            mysqli_stmt_execute($stmt);
        }

        // UPDATE FOTO DI MASTER_USER
        $sql_update = "UPDATE master_user SET photo_profile = ? WHERE id_users = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ss", $new_file_name, $id_users);
        mysqli_stmt_execute($stmt_update);

        mysqli_commit($conn);

        echo json_encode([
            "status" => "success",
            "redirect" => ($role === 'pekerja') ? "../../dashboard/worker/waiting-confirmation.php" : "../../dashboard/user/home-user.php"
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => "Terjadi kesalahan: " . $e->getMessage()]);
    }
}
