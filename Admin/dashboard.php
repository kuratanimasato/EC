<?php
require_once dirname(__DIR__) . '/app/functions.php';
startSession();
if (!isAdminLoggedIn()) {
  header("location: login.php");
  exit;
}
?>
<?php require_once dirname(__DIR__) . '/Admin/parts/header.php'; ?>

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
  <?php require_once dirname(__DIR__) . '/Admin/parts/footer.php'; ?>