<?php
session_set_cookie_params([
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

require '../dbconnection/dbconnection.php';

// Set header agar browser tahu ini adalah file JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT id_users, username, password, role FROM master_user WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id_users'];
        $_SESSION['role'] = $user['role'];

        // Tentukan path redirect
        $redirect = '';
        if ($user['role'] === 'pelanggan') {
            $redirect = "../../dashboard/user/home-user.php";
        } elseif ($user['role'] === 'pekerja') {

            $id_users_pekerja = $user['id_users'];

            // Query ambil status_kerja
            $stmt_status = mysqli_prepare($conn, "SELECT status_kerja FROM profile_pekerja WHERE id_users = ?");
            mysqli_stmt_bind_param($stmt_status, "s", $id_users_pekerja);
            mysqli_stmt_execute($stmt_status);
            $result_status = mysqli_stmt_get_result($stmt_status);
            $pekerja = mysqli_fetch_assoc($result_status);

            // LOGIKA PENYARINGAN STATUS
            if ($pekerja) {
                if ($pekerja['status_kerja'] === 'banned') {
                    $redirect = "../../dashboard/worker/banned-account.php";
                } elseif ($pekerja['status_kerja'] === 'process') {
                    $redirect = "../../dashboard/worker/waiting-confirmation.php";
                } else {
                    $redirect = "../../dashboard/worker/board-worker.php";
                }
            } else {
                // Default jika data profil tidak ditemukan
                $redirect = "../../dashboard/worker/board-worker.php";
            }

        } elseif ($user['role'] === 'admin' || $user['role'] === 'supervisor') {
            $redirect = "../../dashboard/admin/dashboard-admin.php";
        }

        // Kirim respon sukses
        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil! Mengalihkan...',
            'redirect' => $redirect
        ]);
        exit;
    }

    // Login Gagal
    echo json_encode([
        'success' => false,
        'message' => 'Username atau password salah!'
    ]);
    exit;

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid Request Method'
    ]);
    exit;
}