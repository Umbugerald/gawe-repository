<?php
ob_start();
session_set_cookie_params([
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
header('Content-Type: application/json');
require '../../dbconnection/dbconnection.php'; // Sesuaikan path jika perlu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['user_id'])) {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Sesi tidak valid. Silakan daftar/login ulang."]);
        exit;
    }

    $id_user = $_SESSION['user_id'];

    $sql_cek = mysqli_query($conn, "SELECT role FROM master_user WHERE id_users = '$id_user'");
    $data_user = mysqli_fetch_assoc($sql_cek);

    if (!empty($data_user['role']) && $data_user['role'] !== 'pending') {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Role kamu sudah terdaftar sebagai " . $data_user['role']]);
        exit;
    }

    $role_pilihan = $_POST['role'] ?? '';

    // 2. Validasi input role (mencegah user iseng mengirim role 'admin' lewat inspect element)
    if ($role_pilihan !== 'pekerja' && $role_pilihan !== 'pelanggan') {
        ob_clean();
        echo json_encode(["status" => "error", "message" => "Pilihan role tidak valid!"]);
        exit;
    }

    error_log("DEBUG: Mengupdate user ID: " . $id_user . " menjadi role: " . $role_pilihan);

    $stmt_update = mysqli_prepare($conn, "UPDATE master_user SET role = ? WHERE id_users = ?");
    mysqli_stmt_bind_param($stmt_update, "ss", $role_pilihan, $id_user);

    if (mysqli_stmt_execute($stmt_update)) {

        $_SESSION['role'] = $role_pilihan;

        $redirect_url = ($role_pilihan === 'pekerja') ? "worker-setup.php" : "upload-photo-profile.php";

        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode([
            "status" => "success",
            "redirect" => $redirect_url
        ]);
        exit;
    } else {
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan pilihan, coba lagi!"]);
        exit;
    }
} else {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Invalid Request"]);
    exit;
}
