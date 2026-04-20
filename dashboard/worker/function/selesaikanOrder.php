<?php
header('Content-Type: application/json');
require '../../../dbconnection/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_order = $_POST['id_order'] ?? '';

    // 1. Validasi Input
    if (empty($id_order) || !isset($_FILES['bukti_penyelesaian'])) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        exit;
    }

    $file = $_FILES['bukti_penyelesaian'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileError = $file['error'];

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Ukuran file maksimal 2 MB.']);
        exit;
    }

    $targetDir = "../../../assets/img/uploads/bukti_transaksi/";

    // Buat folder jika belum ada
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // 3. Filter Ekstensi File
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            // Generate nama file unik: IDORDER_TIMESTAMP.jpg
            $newFileName = $id_order . "_" . time() . "." . $fileExt;
            $fileDestination = $targetDir . $newFileName;

            // 4. Pindahkan File ke Server
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                try {
                    $query = "UPDATE orders SET status = 'selesai', bukti_foto = ?, selesai_at = IF(? = 'selesai', NOW(), selesai_at) WHERE id_order = ?";

                    $stmt = $conn->prepare($query);

                    // Definisikan variabel sebelum bind_param
                    $status_trigger = 'selesai';

                    // Pastikan urutan bind sesuai: bukti_foto (s), status_trigger (s), id_order (s)
                    $stmt->bind_param("sss", $newFileName, $status_trigger, $id_order);

                    // Eksekusi sekali saja
                    if ($stmt->execute()) {
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Order berhasil diselesaikan!',
                            'filename' => $newFileName
                        ]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal update database.']);
                    }
                } catch (Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                }

                // ... (sisa kode)
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload file ke folder server.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Terjadi error pada file foto.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Format file harus JPG, JPEG, atau PNG.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode akses dilarang.']);
}
