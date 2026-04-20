<form id="formOrder" enctype="multipart/form-data">
    <div class="form-content">
        <div onclick="pilihAlamatDanSimpanDraft()" class="card-box address-card">
            <div class="address-flex">
                <div class="address-info">
                    <div class="address-icon">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <?php if ($dataAlamat): ?>
                        <div>
                            <input type="hidden" name="id_alamat" value="<?= $dataAlamat['id_alamat'] ?? '' ?>">
                            <h3 class="address-name"><?= $dataAlamat['nama_lengkap'] ?> <span
                                    class="address-phone">(<?= $dataAlamat['no_wa'] ?>)</span></h3>
                            <p class="address-detail">
                                <?= (!empty($dataAlamat['detail_alamat'])) . "," ? $dataAlamat['detail_alamat'] : "detail tidak ditambahkan," ?>
                                <?= $dataAlamat['kelurahan'] ?>,
                                <?= $dataAlamat['kecamatan'] ?>, <?= $dataAlamat['kota'] ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div>
                            <h3 class="address-name">Belum ada alamat</h3>
                            <p class="address-detail">Klik untuk menambahkan alamat baru</p>
                        </div>
                    <?php endif; ?>
                </div>
                <i class="fa-solid fa-chevron-right chevron-icon"></i>
            </div>
        </div>

        <div class="card-box">
            <label class="form-label">Detail Keluhan</label>
            <div class="textarea-box">
                <textarea name="detail_keluhan" id="keluhan-textarea" rows="3" maxlength="150"
                    placeholder="Contoh : Pompa Air saya tidak bisa nyala selama 2 hari..."></textarea>
                <div class="char-count-display">
                    <span id="char-count">0</span>/150
                </div>
            </div>
        </div>

        <div class="card-box">
            <label class="form-label">Upload Foto Keluhan</label>
            <label class="upload-box" id="upload-label">
                <div class="upload-content" id="upload-placeholder">
                    <div class="upload-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <span class="upload-title">Klik untuk mengunggah foto</span>
                    <span class="upload-subtitle">PNG, JPG, atau JPEG (Maks. 2MB)</span>
                </div>

                <img src="#" id="image-preview"
                    style="display: none; width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">

                <input type="file" name="foto_keluhan" id="file-input"
                    style="position: absolute; opacity: 0; width: 1px; height: 1px; overflow: hidden;" accept="image/*"
                    required>
                <input type="hidden" name="id_user_pelanggan" value="<?= $_SESSION['user_id'] ?>">
            </label>
        </div>
    </div>

    <div id="custom-success-alert" class="custom-alert-wrapper">
        <div class="success">
            <div class="success__icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M12 2C6.49 2 2 6.49 2 12C2 17.51 6.49 22 12 22C17.51 22 22 17.51 22 12C22 6.49 17.51 2 12 2ZM16.78 9.7L11.11 15.37C10.97 15.51 10.78 15.59 10.58 15.59C10.38 15.59 10.19 15.51 10.05 15.37L7.22 12.54C6.93 12.25 6.93 11.77 7.22 11.48C7.51 11.19 7.99 11.19 8.28 11.48L10.58 13.78L15.72 8.64C16.01 8.35 16.49 8.35 16.78 8.64C17.07 8.93 17.07 9.4 16.78 9.7Z">
                    </path>
                </svg>
            </div>
            <div class="success__title" id="success-alert-text">Pesanan Berhasil! Mengalihkan...</div>
            <div class="success__close" onclick="document.getElementById('custom-success-alert').style.display='none'">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z">
                    </path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bottom-bar">
        <div class="price-input-box" id="price-section">
            <span class="currency-symbol">Rp</span>
            <input type="number" id="price-input" name="harga" min="0" placeholder="Harga Tawaran" class="price-input"
                required>
        </div>

        <button class="btn-submit">
            Pesan
        </button>
    </div>
