<?php
session_start();
require '../../dbconnection/dbconnection.php'; // Sesuaikan dengan path koneksi kamu

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../../login.php");
    exit;
}

$id_user_login = $_SESSION['user_id'];


// Query JOIN untuk mengambil data alamat + nama & no_hp dari tabel user
$stmt = $conn->prepare(" SELECT a.*, u.nama_lengkap, u.no_wa 
    FROM master_alamat a 
    JOIN master_user u ON a.id_users = u.id_users 
    WHERE a.id_users = ? AND a.is_deleted = 0 
    ORDER BY a.id_alamat DESC
");
$stmt->bind_param("s", $id_user_login);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Alamat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/components/sc1-alert.css">
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <script src="../../assets/components/sc1-alert.js"></script>
    <style>
        /* --- CSS TETAP SAMA SEPERTI SEBELUMNYA --- */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        .mobile-container {
            max-width: 480px;
            margin: 0 auto;
            background: #f4f6f9;
            min-height: 100vh;
            position: relative;
            padding-bottom: 80px;
        }

        .header {
            display: flex;
            align-items: center;
            padding: 1.5rem 1rem;
            position: relative;
        }

        .back-btn {
            background: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 1.2rem;
            color: #666;
        }

        .title-pill {
            background: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1rem;
            margin-left: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .address-list {
            padding: 0 1rem;
        }

        .address-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: 0.3s;
            border: 2px solid transparent;
        }

        .address-card.selected {
            border-color: #7b61ff;
            background: #f9f8ff;
        }

        .custom-radio {
            margin-right: 1rem;
            margin-top: 0.2rem;
            cursor: pointer;
        }

        .custom-radio input {
            display: none;
        }

        .radio-mark {
            width: 18px;
            height: 18px;
            border: 2px solid #ccc;
            border-radius: 50%;
            display: inline-block;
            position: relative;
        }

        .custom-radio input:checked+.radio-mark {
            border-color: #7b61ff;
        }

        .custom-radio input:checked+.radio-mark::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 12px;
            height: 12px;
            background: #7b61ff;
            border-radius: 50%;
        }

        .address-info {
            flex: 1;
        }

        .address-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0 0 0.3rem 0;
            color: #000;
        }

        .address-phone {
            color: #4a67ff;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .address-detail {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
            line-height: 1.4;
        }

        .btn-delete {
            background: transparent;
            border: none;
            color: #ccc;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: 0.2s;
        }

        .btn-delete:hover {
            color: #ff4d4d;
        }

        .bottom-action {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            padding: 1rem;
            background: #f4f6f9;
            box-sizing: border-box;
            text-align: center;
        }

        .btn-add-address {
            width: 100%;
            padding: 1rem;
            background: transparent;
            border: 2px solid #4a67ff;
            color: #4a67ff;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: block;
            box-sizing: border-box;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-card {
            background: white;
            width: 80%;
            max-width: 320px;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-cancel {
            background: #eee;
            color: #333;
            border: none;
            padding: 0.7rem;
            border-radius: 8px;
            flex: 1;
            cursor: pointer;
        }

        .btn-confirm {
            background: #ff4d4d;
            color: white;
            border: none;
            padding: 0.7rem;
            border-radius: 8px;
            flex: 1;
            cursor: pointer;
        }

        .show {
            display: flex !important;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #999;
        }
    </style>
</head>

<body>

    <div class="mobile-container">

        <div class="header">
            <a href="home-user.php" style="text-decoration: none;">
                <button class="back-btn"><i class="fa-solid fa-chevron-left"></i></button>
            </a>
            <div class="title-pill">Pilih Alamat</div>
        </div>

        <div class="address-list">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="address-card" id="card-<?= $row['id_alamat'] ?>">
                        <label class="custom-radio">
                            <input type="radio" name="selected_address" value="<?= $row['id_alamat'] ?>"
                                onchange="selectCard('<?= $row['id_alamat'] ?>')">
                            <span class="radio-mark"></span>
                        </label>
                        <div class="address-info">
                            <h4 class="address-name">
                                <?= $row['nama_lengkap'] ?>
                                <span class="address-phone">(<?= $row['no_wa'] ?>)</span>
                            </h4>
                            <p class="address-detail">
                                <strong><?= $row['nama_alamat'] ?></strong><br>
                                <?= $row['detail_alamat'] ?>, <?= $row['kelurahan'] ?>, <?= $row['kecamatan'] ?>,
                                <?= $row['kota'] ?>, <?= $row['provinsi'] ?>
                            </p>
                        </div>
                        <button class="btn-delete" onclick="openDeleteModal('<?= $row['id_alamat'] ?>')">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-location-dot fa-3x"></i>
                    <p>Belum ada alamat tersimpan.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="bottom-action">
            <a href="tambahalamat.php" class="btn-add-address">+ Tambah Alamat Baru</a>
        </div>

        <div class="modal-overlay" id="deleteModal">
            <div class="modal-card">
                <h3 style="margin:0;">Hapus Alamat</h3>
                <p style="color:#666; font-size:0.9rem;">Apakah anda yakin ingin menghapus alamat ini?</p>
                <div class="modal-actions">
                    <input type="hidden" id="idToDelete">
                    <button class="btn-cancel" onclick="closeDeleteModal()">Batal</button>
                    <button class="btn-confirm" onclick="confirmDelete()">Hapus</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        const modal = document.getElementById('deleteModal');
        const idToDeleteInput = document.getElementById('idToDelete');

        // Fungsi visual saat radio dipilih
        function selectCard(id) {
            // Beri highlight visual
            document.querySelectorAll('.address-card').forEach(card => card.classList.remove('selected'));
            document.getElementById('card-' + id).classList.add('selected');

            // Kirim ID ke server untuk disimpan di session via AJAX
            fetch('function/order/simpanAlamatSession.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Setelah tersimpan di session, balik ke halaman order
                        window.location.href = 'home-user.php?open_order=true';
                    }
                });
        }

        function openDeleteModal(id) {
            idToDeleteInput.value = id;
            modal.classList.add('show');
        }

        function closeDeleteModal() {
            modal.classList.remove('show');
        }

        function confirmDelete() {
            const id = idToDeleteInput.value;
            // Kirim ke handler hapus via fetch/ajax
            fetch('function/order/hapusAlamatHandler.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        SC1Alert.show('Berhasil menghapus alamat', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        SC1Alert.show('Gagal menghapus: ' + data.message, 'error');
                    }
                });
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeDeleteModal();
        });
    </script>

</body>

</html>