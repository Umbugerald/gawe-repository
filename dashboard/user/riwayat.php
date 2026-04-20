<div class="riwayat-container">
    <div class="sub-tab-container">
        <button class="sub-tab-btn active" id="sub-mencari">Mencari</button>
        <button class="sub-tab-btn" id="sub-proses">Proses</button>
        <button class="sub-tab-btn" id="sub-selesai">Selesai</button>
    </div>

    <div class="riwayat-list">
        <div id="content-mencari" style="display: block;">
            <div style="text-align:center; padding: 20px;">Memuat data...</div>
        </div>
        <div id="content-proses" style="display: none;"></div>
        <div id="content-selesai" style="display: none;"></div>
    </div>

    <div id="modal-overlay" class="modal-overlay"></div>

    <div id="modal-lapor" class="custom-modal">
        <h3 class="modal-title">Lapor</h3>
        <form id="formLapor" enctype="multipart/form-data">
            <input type="hidden" name="id_order" id="lapor-id-order">
            <div class="form-group">
                <label class="modal-label">Laporan</label>
                <textarea name="detail_report" class="lapor-textarea"
                    placeholder="Cont : Pompa Air tetap tidak nyala..." required></textarea>
            </div>
            <div class="form-group">
                <label class="modal-label">Upload Foto Laporan</label>
                <label class="lapor-upload-box" id="upload-box">
                    <i class="fa-solid fa-file-invoice upload-icon" style="font-size: 1.8rem; margin-bottom: 8px;"></i>
                    <span class="upload-text-modal">Click to upload image</span>
                    <img id="img-preview-lapor" style="display:none;" />
                    <input type="file" name="foto_report" id="foto-report-input" hidden accept="image/*">
                </label>
            </div>
            <button type="submit" class="btn-confirm">Confirm</button>
        </form>
    </div>

    <div id="modal-nilai" class="custom-modal">
        <div class="star-rating-container">
            <i class="fa-regular fa-star star-item" data-value="1"></i>
            <i class="fa-regular fa-star star-item" data-value="2"></i>
            <i class="fa-regular fa-star star-item" data-value="3"></i>
            <i class="fa-regular fa-star star-item" data-value="4"></i>
            <i class="fa-regular fa-star star-item" data-value="5"></i>
        </div>
        <button class="btn-confirm">Confirm</button>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const btnMencari = document.getElementById('sub-mencari');
        const btnProses = document.getElementById('sub-proses');
        const btnSelesai = document.getElementById('sub-selesai');

        const contentMencari = document.getElementById('content-mencari');
        const contentProses = document.getElementById('content-proses');
        const contentSelesai = document.getElementById('content-selesai');

        function resetSubTabs() {
            if (contentMencari) contentMencari.style.display = 'none';
            if (contentProses) contentProses.style.display = 'none';
            if (contentSelesai) contentSelesai.style.display = 'none';

            if (btnMencari) btnMencari.classList.remove('active');
            if (btnProses) btnProses.classList.remove('active');
            if (btnSelesai) btnSelesai.classList.remove('active');
        }

        if (btnMencari && btnProses && btnSelesai) {
            btnMencari.addEventListener('click', () => {
                resetSubTabs();
                contentMencari.style.display = 'block';
                btnMencari.classList.add('active');
            });

            btnProses.addEventListener('click', () => {
                resetSubTabs();
                contentProses.style.display = 'block';
                btnProses.classList.add('active');
            });

            btnSelesai.addEventListener('click', () => {
                resetSubTabs();
                contentSelesai.style.display = 'block';
                btnSelesai.classList.add('active');
            });
        }

        let currentOrderRating = '';
        let selectedRating = 0;
        let currentBtnElement = null;

        const modalOverlay = document.getElementById('modal-overlay');
        const modalLapor = document.getElementById('modal-lapor');
        const modalNilai = document.getElementById('modal-nilai');

        // --- 3. LOGIKA BINTANG RATING (Cukup jalan sekali) ---
        const stars = document.querySelectorAll('.star-item');
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                selectedRating = star.getAttribute('data-value');
                stars.forEach(s => {
                    s.classList.remove('fa-solid');
                    s.classList.add('fa-regular');
                    s.style.color = "#ccc";
                });
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.remove('fa-regular');
                    stars[i].classList.add('fa-solid');
                    stars[i].style.color = "#ffca08";
                }
            });

            star.addEventListener('mouseover', () => {
                for (let i = 0; i <= index; i++) {
                    stars[i].style.color = "#ffca08";
                }
            });

            star.addEventListener('mouseout', () => {
                stars.forEach((s, i) => {
                    if (i >= selectedRating) {
                        s.style.color = "#ccc";
                    } else {
                        s.style.color = "#ffca08";
                    }
                });
            });
        });

        if (modalOverlay) {
            modalOverlay.addEventListener('click', () => {
                modalOverlay.classList.remove('show');
                if (modalLapor) modalLapor.classList.remove('show');
                if (modalNilai) modalNilai.classList.remove('show');
            });
        }

        let intervalsArray = [];

        function jalankanTimer() {
            intervalsArray.forEach(clearInterval);
            intervalsArray = [];

            const mencariCards = document.querySelectorAll('.mencari-card');
            mencariCards.forEach(card => {
                let sisaDetik = parseInt(card.getAttribute('data-sisa'));
                const idOrder = card.getAttribute('data-id');

                const timerElement = document.getElementById(`pelanggan-timer-${idOrder}`);
                const badgeStatusElement = document.getElementById(`badge-status-${idOrder}`);
                const pekerjaStatusElement = document.getElementById(`pekerja-status-${idOrder}`);

                if (sisaDetik > 0) {
                    const hitungMundur = setInterval(() => {
                        sisaDetik--;
                        const menit = Math.floor(sisaDetik / 60);
                        const detik = sisaDetik % 60;

                        const teksMenit = menit < 10 ? '0' + menit : menit;
                        const teksDetik = detik < 10 ? '0' + detik : detik;

                        if (timerElement) timerElement.textContent = `${teksMenit}:${teksDetik}`;

                        if (sisaDetik <= 0) {
                            clearInterval(hitungMundur);
                            if (timerElement) timerElement.textContent = "00:00";
                            if (badgeStatusElement) {
                                badgeStatusElement.style.color = 'white';
                                badgeStatusElement.style.background = '#d32f2f';
                                badgeStatusElement.style.border = '1px solid #d32f2f';
                                badgeStatusElement.innerHTML = 'Waktu Habis / Batal';
                            }
                            if (pekerjaStatusElement) {
                                pekerjaStatusElement.innerHTML = `Pekerja : <span style="color: #d32f2f;">Tidak ditemukan</span>`;
                            }
                        }
                    }, 1000);
                    intervalsArray.push(hitungMundur);
                }
            });
        }

        function pasangEventTombol() {
            const buttons = document.querySelectorAll(".btn-hubungi");
            buttons.forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();

                    const noAdmin = this.dataset.admin;
                    const nama = this.dataset.nama || "User";
                    const status = this.dataset.status || "";


                    if (!noAdmin) {
                        alert("Nomor admin tidak tersedia");
                        return;
                    }

                    let cleanNumber = noAdmin.replace(/\D/g, '');
                    if (cleanNumber.startsWith('0')) {
                        cleanNumber = '62' + cleanNumber.slice(1);
                    }

                    const pesan = encodeURIComponent(`Halo Admin, saya ingin menanyakan terkait pesanan saya.`);

                    window.open(`https://wa.me/${cleanNumber}?text=${pesan}`, '_blank');
                });
            });

            document.querySelectorAll('.btn-lapor').forEach(btn => {
                btn.addEventListener('click', () => {
                    const idOrder = btn.getAttribute('data-id');
                    document.getElementById('lapor-id-order').value = idOrder;
                    modalOverlay.classList.add('show');
                    modalLapor.classList.add('show');
                });
            });

            document.querySelectorAll('.btn-nilai').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentOrderRating = btn.getAttribute('data-id');
                    currentBtnElement = btn;
                    selectedRating = 0;

                    document.querySelectorAll('.star-item').forEach(s => {
                        s.classList.remove('fa-solid');
                        s.classList.add('fa-regular');
                        s.style.color = "#ccc";
                    });

                    modalOverlay.classList.add('show');
                    modalNilai.classList.add('show');
                });
            });
        }

        async function loadDataRiwayat() {
            try {
                const response = await fetch('function/riwayat/api_riwayat.php');
                const data = await response.json();


                document.getElementById('content-mencari').innerHTML = data.mencari;
                document.getElementById('content-proses').innerHTML = data.proses;
                document.getElementById('content-selesai').innerHTML = data.selesai;

                jalankanTimer();
                pasangEventTombol();

            } catch (error) {
                console.error("Gagal mengambil data background:", error);
            }
        }

        loadDataRiwayat();
        setInterval(loadDataRiwayat, 5000);

        const btnConfirmNilai = document.querySelector('#modal-nilai .btn-confirm');
        if (btnConfirmNilai) {
            btnConfirmNilai.addEventListener('click', async () => {
                const idOrder = currentOrderRating;

                if (selectedRating == 0) {
                    await SC1Alert.show('Pilih bintangnya dulu, Bos!', 'warning');
                    return;
                }

                btnConfirmNilai.disabled = true;
                btnConfirmNilai.innerText = "Saving...";

                try {
                    const response = await fetch('function/riwayat/insertRatings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id_order: idOrder,
                            rating: selectedRating 
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        await SC1Alert.show(result.message, 'success');

                        modalOverlay.classList.remove('show');
                        modalNilai.classList.remove('show');

                        if (currentBtnElement) {
                            currentBtnElement.disabled = true;
                            currentBtnElement.innerText = "Sudah Dinilai";
                            currentBtnElement.style.backgroundColor = '#cccccc';
                            currentBtnElement.style.color = '#666666';
                            currentBtnElement.style.cursor = 'not-allowed';
                            currentBtnElement.removeAttribute('data-id');
                        }

                        btnConfirmNilai.disabled = false;
                        btnConfirmNilai.innerText = "Confirm";

                    } else {
                        await SC1Alert.show(result.message, 'error');
                        btnConfirmNilai.disabled = false;
                        btnConfirmNilai.innerText = "Confirm";
                    }
                } catch (err) {
                    console.error(err);
                    await SC1Alert.show('Gagal koneksi ke server.', 'error');
                    btnConfirmNilai.disabled = false;
                    btnConfirmNilai.innerText = "Confirm";
                }
            });
        }

    });
