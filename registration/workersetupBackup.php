<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Setup - Yang Penting Kerja</title>
    <style>
        /* ... (Semua CSS kamu sebelumnya tetap sama) ... */
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
            background: #ebebeb;
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
            /* Support Safari */
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
        }

        .avatar svg {
            width: 60px;
            height: 60px;
            fill: #000;
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

        /* =========================================
   DESAIN KOTAK UPLOAD KTP
   ========================================= */
        .upload-box {
            position: relative;
            width: 100%;
            padding: 30px 20px;
            border: 1.5px dashed #b0b0b5;
            /* Garis putus-putus */
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        /* Efek saat kursor diarahkan ke kotak upload */
        .upload-box:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #86868b;
        }

        /* Sembunyikan input file aslinya, tapi buat ukurannya penuhi kotak */
        .upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
            /* Pastikan ada di lapisan paling atas agar bisa diklik */
        }

        /* Gambar KTP Animasi */
        .upload-box img {
            width: 110px;
            /* Sesuaikan ukuran gambar animasinya */
            margin-bottom: 15px;
            pointer-events: none;
            /* Biar klik tembus ke input file */
        }

        .upload-box .upload-text {
            font-size: 14px;
            color: #86868b;
            font-weight: 500;
            pointer-events: none;
        }

        /* Tombol Lanjut */
        button.btn-submit {
            width: 100%;
            padding: 16px;
            background: #3b68ff;
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

        /* Tambahkan CSS Alert iOS dari halaman sebelumnya */
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
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#000" stroke="none" />
            </svg>
        </div>

        <h2>Worker Setup</h2>
        <p class="subtitle">Upload KTP Anda</p>

        <form id="formUploadKtp">
            <div class="upload-box">
                <input type="file" name="ktp_image" id="ktpInput" accept="image/png, image/jpeg, image/jpg" required>

                <img id="ktpPreview" src="/assets/img/logo/ktpanimasi.png" alt="Ilustrasi KTP">

                <span class="upload-text" id="uploadText">Klik untuk pilih gambar</span>
            </div>

            <div id="ktpAlert" class="alert-message"></div>

            <button type="submit" class="btn-submit" id="btnSubmit">Lanjut</button>
        </form>
    </div>

    <script>
        // 1. Fitur Image Preview
        const fileInput = document.getElementById('ktpInput');
        const imgPreview = document.getElementById('ktpPreview');
        const uploadText = document.getElementById('uploadText');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Tampilkan preview gambar
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    // Sesuaikan style agar foto terlihat pas di kotak
                    imgPreview.style.width = '100%';
                    imgPreview.style.height = '120px';
                    imgPreview.style.objectFit = 'cover';
                    imgPreview.style.borderRadius = '8px';
                    uploadText.textContent = file.name;
                }
                reader.readAsDataURL(file);
            }
        });

        // 2. Fetch API untuk Upload (Background Process)
        document.getElementById('formUploadKtp').addEventListener('submit', async function(e) {
            e.preventDefault();

            const alertBox = document.getElementById('ktpAlert');
            const submitBtn = document.getElementById('btnSubmit');

            alertBox.className = 'alert-message'; // Reset alert

            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = "Mengunggah...";
            submitBtn.disabled = true;

            const formData = new FormData(this); // Otomatis mengambil file dari form

            try {
                // Arahkan ke file handler PHP
                const response = await fetch('function/workerSetupHandler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    alertBox.textContent = "Berhasil! Mengalihkan...";
                    alertBox.className = 'alert-message alert-success';
                    setTimeout(() => {
                        window.location.replace(data.redirect);
                    }, 1000);
                } else {
                    alertBox.textContent = data.message;
                    alertBox.className = 'alert-message alert-error';
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                alertBox.textContent = "Terjadi kesalahan pada server.";
                alertBox.className = 'alert-message alert-error';
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    </script>
</body>

</html>