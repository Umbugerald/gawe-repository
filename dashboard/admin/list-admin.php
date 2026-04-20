<?php
session_start();
// Pastikan hanya role yang berwenang (misal supervisor/superadmin) yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: ../../login.php");
    exit;
}

require '../../dbconnection/dbconnection.php';

// ================= KODE UNTUK MENGAMBIL FOTO PROFILE USER LOGIN =================
$id_users = $_SESSION['user_id'] ?? 'USR-000'; // Gunakan dummy jika session kosong saat testing

$sql = "SELECT nama_lengkap, role, photo_profile FROM master_user WHERE id_users = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $id_users);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $currentUser = mysqli_fetch_assoc($result);
} else {
    $currentUser = null;
}

$currentName = $currentUser ? $currentUser['nama_lengkap'] : 'Supervisor';
$currentRole = $currentUser ? ucfirst($currentUser['role']) : 'Supervisor';

if ($currentUser && $currentUser['photo_profile']) {
    $photoUrl = '../../assets/img/uploads/profile/' . $currentUser['photo_profile'];
} else {
    $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($currentName) . '&background=random';
}

// ================= PENCARIAN & DAFTAR ADMIN =================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Mengambil user dengan role admin
$sqlAdminList = "SELECT 
    id_users, 
    nama_lengkap, 
    photo_profile,
    no_wa,
    created_at 
FROM master_user 
WHERE role = 'admin'";

$params = [];
$types = "";

if ($search !== '') {
    $sqlAdminList .= " AND (id_users LIKE ? OR nama_lengkap LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sqlAdminList .= " ORDER BY created_at DESC";

$stmtAdmin = mysqli_prepare($conn, $sqlAdminList);

if ($stmtAdmin) {
    if ($search !== '') {
        mysqli_stmt_bind_param($stmtAdmin, $types, ...$params);
    }
    mysqli_stmt_execute($stmtAdmin);
    $resultAdmin = mysqli_stmt_get_result($stmtAdmin);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <title>List Admin - Yang Penting Kerja</title>

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
            flex: 1;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 12px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
        }

        .search-box input {
            width: 100%;
            border: none;
            background: transparent;
            outline: none;
            font-size: 13px;
            color: #515154;
        }

        .search-box input::placeholder {
            color: #a1a1a6;
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

        /* ================= TABLE ADMIN ================= */
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
            border: 2px solid #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .detail-info-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
            padding: 0 20px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 50px 10px 1fr;
            text-align: left;
            font-size: 12px;
            color: #1d1d1f;
        }

        .detail-row .val {
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
        }

        .btn-hubungi svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* Specific Modals text */
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
                width: 100%;
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

            header {
                margin-bottom: 30px;
            }
        }
    </style>
</head>

