<?php
session_start();
require '../../../../dbconnection/dbconnection.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$nama_lengkap = $_POST['nama_lengkap'] ?? '';
$username = $_POST['username'] ?? '';
$password_raw = $_POST['password'] ?? '';
$whatsapp = $_POST['no_wa'] ?? '';

// --- VALIDASI & FORMATING ---
if (empty($nama_lengkap) || empty($username) || empty($password_raw) || empty($whatsapp)) {
    sendResponse('error', 'Semua kolom wajib diisi.');
}

if (strpos($whatsapp, '0') === 0) {
    $whatsapp = substr($whatsapp, 1);
}
$whatsapp_full = '62' . $whatsapp;

$check_sql = "SELECT username, no_wa FROM master_user WHERE username = ? OR no_wa = ?";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("ss", $username, $whatsapp_full);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    $existing = $result->fetch_assoc();
    if ($existing['username'] === $username) {
        sendResponse('error', 'Username sudah digunakan.');
    } else {
        sendResponse('error', 'Nomor WhatsApp sudah terdaftar.');
    }
}
$stmt_check->close();

$password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
$id_user_baru = "US-" . date('ym') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

// --- FUNGSI RESPONSE JSON ---
function sendResponse($status, $message)
{
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// --- VALIDASI FOTO PROFILE ---
if (!isset($_FILES['foto_profile']) || $_FILES['foto_profile']['error'] === UPLOAD_ERR_NO_FILE) {
    sendResponse('error', 'Foto profil wajib diunggah.');
}

// Cek error upload lainnya
if ($_FILES['foto_profile']['error'] !== UPLOAD_ERR_OK) {
    sendResponse('error', 'Terjadi kesalahan saat mengunggah foto.');
}

$file_tmp = $_FILES['foto_profile']['tmp_name'];
$file_size = $_FILES['foto_profile']['size'];
$file_name = $_FILES['foto_profile']['name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// 1. Validasi Ukuran (Max 2MB = 2 * 1024 * 1024 bytes)
if ($file_size > 2 * 1024 * 1024) {
    sendResponse('error', 'Ukuran foto maksimal 2MB.');
}

// 2. Validasi Ekstensi
$allowed = ['jpg', 'jpeg', 'png'];
if (!in_array($file_ext, $allowed)) {
    sendResponse('error', 'Format foto harus JPG, JPEG, atau PNG.');
}

// 3. Simpan File
$foto_name = uniqid('adm_', true) . '.' . $file_ext;
$upload_path = '../../../../assets/img/uploads/profile/' . $foto_name;

if (!move_uploaded_file($file_tmp, $upload_path)) {
    sendResponse('error', 'Gagal memindahkan file foto.');
}

// ... setelah ini, baru jalankan $stmt->execute() ...

// --- LOGIKA INSERT ---
// (Pastikan semua echo "<script>..." diganti menjadi sendResponse('error', 'Pesan...'))

$query = "INSERT INTO master_user (id_users, username, nama_lengkap, password, no_wa, role, photo_profile) 
          VALUES (?, ?, ?, ?, ?, 'admin', ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssss", $id_user_baru, $username, $nama_lengkap, $password_hashed, $whatsapp_full, $foto_name);

if ($stmt->execute()) {
    sendResponse('success', 'Admin berhasil ditambahkan!');
} else {
    sendResponse('error', 'Gagal menyimpan ke database.');
}
