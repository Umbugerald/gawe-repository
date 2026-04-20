<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header("Location: ../../login.php");
    exit;
}
require '../../dbconnection/dbconnection.php';

// KODE UNTUK MENGAMBIL FOTO PROFILE PEKERJA
$id_users = $_SESSION['user_id'];

$sql = "SELECT nama_lengkap, role, photo_profile FROM master_user WHERE id_users = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id_users);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

$adminName = $admin ? $admin['nama_lengkap'] : 'Admin';
$adminRole = $admin ? ucfirst($admin['role']) : 'Admin';

if ($admin && $admin['photo_profile']) {
    $photoUrl = '../../assets/img/uploads/profile/' . $admin['photo_profile'];
} else {
    $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($adminName) . '&background=random';
}

// Ambil input dari filter search & date
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Query Sakti: Join ke master_user DUA KALI
$sqlOrders = "SELECT 
    o.id_order, 
    u_pel.nama_lengkap AS nama_pelanggan, 
    u_pek.nama_lengkap AS nama_pekerja,   
    o.keluhan,
    o.detail_keluhan,
    ma.detail_alamat,
    ma.provinsi,
    ma.kota,
    ma.kecamatan,
    ma.kelurahan,
    o.harga,
    o.foto_keluhan,
    o.bukti_foto,
    o.created_at, 
    o.selesai_at,
    o.rating,
    o.status 
FROM orders o
LEFT JOIN master_user u_pel ON o.id_user_pelanggan = u_pel.id_users 
LEFT JOIN master_user u_pek ON o.id_users_pekerja = u_pek.id_users 
LEFT JOIN master_alamat ma ON o.id_alamat = ma.id_alamat
WHERE 1=1";

$params = [];
$types = "";

if ($search !== '') {
    // Cari di ID Order, Nama Pelanggan, atau Nama Pekerja
    $sqlOrders .= " AND (o.id_order LIKE ? OR u_pel.nama_lengkap LIKE ? OR u_pek.nama_lengkap LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = "sss";
}

if ($date !== '') {
    $sqlOrders .= " AND DATE(o.created_at) = ?";
    $params[] = $date;
    $types .= "s";
}

$sqlOrders .= " ORDER BY o.created_at DESC";

