<?php
session_start();
require '../../../../dbconnection/dbconnection.php'; 

$id_users = $_SESSION['user_id'];

$sqlCleanup = "UPDATE `orders` SET status = 'dibatalkan_sistem' WHERE status = 'mencari' AND created_at <= (NOW() - INTERVAL 5 MINUTE)";
mysqli_query($conn, $sqlCleanup);

$sqlMencari = "SELECT 
    o.*, 
    (
        SELECT no_wa 
        FROM master_user 
        WHERE role = 'admin' 
        LIMIT 1
    ) as no_wa_admin,
    TIMESTAMPDIFF(SECOND, NOW(), o.created_at + INTERVAL 5 MINUTE) AS sisa_detik 
FROM orders o 
WHERE o.id_user_pelanggan = '$id_users' 
AND o.status = 'mencari' 
ORDER BY o.id_order DESC";
$resMencari = mysqli_query($conn, $sqlMencari);
$dataMencariValid = [];
if ($resMencari && mysqli_num_rows($resMencari) > 0) {
    while ($row = mysqli_fetch_assoc($resMencari)) {
        if ((int) $row['sisa_detik'] > 0) {
            $dataMencariValid[] = $row;
        }
    }
}

$sqlProses = "SELECT 
    o.*, 
    u.nama_lengkap as nama_pekerja,
    (
        SELECT no_wa 
        FROM master_user 
        WHERE role = 'admin' 
        LIMIT 1
    ) as no_wa_admin
FROM orders o 
LEFT JOIN master_user u 
    ON o.id_users_pekerja = u.id_users
WHERE o.id_user_pelanggan = '$id_users' 
AND o.status IN ('diterima', 'sampai', 'proses') 
ORDER BY o.id_order DESC";
$resProses = mysqli_query($conn, $sqlProses);

$sqlSelesai = "SELECT o.*, u.nama_lengkap as nama_pekerja, u.no_wa, CASE WHEN r.id_report IS NOT NULL THEN 1 ELSE 0 END as is_reported FROM `orders` o LEFT JOIN master_user u ON o.id_users_pekerja = u.id_users LEFT JOIN report r ON o.id_order = r.id_order WHERE o.id_user_pelanggan = '$id_users' AND o.status = 'selesai' ORDER BY o.id_order DESC";
$resSelesai = mysqli_query($conn, $sqlSelesai);

ob_start();
if (!empty($dataMencariValid)):
    foreach ($dataMencariValid as $row): ?>
        <div class="card-box riwayat-card mencari-card" id="pelanggan-card-<?= $row['id_order'] ?>"
            data-sisa="<?= $row['sisa_detik'] ?>" data-id="<?= $row['id_order'] ?>">
            <img src="../../assets/img/uploads/keluhan/<?= $row['foto_keluhan'] ?>" alt="Foto" class="riwayat-img">
            <div class="riwayat-info">
                <div class="riwayat-header">
                    <span class="riwayat-pekerja" id="pekerja-status-<?= $row['id_order'] ?>">Pekerja : <span
                            style="color: #ff9800;">Mencari...</span></span>
                    <span class="riwayat-status" id="badge-status-<?= $row['id_order'] ?>"
                        style="background: #fff3e0; border: 1px solid #ff9800; color: #d32f2f;">
                        Sisa: <span id="pelanggan-timer-<?= $row['id_order'] ?>" style="font-weight: bold;">Menghitung...</span>
                    </span>
                </div>
                <h4 class="riwayat-title"><?= htmlspecialchars($row['keluhan']) ?></h4>
                <p class="riwayat-desc"><?= htmlspecialchars($row['detail_keluhan']) ?></p>
            </div>
        </div>
    <?php endforeach;
else: ?>
    <p class="empty-state">Belum ada order dengan status mencari (atau waktu sudah habis).</p>
    <?php endif;
$htmlMencari = ob_get_clean();

