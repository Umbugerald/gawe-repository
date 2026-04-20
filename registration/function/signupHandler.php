<?php
session_set_cookie_params([
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
header('Content-Type: application/json');
require '../../dbconnection/dbconnection.php'; // Pastikan path ini benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = htmlspecialchars(trim($_POST['nama_lengkap']));
    $username     = htmlspecialchars(trim($_POST['username']));
    $password     = trim($_POST['password']) ?? '';
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        echo json_encode([
            "status" => "error",
            "message" => "Password tidak sama!"
        ]);
        exit;
    }


    // PERBAIKAN: Gunakan json_encode agar format JSON tidak rusak di frontend
    if (strlen($password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password minimal 8 karakter!"]);
        exit;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode(["status" => "error", "message" => "Password harus ada huruf besar!"]);
        exit;
    }

    if (!preg_match('/[0-9]/', $password)) {
        echo json_encode(["status" => "error", "message" => "Password harus ada angka!"]);
        exit;
    }

    if (!preg_match('/[\W_]/', $password)) {
        echo json_encode(["status" => "error", "message" => "Password harus ada simbol!"]);
        exit;
    }

    $whatsapp = htmlspecialchars(trim($_POST['whatsapp']));

    // 1. Rapikan Format WhatsApp menjadi 628...
    if (strpos($whatsapp, '0') === 0) {
        $whatsapp = substr($whatsapp, 1); // Buang '0' di depan
    }
    $whatsapp_full = '62' . $whatsapp; // Gunakan 62 tanpa '+'

    // PERBAIKAN: Gabungkan cek username dan whatsapp dalam satu query agar lebih efisien
    $stmt_cek = mysqli_prepare($conn, "SELECT id_users FROM master_user WHERE username = ? OR no_wa = ?");
    mysqli_stmt_bind_param($stmt_cek, "ss", $username, $whatsapp_full);
    mysqli_stmt_execute($stmt_cek);
    mysqli_stmt_store_result($stmt_cek);

    if (mysqli_stmt_num_rows($stmt_cek) > 0) {
        echo json_encode(["status" => "error", "message" => "Username atau Nomor WhatsApp sudah terdaftar!"]);
        exit;
    }

    $prefix = "US-";
    $yymm   = date('ym'); // 'y' untuk tahun 2 digit (26), 'm' untuk bulan 2 digit (02)
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $id_user_baru = $prefix . $yymm . $random;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Masukkan $id_user_baru ke dalam perintah INSERT
    $stmt_insert = mysqli_prepare($conn, "INSERT INTO master_user (id_users, nama_lengkap, username, no_wa, password) VALUES (?, ?, ?, ?, ?)");

    // Bind param ditambah satu 's' di depan untuk ID
    mysqli_stmt_bind_param($stmt_insert, "sssss", $id_user_baru, $nama_lengkap, $username, $whatsapp_full, $hashed_password);

    if (mysqli_stmt_execute($stmt_insert)) {

        session_regenerate_id(true);
        $_SESSION['user_id'] = $id_user_baru;

        if (ob_get_length()) {
            ob_clean();
        }

        // 4. Arahkan ke halaman pemilihan role
        echo json_encode(["status" => "success", "redirect" => "select-role.php"]);
        exit;
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal mendaftar, coba lagi!"]);
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request"]);
    exit;
}