$stmt = mysqli_prepare($conn, $sqlOrders);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resOrders = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <link rel="stylesheet" href="../../assets/components/sc1-alert.css">
    <script src="../../assets/components/sc1-alert.js"></script>
    <title>Order - Yang Penting Kerja</title>

    <style>
        /* ================= RESET & BASE ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: #ebebeb;
            color: #1d1d1f;
        }

        /* ================= LAYOUT UTAMA ================= */
        .app-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            padding: 20px;
            padding-bottom: 110px;
            /* Ruang untuk bottom nav di HP */
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            transition: all 0.3s ease;
        }

        /* ================= GLASSMORPHISM ================= */
        .glass-panel {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }

        /* ================= HEADER ================= */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-pic {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .profile-pic:active {
            transform: scale(0.9);
        }

        .header-title {
            background: rgba(255, 255, 255, 0.8);
            padding: 8px 25px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .desktop-logo {
            display: none;
        }

        /* ================= SEARCH BAR ================= */
        .search-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-box {
            width: 80%;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 12px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
        }

        .date-box {
            width: 20%;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 12px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
        }

        .date-box input {
            width: 100%;
            border: none;
            background: transparent;
            outline: none;
            font-size: 13px;
            color: #515154;
        }

        .search-box input {
            width: 100%;
            border: none;
            background: transparent;
            outline: none;
            font-size: 13px;
            color: #515154;
        }

        .search-btn {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            width: 45px;
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            cursor: pointer;
        }

        .search-btn svg {
            width: 20px;
            height: 20px;
            stroke: #1d1d1f;
            fill: none;
            stroke-width: 2;
        }

        /* ================= TABLE ORDER ================= */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            text-align: center;
            padding: 15px 5px;
            vertical-align: middle;
        }

        th {
            font-weight: 500;
            color: #515154;
            border-bottom: 1px solid #d1d1d6;
        }

        th:not(:first-child),
        td:not(:first-child) {
            border-left: 1px solid #e5e5ea;
        }

        .btn-detail {
            background-color: #4b6fff;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-detail:active {
            background-color: #3554d1;
        }

        /* Status Dropdown Styling */
        .status-select {
            border-radius: 10px;
            padding: 5px 20px 5px 10px;
            font-size: 11px;
            font-weight: 600;
            border: none;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%231d1d1f" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 2px center;
            text-align: center;
        }

        /* Warna Khusus Order */
        .status-selesai {
            background-color: rgb(33, 218, 97);
            color: rgb(16, 83, 42);
        }

        .status-proses {
            background-color: #fef08a;
            color: #854d0e;
        }

        .status-batal {
            background-color: rgb(180, 104, 104);
            color: rgb(43, 2, 2);
        }

        .status-mencari {
            background-color: rgb(18, 101, 140);
            color: rgb(222, 222, 238);
        }

        .status-diterima {
            background-color: yellow;
            color: black;
        }

        .status-sampai {
            background-color: orange;
            color: white;
        }

        .status-dibatalkan-sistem {
            background-color: rgb(180, 104, 104);
            color: #991b1b;
        }

        /* ================= NAVIGATION ================= */
        .nav-menu {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 95%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 30px;
            display: flex;
            justify-content: space-around;
            padding: 12px 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.6);
            z-index: 10;
            overflow-x: auto;
            /* Untuk menampung banyak menu di HP */
            white-space: nowrap;
            gap: 15px;
        }

        .nav-menu::-webkit-scrollbar {
            display: none;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #515154;
            font-size: 10px;
            font-weight: 500;
            gap: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 60px;
        }

        .nav-item svg {
            width: 24px;
            height: 24px;
            stroke: #1d1d1f;
            stroke-width: 1.5;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .nav-item.active {
            color: #1d1d1f;
            font-weight: 700;
        }

        .nav-item.active svg {
            stroke-width: 2.2;
        }


        /* ================= MODALS ================= */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(235, 235, 235, 0.4);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
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
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-overlay.active .modal-card {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }

        .modal-title {
            flex: 1;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
        }

        .back-icon {
            width: 24px;
            height: 24px;
            stroke: #1d1d1f;
            stroke-width: 2.5;
            fill: none;
            cursor: pointer;
            position: absolute;
            left: 0;
        }

        .modal-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }

        /* Detail Modal Specifics */
        .detail-info-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
            padding: 0 5px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 110px 10px 1fr;
            text-align: left;
            font-size: 12px;
            color: #1d1d1f;
            align-items: start;
        }

        .detail-row .val {
            font-weight: 500;
            line-height: 1.4;
            word-break: break-word;
        }

        /* Toggle Foto Links */
        .foto-link {
            color: #4b6fff;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
        }

        .foto-preview {
            display: none;
            margin-top: 10px;
            text-align: center;
        }

        .foto-preview img {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #d1d1d6;
        }

        .foto-preview.active {
            display: block;
        }

        /* Status label in modal */
        .modal-status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-hubungi {
            display: inline-flex;
            align-items: center;
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
            width: 100%;
        }

        .btn-hubungi svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* Admin Modal */
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
        }

        /* ================= RESPONSIVE DESKTOP ================= */
        @media (min-width: 768px) {
            body {
                background-color: #f4f5f7;
            }

            .app-wrapper {
                flex-direction: row;
                height: 100vh;
                overflow: hidden;
            }

            .nav-menu {
                position: static;
                transform: none;
                width: 250px;
                max-width: none;
                height: 100vh;
                border-radius: 0;
                flex-direction: column;
                justify-content: flex-start;
                padding: 30px 20px;
                border: none;
                border-right: 1px solid rgba(255, 255, 255, 0.8);
                gap: 15px;
            }

            .nav-item {
                flex-direction: row;
                justify-content: flex-start;
                padding: 12px 20px;
                font-size: 14px;
                gap: 15px;
                border-radius: 12px;
            }

            .nav-item:hover {
                background: rgba(255, 255, 255, 0.5);
            }

            .nav-item.active {
                background: white;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            }

            .desktop-logo {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 10px;
                font-size: 20px;
                font-weight: 800;
                color: #3b68ff;
                margin-bottom: 20px;
            }

            .mobile-only {
                display: none;
            }

            .main-content {
                flex: 1;
                padding: 30px 40px;
                overflow-y: auto;
                height: 100vh;
            }
        }
    </style>
</head>

