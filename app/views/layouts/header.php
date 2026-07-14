<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="assets/img/favicon2.png">
    <title><?= autoTitle($_GET['controller'] ?? '', $_GET['method'] ?? '') ?> | SiBer PPRM</title>


    <!-- Fonts and icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">


    <!-- Font Awesome 6 Free (CSS CDN) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link id="pagestyle" href="assets/css/material-dashboard.css?v=3.0.0" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Tambahkan di head atau sebelum penutup </body> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
</head>
<style>
    /* Tombol aktif */
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #4CAF50;
        /* hijau */
        border-color: #4CAF50;
        color: #fff;
    }

    /* Tombol Previous & Next */
    .dataTables_wrapper .pagination .page-item.previous .page-link,
    .dataTables_wrapper .pagination .page-item.next .page-link {
        background-color: #42424a;
        color: #fff;
    }

    /* Warna default lainnya */
    .dataTables_wrapper .pagination .page-link {
        color: #333;
    }

    /* Hover effect */
    .dataTables_wrapper .pagination .page-link:hover {
        background-color: #45a049;
        color: #fff;
    }

    /* Optional: Tambahkan border-radius agar lebih halus */
    .dataTables_wrapper .pagination .page-link {
        border-radius: 4px;
    }

    /* Mobile (sidebar toggle) */
    @media (max-width: 1199.98px) {
        #sidenav-main {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1050;
        }

        #sidenav-main.show {
            transform: translateX(0);
        }

        #main-content {
            margin-left: 0 !important;
        }
    }

    /* Desktop: sidebar tampil permanen */
    @media (min-width: 1200px) {
        #sidenav-main {
            transform: translateX(0) !important;
        }

        #main-content {
            margin-left: 18rem;
            /* Sesuaikan dengan lebar sidebar */
        }
    }

    @media (max-width: 1199.98px) {
        #sidenav-main {
            display: none;
            /* Jika kamu ingin efek sliding, bisa pakai transform, tapi display:none ini yang benar-benar hide */
        }

        #main-content {
            margin-left: 0 !important;
        }
    }

    @media (min-width: 1200px) {
        #sidenav-main {
            display: block;
            transform: translateX(0) !important;
        }

        #main-content {
            margin-left: 18rem;
            /* sesuai lebar sidebar */
        }
    }

    .form-control {
        padding-left: 1rem !important;
    }

    .select2-results__option {
        padding-left: 1.5rem !important;
        position: relative;
    }

    .select2-results__option::before {
        content: "☐";
        position: absolute;
        left: 0.5rem;
        color: #aaa;
    }

    .select2-results__option--selected::before {
        content: "☑";
        color: #28a745;
    }

    .form-check-custom input[type="checkbox"]:checked~.form-check-label .checked-icon {
        display: inline-block !important;
    }

    .form-check-custom input[type="checkbox"]:checked~.form-check-label .unchecked-icon {
        display: none !important;
    }

    .form-check-custom .mdi {
        font-size: 1.2rem;
        top: 0.1rem;
    }

    #calendar {
        max-width: 900px;
        margin: 0 auto;
    }

    .fc-daygrid-day.fc-day-today {
        background-color: rgba(63, 83, 119, 0.12) !important;
        /* 🔵 biru tua untuk nuansa dark */
        color: #fff !important;
        /* teks putih */
    }

    .fc-toolbar-title {
        font-family: 'Poppins', sans-serif;
        /* Ganti ke font favoritmu */
        font-size: 0.95rem;
        /* Ukuran opsional */
        font-weight: 500;
        /* Tebal sedang */
    }

    .fc-button.fc-today-button {
        background-color: #4caf50 !important;
        /* ✅ Warna success */
        border-color: #4caf50 !important;
        color: #fff !important;
    }

    .swal2-title {
        font-family: 'Poppins', sans-serif !important;
    }

    /* Styling box search DataTables */
    div.dataTables_filter input {
        border: 1px solid #ccc;
        /* garis abu */
        border-radius: 6px;
        /* biar agak rounded */
        padding: 4px 10px;
        /* kasih padding */
        outline: none;
        /* hilangkan default biru */
        box-shadow: none;
        /* hilangkan shadow bootstrap */
        transition: all 0.2s ease;
        /* animasi halus */
    }

    /* Efek saat fokus */
    div.dataTables_filter input:focus {
        border-color: #198754;
        /* hijau bootstrap (success) */
        box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.25);
    }
</style>



<body class="g-sidenav-show bg-gray-200">
    <nav class="navbar navbar-main navbar-expand-lg px-3 shadow-none bg-gradient-dark d-xl-none">
        <div class="container-fluid py-1 px-2 d-flex justify-content-end align-items-center">
            <button class="btn btn-sm text-white" id="menu-toggle">
                <i class="mdi mdi-menu mdi-24px"></i>
            </button>
        </div>
    </nav>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">