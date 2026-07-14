
<div class="container-fluid py-4">
  <?php if ($msg = getFlash('success')): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: <?= json_encode($msg) ?>,
          confirmButtonColor: '#4caf50',
          confirmButtonText: 'OK'
        });
      });
    </script>
  <?php endif; ?>

  <div class="row">
    <div class="col-12">
      <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
            <h6 class="text-white text-capitalize ps-3">Kalender Hari Libur</h6>
          </div>
        </div>

        <div class="my-4 mx-4">
          <p class="text-sm text-muted">Klik dan drag untuk memilih rentang tanggal libur. Hari Minggu ditandai otomatis.</p>
        </div>

        <div class="card-body px-0 pb-4">
          <div id='calendar' class="p-4"></div>
        </div>
      </div>
    </div>
  </div>
</div>

