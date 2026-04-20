<?php
session_start();

require '../../dbconnection/dbconnection.php'; // Sesuaikan dengan path koneksi kamu

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../../login.php");
    exit;
}

$id_user_login = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Alamat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/components/sc1-alert.css">
    <script src="../../assets/components/sc1-alert.js"></script>
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
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
            text-decoration: none;
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

        .form-section {
            padding: 0 1rem;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .form-card-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            color: #000;
        }

        .custom-input {
            width: 100%;
            border: none;
            border-bottom: 1px solid #ddd;
            padding: 0.8rem 0 0.5rem 0;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #333;
            font-family: inherit;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
        }

        .custom-input::placeholder {
            color: #aaa;
        }

        .custom-input:focus {
            border-bottom: 1px solid #4a67ff;
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
            z-index: 1000;
        }

        .btn-save {
            width: 100%;
            padding: 1rem;
            background-color: #3b34cc;
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
        }

        select.custom-input {
            background-color: transparent;
            cursor: pointer;
            color: #333;
            appearance: auto;
        }

        select.custom-input:disabled {
            color: #aaa;
            cursor: not-allowed;
        }

        .success {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            width: 320px;
            padding: 12px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: start;
            background: #EDFBD8;
            border-radius: 8px;
            border: 1px solid #84D65A;
            box-shadow: 0px 0px 5px -3px #111;
        }

        .success__icon {
            width: 20px;
            height: 20px;
            transform: translateY(-2px);
            margin-right: 8px;
        }

        .success__icon path {
            fill: #84D65A;
        }

        .success__title {
            font-weight: 500;
            font-size: 14px;
            color: #2B641E;
        }

        .success__close {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin-left: auto;
        }

        .success__close path {
            fill: #2B641E;
        }
    </style>
</head>

