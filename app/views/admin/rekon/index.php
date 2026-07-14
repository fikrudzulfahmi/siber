<div class="container-fluid py-4">
    <div class="row">
        <!-- Grafik Bulanan -->
        <div class="col-lg-4 col-md-12 mt-4 mb-4">
            <div class="card z-index-2">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                    <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                        <div class="chart">
                            <canvas id="rekonBulanChart" height="170"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-0">Konseling per Bulan</h6>
                    <p class="text-sm">
                        Tahun Pelajaran <?= $tpAktif['tahun_pelajaran'] ?> - Semester <?= ucfirst($tpAktif['semester']) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Grafik Kategori -->
        <div class="col-lg-4 col-md-12 mt-4 mb-4">
            <div class="card z-index-2">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                    <div class="bg-gradient-success shadow-success border-radius-lg py-3 pe-1">
                        <div class="chart">
                            <canvas id="rekonKategoriChart" height="170"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-0">Konseling per Kategori</h6>
                    <p class="text-sm">
                        Tahun Pelajaran <?= $tpAktif['tahun_pelajaran'] ?> - Semester <?= ucfirst($tpAktif['semester']) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Grafik Kelas -->
        <div class="col-lg-4 col-md-12 mt-4 mb-4">
            <div class="card z-index-2">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                    <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                        <div class="chart">
                            <canvas id="rekonKelasChart" height="170"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-0">Konseling per Kelas</h6>
                    <p class="text-sm">
                        Tahun Pelajaran <?= $tpAktif['tahun_pelajaran'] ?> - Semester <?= ucfirst($tpAktif['semester']) ?>
                    </p>
                </div>
            </div>
        </div>
        <!-- Card Cetak -->
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Cetak Rekap Konseling</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <form method="get" target="_blank">
                        <input type="hidden" name="controller" value="rekon">
                        <input type="hidden" name="method" value="cetak">

                        <div class="row g-3 align-items-end">
                            <!-- Mode Cetak -->
                            <div class="col-md-3">
                                <label class="form-label mb-1">Mode Cetak:</label>
                                <select name="mode" id="mode" class="form-control">
                                    <option value="semua">Semua (Range Tanggal)</option>
                                    <option value="semester">Per Semester</option>
                                    <option value="bulan">Per Bulan</option>
                                    <option value="kategori">Per Kategori</option>
                                    <option value="kelas">Per Kelas</option>
                                    <option value="siswa">Per Siswa</option>
                                </select>
                            </div>

                            <!-- Input Dinamis -->
                            <div class="col-md-3 mode-input mode-semua">
                                <label class="form-label mb-1">Dari:</label>
                                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>">
                            </div>
                            <div class="col-md-3 mode-input mode-semua">
                                <label class="form-label mb-1">Sampai:</label>
                                <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-t') ?>">
                            </div>

                            <div class="col-md-3 mode-input mode-semester d-none">
                                <label class="form-label mb-1">Tahun Pelajaran:</label>
                                <select name="id_tahun_pelajaran" class="form-control">
                                    <?php foreach ($tahunPelajaran as $tp): ?>
                                        <option value="<?= $tp['id_tahun_pelajaran'] ?>">
                                            <?= $tp['tahun_pelajaran'] ?> (<?= ucfirst($tp['semester']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mode-input mode-bulan d-none">
                                <label class="form-label mb-1">Pilih Bulan:</label>
                                <input type="month" name="bulan" class="form-control">
                            </div>

                            <div class="col-md-3 mode-input mode-kategori d-none">
                                <label class="form-label mb-1">Kategori:</label>
                                <select name="id_kategori" class="form-control">
                                    <?php foreach ($kategoriList as $kat): ?>
                                        <option value="<?= $kat['id_kategori'] ?>"><?= $kat['nama_kategori'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Mode Kelas -->
                            <div class="col-md-3 mode-input mode-kelas d-none">
                                <label class="form-label mb-1">Kelas:</label>
                                <select name="id_kelas" class="form-control kelas-select">
                                    <?php foreach ($kelasList as $k): ?>
                                        <option value="<?= $k['id_kelas'] ?>"><?= $k['kelas'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Mode Siswa -->
                            <div class="col-md-6 mode-input mode-siswa d-none">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Kelas:</label>
                                        <select name="id_kelas" class="form-control kelas-select siswa-kelas">
                                            <?php foreach ($kelasList as $k): ?>
                                                <option value="<?= $k['id_kelas'] ?>"><?= $k['kelas'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Siswa:</label>
                                        <select name="id_siswa" id="id_siswa" class="form-control">
                                            <option value="">-- Pilih Kelas Dulu --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>




                            <div class="col-auto">
                                <button type="submit" class="btn btn-dark">
                                    <i class="fas fa-print me-1"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // --- Bagian untuk toggle input filter (TETAP SAMA) ---
                const modeSelect = document.getElementById("mode");
                const inputs = document.querySelectorAll(".mode-input");

                function toggleInputs() {
                    const mode = modeSelect.value;
                    inputs.forEach(el => {
                        el.classList.toggle("d-none", !el.classList.contains("mode-" + mode));
                    });
                }
                modeSelect.addEventListener("change", toggleInputs);
                toggleInputs();


                // --- ✅ PERBAIKAN LOGIKA AJAX UNTUK FILTER SISWA ---
                const BASE_URL = '<?= BASEURL ?>';
                const kelasSelect = document.querySelector(".siswa-kelas");
                const siswaSelect = document.getElementById("id_siswa");

                // Fungsi untuk mengambil siswa dari server
                function fetchSiswa(idKelas) {
                    // URL sekarang selalu sama, controller yang akan menentukan datanya
                    let url = `${BASE_URL}/index.php?controller=rekon&method=getSiswa`;

                    if (idKelas) {
                        url += `&id_kelas=${idKelas}`;
                    }

                    siswaSelect.innerHTML = '<option value="">Memuat siswa...</option>';

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            let opt = '<option value="">-- Pilih Siswa --</option>';
                            data.forEach(s => {
                                opt += `<option value="${s.id_siswa}">${s.nama_siswa}</option>`;
                            });
                            siswaSelect.innerHTML = opt;
                        })
                        .catch(error => {
                            console.error('Error fetching siswa:', error);
                            siswaSelect.innerHTML = '<option value="">Gagal memuat</option>';
                        });
                }

                // Event listener saat pilihan kelas berubah
                kelasSelect.addEventListener("change", function() {
                    fetchSiswa(this.value);
                });

                // Jika dropdown kelas hanya punya 1 pilihan (kasus untuk Wali Kelas),
                // langsung picu event 'change' untuk otomatis memuat daftar siswanya.
                if (kelasSelect.options.length <= 2) { // <= 2 karena ada opsi "-- Pilih Kelas --"
                    kelasSelect.dispatchEvent(new Event('change'));
                }
            });
        </script>




        <script src="../assets/js/plugins/chartjs.min.js"></script>
        <script>
            // Grafik kategori
            new Chart(document.getElementById("rekonKategoriChart"), {
                type: "bar",
                data: {
                    labels: <?= json_encode(array_column($kategoriData, 'nama_kategori')) ?>,
                    datasets: [{
                        label: "Jumlah Konseling",
                        data: <?= json_encode(array_column($kategoriData, 'total')) ?>,
                        backgroundColor: "rgba(255,255,255,0.8)",
                        borderRadius: 4,
                        maxBarThickness: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: "#fff"
                            },
                            grid: {
                                color: "rgba(255,255,255,0.2)"
                            }
                        },
                        x: {
                            ticks: {
                                color: "#fff"
                            },
                            grid: {
                                color: "rgba(255,255,255,0.2)"
                            }
                        }
                    }
                }
            });

            // Grafik kelas
            new Chart(document.getElementById("rekonKelasChart"), {
                type: "bar",
                data: {
                    labels: <?= json_encode(array_column($kelasData, 'kelas')) ?>,
                    datasets: [{
                        label: "Jumlah Konseling",
                        data: <?= json_encode(array_column($kelasData, 'total')) ?>,
                        backgroundColor: "rgba(255,255,255,0.8)",
                        borderRadius: 4,
                        maxBarThickness: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: "#fff"
                            },
                            grid: {
                                color: "rgba(255,255,255,0.2)"
                            }
                        },
                        x: {
                            ticks: {
                                color: "#fff"
                            },
                            grid: {
                                color: "rgba(255,255,255,0.2)"
                            }
                        }
                    }
                }
            });

            // Grafik Bulan
            new Chart(document.getElementById("rekonBulanChart"), {
                type: "line",
                data: {
                    labels: <?= json_encode(array_column($bulanData, 'bulan')) ?>,
                    datasets: [{
                        label: "Jumlah Konseling",
                        data: <?= json_encode(array_column($bulanData, 'total')) ?>,
                        borderColor: "rgba(255,255,255,0.9)",
                        backgroundColor: "rgba(255,255,255,0.3)",
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: "#fff"
                            },
                            grid: {
                                color: "rgba(255,255,255,0.2)"
                            }
                        },
                        x: {
                            ticks: {
                                color: "#fff"
                            },
                            grid: {
                                color: "rgba(255,255,255,0.2)"
                            }
                        }
                    }
                }
            });
        </script>