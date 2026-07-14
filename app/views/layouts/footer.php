<footer class="footer py-4">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
                <div class="copyright text-center text-sm text-muted text-lg-start">
                    © <script>
                        document.write(new Date().getFullYear())
                    </script>,
                    made with <i class="mdi mdi-heart"></i> by
                    <a href="https://pondokminggirsari.com/" class="font-weight-bold text-dark" target="_blank">Pondok Minggirsari</a>
                    All Rights Reserved
                </div>
            </div>
            <div class="col-lg-6">
                <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                    <li class="nav-item"><a href="https://www.instagram.com/pondokminggirsariblitar/" class="nav-link text-muted" target="_blank">Instagram</a></li>
                    <li class="nav-item"><a href="https://www.facebook.com/PonpesRoudlotulMutaaliminMinggirsariBlitar/" class="nav-link text-muted" target="_blank">Facebook</a></li>
                    <li class="nav-item"><a href="https://maps.app.goo.gl/q9QR6zC1cKkzvBud7" class="nav-link text-muted" target="_blank">Alamat</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
</div>
</main>


<!-- Core JS Files -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            language: {
                searchPlaceholder: "Cari disini...",
                paginate: {
                    previous: '<i class="material-icons-round">chevron_left</i>',
                    next: '<i class="material-icons-round">chevron_right</i>'
                }
            },
            columnDefs: [{
                targets: 0, // kolom pertama (penomoran)
                searchable: true,
                orderable: true,
                render: function(data, type, row, meta) {
                    return meta.row + 1;
                }
            }]
        });
    });
</script>
<script>
    $('#datatable3').DataTable({
        language: {
            searchPlaceholder: "Cari disini...",
            paginate: {
                previous: '<i class="material-icons-round">chevron_left</i>',
                next: '<i class="material-icons-round">chevron_right</i>'
            }
        },
        columnDefs: [{
            targets: 0, // kolom pertama (No)
            searchable: false,
            orderable: false,
            render: function(data, type, row, meta) {
                return meta.row + 1;
            }
        }]
    });
</script>
<script>
    $('#datatable4').DataTable({
        language: {
            searchPlaceholder: "Cari disini...",
            paginate: {
                previous: '<i class="material-icons-round">chevron_left</i>',
                next: '<i class="material-icons-round">chevron_right</i>'
            }
        },
        columnDefs: [{
            targets: 0, // kolom pertama (No)
            searchable: false,
            orderable: false,
            render: function(data, type, row, meta) {
                return meta.row + 1;
            }
        }]
    });