<body>

    <div class="app-wrapper">

        <nav class="nav-menu">
            <div class="desktop-logo">
                <img src="../../assets/img/logo/gawelogo.png" alt="Logo"
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
                <a href="list-admin.php" class="nav-item active">
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
                    <div class="header-title">List Admin</div>
                </div>
                <div class="logo-circle mobile-only">
                    <img src="../../assets/img/logo/gawelogo.png" alt="Logo"
                        style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; background:#3b68ff;">
                </div>
            </header>

            <form action="" method="GET" class="search-container">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Cari nama admin, id admin"
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </div>
                <button type="submit" class="search-btn">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
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
                                <th>Nama</th>
                                <th>Sejak</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($resultAdmin) && mysqli_num_rows($resultAdmin) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($resultAdmin)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_users']) ?>.</td>
                                        <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td><?= date('d/m/y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <button class="btn-detail" data-id="<?= htmlspecialchars($row['id_users']) ?>"
                                                data-nama="<?= htmlspecialchars($row['nama_lengkap']) ?>"
                                                data-profile="<?= htmlspecialchars($row['photo_profile'] ?? '') ?>"
                                                data-nomor="<?= htmlspecialchars($row['no_wa'] ?? '') ?>"
                                                data-sejak="<?= date('d/m/Y', strtotime($row['created_at'])); ?>"
                                                onclick="bukaModalDetailAdmin(this)"> Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding: 20px; color:#a1a1a6;">
                                        Data admin tidak ditemukan.
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
                <div class="modal-title">Profile Anda</div>
                <div style="width: 24px;"></div>
            </div>
            <img src="<?= $photoUrl; ?>" alt="Admin" class="modal-avatar">
            <div class="modal-name"><?= htmlspecialchars($currentName) ?></div>
            <div class="modal-role"><?= htmlspecialchars($currentRole) ?></div>
            <svg class="logout-icon" viewBox="0 0 24 24" onclick="window.location.href='function/logoutAdmin.php'">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-card">
            <div class="modal-header">
                <svg class="back-icon" id="closeDetailBtn" viewBox="0 0 24 24">
                    <path d="M19 12H5"></path>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <div class="modal-title">Detail Admin</div>
            </div>

            <img src="" alt="Admin Profile" class="modal-avatar" id="detailAvatar">

            <div class="detail-info-container">
                <div class="detail-row">
                    <span class="lbl">ID User</span>
                    <span>:</span>
                    <span class="val" id="detailId">ADM-001</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Nama</span>
                    <span>:</span>
                    <span class="val" id="detailNama">Nama Admin</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Sejak</span>
                    <span>:</span>
                    <span class="val" id="detailSejak">25/11/26</span>
                </div>
            </div>

            <a href="#" id="btnHubungiWA" target="_blank" class="btn-hubungi">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                </svg>
                Hubungi
            </a>
        </div>
    </div>

    <script>
        // Fungsi Untuk Membuka Modal Detail Admin dari Table
        window.bukaModalDetailAdmin = function (btn) {
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama');
            const sejak = btn.getAttribute('data-sejak');
            const foto = btn.getAttribute('data-profile');
            const noHp = btn.getAttribute('data-nomor');

            document.getElementById('detailId').textContent = id;
            document.getElementById('detailNama').textContent = nama;
            document.getElementById('detailSejak').textContent = sejak;

            const modalAvatar = document.getElementById('detailAvatar');
            if (foto && foto !== "") {
                modalAvatar.src = "../../assets/img/uploads/profile/" + foto;
            } else {
                modalAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(nama)}&background=random&color=fff`;
            }

            const btnWA = document.getElementById('btnHubungiWA');
            if (noHp && noHp !== "") {
                let cleanNumber = noHp.replace(/\D/g, '');
                if (cleanNumber.startsWith('0')) {
                    cleanNumber = '62' + cleanNumber.slice(1);
                }
                const pesan = encodeURIComponent(`Halo ${nama}, saya ingin membahas terkait operasional sistem YangPentingKerja.`);
                btnWA.href = `https://wa.me/${cleanNumber}?text=${pesan}`;
                btnWA.style.display = 'inline-flex';
            } else {
                btnWA.style.display = 'none';
            }

            const detailModal = document.getElementById('detailModal');
            if (detailModal) {
                detailModal.classList.add('active');
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            // Profile Modal Logic
            const profileBtn = document.getElementById('profileBtn');
            const profileModal = document.getElementById('profileModal');

            if (profileBtn) {
                profileBtn.addEventListener('click', () => profileModal.classList.add('active'));
            }
            if (profileModal) {
                profileModal.addEventListener('click', (e) => {
                    if (e.target === profileModal) profileModal.classList.remove('active');
                });
            }

            // Close Modal Detail Logic
            const detailModal = document.getElementById('detailModal');
            const closeDetailBtn = document.getElementById('closeDetailBtn');

            if (closeDetailBtn) {
                closeDetailBtn.addEventListener('click', () => detailModal.classList.remove('active'));
            }
            if (detailModal) {
                detailModal.addEventListener('click', (e) => {
                    if (e.target === detailModal) detailModal.classList.remove('active');
                });
            }
        });
    </script>
</body>

</html>