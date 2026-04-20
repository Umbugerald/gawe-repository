<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header("Location: ../../login.php");
    exit;
}

require '../../dbconnection/dbconnection.php';

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

$statPendapatan = 0;
$statPekerja = 0;
$statOrder = 0;
$statReport = 0;

$resPendapatan = mysqli_query($conn, "SELECT SUM(harga) as total FROM `orders` WHERE DATE(created_at) = CURDATE() AND status = 'selesai'");
if ($resPendapatan && $row = mysqli_fetch_assoc($resPendapatan)) {
    $statPendapatan = $row['total'] ? (int) $row['total'] : 0;
}

$formatPendapatan = 'Rp.' . number_format($statPendapatan, 0, ',', '.');

$resPekerja = mysqli_query($conn, "SELECT COUNT(id_pekerja) as total FROM profile_pekerja WHERE status_kerja = 'active'");
if ($resPekerja && $row = mysqli_fetch_assoc($resPekerja)) {
    $statPekerja = (int) $row['total'];
}

$resOrder = mysqli_query($conn, "SELECT COUNT(id_order) as total FROM `orders` WHERE DATE(created_at) = CURDATE()");
if ($resOrder && $row = mysqli_fetch_assoc($resOrder)) {
    $statOrder = (int) $row['total'];
}

$resReport = mysqli_query($conn, "
    SELECT COUNT(r.id_report) as total
    FROM report r
    JOIN orders o ON r.id_order = o.id_order
    WHERE DATE(o.created_at) = CURDATE()
");

if ($resReport && $row = mysqli_fetch_assoc($resReport)) {
    $statReport = (int) $row['total'];
}

$chartArray = [];
for ($i = 6; $i >= 0; $i--) {
    $dateSQL = date('Y-m-d', strtotime("-$i days"));
    $dateLabel = date('d/m/Y', strtotime("-$i days"));
    $chartArray[$dateSQL] = [
        'label' => $dateLabel,
        'total' => 0
    ];
}

$sqlChart = "SELECT DATE(created_at) as tgl, COUNT(id_order) as total
             FROM `orders`
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)";
$resChart = mysqli_query($conn, $sqlChart);

if ($resChart) {
    while ($row = mysqli_fetch_assoc($resChart)) {
        $tglDB = $row['tgl'];
        if (isset($chartArray[$tglDB])) {
            $chartArray[$tglDB]['total'] = (int) $row['total'];
        }
    }
}

$labelsData = [];
$valuesData = [];
foreach ($chartArray as $data) {
    $labelsData[] = $data['label'];
    $valuesData[] = $data['total'];
}

$jsonLabels = json_encode($labelsData);
$jsonValues = json_encode($valuesData);

$topPekerja = [];

$sqlTop = "SELECT
                mu.nama_lengkap,
                COUNT(o.id_order) as jumlah_pesanan,
                pp.rating as rata_rating
           FROM `orders` o
           JOIN profile_pekerja pp ON o.id_users_pekerja = pp.id_users
           JOIN master_user mu ON pp.id_users = mu.id_users
           WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             AND o.status = 'selesai'
           GROUP BY pp.id_pekerja
           ORDER BY rata_rating DESC, jumlah_pesanan DESC
           LIMIT 10";

$resTop = mysqli_query($conn, $sqlTop);