</script>
<script>
    $('#datatable5').DataTable({
        language: {
            searchPlaceholder: "Cari disini...",
            paginate: {
                previous: '<i class="material-icons-round">chevron_left</i>',
                next: '<i class="material-icons-round">chevron_right</i>'
            }
        },
        columnDefs: [{
                targets: 0, // Kolom pertama (No)
                searchable: false,
                orderable: false,
                render: function(data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 7, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '500px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 3, // Targetkan kolom ke-4 (indeks 3)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '500px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 1, // Targetkan kolom ke-3 (indeks 2)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '500px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 8, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 3, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '300px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 4, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '300px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 2, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 5, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 6, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            }
        ]
    });
</script>
<script>
    $('#datatable6').DataTable({
        language: {
            searchPlaceholder: "Cari disini...",
            paginate: {
                previous: '<i class="material-icons-round">chevron_left</i>',
                next: '<i class="material-icons-round">chevron_right</i>'
            }
        },
        columnDefs: [{
                targets: 0, // Kolom pertama (No)
                searchable: false,
                orderable: false,
                render: function(data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 7, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '500px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 3, // Targetkan kolom ke-4 (indeks 3)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '500px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 1, // Targetkan kolom ke-3 (indeks 2)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '500px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 8, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 3, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '300px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 4, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '300px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 2, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 5, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            },
            { // ✅ PENYESUAIAN UNTUK WRAP TEXT
                targets: 6, // Targetkan kolom ke-8 (indeks 7)
                className: 'wrap-text', // Terapkan kelas CSS
                width: '250px' // Beri batas lebar agar teks tahu kapan harus turun
            }
        ]
    });
</script>

<!-- ==================== JS TOGGLE MOBILE ==================== -->
<script>
    const menuToggle = document.getElementById('menu-toggle');
    const mobileSidebar = document.getElementById('sidenav-mobile');
    const closeSidebar = document.getElementById('close-sidebar-mobile');

    menuToggle?.addEventListener('click', () => {
        mobileSidebar.style.transform =
            mobileSidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0)';
    });
    closeSidebar?.addEventListener('click', () => mobileSidebar.style.transform = 'translateX(-100%)');

    document.addEventListener('click', e => {
        if (!mobileSidebar.contains(e.target) && !e.target.closest('#menu-toggle') && window.innerWidth < 1200) {
            mobileSidebar.style.transform = 'translateX(-100%)';
        }
    });
</script>
<?php
$controller = $_GET['controller'] ?? '';
$method = $_GET['method'] ?? '';
?>

<?php if ($controller === 'kalender' && $method === 'index'): ?>

    <!-- Import Google Fonts Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        body,
        .fc {
            font-family: 'Roboto', sans-serif !important;
            background-color: #f6f8fa;
        }

        /* FullCalendar header */
        .fc-toolbar-title {
            font-weight: 700;
            font-size: 1.3rem;
            color: #344767;
        }

        /* Button */
        .fc-button {
            border-radius: 8px !important;
            font-weight: 500 !important;
            padding: 6px 14px !important;
            transition: 0.2s ease-in-out;
        }

        .fc-button:hover {
            filter: brightness(0.9);
        }

        .fc-today-button {
            background-color: #4caf50 !important;
            border: none !important;
            font-weight: 600 !important;
        }

        /* Event style */
        .fc-event {
            border-radius: 8px !important;
            font-weight: 500 !important;
            padding: 3px 6px;
            transition: transform 0.15s;
        }

        .fc-event:hover {
            transform: scale(1.03);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        /* Warna khusus untuk hari libur */
        .event-libur {
            background-color: #e53935 !important;
            border: none !important;
            color: #fff !important;
        }

        /* Swal style */
        .swal2-popup {
            font-family: 'Roboto', sans-serif !important;
            border-radius: 12px !important;
        }
    </style>

    <script>
        function containsSundayInRange(start, end) {
            const current = new Date(start);
            while (current <= end) {
                if (current.getDay() === 0) return true;
                current.setDate(current.getDate() + 1);
            }
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                selectable: true,

                buttonText: {
                    today: 'Today'
                },

                select: function(info) {
                    const startDate = new Date(info.startStr);
                    const endDate = new Date(info.endStr);
                    endDate.setDate(endDate.getDate() - 1);

                    if (containsSundayInRange(startDate, endDate)) {
                        Swal.fire('Range tanggal mengandung Hari Minggu. Tidak bisa disimpan.');
                        return;
                    }

                    Swal.fire({
                        title: 'Tambah Hari Libur',
                        input: 'text',
                        inputLabel: 'Keterangan',
                        showCancelButton: true,
                        confirmButtonText: 'Simpan',
                        confirmButtonColor: '#4caf50',
                        cancelButtonColor: '#344767',
                        inputValidator: (value) => {
                            if (!value) return 'Keterangan wajib diisi!';
                        },
                        preConfirm: (keterangan) => {
                            return fetch('index.php?controller=kalender&method=store', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        tanggal_mulai: info.startStr,
                                        tanggal_selesai: endDate.toISOString().split('T')[0],
                                        keterangan: keterangan
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.status === 'error') throw new Error(data.message);

                                    // +1 hari karena FullCalendar anggap end exclusive
                                    const endDisplay = new Date(endDate);
                                    endDisplay.setDate(endDisplay.getDate() + 1);
                                    const endDisplayStr = endDisplay.toISOString().split('T')[0];

                                    calendar.addEvent({
                                        id: data.id,
                                        title: keterangan,
                                        start: info.startStr,
                                        end: endDisplayStr,
                                        allDay: true,
                                        classNames: ['event-libur']
                                    });

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil',
                                        text: 'Hari libur ditambahkan!',
                                        confirmButtonColor: '#4caf50'
                                    });

                                    return data;
                                })
                                .catch(err => {
                                    Swal.showValidationMessage(err.message);
                                });
                        }
                    });
                },

                eventSources: [{
                    events: <?= json_encode($libur) ?>
                }],

                eventDidMount: function(info) {
                    // Tambah class khusus kalau event libur
                    if (info.event.title && info.event.title.toLowerCase().includes('libur')) {
                        info.el.classList.add('event-libur');
                    }
                },

                eventClick: function(info) {
                    const id = info.event.id;
                    if (!id) return;

                    Swal.fire({
                        title: 'Hapus Hari Libur?',
                        text: info.event.title,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        cancelButtonColor: '#344767',
                        confirmButtonColor: '#e53935'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('index.php?controller=kalender&method=deleteById', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        id: id
                                    })
                                }).then(res => res.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        info.event.remove();
                                        Swal.fire('Berhasil', 'Hari libur dihapus', 'success');
                                    }
                                });
                        }
                    });
                }
            });

            calendar.render();
        });
    </script>
<?php endif; ?>