<body>

    <div class="mobile-container">

        <div class="header">
            <a href="pilihalamat.php" class="back-btn"><i class="fa-solid fa-chevron-left"></i></a>
            <div class="title-pill">Tambah Alamat</div>
        </div>

        <div class="form-section">

            <form id="formAlamat">
                <div class="form-card">
                    <h3 class="form-card-title">Nama Penerima</h3>
                    <input type="text" name="nama_penerima" id="nama_penerima" class="custom-input"
                        placeholder="Nama Lengkap" required>
                </div>

                <div class="form-card">
                    <h3 class="form-card-title">Alamat</h3>
                    <select id="provinsi" name="provinsi" class="custom-input" required>
                        <option value="" disabled selected>Pilih Provinsi</option>
                    </select>
                    <select id="kota" name="kota" class="custom-input" required>
                        <option value="" disabled selected>Pilih Kota/Kabupaten</option>
                    </select>
                    <select id="kecamatan" name="kecamatan" class="custom-input" required>
                        <option value="" disabled selected>Pilih Kecamatan</option>
                    </select>
                    <select id="kelurahan" name="kelurahan" class="custom-input" required>
                        <option value="" disabled selected>Pilih Kelurahan</option>
                    </select>
                    <input type="text" name="detail_alamat" id="detail_alamat" class="custom-input"
                        placeholder="Jln, no, rt/rw, patokan" required>
                </div>
            </form>

            <div style="margin-top:1rem;">
                <small>*Klik peta untuk menentukan lokasi</small>
                <div id="map" style="height:300px; border-radius:10px; margin-top:8px;"></div>
            </div>

            <input class="custom-input" type="hidden" id="latitude">
            <input class="custom-input" type="hidden" id="longitude">
        </div>

        <div class="bottom-action">

            <div id="custom-success-alert" class="success"
                style="display: none; margin-bottom: 12px; width: 100%; box-sizing: border-box;">
                <div class="success__icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 2C6.49 2 2 6.49 2 12C2 17.51 6.49 22 12 22C17.51 22 22 17.51 22 12C22 6.49 17.51 2 12 2ZM16.78 9.7L11.11 15.37C10.97 15.51 10.78 15.59 10.58 15.59C10.38 15.59 10.19 15.51 10.05 15.37L7.22 12.54C6.93 12.25 6.93 11.77 7.22 11.48C7.51 11.19 7.99 11.19 8.28 11.48L10.58 13.78L15.72 8.64C16.01 8.35 16.49 8.35 16.78 8.64C17.07 8.93 17.07 9.4 16.78 9.7Z">
                        </path>
                    </svg>
                </div>
                <div class="success__title" id="success-alert-text">Alamat Berhasil Disimpan! Mengalihkan...</div>
                <div class="success__close"
                    onclick="document.getElementById('custom-success-alert').style.display='none'">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z">
                        </path>
                    </svg>
                </div>
            </div>

            <button class="btn-save">Simpan</button>
        </div>

    </div>

    <script>

        const apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';

        const provSelect = document.getElementById('provinsi');
        const kotaSelect = document.getElementById('kota');
        const kecSelect = document.getElementById('kecamatan');
        const kelSelect = document.getElementById('kelurahan');

        fetch(`${apiBase}/provinces.json`)
            .then(response => response.json())
            .then(provinces => {
                provinces.forEach(prov => {
                    let option = document.createElement('option');
                    option.value = prov.id;
                    option.text = prov.name;
                    provSelect.appendChild(option);
                });
            });

        provSelect.addEventListener('change', (e) => {
            const idProv = e.target.value;

            kotaSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';
            kecSelect.innerHTML = '<option value="" disabled selected>Pilih Kecamatan</option>';
            kelSelect.innerHTML = '<option value="" disabled selected>Pilih Kelurahan</option>';
            kecSelect.disabled = true;
            kelSelect.disabled = true;

            fetch(`${apiBase}/regencies/${idProv}.json`)
                .then(response => response.json())
                .then(regencies => {
                    kotaSelect.innerHTML = '<option value="" disabled selected>Pilih Kota/Kabupaten</option>';
                    kotaSelect.disabled = false;
                    regencies.forEach(kota => {
                        let option = document.createElement('option');
                        option.value = kota.id;
                        option.text = kota.name;
                        kotaSelect.appendChild(option);
                    });
                });
            updateMapToSelection();
        });

        kotaSelect.addEventListener('change', (e) => {
            const idKota = e.target.value;

            kecSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';
            kelSelect.innerHTML = '<option value="" disabled selected>Pilih Kelurahan</option>';
            kelSelect.disabled = true;

            fetch(`${apiBase}/districts/${idKota}.json`)
                .then(response => response.json())
                .then(districts => {
                    kecSelect.innerHTML = '<option value="" disabled selected>Pilih Kecamatan</option>';
                    kecSelect.disabled = false;
                    districts.forEach(kec => {
                        let option = document.createElement('option');
                        option.value = kec.id;
                        option.text = kec.name;
                        kecSelect.appendChild(option);
                    });
                });
            updateMapToSelection();
        });

        kecSelect.addEventListener('change', (e) => {
            const idKec = e.target.value;
            kelSelect.innerHTML = '<option value="" disabled selected>Loading...</option>';

            fetch(`${apiBase}/villages/${idKec}.json`)
                .then(response => response.json())
                .then(villages => {
                    kelSelect.innerHTML = '<option value="" disabled selected>Pilih Kelurahan</option>';
                    kelSelect.disabled = false;
                    villages.forEach(kel => {
                        let option = document.createElement('option');
                        option.value = kel.id;
                        option.text = kel.name;
                        kelSelect.appendChild(option);
                    });
                });
            updateMapToSelection();
        });

        kelSelect.addEventListener('change', () => {

            updateMapToSelection();
        });

        document.querySelector('.btn-save').addEventListener('click', function () {
            const form = document.getElementById('formAlamat');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const btn = this;
            const formData = new FormData();

            const provText = provSelect.selectedIndex > 0 ? provSelect.options[provSelect.selectedIndex].text : '';
            const kotaText = kotaSelect.selectedIndex > 0 ? kotaSelect.options[kotaSelect.selectedIndex].text : '';
            const kecText = kecSelect.selectedIndex > 0 ? kecSelect.options[kecSelect.selectedIndex].text : '';
            const kelText = kelSelect.selectedIndex > 0 ? kelSelect.options[kelSelect.selectedIndex].text : '';

            if (!provText || !kotaText || !kecText || !kelText) {
                SC1Alert.show('Harap pilih provinsi, kota, kecamatan, dan kelurahan dengan benar!', 'warning');
                return;
            }

            formData.append('nama_alamat', document.getElementById('nama_penerima').value);
            formData.append('provinsi', provText);
            formData.append('kota', kotaText);
            formData.append('kecamatan', kecText);
            formData.append('kelurahan', kelText);
            formData.append('detail_alamat', document.getElementById('detail_alamat').value);
            formData.append('latitude', document.getElementById('latitude').value);
            formData.append('longitude', document.getElementById('longitude').value);

            btn.disabled = true;
            btn.innerText = "Menyimpan...";

            fetch('function/order/tambahAlamatHandler.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {

                        document.getElementById('custom-success-alert').style.display = 'flex';

                        setTimeout(() => {
                            window.location.href = 'pilihalamat.php';
                        }, 1500);

                    } else {
                        SC1Alert.show('Gagal: ' + data.message, 'error');
                        btn.disabled = false;
                        btn.innerText = "Simpan";
                    }
                })
                .catch(err => {
                    console.error(err);
                    SC1Alert.show('Terjadi kesalahan pada server.', 'error');
                    btn.disabled = false;
                    btn.innerText = "Simpan";
                });
        });

        var map = L.map('map').setView([-6.200000, 106.816666], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker;

        map.on('click', function (e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            if (marker) {
                map.removeLayer(marker);
            }

            marker = L.marker([lat, lng]).addTo(map);

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            console.log("Lat:", lat, "Lng:", lng);
        });

        function bersihkanTeksWilayah(teks) {
            if (!teks) return '';
            let hasil = teks.replace('KABUPATEN ', '');
            hasil = hasil.replace('KOTA ADMINISTRASI ', '');
            hasil = hasil.replace('KOTA ', '');

            return hasil.trim();
        }

        let mapUpdateTimer;

        function updateMapToSelection() {

            clearTimeout(mapUpdateTimer);

            mapUpdateTimer = setTimeout(() => {
                let provText = provSelect.selectedIndex > 0 ? provSelect.options[provSelect.selectedIndex].text : '';
                let kotaText = kotaSelect.selectedIndex > 0 ? kotaSelect.options[kotaSelect.selectedIndex].text : '';
                let kecText = kecSelect.selectedIndex > 0 ? kecSelect.options[kecSelect.selectedIndex].text : '';
                let kelText = kelSelect.selectedIndex > 0 ? kelSelect.options[kelSelect.selectedIndex].text : '';

                if (typeof bersihkanTeksWilayah === 'function') {
                    provText = bersihkanTeksWilayah(provText);
                    kotaText = bersihkanTeksWilayah(kotaText);
                    kecText = bersihkanTeksWilayah(kecText);
                    kelText = bersihkanTeksWilayah(kelText);
                }

                let queryArray = [];
                if (kelText) queryArray.push(kelText);
                if (kecText) queryArray.push(kecText);
                if (kotaText) queryArray.push(kotaText);
                if (provText) queryArray.push(provText);

                if (queryArray.length === 0) return;

                const searchQuery = queryArray.join(', ') + ', Indonesia';
                console.log("Mencari di map:", searchQuery);

                const myEmail = "email_kamu@gmail.com";
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&limit=1&email=${myEmail}`;

                fetch(url, {
                    headers: {
                        "Accept-Language": "id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7"
                    }
                })
                    .then(res => {
                        if (!res.ok) throw new Error("Server peta menolak request (Mungkin masih kena limit)");
                        return res.json();
                    })
                    .then(data => {
                        if (data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);

                            map.flyTo([lat, lng], 13);

                            if (marker) map.removeLayer(marker);
                            marker = L.marker([lat, lng]).addTo(map);

                            document.getElementById('latitude').value = lat;
                            document.getElementById('longitude').value = lng;
                        } else {
                            console.warn("Waduh, Map tidak bisa menemukan lokasi ini:", searchQuery);
                        }
                    })
                    .catch(err => console.error("Gagal memuat titik map:", err.message));

            }, 800);
        }
    </script>

</body>

</html>