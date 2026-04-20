<?php
session_start();
require '../../dbconnection/dbconnection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SESSION['role'] === 'pekerja') {
    $id_user = $_SESSION['user_id'];
    $query_cek = mysqli_query($conn, "SELECT status_kerja FROM profile_pekerja WHERE id_users = '$id_user'");
    $data_cek = mysqli_fetch_assoc($query_cek);

    // Jika data ditemukan, lakukan penyaringan
    if ($data_cek) {
        if ($data_cek['status_kerja'] === 'banned') {
            // Tendang kembali ke halaman banned
            header("Location: banned-account.php");
            exit;
        } elseif ($data_cek['status_kerja'] === 'process') {
            // Tendang kembali ke halaman proses
            header("Location: waiting-confirmation.php");
            exit;
        }
        // Jika statusnya 'active', biarkan script lanjut mengeksekusi halaman dashboard di bawah
    }
}

$id_users = $_SESSION['user_id'];

// Query dengan JOIN untuk mengambil data user dan rating pekerja
$sql = "SELECT 
            m.nama_lengkap, 
            m.role, 
            m.photo_profile, 
            p.rating 
        FROM master_user m
        LEFT JOIN profile_pekerja p ON m.id_users = p.id_users
        WHERE m.id_users = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id_users);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Default value jika data kosong
$adminName = !empty($admin['nama_lengkap']) ? $admin['nama_lengkap'] : 'User Baru';
$adminRole = !empty($admin['role']) ? ucfirst($admin['role']) : 'Role';
$rating = isset($admin['rating']) ? number_format($admin['rating'], 1) : '0.0';

