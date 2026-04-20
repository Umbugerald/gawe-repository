<?php
session_start();
header('Content-Type: application/json');
require '../../../../dbconnection/dbconnection.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sesi tidak valid!"]);
    exit;
}

$id_users = $_SESSION['user_id'];

$sql = "SELECT nama_lengkap, role FROM master_user WHERE id_users = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id_users);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    // 3. Jika data ditemukan, kirim balik ke JavaScript
    echo json_encode([
        "status" => "success",
        "data" => [
            "nama" => $row['nama_lengkap'],
            "role" => $row['role'],
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Data admin tidak ditemukan"]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>