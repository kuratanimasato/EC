<?php
require_once dirname(__DIR__) . '/app/functions.php';
startSession();
if (!isAdminLoggedIn()) {
  header("location: login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ダッシュボード</title>
    <link rel="icon" href="favicon.ico" />
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" />
  </head>

  <body class="bg-light text-dark min-vh-100 d-flex flex-column">
    <!-- Header -->
    <header class="bg-white shadow-sm mb-4">
      <div class="container-fluid px-4 py-3 d-flex justify-content-between align-items-center">
        <h1 class="h4 mb-0">
          <a href="../Admin/dashboard.php" class="text-primary text-decoration-none">管理画面</a>
        </h1>
        <nav>
          こんにちは
          <?php echo isset($_SESSION["admin_user"]) ? h($_SESSION["admin_user"]) : '管理者'; ?>さん
          <a href="logout.php" class="text-muted text-decoration-none small">ログアウト</a>
        </nav>
      </div>
    </header>
    <!-- Main Content -->
    <main class="flex-grow-1 py-4">
      <div class="container-fluid px-4">
        <div class="mb-4">
          <h2 class="h5 font-weight-bold">ダッシュボード</h2>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
          <!-- ボックス項目 -->
          <div class="col">
            <a href="admin_settings.php"
              class="d-flex flex-column align-items-center justify-content-center p-4 bg-white rounded-3 shadow-sm hover-shadow-lg transition">
              <i class="fas fa-user-cog text-primary mb-3 fs-1"></i>
              <p class="h6 text-center">管理者設定</p>
            </a>
          </div>
          <div class="col">
            <a href="UserManagement/users.php"
              class="d-flex flex-column align-items-center justify-content-center p-4 bg-white rounded-3 shadow-sm hover-shadow-lg transition">
              <i class="fas fa-users icon text-primary mb-3 fs-1"></i>
              <p class="h6 text-center">会員管理</p>
            </a>
          </div>
          <div class="col">
            <a href="OrderManagement/orders.php"
              class="d-flex flex-column align-items-center justify-content-center p-4 bg-white rounded-3 shadow-sm hover-shadow-lg transition">
              <i class="fas fa-clipboard-list icon text-primary mb-3 fs-1"></i>
              <p class="h6 text-center"> 受注管理</p>
            </a>
          </div>
          <div class="col">
            <a href="ProductManagement/products.php"
              class="d-flex flex-column align-items-center justify-content-center p-4 bg-white rounded-3 shadow-sm hover-shadow-lg transition">
              <i class="fas fa-box icon text-primary mb-3 fs-1"></i>
              <p class="h6 text-center">商品管理</p>
            </a>
          </div>
        </div>
      </div>
    </main>
    <!-- Footer -->
    <footer class=" bg-white shadow-sm mt-4">
    </footer>

  </body>

</html>