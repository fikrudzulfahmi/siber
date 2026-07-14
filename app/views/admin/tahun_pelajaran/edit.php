<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>


<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Tahun Pelajaran</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=tahunPelajaran&method=store" method="POST">

                        <!-- Nama Kelas -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Tahun Pelajaran</label>
                            <input type="hidden" name="id_tahun_pelajaran" value="">
                            <input type="text" name="tahun_pelajaran"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                value="<?= $tahun['tahun_pelajaran'] ?>" required>
                        </div>

                        <!-- Tingkat -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Semester</label>
                            <input type="text" name="semester"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                value="<?= $tahun['semester'] ?>" required>
                        </div>

                        <!-- Tombol -->
                        <div class="d-flex justify-content-end">
                            <a href="?controller=tahunPelajaran&method=index" class="btn btn-outline-secondary me-2">Kembali</a>
                            <button type="submit" class="btn bg-gradient-success text-white px-4">Simpan</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>



<?php include '../app/views/layouts/footer.php'; ?>