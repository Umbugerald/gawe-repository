<?php
require '../../../../dbconnection/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pekerja = $_POST['id_pekerja'];
    $status_baru = $_POST['status'];

    $status_map = [
        'aktif' => 'active',
        'proses' => 'process',
        'banned' => 'banned'
    ];

    $status_final = $status_map[$status_baru] ?? 'pending';

    $query = "UPDATE profile_pekerja SET status_kerja = ? WHERE id_pekerja = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $status_final, $id_pekerja);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}
?>