</form>
<script>
    const fileInput = document.getElementById('file-input');
    const imagePreview = document.getElementById('image-preview');
    const uploadPlaceholder = document.getElementById('upload-placeholder');

    fileInput.addEventListener('change', function() {
        const file = this.files[0];

        if (file) {
            const fileType = file.type;
            const validExtensions = ['image/jpeg', 'image/jpg', 'image/png'];
            const maxSize = 2 * 1024 * 1024;

            if (validExtensions.includes(fileType)) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    // Set sumber gambar ke hasil pembacaan file
                    imagePreview.src = e.target.result;

                    // Tampilkan gambar, sembunyikan icon/placeholder
                    imagePreview.style.display = 'block';
                    uploadPlaceholder.style.display = 'none';
                }

                reader.readAsDataURL(file);
            } else {
                SC1Alert.show('Hanya file gambar (JPG, JPEG, PNG) yang diperbolehkan!', 'warning');
                this.value = ""; // Reset input
            }

            if (file.size > maxSize) {
                SC1Alert.show('Ukuran file terlalu besar! Maksimal 2 MB.', 'error');
                this.value = "";
            }
        }
    });

    document.getElementById('formOrder').addEventListener('submit', function(e) {
        e.preventDefault();

        const idAlamatInput = this.querySelector('input[name="id_alamat"]');

        if (!idAlamatInput || idAlamatInput.value.trim() === "") {
            SC1Alert.show('Eits, pilih alamat dulu ya sebelum memesan!', 'warning');
            return; 
        }

        const formData = new FormData(this);

        // Ambil nilai dari input utama di hero section (untuk kolom 'keluhan')
        const mainInput = document.getElementById('main-input').value;
        formData.append('keluhan', mainInput);

        const btn = this.querySelector('.btn-submit');
        btn.disabled = true;
        btn.innerText = "Mengirim...";

        fetch('function/order/tambahOrderHandler.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. Cek dulu apakah price-section ada, biar tidak crash
                    const priceSection = document.getElementById('price-section');
                    if (priceSection) {
                        priceSection.style.display = 'none';
                    }

                    // 2. Munculkan Custom Alert buatanmu
                    const alertBox = document.getElementById('custom-success-alert');
                    if (alertBox) {
                        document.getElementById('success-alert-text').innerText = "Berhasil! Mengalihkan...";
                        alertBox.style.display = 'block';
                    }

                    // 3. Ubah teks tombol
                    btn.innerText = "Memproses...";

                    // 4. Jeda 2 detik lalu redirect
                    setTimeout(() => {
                        sessionStorage.removeItem('draft_detail_keluhan');
                        sessionStorage.removeItem('draft_harga');
                        sessionStorage.removeItem('draft_main_input');

                        window.location.href = 'home-user.php';
                    }, 2000);

                } else {
                    // Jika data.status 'error' dari PHP
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message,
                        confirmButtonColor: '#d33'
                    });
                    btn.disabled = false;
                    btn.innerText = "Pesan";
                }
            })
            .catch(error => {
                // INI PENTING: Menangkap error jaringan atau error PHP (bukan JSON)
                console.error('Error Sistem/Fetch:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Sistem',
                    text: 'Terjadi error di server. Cek Inspect Element -> tab Console!',
                    confirmButtonColor: '#d33'
                });
                btn.disabled = false;
                btn.innerText = "Pesan";
            });
    });

    // --- FITUR SIMPAN DRAFT (SESSION STORAGE) ---

    // 1. Fungsi saat tombol pilih alamat diklik
    function pilihAlamatDanSimpanDraft() {
        // Ambil value dari form
        const detailKeluhan = document.getElementById('keluhan-textarea').value;
        const harga = document.getElementById('price-input').value;

        // Ambil main-input (dari hero section seperti di kode Anda sebelumnya)
        const mainInputEl = document.getElementById('main-input');

        // Simpan ke sessionStorage browser
        sessionStorage.setItem('draft_detail_keluhan', detailKeluhan);
        sessionStorage.setItem('draft_harga', harga);
        if (mainInputEl) {
            sessionStorage.setItem('draft_main_input', mainInputEl.value);
        }

        // Pindah ke halaman pilih alamat
        window.location.href = '/dashboard/user/pilihalamat.php';
    }

    // 2. Fungsi saat halaman order.php/home-user.php dimuat kembali
    window.addEventListener('DOMContentLoaded', () => {

        const urlParams = new URLSearchParams(window.location.search);
        const isOpenOrder = urlParams.get('open_order');

        // 1. Kembalikan draft teks ke dalam form
        if (sessionStorage.getItem('draft_detail_keluhan')) {
            const keluhanText = sessionStorage.getItem('draft_detail_keluhan');
            document.getElementById('keluhan-textarea').value = keluhanText;
            document.getElementById('char-count').innerText = keluhanText.length;
        }

        if (sessionStorage.getItem('draft_harga')) {
            document.getElementById('price-input').value = sessionStorage.getItem('draft_harga');
        }

        if (sessionStorage.getItem('draft_main_input')) {
            const mainInputEl = document.getElementById('main-input');
            if (mainInputEl) {
                mainInputEl.value = sessionStorage.getItem('draft_main_input');
            }
        }

        // 2. Buka panel jika ada parameter open_order=true
        if (isOpenOrder === 'true') {

            setTimeout(() => {
                // Ambil elemen yang butuh dianimasikan
                const formPanel = document.getElementById('form-panel');
                const heroSection = document.querySelector('.hero-section'); // Ambil dari class karena di CSS pakai class

                // Tambahkan class 'active-state' ke form panel biar muncul
                if (formPanel) {
                    formPanel.classList.add('active-state');
                }

                // Tambahkan class 'active-state' ke hero section biar geser ke atas/kiri
                if (heroSection) {
                    heroSection.classList.add('active-state');
                }

                // Bersihkan URL biar rapi (hilangkan ?open_order=true dari link)
                window.history.replaceState({}, document.title, window.location.pathname);

            }, 300);
        }
    });
</script>