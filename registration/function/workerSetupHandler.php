<?php
session_start();
header('Content-Type: application/json');
require '../../dbconnection/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "Sesi tidak valid!"]);
        exit;
    }

    $id_users = $_SESSION['user_id'];

    if (isset($_FILES['ktp_image']) && $_FILES['ktp_image']['error'] === UPLOAD_ERR_OK) {

        $max_size = 5 * 1024 * 1024;
        if ($_FILES['ktp_image']['size'] > $max_size) {
            echo json_encode(["status" => "error", "message" => "Ukuran file terlalu besar! Maksimal 2 MB."]);
            exit;
        }

        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($_FILES['ktp_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            echo json_encode(["status" => "error", "message" => "Format file tidak didukung! Gunakan JPG atau PNG."]);
            exit;
        }

        $file_tmp = $_FILES['ktp_image']['tmp_name'];
        $file_name = $_FILES['ktp_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $check_sql = "SELECT id_pekerja, rating, jml_penilai FROM profile_pekerja WHERE id_users = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $id_users);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $existing_worker = mysqli_fetch_assoc($result);

        if ($existing_worker) {
            $id_pekerja = $existing_worker['id_pekerja'];
        } else {
            $prefix = "PK-";
            $yymm   = date('ym');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $id_pekerja = $prefix . $yymm . $random;
        }

        $rating_baru = 5.0; // Nilai default pendaftaran
        $rating_sekarang = ($existing_worker && $existing_worker['rating'] != NULL) ? (float)$existing_worker['rating'] : 5.0;
        $jml_penilai_asli = ($existing_worker) ? (int)$existing_worker['jml_penilai'] : 0;

        $beban_n = 10;
        $total_orang_lama = $beban_n + $jml_penilai_asli;

        // Rumus rating update
        $rating_update = (($rating_sekarang * $total_orang_lama) + $rating_baru) / ($total_orang_lama + 1);
        $rating_final = number_format($rating_update, 1);
        $jml_penilai_baru = $jml_penilai_asli + 1;

        $new_file_name = "ktp_" . $id_pekerja . "_" . uniqid() . "." . $file_ext;
        $destination = '../../assets/img/uploads/identity/' . $new_file_name;

        if (move_uploaded_file($file_tmp, $destination)) {

            if ($existing_worker) {
                $sql = "UPDATE profile_pekerja SET foto_ktp = ?, status_kerja = 'process', rating = ?, jml_penilai = ? WHERE id_users = ?";
                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "sdis", $new_file_name, $rating_final, $jml_penilai_baru, $id_users);
            } else {
                $sql = "INSERT INTO profile_pekerja (id_pekerja, id_users, status_kerja, rating, foto_ktp, jml_penilai) VALUES (?, ?, 'process', ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "ssdsi", $id_pekerja, $id_users, $rating_final, $new_file_name, $jml_penilai_baru);
            }

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['ktp_selesai'] = true;
                echo json_encode([
                    "status" => "success",
                    "message" => "Upload berhasil!",
                    "redirect" => "upload-photo-profile.php"
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Gagal simpan DB: " . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal pindah file."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Harap pilih gambar KTP!"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request"]);
}
