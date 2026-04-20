<?php
session_start();
require '../../../dbconnection/dbconnection.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_order = $_POST['id_order'] ?? '';
    $status_baru = $_POST['status'] ?? '';

    if (empty($id_order) || empty($status_baru)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Update status di tabel orders
    $sql = "UPDATE orders SET status = ? WHERE id_order = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $status_baru, $id_order);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Status berhasil diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update database']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
}
?>