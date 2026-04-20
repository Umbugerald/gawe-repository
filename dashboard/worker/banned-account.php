<?php
error_reporting(0); // Sembunyikan semua error dari pandangan user
ini_set('display_errors', 0);
session_start();
require '../../dbconnection/dbconnection.php'; // Sesuaikan path koneksi database

// Jika tidak ada session, tendang ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// Cek status terbaru di database
$query = mysqli_query($conn, "SELECT status_kerja FROM profile_pekerja WHERE id_users = '$id_user'");
$data = mysqli_fetch_assoc($query);

// JIKA SUDAH AKTIF, LANGSUNG PINDAH
if ($data['status_kerja'] == 'active') {
    header("Location: board-worker.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="10">
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Blokir</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1c19b0, #abb5e9, #dceaf6, #2584c9);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 450px;
            width: 90%;
        }


        h1 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #222;
        }

        p {
            color: #777;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #fff3e0;
            color: #e40000;
            border-radius: 50px;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .btn-logout {
            display: inline-block;
            text-decoration: none;
            color: #888;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .btn-logout:hover {
            color: #da0000;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="status-badge">
            <i class="fa-solid fa-ban"></i> Akun Diblokir
        </div>
        <h1>Akun Anda Telah Diblokir</h1>
        <p>Akun telah diblokir oleh admin karena tidak memenuhi ketentuan yang berlaku.</p>

        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">

        <a href="function/logoutWorker.php" class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar
        </a>
    </div>

    <script>
        // Opsional: Cek via AJAX setiap 5 detik agar lebih "smooth" tanpa reload seluruh halaman
        /*
        setInterval(function(){
            fetch('check-status.php')
            .then(res => res.json())
            .then(data => {
                if(data.status === 'active') window.location.href = 'board-worker.php';
            });
        }, 5000);
        */
    </script>
</body>

</html>