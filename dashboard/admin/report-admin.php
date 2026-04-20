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


// Ambil input dari form
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// 1. Query Dasar dengan Multiple JOIN
$sqlReport = "SELECT 
    r.id_report, 
    r.id_order, 
    u_pel.nama_lengkap AS nama_pelanggan, 
    u_pel.no_wa AS wa_pelanggan, -- Ambil WA Pelanggan
    u_pek.nama_lengkap AS nama_pekerja, 
    u_pek.no_wa AS wa_pekerja,   -- Ambil WA Pekerja yang menghandle
    o.created_at AS tanggal_order_fix,
    o.keluhan,
    o.detail_keluhan,
    r.foto_report,
    r.detail_report
FROM report r
JOIN orders o ON r.id_order = o.id_order
LEFT JOIN master_user u_pel ON o.id_user_pelanggan = u_pel.id_users
LEFT JOIN master_user u_pek ON o.id_users_pekerja = u_pek.id_users
WHERE 1=1";

$params = [];
$types = "";

// 2. Logika Filter Search (Teks)
if ($search !== '') {
    $sqlReport .= " AND (r.id_order LIKE ? OR u_pel.nama_lengkap LIKE ? OR u_pek.nama_lengkap LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// 3. Logika Filter Tanggal
if ($date !== '') {
    $sqlReport .= " AND DATE(o.created_at) = ?";
    $params[] = $date;
    $types .= "s";
}

// Urutkan berdasarkan yang terbaru
$sqlReport .= " ORDER BY o.created_at DESC";

// 4. Eksekusi Prepared Statement
$stmt = mysqli_prepare($conn, $sqlReport);

if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <title>Report - Yang Penting Kerja</title>

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

        /* ================= SEARCH / FILTER BAR ================= */
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

        /* ================= TABLE ================= */
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

        /* Layout Container Hubungi */
        .hubungi-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-top: 15px;
            padding-top: 15px;
        }

        .hubungi-title {
            font-size: 14px;
            color: #1d1d1f;
            padding-bottom: 5px;
        }

        .hubungi-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }

        /* Base Button Style */
        .btn-hubungi {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: white;
            border: none;
            padding: 10px 0;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            flex: 1;
            /* Membagi ukuran tombol rata 50:50 */
            max-width: 130px;
            /* Agar tidak terlalu melar */
        }

        /* Warna Tombol Pelanggan (Biru Kehijauan) */
        .btn-pelanggan {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            box-shadow: 0 4px 12px rgba(24, 178, 183, 0.3);
        }

        .btn-pelanggan:hover {
            /* Warna gradient sedikit digelapkan */
            background: linear-gradient(135deg, #20bd5c, #0e7165);
            /* Bayangan diperbesar dan dipertegas */
            box-shadow: 0 6px 16px rgba(24, 178, 183, 0.5);
            /* Efek tombol sedikit terangkat ke atas */
            transform: translateY(-2px);
        }

        .btn-pekerja {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            box-shadow: 0 4px 12px rgba(76, 222, 128, 0.3);
        }

        .btn-pekerja:hover {
            /* Warna gradient sedikit digelapkan */
            background: linear-gradient(135deg, #20bd5c, #0e7165);
            /* Bayangan diperbesar dan dipertegas */
            box-shadow: 0 6px 16px rgba(76, 222, 128, 0.5);
            /* Efek tombol sedikit terangkat ke atas */
            transform: translateY(-2px);
        }

        .btn-pelanggan,
        .btn-pekerja {
            transition: all 0.3s ease;
            /* Membuat animasi perubahan menjadi halus */
        }

        .btn-pelanggan:active,
        .btn-pekerja:active {
            /* Mengembalikan posisi tombol ke semula saat ditekan */
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-hubungi svg {
            width: 16px;
            height: 16px;
            fill: white;
        }

        .logout-icon {
            width: 35px;
            height: 35px;
            stroke: #515154;
            fill: none;
            stroke-width: 1.5;
            cursor: pointer;
        }

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

            <a href="order-admin.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <path d="M9 14h6"></path>
                    <path d="M9 10h6"></path>
                    <path d="M9 18h6"></path>
                </svg>
                <span>Order</span>
            </a>
            <a href="pelanggan-admin.php" class="nav-item ">
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
            <a href="report-admin.php" class="nav-item active">
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
                    <div class="header-title">Report</div>
                </div>
                <div class="logo-circle mobile-only">
                    <img src="/assets/img/logo/gawelogo.png" alt="Logo"
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
                                <th>id_report</th>
                                <th>Id_order</th>
                                <th>Pelanggan</th>
                                <th>Pekerja</th>
                                <th>Tanggal_report</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result)):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_report']) ?></td>
                                        <td><?= htmlspecialchars($row['id_order']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_pekerja']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_order_fix'])) ?></td>
                                        <td>
                                            <button class="btn-detail" data-id-report="<?= $row['id_report'] ?>"
                                                data-id-order="<?= $row['id_order'] ?>"
                                                data-pelanggan="<?= htmlspecialchars($row['nama_pelanggan']) ?>"
                                                data-pekerja="<?= htmlspecialchars($row['nama_pekerja']) ?>"
                                                data-tanggal="<?= date('d/m/Y', strtotime($row['tanggal_order_fix'])) ?>"
                                                data-kategori="<?= htmlspecialchars($row['keluhan']) ?>"
                                                data-detail-keluhan="<?= htmlspecialchars($row['detail_keluhan']) ?>"
                                                data-wa-pelanggan="<?= $row['wa_pelanggan'] ?>"
                                                data-wa-pekerja="<?= $row['wa_pekerja'] ?>"
                                                data-detailreport="<?= htmlspecialchars($row['detail_report']) ?>"
                                                data-foto="<?= $row['foto_report'] ?>" onclick="bukaModalReport(this)">
                                                Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 50px 0;">
                                        <div class="empty-state">
                                            <p style="color: #999;">
                                                <?php if ($search !== '' || $date !== ''): ?>
                                                    Tidak ada data report yang sesuai dengan pencarian
                                                    "<strong><?= htmlspecialchars($search . ' ' . $date) ?></strong>"
                                                <?php else: ?>
                                                    Data report tidak ditemukan.
                                                <?php endif; ?>
                                            </p>
                                        </div>
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
                <div class="modal-title">Detail Report</div>
                <div style="width: 24px;"></div>
            </div>

            <div class="detail-info-container">
                <div class="detail-row">
                    <span class="lbl">Id_report</span>
                    <span>:</span>
                    <span class="val" id="modIdReport">1</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">id_order</span>
                    <span>:</span>
                    <span class="val" id="modIdOrder">1</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Tanggal_report</span>
                    <span>:</span>
                    <span class="val" id="modTanggal">04/12/2024</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Nama_pelanggan</span>
                    <span>:</span>
                    <span class="val" id="modPelanggan">Ucup</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Nama_pekerja</span>
                    <span>:</span>
                    <span class="val" id="modPekerja">Bagus</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Keluhan</span>
                    <span>:</span>
                    <span class="val" id="modKategori">Listrik</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Detail_keluhan</span>
                    <span>:</span>
                    <span class="val" id="anjingKentot">Pekerja tidak bisa mengatasi mati lampu</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Bukti Foto Selesai</span>
                    <span>:</span>
                    <span class="val foto-link" id="btnToggleBukti">Lihat Foto</span>
                </div>
                <div class="foto-preview foto-link" id="previewBukti">
                    <img src="https://via.placeholder.com/300x400/4b6fff/ffffff?text=Bukti+Pembayaran"
                        alt="Bukti Pembayaran" id="previewBuktiImg">
                </div>
                <div class="hubungi-section">
                    <div class="hubungi-title">Hubungi</div>
                    <div class="hubungi-buttons">
                        <a href="#" class="btn-hubungi btn-pelanggan">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            Pelanggan
                        </a>
                        <a href="#" class="btn-hubungi btn-pekerja">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            Pekerja
                        </a>
                    </div>
                </div>

                </a>
            </div>
        </div>
    </div>
    </div>

    <script>
        // --- 1. Fungsi GLOBAL untuk Detail Report ---
        window.bukaModalReport = function (btn) {
            // Ambil data
            const idReport = btn.getAttribute('data-id-report');
            const detailKeluhanOrder = btn.getAttribute('data-detail-keluhan');
            const idOrder = btn.getAttribute('data-id-order');
            const tglReport = btn.getAttribute('data-tanggal');
            const namaPelanggan = btn.getAttribute('data-pelanggan');
            const namaPekerja = btn.getAttribute('data-pekerja');
            const keluhan = btn.getAttribute('data-kategori');
            const detailKeluhan = btn.getAttribute('data-detailreport'); // Sesuai atribut di HTML
            const buktiFotoSelesai = btn.getAttribute('data-foto');

            document.getElementById('modIdReport').textContent = idReport;
            document.getElementById('modIdOrder').textContent = idOrder;
            document.getElementById('modTanggal').textContent = tglReport;
            document.getElementById('modPelanggan').textContent = namaPelanggan;
            document.getElementById('modPekerja').textContent = namaPekerja;
            document.getElementById('modKategori').textContent = keluhan;

            // ID Unik kamu
            const targetDetail = document.getElementById('anjingKentot');
            if (targetDetail) {
                targetDetail.innerHTML = detailKeluhan || "Tidak ada detail laporan";
            }

            // Logika Foto
            const imgBukti = document.getElementById('previewBuktiImg');
            const previewContainer = document.getElementById('previewBukti');

            if (imgBukti) {
                imgBukti.src = buktiFotoSelesai ? '../../assets/img/uploads/report/' + buktiFotoSelesai : '../../assets/img/default-placeholder.png';
            }

            // Reset toggle foto saat buka modal baru
            if (previewContainer) previewContainer.classList.remove('active');
            const btnToggle = document.getElementById('btnToggleBukti');
            if (btnToggle) btnToggle.textContent = "Lihat Foto";

            const waPelanggan = btn.getAttribute('data-wa-pelanggan');
            const waPekerja = btn.getAttribute('data-wa-pekerja');

            // Helper fungsi agar tidak nulis logika yang sama berulang kali
            const setupWA = (elementSelector, nomor, nama, role, pesanTemplate) => {
                const btnWA = document.querySelector(elementSelector);
                if (btnWA && nomor && nomor !== "") {
                    let cleanNumber = nomor.replace(/\D/g, '');
                    if (cleanNumber.startsWith('0')) {
                        cleanNumber = '62' + cleanNumber.slice(1);
                    }

                    // Encode pesan agar aman untuk URL
                    const pesan = encodeURIComponent(pesanTemplate);

                    btnWA.href = `https://wa.me/${cleanNumber}?text=${pesan}`;
                    btnWA.style.display = 'flex';
                } else if (btnWA) {
                    btnWA.style.display = 'none';
                }
            };

            // Data Admin (ambil dari state atau global variable)
            const namaAdmin = document.getElementById('adminName').textContent;

            // Template Pesan 1 (Untuk Pelanggan)
            const pesanPelanggan = `Halo ${namaPelanggan},

            Perkenalkan, saya ${namaAdmin} dari tim admin Gawe. Mohon maaf atas ketidaknyamanan yang Anda alami terkait kendala pada pesanan ${keluhan}.

            Melalui pesan ini, kami ingin mengonfirmasi bahwa laporan Anda dengan ID ${idReport} sedang dalam proses pengecekan oleh tim kami. Kami akan segera menghubungi Anda kembali segera setelah ada perkembangan lebih lanjut.

            Terima kasih atas kepercayaan Anda menggunakan layanan Gawe.`;

            // Template Pesan 2 (Untuk Pekerja)
            const pesanPekerja = `Halo ${namaPekerja},

            Mohon perhatiannya, kami menerima laporan dari pelanggan mengenai pengerjaan pesanan ${keluhan} dengan ID Report: ${idReport}.

            Detail keluhan: "${detailKeluhanOrder}"

            Harap segera meninjau masalah ini dan memberikan tanggapan atau solusi kepada kami agar bisa segera kami sampaikan kembali ke pelanggan.

            Terima kasih.`;

            // Eksekusi
            setupWA('.btn-pelanggan', waPelanggan, namaPelanggan, 'Pelanggan', pesanPelanggan);
            setupWA('.btn-pekerja', waPekerja, namaPekerja, 'Pekerja', pesanPekerja);

            // Tampilkan Modal
            const modal = document.getElementById('detailModal');
            if (modal) {
                modal.classList.add('active');
                modal.style.display = 'flex';
            }
        };

        // --- 2. Logic lainnya (Profile & Close Modal) ---
        document.addEventListener('DOMContentLoaded', function () {
            // Load Profile Admin
            fetch('function/dashboard/profileAdminHandler.php')
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        document.getElementById('adminName').textContent = result.data.nama;
                        document.getElementById('adminRole').textContent = result.data.role.charAt(0).toUpperCase() + result.data.role.slice(1);
                    }
                });

            // Toggle Foto Logic
            const modToggleBukti = document.getElementById('btnToggleBukti');
            const modFotoBukti = document.getElementById('previewBukti');
            if (modToggleBukti && modFotoBukti) {
                modToggleBukti.addEventListener('click', () => {
                    modFotoBukti.classList.toggle('active');
                    modToggleBukti.textContent = modFotoBukti.classList.contains('active') ? "Sembunyikan Foto" : "Lihat Foto";
                });
            }

            // Close Modal Logic
            const detailModal = document.getElementById('detailModal');
            const profileModal = document.getElementById('profileModal');
            const profileBtn = document.getElementById('profileBtn');
            const closeDetailBtn = document.getElementById('closeDetailBtn');

            if (profileBtn) {
                profileBtn.addEventListener('click', () => profileModal.classList.add('active'));
            }
            if (closeDetailBtn) {
                closeDetailBtn.addEventListener('click', () => detailModal.classList.remove('active'));
            }
            window.addEventListener('click', (e) => {
                if (e.target === detailModal) detailModal.classList.remove('active');
                if (e.target === document.getElementById('profileModal')) document.getElementById('profileModal').classList.remove('active');
            });
        });
    </script>
</body>

</html>