<body>

    <div class="app-wrapper">
        <nav class="nav-menu">
            <div class="desktop-logo">
                <img src="/assets/img/logo/gawelogo.png" alt="Logo"
                    style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
            </div>

            <a href="order-admin.php" class="nav-item active">
                <svg viewBox="0 0 24 24">
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <path d="M9 14h6"></path>
                    <path d="M9 10h6"></path>
                    <path d="M9 18h6"></path>
                </svg>
                <span>Order</span>
            </a>
            <a href="pelanggan-admin.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Pelanggan</span>
            </a>
            <a href="dashboard-admin.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="pekerja-admin.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <rect x="13" y="14" width="9" height="6" rx="1"></rect>
                    <path d="M15 14v-2a2 2 0 0 1 4 0v2"></path>
                </svg>
                <span>Pekerja</span>
            </a>
            <a href="report-admin.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Report</span>
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor'): ?>
                <a href="tambah-admin.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <span>Tambah Admin</span>
                </a>
                <a href="list-admin.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    <span>List Admin</span>
                </a>
            <?php endif; ?>
        </nav>

        <main class="main-content">
            <header>
                <div class="header-left">
                    <img src="<?= $photoUrl; ?>" alt="Profile" class="profile-pic" id="profileBtn">
                    <div class="header-title">Order</div>
                </div>
                <div class="logo-circle mobile-only">
                    <img src="<?= $photoUrl; ?>" alt="Logo"
                        style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; background:#3b68ff;">
                </div>
            </header>

            <form method="GET" action="" class="search-container">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Cari id order, pelanggan, pekerja"
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </div>
                <div class="date-box">
                    <input type="date" name="date" value="<?= isset($_GET['date']) ? $_GET['date'] : '' ?>">
                </div>
                <button type="submit" class="search-btn">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>

            <div class="glass-panel">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>id</th>
                                <th>Pelanggan</th>
                                <th>Pekerja</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($resOrders) > 0): ?>
                                <?php $no = 1;
                                while ($row = mysqli_fetch_assoc($resOrders)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_order']) ?>.</td>
                                        <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan'); ?></td>

                                        <td>
                                            <?php if ($row['nama_pekerja']): ?>
                                                <?= htmlspecialchars($row['nama_pekerja']); ?>
                                            <?php else: ?>
                                                <span style="color: #999; font-style: italic;">Menunggu Pekerja...</span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= date('d/m/y', strtotime($row['created_at'])); ?></td>

                                        <td>
                                            <select class="status-select status-<?= $row['status']; ?>"
                                                onchange="updateStatusOrder('<?= $row['id_order']; ?>', this)">
                                                <option value="mencari" <?= $row['status'] == 'mencari' ? 'selected' : ''; ?>>
                                                    Mencari</option>
                                                <option value="diterima" <?= $row['status'] == 'diterima' ? 'selected' : ''; ?>>
                                                    Diterima</option>
                                                <option value="sampai" <?= $row['status'] == 'sampai' ? 'selected' : ''; ?>>Sampai
                                                </option>
                                                <option value="dibatalkan_sistem" <?= $row['status'] == 'dibatalkan_sistem' ? 'selected' : ''; ?>>Dibatalkan sistem</option>
                                                <option value="proses" <?= $row['status'] == 'proses' ? 'selected' : ''; ?>>Proses
                                                </option>
                                                <option value="selesai" <?= $row['status'] == 'selesai' ? 'selected' : ''; ?>>
                                                    Selesai</option>
                                                <option value="batal" <?= $row['status'] == 'batal' ? 'selected' : ''; ?>>Batal
                                                </option>
                                            </select>
                                        </td>

                                        <td>
                                            <button class="btn-detail" data-id="<?= $row['id_order'] ?>"
                                                data-pelanggan="<?= htmlspecialchars($row['nama_pelanggan']) ?>"
                                                data-pekerja="<?= htmlspecialchars($row['nama_pekerja'] ?? 'Menunggu...') ?>"
                                                data-keluhan="<?= htmlspecialchars($row['keluhan'] ?? '-') ?>"
                                                data-lokasi="<?= htmlspecialchars($row['detail_alamat'] ?? '-') ?>"
                                                data-lokasi-provinsi="<?= htmlspecialchars($row['provinsi'] ?? '-') ?>"
                                                data-lokasi-kota="<?= htmlspecialchars($row['kota'] ?? '-') ?>"
                                                data-lokasi-kecamatan="<?= htmlspecialchars($row['kecamatan'] ?? '-') ?>"
                                                data-lokasi-kelurahan="<?= htmlspecialchars($row['kelurahan'] ?? '-') ?>"
                                                data-dibuat="<?= htmlspecialchars($row['created_at'] ?? '-') ?>"
                                                data-selesai="<?= htmlspecialchars($row['selesai_at'] ?? '-') ?>"
                                                data-rating="<?= htmlspecialchars($row['rating'] ?? '-') ?>"
                                                data-detail-keluhan="<?= htmlspecialchars($row['detail_keluhan'] ?? '-') ?>"
                                                data-bukti="<?= $row['bukti_foto'] ?>" data-harga="<?= $row['harga'] ?>"
                                                data-fotokeluhan="<?= $row['foto_keluhan'] ?>"
                                                data-status="<?= $row['status'] ?>" onclick="bukaModalOrderDetail(this)"> Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 20px;">
                                        Data order tidak ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="profileModal">
        <div class="modal-card">
            <div class="modal-header">
                <div style="width: 24px;"></div>
                <div class="modal-title">Profile</div>
                <div style="width: 24px;"></div>
            </div>
            <img src="<?= $photoUrl; ?>" alt="Admin" class="modal-avatar">
            <div class="modal-name" id="adminName"></div>
            <div class="modal-role" id="adminRole"></div>
            <svg class="logout-icon" viewBox="0 0 24 24" onclick="window.location.href='function/logoutAdmin.php'">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-card" style="text-align: left;">
            <div class="modal-header">
                <svg class="back-icon" id="closeDetailBtn" viewBox="0 0 24 24">
                    <path d="M19 12H5"></path>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <div class="modal-title">Detail Order</div>
                <div style="width: 24px;"></div>
            </div>

            <div class="detail-info-container">
                <div class="detail-row">
                    <span class="lbl">Id_order</span>
                    <span>:</span>
                    <span class="val" id="detailId">1</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Nama Pelanggan</span>
                    <span>:</span>
                    <span class="val" id="detailPelanggan">Ucup</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Nama Pekerja</span>
                    <span>:</span>
                    <span class="val" id="detailPekerja">Bagus</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Keluhan</span>
                    <span>:</span>
                    <span class="val" id="detailKeluhan">AC tidak dingin</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Detail Keluhan</span>
                    <span>:</span>
                    <span class="val" id="detailKeluhanOrder" style="font-size: 10px; font-weight:400">AC tidak dingin karena
                        dia memakan listrik terlalu banyak</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Lokasi</span>
                    <span>:</span>
                    <div class="alamat-container">
                        <span class="val" id="detailLokasiProvinsi">BANTEN</span>,
                        <span class="val" id="detailLokasiKota">KOTA TANGERANG</span>,
                        <span class="val" id="detailLokasiKecamatan">CIPONDOH</span>,
                        <span class="val" id="detailLokasiKelurahan">PORIS PLAWAD</span>,
                        <span class="val" id="detailLokasi">Taman royal</span>
                    </div>
                </div>
                <div class="detail-row">
                    <span class="lbl">Status</span>
                    <span>:</span>
                    <span class="val">
                        <span class="modal-status-badge status-proses" id="detailStatusBadge">Proses</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Harga</span>
                    <span>:</span>
                    <span class="val" id="detailHarga">Rp.10.000</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Pesanan Dibuat</span>
                    <span>:</span>
                    <span class="val" id="detailDibuat">2025-01-06T08:39:32</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Pesanan Selesai</span>
                    <span>:</span>
                    <span class="val" id="detailSelesai">2025-01-06T09:39:32</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Rating</span>
                    <span>:</span>
                    <span class="val" id="detailRating">2025-01-06T09:39:32</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Foto Keluhan</span>
                    <span>:</span>
                    <span class="val foto-link" id="btnToggleKeluhan">Lihat Foto</span>
                </div>
                <div class="foto-preview" id="previewKeluhan">
                    <img src="" alt="">
                </div>
                <div class="detail-row">
                    <span class="lbl">Bukti Foto Selesai</span>
                    <span>:</span>
                    <span class="val foto-link" id="btnToggleBukti">Lihat Foto</span>
                </div>
                <div class="foto-preview" id="previewBukti">
                    <img src="" alt="">
                </div>
            </div>
        </div>
    </div>

    <script>
        window.bukaModalOrderDetail = function(btn) {
            // Ambil datanya
            const id = btn.getAttribute('data-id');
            const pelanggan = btn.getAttribute('data-pelanggan');
            const pekerja = btn.getAttribute('data-pekerja');
            const keluhan = btn.getAttribute('data-keluhan');
            const lokasi = btn.getAttribute('data-lokasi');
            const provinsi = btn.getAttribute('data-lokasi-provinsi');
            const kota = btn.getAttribute('data-lokasi-kota');
            const kecamatan = btn.getAttribute('data-lokasi-kecamatan');
            const kelurahan = btn.getAttribute('data-lokasi-kelurahan');
            const detailKeluhan = btn.getAttribute('data-detail-keluhan');
            const dibuat = btn.getAttribute('data-dibuat');
            const selesai = btn.getAttribute('data-selesai');
            const rating = btn.getAttribute('data-rating');
            const hargaRaw = btn.getAttribute('data-harga');
            const hargaFormatted = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(hargaRaw);
            const status = btn.getAttribute('data-status') || '';
            const fotoK = btn.getAttribute('data-fotokeluhan');
            const fotoB = btn.getAttribute('data-bukti'); // Biar gak NULL

            // Masukkan ke elemen modal
            document.getElementById('detailId').textContent = id;
            document.getElementById('detailPelanggan').textContent = pelanggan;
            document.getElementById('detailPekerja').textContent = pekerja || 'Menunggu...';
            document.getElementById('detailKeluhan').textContent = keluhan;
            document.getElementById('detailKeluhanOrder').textContent = detailKeluhan;
            document.getElementById('detailLokasiProvinsi').textContent = provinsi;
            document.getElementById('detailLokasiKota').textContent = kota;
            document.getElementById('detailLokasiKecamatan').textContent = kecamatan;
            document.getElementById('detailLokasiKelurahan').textContent = kelurahan;
            document.getElementById('detailLokasi').textContent = lokasi;
            document.getElementById('detailHarga').textContent = hargaFormatted;
            document.getElementById('detailDibuat').textContent = dibuat;
            document.getElementById('detailSelesai').textContent = selesai;
            document.getElementById('detailRating').textContent = rating;

            const badge = document.getElementById('detailStatusBadge');
            if (badge) {
                badge.textContent = status;
                badge.className = 'modal-status-badge'; // Reset class
                const s = status.toLowerCase();
                if (s === 'selesai') badge.classList.add('status-selesai');
                else if (s === 'proses') badge.classList.add('status-proses');
                else if (s === 'batal') badge.classList.add('status-batal');
                else if (s === 'sampai') badge.classList.add('status-sampai');
                else if (s === 'diterima') badge.classList.add('status-diterima');
                else if (s === 'mencari') badge.classList.add('status-mencari');
                else if (s === 'dibatalkan-sistem') badge.classList.add('status-dibatalkan-sistem');
            }

            const imgKeluhan = document.querySelector('#previewKeluhan img');
            const imgBukti = document.querySelector('#previewBukti img');

            if (imgKeluhan) imgKeluhan.src = '../../assets/img/uploads/keluhan/' + fotoK;
            if (imgBukti) imgBukti.src = '../../assets/img/uploads/bukti_transaksi/' + fotoB;

            // Tampilkan modal
            document.getElementById('detailModal').classList.add('active');
            document.getElementById('detailModal').style.display = 'flex';
        };

        // --- Pastikan fungsi updateStatusOrder ada di Scope GLOBAL (Luar DOMContentLoaded) ---
        window.updateStatusOrder = function(idOrder, selectElement) {
            const statusBaru = selectElement.value;

            // 1. DAFTAR SEMUA CLASS STATUS YANG ADA
            const semuaStatus = [
                'status-selesai', 'status-proses', 'status-batal',
                'status-sampai', 'status-diterima', 'status-mencari',
                'status-dibatalkan-sistem'
            ];

            // 2. HAPUS SEMUA CLASS TERSEBUT DARI ELEMENT
            selectElement.classList.remove(...semuaStatus);

            // 3. TAMBAHKAN CLASS BARU SESUAI STATUS
            // Menggunakan replace agar format "dibatalkan_sistem" di DB cocok dengan class "dibatalkan-sistem"
            let classBaru = 'status-' + statusBaru.replace('_', '-');
            selectElement.classList.add(classBaru);

            // 4. FETCH KE SERVER
            const formData = new FormData();
            formData.append('id_order', idOrder);
            formData.append('status', statusBaru);

            fetch('function/order/orderHandler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        SC1Alert.show('Gagal update: ' + data.message, 'error');
                        location.reload(); // Refresh jika gagal agar kembali ke status awal
                    }
                })
                .catch(err => console.error('Error:', err));
        };

        document.addEventListener('DOMContentLoaded', async function() {
            // --- 1. Load Profile Admin ---
            try {
                const response = await fetch('function/dashboard/profileAdminHandler.php');
                const result = await response.json();
                if (result.status === 'success') {
                    const data = result.data;
                    document.getElementById('adminName').textContent = data.nama;
                    document.getElementById('adminRole').textContent = data.role.charAt(0).toUpperCase() + data.role.slice(1);
                }
            } catch (error) {
                console.error("Terjadi kesalahan koneksi:", error);
            }

            // --- 2. Modal Profile Admin ---
            const profileBtn = document.getElementById('profileBtn');
            const profileModal = document.getElementById('profileModal');
            const closeProfileBtn = document.getElementById('closeModalBtn');

            if (profileBtn) {
                profileBtn.addEventListener('click', () => profileModal.classList.add('active'));
            }
            if (closeProfileBtn) {
                closeProfileBtn.addEventListener('click', () => profileModal.classList.remove('active'));
            }
            profileModal.addEventListener('click', (e) => {
                if (e.target === profileModal) profileModal.classList.remove('active');
            });

            // --- 3. Modal Detail Order ---
            const detailModal = document.getElementById('detailModal');
            const closeDetailBtn = document.getElementById('closeDetailBtn');
            const btnDetails = document.querySelectorAll('.btn-detail');

            const modalId = document.getElementById('detailId');
            const modalPelanggan = document.getElementById('detailPelanggan');
            const modalPekerja = document.getElementById('detailPekerja');
            const modalKeluhan = document.getElementById('detailKeluhan');
            const modalLokasi = document.getElementById('detailLokasi');
            const modalStatusBadge = document.getElementById('detailStatusBadge');

            const btnToggleBukti = document.getElementById('btnToggleBukti');
            const previewBukti = document.getElementById('previewBukti');
            const btnToggleKeluhan = document.getElementById('btnToggleKeluhan');
            const previewKeluhan = document.getElementById('previewKeluhan');

            btnDetails.forEach((btn) => {
                btn.addEventListener('click', function() {
                    modalId.textContent = this.getAttribute('data-id');
                    modalPelanggan.textContent = this.getAttribute('data-pelanggan');
                    modalPekerja.textContent = this.getAttribute('data-pekerja');
                    modalKeluhan.textContent = this.getAttribute('data-keluhan');
                    modalLokasi.textContent = this.getAttribute('data-lokasi');

                    const status = this.getAttribute('data-status');
                    modalStatusBadge.textContent = status.toUpperCase();

                    const semuaClassStatus = [
                        'status-selesai', 'status-proses', 'status-batal',
                        'status-sampai', 'status-diterima', 'status-mencari',
                        'status-dibatalkan-sistem'
                    ];

                    modalStatusBadge.className = 'modal-status-badge';
                    const classBaru = 'status-' + status.replace('_', '-');
                    if (semuaClassStatus.includes(classBaru)) {
                        modalStatusBadge.classList.add(classBaru);
                    }

                    previewBukti.classList.remove('active');
                    if (btnToggleBukti) btnToggleBukti.textContent = "Lihat Foto";

                    previewKeluhan.classList.remove('active');
                    if (btnToggleKeluhan) btnToggleKeluhan.textContent = "Lihat Foto";

                    detailModal.classList.add('active');
                });
            });

            if (btnToggleBukti) {
                btnToggleBukti.addEventListener('click', () => {
                    previewBukti.classList.toggle('active');
                    btnToggleBukti.textContent = previewBukti.classList.contains('active') ? "Sembunyikan Foto" : "Lihat Foto";
                });
            }

            if (btnToggleKeluhan) {
                btnToggleKeluhan.addEventListener('click', () => {
                    previewKeluhan.classList.toggle('active');
                    btnToggleKeluhan.textContent = previewKeluhan.classList.contains('active') ? "Sembunyikan Foto" : "Lihat Foto";
                });
            }

            closeDetailBtn.addEventListener('click', () => detailModal.classList.remove('active'));
            detailModal.addEventListener('click', (e) => {
                if (e.target === detailModal) detailModal.classList.remove('active');
            });
        });
    </script>
</body>

</html>