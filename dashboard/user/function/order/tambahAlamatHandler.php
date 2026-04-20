<?php
session_start();
require '../../../../dbconnection/dbconnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_users      = $_SESSION['user_id'] ?? '';
    $nama_alamat   = $_POST['nama_alamat'] ?? '';
    $provinsi      = $_POST['provinsi'] ?? '';
    $kota          = $_POST['kota'] ?? '';
    $kecamatan     = $_POST['kecamatan'] ?? '';
    $kelurahan     = $_POST['kelurahan'] ?? '';
    $detail_alamat = $_POST['detail_alamat'] ?? '';
    $latitude      = $_POST['latitude'] ?? '';
    $longitude     = $_POST['longitude'] ?? '';

    if (empty($id_users)) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login kembali.']);
        exit;
    }

    $prefix = "AL-" . date('ym');

    $queryCek = mysqli_query($conn, "SELECT id_alamat FROM master_alamat WHERE id_alamat LIKE '$prefix%' ORDER BY id_alamat DESC LIMIT 1");

    if (mysqli_num_rows($queryCek) > 0) {
        $row = mysqli_fetch_assoc($queryCek);

        $lastNumber = (int)substr($row['id_alamat'], -4);
        $newNumber = str_pad($lastNumber + 1, 4, "0", STR_PAD_LEFT);
    } else {

        $newNumber = "0001";
    }

    $newId = $prefix . $newNumber;

    if (empty($latitude) || empty($longitude)) {
        echo json_encode(['status' => 'error', 'message' => 'Titik koordinat belum dipilih di peta!']);
        exit;
    }

    $sql = "INSERT INTO master_alamat (id_alamat, id_users, nama_alamat, kecamatan, kelurahan, kota, provinsi, detail_alamat, longitude, latitude)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ssssssssdd",
        $newId,
        $id_users,
        $nama_alamat,
        $kecamatan,
        $kelurahan,
        $kota,
        $provinsi,
        $detail_alamat,
        $longitude,
        $latitude
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Alamat berhasil ditambahkan', 'id' => $newId]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>