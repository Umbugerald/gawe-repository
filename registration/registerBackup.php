<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Peran - Yang Penting Kerja</title>
    <style>
        /* Reset & Base Font ala iOS */
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

        .avatar svg { width: 60px; height: 60px; fill: #000; }

        h2 { font-size: 22px; font-weight: 700; color: #1d1d1f; margin-bottom: 8px; }
        p.subtitle { font-size: 14px; color: #515154; margin-bottom: 30px; }

        .role-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 30px;
        }

        .role-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 48%;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .role-option input[type="radio"] { display: none; }

        .role-icon-box {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #ffffff;
            border: 1px solid #d1d1d6;
            border-radius: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .role-icon-box svg {
            width: 65px; height: 65px;
            stroke: #1d1d1f; fill: none; stroke-width: 1.5;
            stroke-linecap: round; stroke-linejoin: round;
            transition: stroke 0.3s ease;
        }

        .role-text { font-size: 15px; font-weight: 600; color: #1d1d1f; }

        /* Checked Efek */
        .role-option input[type="radio"]:checked+.role-icon-box {
            background: #658cc6;
            border-color: #658cc6;
            box-shadow: 0 4px 15px rgba(101, 140, 198, 0.4);
        }
        .role-option input[type="radio"]:checked+.role-icon-box svg { stroke: #ffffff; }

        /* Tombol */
        button.btn-next {
            width: 100%; padding: 16px;
            background: #3b68ff; color: white; border: none;
            border-radius: 20px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 104, 255, 0.3);
        }
        button.btn-next:hover { background: #1a4ce6; transform: translateY(-2px); }
        button.btn-next:active { transform: translateY(0); }
        button.btn-next:disabled { background: #99aaff; cursor: not-allowed; transform: translateY(0); }

        /* =========================================
           STYLING ALERT MESSAGE (TEMA iOS)
           ========================================= */
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

        <h2>Register</h2>
        <p class="subtitle">Pilih peran Anda</p>

        <form method="POST">
            <div class="role-container">
                <label class="role-option">
                    <input type="radio" name="peran" value="pekerja" required>
                    <div class="role-icon-box">
                        <svg viewBox="0 0 24 24">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <rect x="13" y="14" width="9" height="6" rx="1"></rect>
                            <path d="M15 14v-2a2 2 0 0 1 4 0v2"></path>
                        </svg>
                    </div>
                    <span class="role-text">Pekerja</span>
                </label>

                <label class="role-option">
                    <input type="radio" name="peran" value="pelanggan" checked required>
                    <div class="role-icon-box">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <span class="role-text">Pelanggan</span>
                </label>
            </div>

            <div id="roleAlert" class="alert-message"></div>
            
            <button type="submit" class="btn-next" name="lanjut">Lanjut</button>
        </form>
    </div>

    <script>
        async function pilihRole(roleTerpilih) {
            const alertBox = document.getElementById('roleAlert');
            const submitBtn = document.querySelector('.btn-next');

            // Reset alert: Sembunyikan setiap kali tombol ditekan
            alertBox.className = 'alert-message'; 
            
            // Ubah teks tombol
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = "Memproses...";
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('role', roleTerpilih);

            try {
                const response = await fetch('function/roleHandler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // Berhasil? Langsung pindah halaman
                    window.location.href = data.redirect;
                } else {
                    // Munculkan Alert Merah
                    alertBox.textContent = data.message;
                    alertBox.className = 'alert-message alert-error';
                    
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                
                // Munculkan Alert Merah untuk error server
                alertBox.textContent = "Terjadi kesalahan koneksi ke server.";
                alertBox.className = 'alert-message alert-error';

                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault(); 
            const roleTerpilih = document.querySelector('input[name="peran"]:checked').value;
            pilihRole(roleTerpilih);
        });
    </script>
</body>
</html>