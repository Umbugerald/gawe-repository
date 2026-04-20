<?php
session_start();

// 1. Kalau belum login, tendang ke signup
if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php");
    exit;
}

// 2. (Opsional tapi aman) Kalau dia pekerja, pastikan sudah upload KTP dulu
if (isset($_SESSION['role']) && $_SESSION['role'] === 'pekerja') {
    if (!isset($_SESSION['ktp_selesai']) || $_SESSION['ktp_selesai'] !== true) {
        header("Location: worker-setup.php");
        exit;
    }
}

// 3. Kalau dia SUDAH upload foto profil (sudah selesai semua)
if (isset($_SESSION['setup_selesai']) && $_SESSION['setup_selesai'] === true) {
    if ($_SESSION['role'] === 'pelanggan') {
        header("Location: ../../dashboard/user/home-user.php"); // Sesuaikan path-nya
    } else {
        header("Location: ../../dashboard/worker/waiting-confirmation.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/logo/gaweicon.png">
    <link rel="stylesheet" href="../assets/components/sc1-alert.css">
    <script src="../assets/components/sc1-alert.js"></script>
    <title>Setup Profile - Yang Penting Kerja</title>
    <style>
        /* Reset & Base Font ala iOS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* Background Abu-abu lembut */
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1c19b0, #abb5e9, #dceaf6, #2584c9);
            background-size: cover;
            background-attachment: fixed;
        }

        /* Container Glassmorphism */
        .glass-card {
            position: relative;
            width: 90%;
            max-width: 360px;
            padding: 60px 30px 40px;
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 28px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Foto Profil Melayang di Atas */
        .avatar {
            position: absolute;
            top: -45px;
            left: 50%;
            transform: translateX(-50%);
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 3px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            /* PENTING: Supaya foto preview terpotong bulat */
        }

        .avatar svg {
            width: 60px;
            height: 60px;
            fill: #000;
        }

        /* CSS untuk gambar preview */
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            /* Sembunyikan awalnya */
        }

        /* Tipografi */
        h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 8px;
        }

        p.subtitle {
            font-size: 14px;
            color: #515154;
            margin-bottom: 30px;
        }

        /* DESAIN KOTAK UPLOAD FOTO */
        .upload-box {
            position: relative;
            width: 100%;
            padding: 40px 20px;
            border: 1.5px dashed #b0b0b5;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.4);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .upload-box:hover {
            background: rgba(255, 255, 255, 0.7);
            border-color: #86868b;
        }

        .upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .upload-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
            pointer-events: none;
        }

        .upload-text {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            pointer-events: none;
        }

        /* Tombol Konfirmasi */
        button.btn-submit {
            width: 100%;
            padding: 16px;
            background: #2b5cff;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 104, 255, 0.3);
        }

        button.btn-submit:hover {
            background: #1a4ce6;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(43, 92, 255, 0.4);
        }

        button.btn-submit:active {
            transform: translateY(0);
        }

        .alert-message {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 14px;
            text-align: center;
            display: none;
            animation: fadeInAlert 0.4s ease-out forwards;
        }

        .alert-error {
            background-color: rgba(255, 59, 48, 0.1);
            border: 1px solid rgba(255, 59, 48, 0.3);
            color: #d93025;
            display: block;
        }

        .alert-success {
            background-color: rgba(52, 199, 89, 0.1);
            border: 1px solid rgba(52, 199, 89, 0.3);
            color: #248a3d;
            display: block;
        }

        @keyframes fadeInAlert {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="glass-card">
        <div class="avatar">
            <svg id="default-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#000" stroke="none" />
            </svg>
            <img id="preview-image" src="" alt="Preview">
        </div>

        <h2>Setup</h2>
        <p class="subtitle">Unggah Foto Profile Anda</p>

        <form id="profileForm">

            <div class="upload-box">
                <input type="file" name="profile_photo" id="file-input" accept="image/*" required>

                <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h4" />
                    <polyline points="14 2 14 8 20 8" />
                    <path d="M22 16.5a3.5 3.5 0 0 0-6-2.4 3 3 0 0 0-5.4 1.4A2.5 2.5 0 0 0 12 20.5h8.5a2.5 2.5 0 0 0 1.5-4z" />
                </svg>

                <span class="upload-text" id="file-name-text">Click to upload image</span>
            </div>
            <div id="profileAlert" class="alert-message"></div>
            <button type="submit" class="btn-submit" id="submitBtn">Konfirmasi</button>
        </form>
    </div>

    <script>
        const fileInput = document.getElementById('file-input');
        const previewImage = document.getElementById('preview-image');
        const defaultIcon = document.getElementById('default-icon');
        const fileNameText = document.getElementById('file-name-text');
        const profileForm = document.getElementById('profileForm');
        const submitBtn = document.getElementById('submitBtn');
        const profileAlert = document.getElementById('profileAlert');

        // 🔥 FUNCTION ALERT
        function showAlert(message, type = 'error') {
            profileAlert.textContent = message;
            profileAlert.className = 'alert-message';

            if (type === 'success') {
                profileAlert.classList.add('alert-success');
            } else {
                profileAlert.classList.add('alert-error');
            }

            profileAlert.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }

        // 1. LIVE PREVIEW GAMBAR
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                fileNameText.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    defaultIcon.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // 2. SUBMIT FORM
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            submitBtn.textContent = 'Mengunggah...';
            submitBtn.disabled = true;

            const formData = new FormData(this);
            const fileInput = this.querySelector('input[type="file"]');
            // Validasi file sisi klien (seperti yang kita bahas sebelumnya)
            if (fileInput && fileInput.files.length > 0) {
                const file = fileInput.files[0]; // Ambil file-nya

                // 1. Cek Ukuran
                if (file.size > 2 * 1024 * 1024) {
                    await SC1Alert.show('Ukuran file terlalu besar! Maksimal 2 MB.', 'error');
                    submitBtn.textContent = 'Konfirmasi';
                    submitBtn.disabled = false;
                    return;
                }

                // 2. Cek Tipe (Gunakan file.type, bukan fileInput.type)
                if (!file.type.startsWith('image/')) {
                    await SC1Alert.show('Format file tidak didukung! Harap unggah file gambar (JPG/PNG).', 'error');
                    fileInput.value = ''; // Reset input
                    submitBtn.textContent = 'Konfirmasi';
                    submitBtn.disabled = false;
                    return;
                }
            } else {
                await SC1Alert.show('Harap pilih file terlebih dahulu.', 'warning');
                return;
            }

            try {
                const response = await fetch('function/profileHandler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Gunakan SC1Alert
                    await SC1Alert.show('Profil berhasil diperbarui!', 'success');
                    window.location.href = result.redirect;
                } else {
                    // Gunakan SC1Alert untuk error
                    await SC1Alert.show(result.message, 'error');
                    submitBtn.textContent = 'Konfirmasi';
                    submitBtn.disabled = false;
                }

            } catch (error) {
                await SC1Alert.show('Terjadi kesalahan koneksi ke server.', 'error');
                submitBtn.textContent = 'Konfirmasi';
                submitBtn.disabled = false;
            }
        });
    </script>

</body>

</html>