if ($resTop) {
    while ($row = mysqli_fetch_assoc($resTop)) {
        $topPekerja[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Yang Penting Kerja</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
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

        .glass-panel {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }

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

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-card {
            text-align: center;
            padding: 20px 10px;
        }

        .stat-card p {
            font-size: 12px;
            color: #515154;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .skeleton-loader {
            background: linear-gradient(90deg, #e0e0e0 25%, #f5f5f5 50%, #e0e0e0 75%);
            background-size: 200% 100%;
            animation: skeletonShimmer 1.5s infinite linear;
            border-radius: 5px;
            color: transparent !important;

            min-width: 80px;
            min-height: 28px;

            display: inline-block;
        }

        @keyframes skeletonShimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .text-green {
            font-size: 25px;
            background: linear-gradient(135deg, #54e880 50%, #beeb74 50%);
            font-weight: 700;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient {
            font-size: 25px;
            font-weight: 700;
            background: linear-gradient(135deg, #a754e8 50%, #eb8b74 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient1 {
            font-size: 25px;
            font-weight: 700;
            background: linear-gradient(135deg, #546fe8 50%, #74cfeb 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient2 {
            font-size: 25px;
            font-weight: 700;
            background: linear-gradient(15deg, #ff0000 50%, #eba074 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .section-title {
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }

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
            padding: 10px 5px;
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

            .logo-circle.mobile-only {
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

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);

                max-width: 800px;
                margin-bottom: 20px;
            }

            .stat-card p {
                font-size: 14px;
            }

            .content-grid {
                grid-template-columns: 2fr 1.2fr;

                gap: 25px;
            }

            .chart-container {
                height: 300px;
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
            <a href="pelanggan-admin.php" class="nav-item">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Pelanggan</span>
            </a>
            <a href="dashboard-admin.php" class="nav-item active">
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
                    <img src="<?= $photoUrl ?>" alt="Profile" class="profile-pic" id="profileBtn">
                    <div class="header-title">Dashboard</div>
                </div>
                <div class="logo-circle mobile-only"><img id="adminAvatarMobile" alt="Logo"
                        style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;"></div>
            </header>

            <div class="stats-grid">
                <div class="glass-panel stat-card">
                    <p>Pendapatan/hari</p>
                    <div class="text-green"><?= $formatPendapatan ?></div>
                </div>
                <div class="glass-panel stat-card">
                    <p>Jumlah Pekerja/hari</p>
                    <div class="text-gradient"><?= $statPekerja ?></div>
                </div>
                <div class="glass-panel stat-card">
                    <p>Jumlah Order/hari</p>
                    <div class="text-gradient1"><?= $statOrder ?></div>
                </div>
                <div class="glass-panel stat-card">
                    <p>Jumlah Report/hari</p>
                    <div class="text-gradient2"><?= $statReport ?></div>
                </div>
            </div>

            <div class="content-grid">
                <div class="glass-panel">
                    <div class="section-title">Grafik Pesanan</div>
                    <div class="chart-container">
                        <canvas id="orderChart"></canvas>
                    </div>
                </div>

                <div class="glass-panel">
                    <div class="section-title">Pekerja Terbaik/Minggu</div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Pesanan</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topPekerja)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 20px;">Belum ada data pesanan
                                            minggu ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1;
                                    foreach ($topPekerja as $p): ?>
                                        <tr>
                                            <td><?= $no++ ?>.</td>
                                            <td style="text-transform: capitalize;"><?= htmlspecialchars($p['nama_lengkap']) ?>
                                            </td>
                                            <td><?= $p['jumlah_pesanan'] ?></td>
                                            <td>
                                                <span style="color: #ffb400;">★</span>
                                                <?= number_format($p['rata_rating'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="profileModal">
        <div class="modal-card">
            <div class="modal-header">
                <div id="closeModalBtn"></div>
                <div class="modal-title">Profile</div>
            </div>

            <img id="adminAvatar" src="<?= $photoUrl ?>" alt="Loading..." class="modal-avatar">
            <div id="adminName" class="modal-name">Memuat...</div>
            <div id="adminRole" class="modal-role">Memuat...</div>

            <svg class="logout-icon" viewBox="0 0 24 24" onclick="window.location.href='function/logoutAdmin.php'">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', async function () {
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
        });

        const ctx = document.getElementById('orderChart').getContext('2d');

        const chartLabels = <?= $jsonLabels ?>;
        const chartDataValues = <?= $jsonValues ?>;

        const orderChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Jumlah Order',
                    data: chartDataValues,
                    borderColor: '#3b68ff',
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b68ff',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10,
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 9
                            }
                        },
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        });

        const profileBtn = document.getElementById('profileBtn');
        const profileModal = document.getElementById('profileModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        profileBtn.addEventListener('click', () => profileModal.classList.add('active'));
        closeModalBtn.addEventListener('click', () => profileModal.classList.remove('active'));
        profileModal.addEventListener('click', (e) => {
            if (e.target === profileModal) profileModal.classList.remove('active');
        });
    </script>
</body>

</html>