ob_start();
if (mysqli_num_rows($resProses) > 0):
    while ($row = mysqli_fetch_assoc($resProses)):
        $statusText = '';
        if ($row['status'] == 'diterima') {
            $statusText = 'Driver menuju ke lokasi';
        } elseif ($row['status'] == 'sampai') {
            $statusText = 'Driver sudah sampai tujuan';
        } elseif ($row['status'] == 'proses') {
            $statusText = 'Sedang mengerjakan';
        }
    ?>
        <div class="card-box riwayat-card">
            <img src="../../assets/img/uploads/keluhan/<?= $row['foto_keluhan'] ?>" class="riwayat-img">
            <div class="riwayat-info">
                <div class="riwayat-header">
                    <span class="riwayat-pekerja">Pekerja : <span
                            style="color: #4f46e5;"><?= htmlspecialchars($row['nama_pekerja']) ?></span></span>
                    <span class="riwayat-status" style="background: #e3f2fd; color: #1976d2; text-align: right;">
                        <strong>Proses</strong><br>
                        <small style="font-size: 0.8em;"><?= $statusText ?></small>
                    </span>
                </div>
                <h4 class="riwayat-title"><?= htmlspecialchars($row['keluhan']) ?></h4>
                <p class="riwayat-desc"><?= htmlspecialchars($row['detail_keluhan']) ?></p>

                <a id="btnHubungiWA" target="_blank" data-admin="<?= htmlspecialchars($row['no_wa_admin']) ?>" class="btn-hubungi" style="display: inline-flex;
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
                    margin-top:20px">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 18px;
                    height: 18px;
                    fill: currentColor;">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z">
                        </path>
                    </svg>
                    Hubungi
                </a>

            </div>
        </div>
    <?php endwhile;
else: ?>
    <p class="empty-state">Tidak ada pekerjaan yang sedang diproses.</p>
    <?php endif;
$htmlProses = ob_get_clean();

ob_start();
if (mysqli_num_rows($resSelesai) > 0):
    while ($row = mysqli_fetch_assoc($resSelesai)): ?>
        <div class="card-box riwayat-card">
            <img src="../../assets/img/uploads/keluhan/<?= $row['foto_keluhan'] ?>" class="riwayat-img">
            <div class="riwayat-info">
                <div class="riwayat-header">
                    <span class="riwayat-pekerja">Pekerja : <span
                            style="color: #4f46e5;"><?= htmlspecialchars($row['nama_pekerja']) ?></span></span>
                    <span class="riwayat-pekerja">No Pekerja : <span
                            style="color: #4f46e5;"><?= htmlspecialchars($row['no_wa']) ?></span></span>
                    <span class="riwayat-status" style="background: #e8f5e9; color: #2e7d32;">
                        <strong>Selesai</strong><br>
                        <small style="font-size: 0.8em;">Orderan Selesai</small>
                    </span>
                </div>
                <h4 class="riwayat-title"><?= htmlspecialchars($row['keluhan']) ?></h4>
                <div class="riwayat-actions">
                    <?php if ($row['is_reported'] == 1): ?>
                        <button class="btn-lapor" disabled
                            style="background-color: #cccccc; cursor: not-allowed; color: #666666;">Sudah Dilaporkan</button>
                    <?php else: ?>
                        <button class="btn-lapor" data-id="<?= $row['id_order'] ?>">Lapor</button>
                    <?php endif; ?>

                    <?php if ($row['is_rated'] == 1): ?>
                        <button class="btn-nilai" disabled
                            style="background-color: #cccccc; color: #666666; cursor: not-allowed;">Sudah Dinilai</button>
                    <?php else: ?>
                        <button class="btn-nilai" data-id="<?= $row['id_order'] ?>">Nilai</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile;
else: ?>
    <p class="empty-state">Belum ada riwayat pekerjaan selesai.</p>
<?php endif;
$htmlSelesai = ob_get_clean();

header('Content-Type: application/json');
echo json_encode([
    'mencari' => $htmlMencari,
    'proses' => $htmlProses,
    'selesai' => $htmlSelesai
]);
?>