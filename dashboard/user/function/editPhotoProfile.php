<?php
session_start();
require '../../../dbconnection/dbconnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_users = $_SESSION['user_id']; // Ambil ID user dari session

    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada file yang diunggah.']);
        exit;
    }

    $file = $_FILES['profile_photo'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    // 1. Validasi Ekstensi
    if (!in_array(strtolower($ext), $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'Format file harus JPG, PNG, atau WEBP.']);
        exit;
    }

    // 2. Validasi Ukuran (5MB sesuai permintaan Anda)
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Ukuran file maksimal 5MB.']);
        exit;
    }

    // 3. Proses Upload
    $nama_file_baru = "prof_edit_" . $id_users . "_" . time() . "." . $ext;
    $tujuan = "../../../assets/img/uploads/profile/" . $nama_file_baru; // Sesuaikan folder Anda

    if (move_uploaded_file($file['tmp_name'], $tujuan)) {

        // (Opsional) Hapus foto lama agar storage tidak penuh
        $sqlOld = "SELECT photo_profile FROM master_user WHERE id_users = '$id_users'";
        $resOld = mysqli_query($conn, $sqlOld);
        $dataOld = mysqli_fetch_assoc($resOld);
        if ($dataOld['photo_profile'] && $dataOld['photo_profile'] != 'default.png') {
            unlink("../../../assets/img/uploads/profile/" . $dataOld['photo_profile']);
        }

        // 4. Update Database
        $sql = "UPDATE master_user SET photo_profile = '$nama_file_baru' WHERE id_users = '$id_users'";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Foto profil berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate database.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file ke server.']);
    }
}
