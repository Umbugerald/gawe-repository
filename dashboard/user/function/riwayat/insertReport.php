<?php
session_start();
require '../../../../dbconnection/dbconnection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_order = $_POST['id_order'] ?? '';
    $detail = $_POST['detail_report'] ?? '';

    $prefix = "RP-" . date('ym');
    $randomNumber = str_pad(rand(0, 9999), 4, "0", STR_PAD_LEFT);
    $id_report = $prefix . $randomNumber;

    $foto_name = "";
    if (isset($_FILES['foto_report']) && $_FILES['foto_report']['error'] === 0) {
        $ext = pathinfo($_FILES['foto_report']['name'], PATHINFO_EXTENSION);
        $foto_name = "REP_" . time() . "_" . uniqid() . "." . $ext;
        $target = "../../../../assets/img/uploads/report/" . $foto_name;

        move_uploaded_file($_FILES['foto_report']['tmp_name'], $target);
    }

    $sqlSelect = "SELECT * FROM report WHERE id_order = '$id_order'";
    $query = mysqli_query($conn, $sqlSelect);
    if (mysqli_num_rows($query) > 0) {

    }

    $sql = "INSERT INTO report (id_report, id_order, detail_report, foto_report) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $id_report, $id_order, $detail, $foto_name);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil terkirim!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query']);
    }
}