</script>

<script>
    const fotoInput = document.getElementById('foto-report-input');
    const imgPreview = document.getElementById('img-preview-lapor');
    const uploadText = document.querySelector('.upload-text-modal');
    const uploadBox = document.getElementById('upload-box');
    const formLapor = document.getElementById('formLapor');

    function resetModalLapor() {
        if (formLapor) formLapor.reset();

        if (imgPreview) {
            imgPreview.src = '';
            imgPreview.style.display = 'none';
        }

        if (uploadBox) {
            uploadBox.classList.remove('has-image');
        }
        if (uploadText) {
            uploadText.textContent = "Click to upload image";
        }
    }

    if (fotoInput) {
        fotoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    imgPreview.src = this.result;
                    imgPreview.style.display = 'block';
                    uploadBox.classList.add('has-image');
                    uploadText.textContent = "Change image";
                });
                reader.readAsDataURL(file);
            } else {
                imgPreview.style.display = 'none';
                uploadBox.classList.remove('has-image');
                uploadText.textContent = "Click to upload image";
            }
        });
    }

    if (formLapor) {
        let isSubmitting = false;
        formLapor.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (isSubmitting) return;
            isSubmitting = true;

            const formData = new FormData(e.target);
            const btnSubmit = formLapor.getElementsByClassName('btn-lapor');
            const originalBtnText = btnSubmit ? btnSubmit.innerHTML : 'Kirim';

            if (btnSubmit) {
                btnSubmit.disabled = true; 
                btnSubmit.innerHTML = 'Memproses...';
            }

            try {
                const response = await fetch('function/riwayat/insertReport.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    await SC1Alert.show(result.message, 'success');

                    document.getElementById('modal-overlay').classList.remove('show');
                    document.getElementById('modal-lapor').classList.remove('show');

                    resetModalLapor();

                } else {
                    await SC1Alert.show('Gagal: ' + result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                await SC1Alert.show('Terjadi kesalahan sistem.', 'error');
            }
        });
    }

    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            
            resetModalLapor();
        });
    }
</script>