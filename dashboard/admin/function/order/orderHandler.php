<?php
session_start();
header('Content-Type: application/json');
require '../../../../dbconnection/dbconnection.php'; // Pastikan path ini benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Cek apakah admin sudah login (Keamanan)
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Sesi tidak valid!"]);
        exit;
    }

    // 2. Ambil data dari POST
    $id_order = isset($_POST['id_order']) ? $_POST['id_order'] : '';
    $status   = isset($_POST['status']) ? $_POST['status'] : '';

    // 3. Validasi input
    if (empty($id_order) || empty($status)) {
        echo json_encode(["success" => false, "message" => "Data tidak lengkap!"]);
        exit;
    }

    $allowed_status = [
        'mencari',
        'diterima',
        'sampai',
        'dibatalkan_sistem',
        'proses',
        'selesai',
        'batal'
    ];

    if (!in_array($status, $allowed_status)) {
        echo json_encode(["success" => false, "message" => "Status tidak valid! Status dikirim: " . $status]);
        exit;
    }

    // ... sisa kode ...

    // 5. Eksekusi Update
    $query = "UPDATE orders SET status = ?, update_at = NOW() WHERE id_order = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $status, $id_order);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "success" => true,
                "message" => "Status order #$id_order berhasil diubah menjadi $status"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Gagal update database: " . mysqli_error($conn)
            ]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Gagal menyiapkan query."
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Metode request tidak diizinkan."]);
}
