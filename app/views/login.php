<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Login | SiBer PPRM</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Icon -->
  <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
  <!-- Favicon -->
  <link rel="shortcut icon" href="assets/img/favicon2.png" type="image/x-icon" />

  <style>
    body, html {
      height: 100%;
      margin: 0;
      background:rgb(252, 252, 252);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .auth-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }

    .auth-content {
      display: flex;
      width: 100%;
      max-width: 900px;
      border-radius: 15px;
      background-color: #ffffff;
      box-shadow: 0 8px 25px rgba(0, 128, 0, 0.1);
      overflow: hidden;
      flex-wrap: nowrap;
    }

    .form-container {
      flex: 0 0 480px;
      padding: 40px 30px;
    }

    .form-container h4 {
      font-weight: 700;
      color: #28a745;
      margin-bottom: 10px;
      text-align: center;
    }

    .form-container h6 {
      font-weight: 400;
      color: #6c757d;
      text-align: center;
      margin-bottom: 30px;
    }

    .input-group-text {
      background-color: #d1e7dd;
      color: #28a745;
      border: none;
      border-radius: 8px 0 0 8px;
      min-width: 45px;
      justify-content: center;
      font-size: 1.2rem;
    }

    .form-control {
      border-radius: 0 8px 8px 0;
      border: 1px solid #28a745;
      font-size: 1rem;
      padding: 12px 16px;
      color: #222;
    }

    .form-control:focus {
      border-color: #218838;
      box-shadow: 0 0 8px rgba(40, 167, 69, 0.4);
      outline: none;
    }

    .btn-success {
      background: linear-gradient(45deg, #28a745, #218838);
      border: none;
      font-weight: 600;
      padding: 14px;
      border-radius: 10px;
      font-size: 1.1rem;
      width: 100%;
      box-shadow: 0 6px 12px rgba(40, 167, 69, 0.3);
      transition: all 0.3s ease;
    }

    .btn-success:hover {
      background: linear-gradient(45deg, #218838, #28a745);
      box-shadow: 0 12px 25px rgba(33, 136, 56, 0.5);
      transform: translateY(-2px);
    }

    .text-center.font-weight-light {
      font-size: 0.95rem;
      color: #6c757d;
      margin-top: 24px;
      text-align: center;
    }

    .text-center.font-weight-light a {
      color: #28a745;
      font-weight: 600;
      text-decoration: none;
    }

    .text-center.font-weight-light a:hover {
      color: #218838;
    }

.auth-bg {
  flex: 1;
  display: none; /* tetap disembunyikan untuk mobile */
  border-radius: 0 12px 12px 0;
  overflow: hidden;
}

.auth-bg img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

@media (min-width: 992px) {
  .auth-bg {
    display: block;
  }
}


    @media (max-width: 991px) {
      .auth-content {
        flex-direction: column;
        border-radius: 0;
        box-shadow: none;
      }

      .form-container {
        max-width: 400px;
        margin: 0 auto;
        border-radius: 12px;
        box-shadow: 0 12px 25px rgba(0, 128, 0, 0.1);
        background: #fff;
      }

      .auth-bg {
        display: none !important;
      }
    }
  </style>
</head>

<body>
  <div class="auth-wrapper">
    <div class="auth-content">
      <div class="form-container">
        <h4>Login Admin</h4>
        <h6>Silakan masuk untuk memulai administrasi</h6>

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger text-center mt-3">Login gagal. Coba lagi.</div>
        <?php endif; ?>

        <form method="POST" action="?controller=auth&method=login">
          <div class="mb-3">
            <div class="input-group">
              <span class="input-group-text">
                <i class="mdi mdi-account"></i>
              </span>
              <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
            </div>
          </div>

          <div class="mb-4">
            <div class="input-group">
              <span class="input-group-text">
                <i class="mdi mdi-lock"></i>
              </span>
              <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            </div>
          </div>

          <button type="submit" name="login" class="btn btn-success">Login</button>
        </form>

        <div class="text-center font-weight-light">
          Belum punya akun? <a href="#">Hubungi Admin</a>
        </div>
      </div>

<div class="auth-bg">
  <img src="assets/img/bg-login.jpg" alt="Gambar Login SiBer">
</div>



    </div>
  </div>

  <!-- Optional JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
