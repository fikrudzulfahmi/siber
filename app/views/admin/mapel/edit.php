<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Mata Pelajaran</h6>
                    </div>
                </div>
                <div class="card-body px-4 py-4">
                    <form action="?controller=mapel&method=update" method="POST">
                        <input type="hidden" name="id_mapel" value="<?= $mapel['id_mapel'] ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Kode Mapel</label>
                            <input type="text" name="kode_mapel"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                value="<?= $mapel['kode_mapel'] ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Mata Pelajaran</label>
                            <input type="text" name="mapel"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                value="<?= $mapel['nama_mapel'] ?>" required>
                        </div>
                        <!-- Tingkat -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Tingkat</label>
                            <input type="number" name="tingkat"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                min="7" max="12" value="<?= $mapel['tingkat_mapel'] ?>" required>
                        </div>


                        <div class="d-flex justify-content-end">
                            <a href="?controller=mapel&method=index" class="btn btn-outline-secondary me-2">Kembali</a>
                            <button type="submit" class="btn bg-gradient-success text-white px-4">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



<?php include '../app/views/layouts/footer.php'; ?>