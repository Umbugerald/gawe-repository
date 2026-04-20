<?php
// session_start();
// session_destroy();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/logo/gaweicon.png">
    <title>Login - Yang Penting Kerja</title>
    <style>
        /* Reset & Base Font ala iOS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* Background dengan Gradien ala iOS untuk memunculkan efek kaca */
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* Mesh gradient lembut */
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
            /* Efek Kaca */
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

        .avatar img {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            object-fit: contain;
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

        /* Grup Input yang menyatu (seperti di gambar) dengan efek kaca */
        .input-group {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: inset 0 2px 5px rgba(255, 255, 255, 0.3);
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #1d1d1f;
            outline: none;
        }

        .input-group input::placeholder,
        .phone-wrapper input::placeholder {
            color: #86868b;
            transition: all 0.3s ease;
        }

        .input-group input:focus::placeholder,
        .phone-wrapper input:focus::placeholder {
            opacity: 0;
            transform: translateX(10px);
        }

        /* Garis pemisah antara username dan password */
        .input-group input:first-child {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        /* Link Daftar */
        .register-text {
            font-size: 13px;
            color: #515154;
            margin-bottom: 20px;
        }

        .register-text a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 600;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        /* Tombol Masuk */
        button.btn-login {
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
            box-shadow: 0 4px 15px rgba(43, 92, 255, 0.3);
        }

        button.btn-login:hover {
            background: #1a4ce6;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(43, 92, 255, 0.4);
        }

        button.btn-login:active {
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

        .alert-message.error {
            background-color: rgba(255, 59, 48, 0.1);
            border: 1px solid rgba(255, 59, 48, 0.3);
            color: #d93025;
            display: block;
        }

        .alert-message.success {
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
                <img src="/assets/img/logo/gawelogo.png" alt="Logo" onerror="this.src='https://ui-avatars.com/api/?name=GW&background=3b82f6&color=fff&rounded=true'">
        </div>

        <h2>Sign in</h2>
        <p class="subtitle">Masuk ke akun Anda.</p>

        <form >
            <div class="input-group">
                <input type="text" name="username" placeholder="nama pengguna" required>
                <input type="password" name="password" placeholder="kata sandi" required>
            </div>

            <div class="register-text">
                Belum punya akun? <a href="../registration/signup.php">Daftar</a>
            </div>
            <div id="alertBox" class="alert-message"></div>
            <button type="submit" class="btn-login" name="login">Masuk</button>
        </form>
    </div>
    <script>
        const form = document.querySelector('form');
        const alertBox = document.getElementById('alertBox');

        form.addEventListener('submit', async (e) => {
            console.log("Tombol login ditekan!");
            e.preventDefault();

            // Sembunyikan alertBox setiap kali submit baru
            alertBox.style.display = 'none';
            alertBox.classList.remove('success', 'error');

            const formData = new FormData(form);

            try {
                const response = await fetch('../function/loginHandler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Tampilkan pesan sukses di box
                    showAlert(data.message, 'success');

                    // Beri jeda 1 detik supaya user sempat baca pesan sebelum pindah halaman
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    // Tampilkan pesan gagal di box
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Terjadi kesalahan sistem atau format JSON tidak valid.', 'error');
            }
        });

        // Fungsi bantuan untuk menampilkan pesan
        function showAlert(message, type) {
            alertBox.textContent = message;
            alertBox.style.display = 'block';
            alertBox.className = 'alert-message ' + type; // nambah class 'success' atau 'error'
        }
    </script>
</body>

</html>