// Logika Foto Profil
if (!empty($admin['photo_profile'])) {
    $photoUrl = '../../assets/img/uploads/profile/' . $admin['photo_profile'];
} else {
    $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($adminName) . '&background=random';
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pekerja - Open Board</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/components/sc1-alert.css">
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <script src="../../assets/components/sc1-alert.js"></script>
    <style>
        /* --- RESET & BASIC SETUP --- */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        /* --- HEADER PROFILE (Mobile Default) --- */
        .header-profile {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Gradient dan padding bawah yang tebal untuk memberi ruang efek lengkung */
            background: linear-gradient(135deg, #1c19b0, #abb5e9, #dceaf6, #2584c9);
            padding: 2rem 1.5rem 3.5rem 1.5rem;
            color: white;
        }

        .profile-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            border-radius: 50px;
        }

        .profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
            background-color: #ddd;
        }

        .profile-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            padding-right: 20px;
        }

        .star-icon {
            color: #facc15;
            /* kuning bintang */
            margin: 0 3px;
            font-size: 0.9em;
        }

        .logo-circle {
            width: 53.2px;
            height: 53.2px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
            background-color: #ddd;
        }

        .logo-circle img {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* --- MAIN CONTENT AREA (Mobile Default) --- */
        .main-content {
            background: #f4f6f9;
            border-radius: 20px 20px 0 0;
            margin-top: -25px;
            /* Efek menimpa header agar melengkung seperti di HP */
            position: relative;
            /* Penting agar berada di atas header */
            padding: 1.5rem;
            min-height: 70vh;
        }

        /* --- WILAYAH KERJA SECTION --- */
        .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            color: #333;
        }

        .custom-select-wrapper {
            position: relative;
            margin-bottom: 0.8rem;
        }

        .custom-select-wrapper i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none;
            font-size: 0.9rem;
        }

        .custom-select {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 20px;
            border: none;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            appearance: none;
            color: #555;
            font-size: 0.85rem;
            font-family: inherit;
            cursor: pointer;
            transition: 0.3s;
            box-sizing: border-box;
        }

        .custom-select:disabled {
            background: #eee;
            cursor: not-allowed;
            color: #aaa;
        }

        .custom-select:focus {
            outline: 1px solid #4a67ff;
        }

        /* --- TABS --- */
        .tabs-container {
            display: flex;
            background: white;
            padding: 0.3rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            margin: 1.5rem 0;
            gap: 0.3rem;
        }

        .tab-btn {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.7rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #a3a3a3;
            cursor: pointer;
            transition: 0.3s;
        }

        .tab-btn.active {
            background: #e2e8f0;
            color: #2344ff;
        }

        /* --- ORDER CARD --- */
        #order-container {
            display: none;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .order-header {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .order-img {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }

        .order-info {
            flex: 1;
        }

        .status-text {
            color: #00b894;
            font-size: 0.75rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.3rem;
        }

        .order-title {
            font-size: 0.9rem;
            font-weight: 700;
            margin: 0 0 0.3rem 0;
            color: #222;
        }

        .order-desc {
            font-size: 0.75rem;
            color: #777;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .order-address {
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .order-address h5 {
            font-size: 0.8rem;
            font-weight: 700;
            margin: 0 0 0.3rem 0;
            color: #222;
        }

        .order-address p {
            font-size: 0.75rem;
            color: #666;
            margin: 0;
            line-height: 1.4;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 1px dashed #eee;
        }

        .order-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: #222;
            margin: 0;
        }


        button.btn-register {
            width: 100%;
            padding: 13px 12px;
            /* lebih kecil */
            margin-top: 10px;
            background: #2b5cff;
            color: white;
            border: none;
            border-radius: 20px;
            /* lebih ramping */
            font-size: 14px;
            /* lebih kecil */
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(59, 104, 255, 0.25);
        }

        button.btn-register:hover {
            background: #1a4ce6;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(43, 92, 255, 0.4);
        }

        button.btn-register:active {
            transform: translateY(0);
        }

        .btn-terima {
            background: #2b5cff;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-terima:hover {
            background: #2a2599;
        }

        .empty-state {
            text-align: center;
            color: #aaa;
            font-size: 0.85rem;
            margin-top: 2rem;
            display: block;
        }

        /* --- TAMBAHAN UNTUK TAB DITERIMA & MODAL --- */
        .phone-text {
            font-size: 0.8rem;
            color: #4a67ff;
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .order-footer-diterima {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed #eee;
        }

        /* Tombol Dinamis */
        .btn-action {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.3s;
            color: white;
        }

        .btn-sampai {
            background: #2b5cff;
        }

        /* Biru */
        .btn-sampai:hover {
            background: #2563eb;
        }

        .btn-mulai {
            background: #ffa32b;
        }

        /* Kuning/Orange */
        .btn-mulai:hover {
            background: #d97706;
        }

        .btn-selesaikan {
            background: #23d440;
        }

        /* Hijau */
        .btn-selesaikan:hover {
            background: #059669;
        }

        /* Upload Box (Muncul di Tahap 3) */
        .upload-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed #eee;
            display: none;
            /* Disembunyikan secara default */
        }

        .upload-section h5 {
            font-size: 0.8rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            color: #222;
        }

        .upload-box {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            color: #888;
            cursor: pointer;
            background: #fafafa;
            transition: 0.3s;
            margin-bottom: 1rem;
        }

        .upload-box:hover {
            border-color: #2b5cff;
            color: #2b5cff;
            background: #f0f4ff;
        }

        .upload-box i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #ccc;
        }

        .upload-box:hover i {
            color: #2b5cff;
        }

        .upload-box p {
            font-size: 0.85rem;
            margin: 0;
            font-weight: 600;
        }

        /* Modal Konfirmasi */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            /* Sembunyikan default */
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(2px);
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 320px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-icon {
            margin-bottom: 1rem;
        }

        .modal-icon img {
            width: 80px;
            height: auto;
        }

        /* Jika pakai gambar ilustrasi */
        .modal-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: #333;
        }

        .modal-content p {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.9rem;
        }

        .btn-batal {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-batal:hover {
            background: #e2e8f0;
        }

        .btn-selesai-modal {
            background: #2b5cff;
            color: white;
        }

        .btn-selesai-modal:hover {
            background: #1a4ce6;
        }

        /* container harga + tombol */
        .order-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-top: 10px;
            box-sizing: border-box;
        }

        /* harga */
        .order-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: #222;
            margin: 0;
            flex-shrink: 0
        }

        /* wrapper tombol */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        /* base button */
        .btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border: none;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            width: 100%;
        }

        /* tombol hubungi */
        .btn.hubungi {
            background: linear-gradient(135deg, #0097b2, #7ed957);
            color: white;
        }

        .btn.hubungi:hover {
            background: linear-gradient(135deg, #007f96, #5ea341);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(134, 134, 134, 0.4);
        }

        /* tombol gmaps */
        .btn.gmaps {
            font-weight: 200;
            background: #ffffff;
            color: #333;
        }

        .btn.gmaps:hover {
            background: #f1f1f1;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(134, 134, 134, 0.4);
        }

        /* ================= MODAL PROFILE ================= */
        .modal-overlay {
            position: fixed;
            /* Fixed agar selalu di layar */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(235, 235, 235, 0.4);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-card {
            width: 85%;
            max-width: 350px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(30px);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-card {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            flex: 1;
            font-size: 16px;
            font-weight: 700;
            margin-right: 25px;
            padding-left: 25px;
        }

        .modal-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .modal-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .modal-role {
            font-size: 14px;
            color: #0056b3;
            font-weight: 600;
            font-style: italic;
            margin-bottom: 25px;
        }

        .logout-icon {
            width: 35px;
            height: 35px;
            stroke: #515154;
            fill: none;
            stroke-width: 1.5;
            cursor: pointer;
            transition: stroke 0.2s;
        }

        .logout-icon:hover {
            stroke: #ff3b30;
        }

        .btn-hubungi {
            display: inline-flex;
            align-items: flex;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            text-decoration: none;
        }

        .btn-hubungi svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* ========================================================
           RESPONSIVE PC / LAPTOP LAYOUT
           Berlaku jika lebar layar lebih dari 768px
        ======================================================== */
        @media (min-width: 768px) {

            /* Header menjadi Navbar simpel di atas */
            .header-profile {
                padding: 1rem 4rem;
                background: linear-gradient(135deg, #1c19b0, #abb5e9, #dceaf6, #2584c9);
                height: 225px;
                z-index: 1;
                position: relative;
            }

            /* Main Content dibelah menjadi Grid (Kiri dan Kanan) */
            .main-content {
                margin-top: -150px;
                /* Tarik ke atas agar menimpa background biru header */
                position: relative;
                /* Wajib ada agar z-index / tumpangan berfungsi */
                z-index: 1000;
                /* Memastikan konten berada di depan header */
                border-radius: 20px 20px 0 0;
                /* Lengkungan kiri & kanan atas */
                padding: 2.5rem 4rem;
                /* Batas lebar agar rapi di layar super lebar */
                display: grid;
                grid-template-columns: 320px 1fr;
                /* Kiri 320px, kanan sisanya */
                gap: 2rem;
                align-items: start;
            }

            .tabs-container {
                margin: 0;
                margin-bottom: 1.5rem;
            }

            .profile-left {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-bottom: 150px;
            }

            .logo-circle {
                margin-bottom: 150px;
            }

            /* Panel Sidebar khusus di PC dibuat punya background putih agar menonjol */
            .sidebar-section {
                background: white;
                padding: 1.5rem;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
                position: sticky;
                /* Membuat sidebar tetap di tempat saat di-scroll ke bawah */
                top: 2rem;
            }

            /* Order tersusun menyamping (Grid) di PC */
            .order-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 1.5rem;
            }

            .empty-state {
                margin-top: 5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="header-profile">
        <div class="profile-left">
            <img src="<?= $photoUrl ?>" alt="Profile" class="profile-img" id="profileBtn">
            <h2 class="profile-name">
                <span id="user-name"><?= $adminName; ?></span>
                <i class="fa-solid fa-star star-icon"></i>
                <span id="user-rating"><?= $rating; ?></span>
            </h2>
        </div>
        <div class="logo-circle">
            <img src="/assets/img/logo/gawelogo.png" alt="Logo"
                onerror="this.src='https://ui-avatars.com/api/?name=GW&background=3b82f6&color=fff&rounded=true'">
        </div>
    </div>

    <div class="main-content">

        <div class="sidebar-section">
            <h3 class="section-title">Wilayah Kerja</h3>

            <div class="custom-select-wrapper">
                <select id="provinsi" class="custom-select">
                    <option value="" disabled selected>Provinsi</option>
                </select>
                <i class="fa-solid fa-chevron-down"></i>
            </div>

            <div class="custom-select-wrapper">
                <select id="kota" class="custom-select" disabled>
                    <option value="" disabled selected>Kota/Kabupaten</option>
                </select>
                <i class="fa-solid fa-chevron-down"></i>
            </div>

            <div class="custom-select-wrapper">
                <select id="kecamatan" class="custom-select" disabled>
                    <option value="" disabled selected>Kecamatan</option>
                </select>
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div>
                <button type="submit" class="btn-register" id="regBtn">Cari Pekerjaan</button>
            </div>

        </div>

        <div class="order-section">
            <div class="tabs-container">
                <button class="tab-btn active">Order</button>
                <button class="tab-btn">Diterima</button>
            </div>
            <div id="empty-message" class="empty-state">
                Silahkan pilih wilayah kerja untuk melihat orderan tersedia.
            </div>

            <div id="order-container">
                <div class="order-grid" style="display: none;">

                </div>
            </div>

            <div id="diterima-container" style="display: none;">
                <div class="order-grid">

                </div>
            </div>

            <div class="modal-overlay" id="finish-modal">
                <div class="modal-content">
                    <div class="modal-icon">
                        <i class="fa-solid fa-circle-check" style="font-size: 4rem; color: #10b981;"></i>
                    </div>
                    <h3>Pekerjaan Selesai?</h3>
                    <p>Pastikan Anda telah menerima uang dari pelanggan menyelesaikan pekerjaan dengan baik, mengunggah
                        foto bukti, serta menerima uang dari pelanggan.</p>
                    <div class="modal-buttons">
                        <button class="btn-batal" id="btn-batal">Batal</button>
                        <button class="btn-selesai-modal" id="btn-konfirmasi-selesai">Selesai</button>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div class="modal-overlay" id="profileModal">
        <div class="modal-card">
            <div class="modal-header">
                <div id="closeModalBtn"></div>
                <div class="modal-title">Profile</div>
            </div>

            <img id="adminAvatar" src="<?= $photoUrl ?>" alt="Loading..." class="modal-avatar">
            <div class="modal-name" id="adminName"></div>
            <div class="modal-role" id="adminRole"></div>

            <svg class="logout-icon" viewBox="0 0 24 24" onclick="window.location.href='function/logoutWorker.php'">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const orderContainer = document.getElementById('order-container');
            const diterimaContainer = document.getElementById('diterima-container');

            // 1. LOGIKA TAB (Trigger)
            tabBtns.forEach((btn, index) => {
                btn.addEventListener('click', () => {
                    // Reset semua tombol jadi tidak active
                    tabBtns.forEach(b => b.classList.remove('active'));
                    // Jadikan tombol yang diklik active
                    btn.classList.add('active');

                    // Logika pergantian tampilan
                    if (index === 0) {
                        // JIKA TAB "ORDER" DIKLIK
                        if (orderContainer) orderContainer.style.display = 'block';
                        if (diterimaContainer) diterimaContainer.style.display = 'none';

                    } else if (index === 1) {
                        // JIKA TAB "DITERIMA" DIKLIK
                        if (orderContainer) orderContainer.style.display = 'none';
                        if (diterimaContainer) diterimaContainer.style.display = 'block';

                        // MEMANGGIL FUNGSI SAAT TAB DIKLIK
                        loadOrderDiterima();
                    }
                });
            });

            // 2. FETCH DATA ADMIN (Profil atas)
            try {
                const response = await fetch('function/fetchDataHandler.php');
                const result = await response.json();
                if (result.status === 'success') {
                    const data = result.data;
                    document.getElementById('adminName').textContent = data.nama;
                    document.getElementById('adminRole').textContent = data.role.charAt(0).toUpperCase() + data.role.slice(1);
                }
            } catch (error) {
                console.error("terjadi kesalahan", error);
            }

            // 3. (Opsional) Langsung load jika tab diterima sedang aktif saat refresh halaman
            if (tabBtns.length > 1 && tabBtns[1].classList.contains('active')) {
                loadOrderDiterima();
            }

            await cekStatusPekerja();
        });

        async function cekStatusPekerja() {
            try {
                const response = await fetch('function/getAcceptedOrder.php');
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    pekerjaSedangSibuk = true;
                    // Blokir fitur cari order
                    blokirFiturCariOrder(true);
                } else {
                    pekerjaSedangSibuk = false;
                    blokirFiturCariOrder(false);
                }
            } catch (error) {
                console.error("Gagal cek status:", error);
            }
        }

        function blokirFiturCariOrder(isBlocked) {
            const sidebar = document.querySelector('.sidebar-section');
            if (!sidebar) return;

            if (isBlocked) {
                sidebar.style.pointerEvents = "none";
                sidebar.style.opacity = "0.5";
                // Tambahkan pesan informasi
                if (!document.getElementById('msg-sibuk')) {
                    sidebar.insertAdjacentHTML('beforeend', '<p id="msg-sibuk" style="color:red; font-size:12px;">Selesaikan pesanan Anda untuk mencari order baru.</p>');
                }
            } else {
                sidebar.style.pointerEvents = "auto";
                sidebar.style.opacity = "1";
                const msg = document.getElementById('msg-sibuk');
                if (msg) msg.remove();
            }
        }

        async function loadOrderDiterima() {
            try {
                const response = await fetch('function/getAcceptedOrder.php');
                const result = await response.json();

                const diterimaGrid = document.querySelector('#diterima-container .order-grid');
                diterimaGrid.innerHTML = ''; // Kosongkan kontainer

                if (result.status === 'success') {
                    const order = result.data;

                    // Format Foto 
                    const fotoSrc = order.foto_keluhan ?
                        `../../assets/img/uploads/keluhan/${order.foto_keluhan}` :
                        `https://via.placeholder.com/70x70.png?text=No+Image`;

                    // Format Nomor WA 
                    let noWa = order.no_wa || '';
                    if (noWa.startsWith('0')) {
                        noWa = '62' + noWa.substring(1);
                    }

                    // --- LOGIKA BARU: TEMPLATE PESAN WA ---
                    // 1. Ambil Nama Pekerja dari elemen profil di atas layar
                    const namaPekerjaElement = document.getElementById('user-name');
                    const namaPekerja = namaPekerjaElement ? namaPekerjaElement.textContent.trim() : 'Pekerja Gawe';

                    // 2. Susun teks pesannya sesuai request Abang
                    const pesanWa = `Halo kak\nSaya ${namaPekerja}, pekerja dari Gawe yang akan menangani pesanan ini.\nKonfirmasi ya, apakah ini dengan ${order.nama_pelanggan} dan keluhannya ${order.keluhan}?\nSaya sedang menuju lokasi.`;

                    // 3. Gabungkan nomor WA dengan teks yang sudah di-encode (agar enter & spasi terbaca oleh WA)
                    const waLink = `https://wa.me/${noWa}?text=${encodeURIComponent(pesanWa)}`;
                    // --------------------------------------

                    // URL Gmaps 
                    const alamatLengkap = `${order.detail_alamat}, ${order.kecamatan}, ${order.kota}, ${order.provinsi}`;
                    const lat = order.latitude || '';
                    const lng = order.longitude || '';

                    let gmapsUrl = '';

                    if (lat && lng) {
                        gmapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
                    } else {
                        // Format URL Directions Google Maps yang BENAR (Pakai Teks Alamat)
                        gmapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(alamatLengkap)}`;
                    }

                    // ==================================================
                    // LOGIKA BARU: TENTUKAN TAMPILAN BERDASARKAN STATUS
                    // ==================================================
                    let btnText = 'Sampai di Tujuan';
                    let btnClass = 'btn-sampai';
                    let uploadDisplay = 'none';
                    let currentState = 1; // Default state 1

                    // Cek status dari database (sesuaikan dengan isi databasemu)
                    if (order.status === 'sampai') {
                        btnText = 'Mulai Kerjakan';
                        btnClass = 'btn-mulai';
                        currentState = 2;
                    } else if (order.status === 'proses') { // <--- TAMBAHKAN INI
                        btnText = 'Selesaikan';
                        btnClass = 'btn-selesaikan';
                        uploadDisplay = 'block'; // Tampilkan kotak upload
                        currentState = 3;
                    }

                    // Render Card

                    // Render Card
                    const cardHTML = `
            <div class="order-card accepted-card" data-id="${order.id_order}">
                <div class="order-header">
                    <img src="${fotoSrc}" alt="Keluhan" class="order-img">
                    <div class="order-info">
                        <span class="status-text">Pelanggan : ${order.nama_pelanggan}</span>
                        <h4 class="order-title">${order.keluhan}</h4>
                        <p class="order-desc">${order.detail_keluhan}</p>
                    </div>
                </div>

                <div class="order-address">
                    <h5>Detail Alamat :</h5>
                    <p>${alamatLengkap}</p>
                </div>

                <div class="order-action">
                    <h3 class="order-price">Rp. ${Number(order.harga).toLocaleString('id-ID')}</h3>
                    <div class="action-buttons">
                        <a href="${waLink}" target="_blank" class="btn hubungi" style="text-decoration: none;">
                            <i class="fa-brands fa-whatsapp"></i> Hubungi
                        </a>
                        <a href="${gmapsUrl}" target="_blank" class="btn gmaps" style="text-decoration: none;">
                            <img src="../../assets/img/logo/Untitled design (1).png" style="width: 16px; height: 16px;">
                            Gmaps
                        </a>
                    </div>
                </div>

               <div class="upload-section" id="upload-section-${order.id_order}" style="display: ${uploadDisplay};">
                    <h5>Bukti Penyelesaian :</h5>
                    <div class="upload-box" id="upload-box-trigger-${order.id_order}" style="cursor: pointer; position: relative;">
                        <div id="upload-default-content-${order.id_order}">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Tap untuk upload foto</p>
                        </div>
                        
                        <img id="upload-preview-${order.id_order}" src="" alt="Preview Bukti" style="display: none; max-width: 100%; border-radius: 8px; margin-top: 10px;">
                    </div>
                    
                    <input type="file" id="file-bukti-${order.id_order}" accept="image/*" capture="environtment" style="display: none;">
                </div>

                <div class="order-footer-diterima">
                    <button class="btn-action ${btnClass}" id="dynamic-btn-${order.id_order}">Sampai di Tujuan</button>
                </div>
            </div>
            `;

                    diterimaGrid.insertAdjacentHTML('beforeend', cardHTML);
                    initDynamicButton(order.id_order, currentState);

                } else {
                    diterimaGrid.innerHTML = '<p style="text-align:center; padding: 20px; grid-column: 1 / -1; text-align:center; opacity:50%;">Kamu belum menerima pekerjaan apa pun.</p>';
                }
            } catch (error) {
                console.error("Error fetching accepted order:", error);
            }
        }

        async function terimaOrder(id_order) {
            if (!await SC1Alert.confirm('Yakin ingin menerima orderan ini?')) return;

            const formData = new FormData();
            formData.append('id_order', id_order);

            try {
                const response = await fetch('function/acceptOrder.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    await SC1Alert.show(result.message, 'success');

                    // GANTI reload() menjadi ini:
                    // Kita arahkan ke halaman yang sama tapi ditambahin ?tab=diterima
                    window.location.href = 'board-worker.php?tab=diterima';

                } else {
                    await SC1Alert.show(result.message, 'error');
                }
            } catch (error) {
                console.error("Error accepting order:", error);
                await SC1Alert.show('Terjadi kesalahan pada sistem.', 'error');
            }

            if (result.status === 'success') {
                await SC1Alert.show(result.message, 'success');

                pekerjaSedangSibuk = true;
                blokirFiturCariOrder(true); // Panggil fungsi blokir di sini

                // Pindah tab
                document.querySelectorAll('.tab-btn')[1].click();
            }
        }

        function initDynamicButton(id_order, initialState) {
            const dynamicBtn = document.getElementById(`dynamic-btn-${id_order}`);
            const uploadSection = document.getElementById(`upload-section-${id_order}`);

            // Variabel untuk Upload Box yang unik per card
            const uploadBoxTrigger = document.getElementById(`upload-box-trigger-${id_order}`);
            const fileBukti = document.getElementById(`file-bukti-${id_order}`);
            const uploadPreview = document.getElementById(`upload-preview-${id_order}`);
            const uploadDefaultContent = document.getElementById(`upload-default-content-${id_order}`);

            // Modal dan Tombol Batal/Konfirmasi biasanya bersifat global (ada di luar card), 
            // jadi ID-nya biarkan saja seperti semula
            const finishModal = document.getElementById('finish-modal');
            const btnBatal = document.getElementById('btn-batal');
            const btnKonfirmasi = document.getElementById('btn-konfirmasi-selesai');

            // ==========================================
            // 1. EVENT UNTUK KOTAK UPLOAD FOTO
            // ==========================================
            if (uploadBoxTrigger && fileBukti) {
                uploadBoxTrigger.addEventListener('click', () => {
                    fileBukti.click(); // Sekarang pasti memicu input yang benar!
                });

                fileBukti.addEventListener('change', function () {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            uploadDefaultContent.style.display = 'none';
                            uploadPreview.src = e.target.result;
                            uploadPreview.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (!dynamicBtn) return;

            let orderState = initialState || 1;

            function updateButtonUI(state) {
                if (state === 1) {
                    dynamicBtn.textContent = 'Sampai di Tujuan';
                    dynamicBtn.className = 'btn-action btn-sampai';
                } else if (state === 2) {
                    dynamicBtn.textContent = 'Mulai Kerjakan';
                    dynamicBtn.className = 'btn-action btn-mulai';
                } else if (state === 3) {
                    dynamicBtn.textContent = 'Selesaikan';
                    dynamicBtn.className = 'btn-action btn-selesaikan';
                    if (uploadSection) uploadSection.style.display = 'block';
                }
            }
            updateButtonUI(orderState);
            dynamicBtn.addEventListener('click', async () => {

                if (orderState === 1) {
                    dynamicBtn.textContent = 'Memproses...';
                    try {
                        let formData = new FormData();
                        formData.append('id_order', id_order);
                        formData.append('status', 'sampai');

                        let response = await fetch('function/updateStatusOrder.php', {
                            method: 'POST',
                            body: formData
                        });
                        let result = await response.json();

                        if (result.status === 'success') {
                            dynamicBtn.textContent = 'Mulai Kerjakan';
                            dynamicBtn.className = 'btn-action btn-mulai';
                            orderState = 2;
                        } else {
                            await SC1Alert.show('Gagal: ' + result.message, 'error');
                            dynamicBtn.textContent = 'Sampai di Tujuan';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        dynamicBtn.textContent = 'Sampai di Tujuan';
                    }

                } else if (orderState === 2) {
                    dynamicBtn.textContent = 'Memproses...';
                    try {
                        let formData = new FormData();
                        formData.append('id_order', id_order);
                        formData.append('status', 'proses');

                        let response = await fetch('function/updateStatusOrder.php', {
                            method: 'POST',
                            body: formData
                        });
                        let result = await response.json();

                        if (result.status === 'success') {
                            dynamicBtn.textContent = 'Selesaikan';
                            dynamicBtn.className = 'btn-action btn-selesaikan';
                            if (uploadSection) uploadSection.style.display = 'block';
                            orderState = 3;
                        } else {
                            await SC1Alert.show('Gagal: ' + result.message, 'error');
                            dynamicBtn.textContent = 'Mulai Kerjakan';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        dynamicBtn.textContent = 'Mulai Kerjakan';
                    }

                } else if (orderState === 3) {
                    // 1. Cek apakah pekerja sudah upload foto bukti
                    const fileInput = document.getElementById(`file-bukti-${id_order}`);

                    if (!fileInput.files || fileInput.files.length === 0) {
                        await SC1Alert.show('Eits, tunggu dulu! Upload foto bukti penyelesaiannya dulu ya.', 'warning');
                        return;
                    }

                    // --- TAMBAHAN VALIDASI ---
                    const file = fileInput.files[0];
                    const maxSize = 5 * 1024 * 1024; // 2 MB

                    // Cek Ukuran
                    if (file.size > maxSize) {
                        await SC1Alert.show('Ukuran foto terlalu besar! Maksimal 2 MB.', 'error');
                        return;
                    }

                    // Cek Tipe File (MIME type image/...)
                    if (!file.type.startsWith('image/')) {
                        await SC1Alert.show('Format file tidak didukung! Harap upload foto (JPG/PNG).', 'error');
                        fileInput.value = ''; // Reset input agar user bisa pilih ulang
                        return;
                    }
                    // -------------------------

                    // 2. Siapkan data untuk dikirim
                    const formData = new FormData();
                    formData.append('id_order', id_order);
                    formData.append('bukti_penyelesaian', file);

                    // 3. Kirim ke PHP
                    try {
                        dynamicBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
                        dynamicBtn.disabled = true;

                        const response = await fetch('function/selesaikanOrder.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            await SC1Alert.show('Kerja bagus! Pesanan berhasil diselesaikan.', 'success');

                            pekerjaSedangSibuk = false;
                            blokirFiturCariOrder(false);

                            document.querySelectorAll('.tab-btn')[0].click();
                            loadOrderDiterima();
                        } else {
                            // Jika ada pesan error dari server (misal gagal simpan DB)
                            await SC1Alert.show(result.message, 'error');
                            dynamicBtn.innerHTML = 'Selesaikan';
                            dynamicBtn.disabled = false;
                        }
                    } catch (error) {
                        console.error("Waduh, error saat menyelesaikan order:", error);
                        await SC1Alert.show('Terjadi kesalahan jaringan.', 'error');
                        dynamicBtn.innerHTML = 'Selesaikan';
                        dynamicBtn.disabled = false;
                    }
                }
            });

            // Event Modal
            if (btnBatal) {
                // Gunakan replaceWith untuk menghapus event listener lama agar tidak dobel 
                // saat modal dipanggil berkali-kali oleh card yang berbeda
                const newBtnBatal = btnBatal.cloneNode(true);
                btnBatal.replaceWith(newBtnBatal);
                newBtnBatal.addEventListener('click', () => {
                    finishModal.style.display = 'none';
                });
            }

            if (btnKonfirmasi) {
                const newBtnKonfirmasi = btnKonfirmasi.cloneNode(true);
                btnKonfirmasi.replaceWith(newBtnKonfirmasi);
                newBtnKonfirmasi.addEventListener('click', () => {
                    finishModal.style.display = 'none';
                    SC1Alert.show('Mantap! Pekerjaan telah berhasil diselesaikan.', 'success');
                    // Logic tambahan nantinya
                });
            }
        }

        const apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        const provSelect = document.getElementById('provinsi');
        const kotaSelect = document.getElementById('kota');
        const kecSelect = document.getElementById('kecamatan');

        const orderContainer = document.getElementById('order-container');
        const emptyMessage = document.getElementById('empty-message');

        // 1. Load Provinsi
        fetch(`${apiBase}/provinces.json`)
            .then(response => response.json())
            .then(provinces => {
                provinces.forEach(prov => {
                    let option = document.createElement('option');
                    option.value = prov.id;
                    option.text = prov.name;
                    provSelect.appendChild(option);
                });
            });

        // 2. Load Kota
        provSelect.addEventListener('change', (e) => {
            const idProv = e.target.value;
            kotaSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';
            kecSelect.innerHTML = '<option value="" disabled selected>Kecamatan</option>';
            kecSelect.disabled = true;

            // Sembunyikan order jika ganti wilayah
            orderContainer.style.display = 'none';
            emptyMessage.style.display = 'block';

            fetch(`${apiBase}/regencies/${idProv}.json`)
                .then(response => response.json())
                .then(regencies => {
                    kotaSelect.innerHTML = '<option value="" disabled selected>Kota/Kabupaten</option>';
                    kotaSelect.disabled = false;
                    regencies.forEach(kota => {
                        let option = document.createElement('option');
                        option.value = kota.id;
                        option.text = kota.name;
                        kotaSelect.appendChild(option);
                    });
                });
        });

        // 3. Load Kecamatan
        kotaSelect.addEventListener('change', (e) => {
            const idKota = e.target.value;
            kecSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';

            // Sembunyikan order jika ganti wilayah
            orderContainer.style.display = 'none';
            emptyMessage.style.display = 'block';

            fetch(`${apiBase}/districts/${idKota}.json`)
                .then(response => response.json())
                .then(districts => {
                    kecSelect.innerHTML = '<option value="" disabled selected>Kecamatan</option>';
                    kecSelect.disabled = false;
                    districts.forEach(kec => {
                        let option = document.createElement('option');
                        option.value = kec.id;
                        option.text = kec.name;
                        kecSelect.appendChild(option);
                    });
                });
        });

        let pekerjaSedangSibuk = false;

        // --- TAMBAHAN: TEMPAT NYIMPEN TIMER BIAR GAK BENTROK ---
        let orderIntervals = [];

        // 4. FUNGSI PENCARIAN ORDER (Bisa dipanggil klik manual ATAU otomatis)
        async function cariOrderOtomatis() {
            // CEK SYARAT: Jangan nyari kalau belum milih kecamatan, atau lagi sibuk, 
            // atau lagi nggak buka tab 'Order' (Tab index 0)
            const tabOrderActive = document.querySelectorAll('.tab-btn')[0].classList.contains('active');
            if (!kecSelect.value || pekerjaSedangSibuk || !tabOrderActive) {
                return; // Berhenti/jangan jalan
            }

            const provName = provSelect.options[provSelect.selectedIndex].text;
            const kotaName = kotaSelect.options[kotaSelect.selectedIndex].text;
            const kecName = kecSelect.options[kecSelect.selectedIndex].text;

            const formData = new FormData();
            formData.append('provinsi', provName);
            formData.append('kota', kotaName);
            formData.append('kecamatan', kecName);

            try {
                const response = await fetch('function/getOrdersByRegion.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    const orderGrid = document.querySelector('#order-container .order-grid');
                    const orderContainer = document.getElementById('order-container');
                    const emptyMessage = document.getElementById('empty-message');

                    if (!orderGrid) return;

                    // BERSIHKAN SEMUA TIMER LAMA SEBELUM HTML BARU DIMASUKKAN
                    orderIntervals.forEach(clearInterval);
                    orderIntervals = [];
                    orderGrid.innerHTML = '';

                    if (result.data.length === 0) {
                        if (emptyMessage) {
                            emptyMessage.style.display = 'block';
                            emptyMessage.textContent = 'Menunggu orderan masuk di wilayah ini...';
                        }
                        orderGrid.style.display = 'none';
                    } else {
                        if (emptyMessage) emptyMessage.style.display = 'none';
                        if (orderContainer) orderContainer.style.display = 'block';

                        orderGrid.style.display = 'grid';

                        result.data.forEach(order => {
                            const fotoSrc = order.foto_keluhan ?
                                `../../assets/img/uploads/keluhan/${order.foto_keluhan}` :
                                `https://via.placeholder.com/70x70.png?text=No+Image`;

                            const tombolTerima = pekerjaSedangSibuk ?
                                `<button class="btn-terima" disabled style="background-color: #cccccc; color: #666666; cursor: not-allowed;">Sedang Sibuk</button>` :
                                `<button class="btn-terima" onclick="terimaOrder('${order.id_order}')">Terima</button>`;

                            const card = `
                            <div class="order-card" data-id="${order.id_order}" id="card-${order.id_order}">
                                <div class="order-header">
                                    <img src="${fotoSrc}" class="order-img" alt="Foto Keluhan">
                                    <div class="order-info">
                                        <span class="status-text">Pelanggan : ${order.nama_pelanggan}</span>
                                        <h4 class="order-title">${order.keluhan}</h4>
                                        
                                        <div class="timer-badge" style="color: #d32f2f; font-weight: bold; font-size: 0.9em; margin-bottom: 5px;">
                                            Sisa Waktu: <span id="timer-${order.id_order}">Menghitung...</span>
                                        </div>

                                        <p class="order-desc">${order.detail_keluhan}</p>
                                    </div>
                                </div>
                                <div class="order-address">
                                    <h5>Detail Alamat :</h5>
                                    <p>${order.detail_alamat}, ${order.kecamatan}, ${order.kota}, ${order.provinsi}</p>
                                </div>
                                <div class="order-footer">
                                    <h3 class="order-price">Rp. ${Number(order.harga).toLocaleString('id-ID')}</h3>
                                    ${tombolTerima} 
                                </div>
                            </div>`;

                            orderGrid.insertAdjacentHTML('beforeend', card);

                            // Panggil fungsi timer untuk card ini
                            jalankanTimer(order.id_order, order.sisa_detik);
                        });
                    }
                }
            } catch (error) {
                console.error("Error fetching or rendering orders:", error);
            }
        }

        // Kalau tombol diklik manual, langsung jalankan pencarian
        document.getElementById('regBtn').addEventListener('click', cariOrderOtomatis);

        // --- INI DIA AJAX POLLING-NYA (Auto Refresh tiap 7 detik) ---
        setInterval(cariOrderOtomatis, 7000);

        // FUNGSI UNTUK MENGHITUNG MUNDUR
        function jalankanTimer(id_order, sisaDetik) {
            const timerElement = document.getElementById(`timer-${id_order}`);
            const cardElement = document.getElementById(`card-${id_order}`);

            let waktu = sisaDetik;

            const hitungMundur = setInterval(() => {
                waktu--;

                const menit = Math.floor(waktu / 60);
                const detik = waktu % 60;

                const teksMenit = menit < 10 ? '0' + menit : menit;
                const teksDetik = detik < 10 ? '0' + detik : detik;

                if (timerElement) {
                    timerElement.textContent = `${teksMenit}:${teksDetik}`;
                }

                // Jika waktu habis, hapus card dari layar pekerja
                if (waktu <= 0) {
                    clearInterval(hitungMundur);
                    if (cardElement) {
                        cardElement.style.display = 'none';
                    }
                }
            }, 1000);

            // Simpan ID timer ke array global biar nanti bisa dibersihkan
            orderIntervals.push(hitungMundur);
        }

        // Script Modal
        const profileBtn = document.getElementById('profileBtn');
        const profileModal = document.getElementById('profileModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        profileBtn.addEventListener('click', () => profileModal.classList.add('active'));
        closeModalBtn.addEventListener('click', () => profileModal.classList.remove('active'));
        profileModal.addEventListener('click', (e) => {
            if (e.target === profileModal) profileModal.classList.remove('active');
        });

        // --- LOGIKA TAB & TAMPILAN KONTAINER ---
        const tabs = document.querySelectorAll('.tab-btn');
        const orderContainerDiv = document.getElementById('order-container');
        const diterimaContainerDiv = document.getElementById('diterima-container');
        const emptyStateDiv = document.getElementById('empty-message');
        const regBtn = document.getElementById('regBtn');
        const sidebar = document.querySelector('.sidebar-section');

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                if (index === 0) {
                    // TAB KIRI (ORDER)
                    diterimaContainerDiv.style.display = 'none';
                    // aktifkan kembali tombol
                    sidebar.style.pointerEvents = "auto";
                    sidebar.style.opacity = "1";
                    // Cek apakah kecamatan sudah dipilih untuk menampilkan order
                    if (kecSelect.value) {
                        orderContainerDiv.style.display = 'block';
                        emptyStateDiv.style.display = 'none';
                    } else {
                        orderContainerDiv.style.display = 'none';
                        emptyStateDiv.style.display = 'block';
                    }
                } else if (index === 1) {
                    // TAB KANAN (DITERIMA)
                    orderContainerDiv.style.display = 'none';
                    emptyStateDiv.style.display = 'none';
                    diterimaContainerDiv.style.display = 'block';

                    sidebar.style.pointerEvents = "none";
                    sidebar.style.opacity = "0.5";
                }
            });
        });

        // LOGIKA UNTUK OTOMATIS PINDAH TAB SETELAH REFRESH
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Ambil informasi dari URL (apakah ada ?tab=...)
            const urlParams = new URLSearchParams(window.location.search);
            const tabAktif = urlParams.get('tab');

            // 2. Jika parameternya adalah 'diterima', maka paksa klik tombol tab diterima
            if (tabAktif === 'diterima') {
                const tabDiterimaBtn = document.querySelectorAll('.tab-btn')[1]; // Index 1 biasanya tab Diterima
                if (tabDiterimaBtn) {
                    tabDiterimaBtn.click();

                    // Opsional: Hapus parameter dari URL biar kalau di-refresh manual gak balik ke situ terus
                    window.history.replaceState({}, document.title, "board-worker.php");
                }
            }
        });

        // --- LOGIKA DYNAMIC BUTTON PADA CARD DITERIMA ---
        // const dynamicBtn = document.getElementById('dynamic-btn');
        // const uploadSection = document.getElementById('upload-section');
        // const finishModal = document.getElementById('finish-modal');
        // const btnBatal = document.getElementById('btn-batal');
        // const btnKonfirmasi = document.getElementById('btn-konfirmasi-selesai');

        // let orderState = 1; // 1: Sampai Tujuan, 2: Mulai Kerjakan, 3: Selesaikan

        // dynamicBtn.addEventListener('click', () => {
        //     if (orderState === 1) {
        //         // Berubah ke state "Mulai Kerjakan"
        //         dynamicBtn.textContent = 'Mulai Kerjakan';
        //         dynamicBtn.className = 'btn-action btn-mulai';
        //         orderState = 2;
        //     } else if (orderState === 2) {
        //         // Berubah ke state "Selesaikan" & Munculkan Form Upload
        //         dynamicBtn.textContent = 'Selesaikan';
        //         dynamicBtn.className = 'btn-action btn-selesaikan';
        //         uploadSection.style.display = 'block';
        //         orderState = 3;
        //     } else if (orderState === 3) {
        //         // Tampilkan Modal Konfirmasi
        //         finishModal.style.display = 'flex';
        //     }
        // });

        // // Tutup Modal jika klik batal
        // btnBatal.addEventListener('click', () => {
        //     finishModal.style.display = 'none';
        // });

        // // Aksi ketika klik selesai di Modal
        // btnKonfirmasi.addEventListener('click', () => {
        //     finishModal.style.display = 'none';
        //     alert('Mantap! Pekerjaan telah berhasil diselesaikan.');
        //     // Logic tambahan untuk mereset tampilan ke awal bisa di taruh di sini
        // });
    </script>
</body>

</html>