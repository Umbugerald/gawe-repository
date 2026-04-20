<?php
session_start();

// Kalau user sudah punya sesi (sudah login/daftar), tendang ke tahap selanjutnya
if (isset($_SESSION['user_id'])) {
    header("Location: select-role.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/img/logo/gaweicon.png">
    <title>Sign up - Yang Penting Kerja</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1c19b0, #abb5e9, #dceaf6, #2584c9);
            background-size: cover;
            background-attachment: fixed;
        }

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

        .input-group {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: inset 0 2px 5px rgba(255, 255, 255, 0.3);
            display: flex;
            flex-direction: column;
        }

        .input-group>input,
        .phone-wrapper {
            width: 100%;
            padding: 16px 20px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #1d1d1f;
            outline: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .input-group>*:last-child {
            border-bottom: none;
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

        .input-group>input:focus,
        .phone-wrapper input:focus {
            background: rgba(255, 255, 255, 0.3);
            transition: background 0.3s ease;
        }

        .phone-wrapper {
            display: flex;
            padding: 0;
            align-items: center;
        }

        .phone-prefix {
            display: flex;
            align-items: center;
            padding: 16px 15px;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            color: #515154;
            font-size: 15px;
            font-weight: 500;
            white-space: nowrap;
        }

        .phone-prefix img {
            width: 22px;
            height: 22px;
            margin-right: 8px;
            object-fit: contain;
        }

        .phone-wrapper input {
            flex: 1;
            padding: 16px 20px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #1d1d1f;
            outline: none;
        }

        .phone-wrapper input::-webkit-outer-spin-button,
        .phone-wrapper input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .phone-wrapper input[type=number] {
            -moz-appearance: textfield;
        }

        .login-text {
            font-size: 13px;
            color: #515154;
            margin-bottom: 20px;
        }

        .login-text a {
            color: #2b5cff;
            text-decoration: none;
            font-weight: 600;
        }

        .login-text a:hover {
            text-decoration: underline;
        }

        button.btn-register {
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

        button.btn-register:hover {
            background: #1a4ce6;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(43, 92, 255, 0.4);
        }

        button.btn-register:active {
            transform: translateY(0);
        }

        /* Tambahan untuk pesan Alert */
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
            <img src="/assets/img/logo/gawelogo.png" alt="Logo" onerror="this.src='https://ui-avatars.com/api/?name=GW&background=3b82f6&color=fff&rounded=true'">
        </div>

        <h2>Sign up</h2>
        <p class="subtitle">Buat akun Anda.</p>

        <form id="registerForm">
            <div class="input-group">
                <input type="text" name="nama_lengkap" placeholder="nama lengkap" required>
                <input type="text" name="username" placeholder="nama pengguna" required>
                <input type="password" id="password" name="password" placeholder="kata sandi"
                    required minlength="8"
                    title="Minimal 8 karakter, harus ada huruf besar, angka, dan simbol"
                    pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$">

                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="konfirmasi kata sandi" required>

                <div class="phone-wrapper">
                    <div class="phone-prefix">
                        <img src="/assets/img/logo/whatsapplogo.png" alt="WA">
                        <span>+62</span>
                    </div>
                    <input type="tel" name="whatsapp" placeholder="879xxxxxxx" required minlength="9" required>
                </div>
            </div>

            <div id="alertBox" class="alert-message"></div>

            <div class="login-text">
                Sudah punya akun? <a href="../login.php">Masuk</a>
            </div>

            <button type="submit" class="btn-register" id="regBtn">Daftar</button>
        </form>
    </div>

    <script>
        const confirmInput = document.getElementById("confirm_password");

        confirmInput.addEventListener("input", function() {
            const password = document.getElementById("password").value;

            if (this.value !== password) {
                this.setCustomValidity("Password tidak sama");
            } else {
                this.setCustomValidity("");
            }
        });

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const alertBox = document.getElementById("alertBox");

            if (password !== confirmPassword) {
                e.preventDefault(); // stop submit
                alertBox.innerHTML = "Konfirmasi password tidak sama!";
                alertBox.style.display = "block";
            }


            const btn = document.getElementById('regBtn');
            const formData = new FormData(this);

            btn.textContent = "Mendaftar...";
            btn.disabled = true;
            alertBox.style.display = 'none';
            alertBox.className = 'alert-message';

            try {
                // Sesuaikan URL ini dengan lokasi file registerHandler.php kamu
                const response = await fetch('function/signupHandler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    alertBox.textContent = "Pendaftaran berhasil! Mengalihkan...";
                    alertBox.classList.add('alert-success');
                    alertBox.style.display = 'block';

                    // Redirect ke halaman Worker Setup
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    alertBox.textContent = data.message;
                    alertBox.classList.add('alert-error');
                    alertBox.style.display = 'block';
                    btn.textContent = "Daftar";
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alertBox.textContent = "Terjadi kesalahan pada server.";
                alertBox.classList.add('alert-error');
                alertBox.style.display = 'block';
                btn.textContent = "Daftar";
                btn.disabled = false;
            }
        });
    </script>

</body>

</html>