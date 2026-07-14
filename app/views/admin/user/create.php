<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
<div class="container-fluid py-4">
    <h4>Tambah User</h4>
    <form action="?controller=user&method=store" method="POST">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Nama Wali Kelas</label>
            <select name="id_walikelas" class="form-control" required>
                <option value="">-- Pilih Wali Kelas --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id_employe'] ?>"><?= htmlspecialchars($user['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>
<?php include '../app/views/layouts/footer.php'; ?>