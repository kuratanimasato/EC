<?php
require_once dirname(__DIR__) . '/app/database/db_connect.php';
require_once dirname(__DIR__) . '/app/functions.php';
// セッションを開始
startSession();


// セッション変数 $_SESSION["loggedin"]を確認。ログイン済だったらウェルカムページへリダイレクト
if (isAdminLoggedIn()) {
  header("location: dashboard.php");
  exit;
}

//POSTされてきたデータを格納する変数の定義と初期化
$datas = [
  'email' => '',
  'password' => '',
];
$errors = [];

//POST通信だった場合はログイン処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  //// --- デバッグコード終了 ---
  if (!validateCsrfToken('admin-login')) {
    // CSRFトークンが無効な場合はエラーメッセージを表示
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }
  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
    if ($postValue !== null) {
      $datas[$key] = $postValue;
    }
  }

  // バリデーション
  $errors = validation_admin_login($datas);
  if (empty($errors)) {
    //ユーザーネームから該当するユーザー情報を取得
    $sql = "SELECT administrator_id, admin_user, password FROM administrators WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('email', $datas['email'], PDO::PARAM_STR);
    $stmt->execute();
    //ユーザー情報があれば変数に格納
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      //パスワードがあっているか確認
      if (password_verify($datas['password'], $row['password'])) {
        //セッションIDをふりなおす
        session_regenerate_id(true);
        //セッション変数にログイン情報を格納
        setLoginSession($row);
        //管理画面にリダイレクト
        header("location:dashboard.php");
        exit();
      } else {
        $login_err = 'ユーザー名またはパスワードが間違っています';
      }
    } else {
      $login_err = 'ユーザー名またはパスワードが間違っています';
    }
  }
}
?>
<?php require_once dirname(__DIR__) . '/Admin/parts/header.php'; ?>

<body>
  <?php
  if (!empty($login_err)) {
    echo '<div class="alert alert-danger">' . $login_err . '</div>';
  }
  ?>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow rounded-4">
          <div class="card-body p-4">
            <h2 class="card-title text-center mb-4">ログイン</h2>
            <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post">
              <?php echo insertCsrfToken('admin-login'); ?>
              <div class="mb-3">
                <label for=" email" class="form-label">メールアドレス</label>
                <input type="text" name="email" id="email"
                  class="form-control <?php echo (!empty($errors['email'])) ? 'is-invalid' : ''; ?>"
                  value="<?php echo h($datas['email'] ?? ''); ?>">
                <?php if (!empty($errors['email'])): ?>
                  <div class="invalid-feedback"><?php echo h($errors['email']); ?></div>
                <?php endif; ?>
              </div>
              <div class=" mb-3">
                <label for="password" class="form-label">パスワード</label>
                <input type="password" name="password" id="password"
                  class="form-control <?php echo (!empty($errors['password'])) ? 'is-invalid' : ''; ?>"
                  value="<?php echo h($datas['password'] ?? ''); ?>">
                <?php if (!empty($errors['password'])): ?>
                  <div class="invalid-feedback"><?php echo h($errors['password']); ?></div>
                <?php endif; ?>
              </div>
              <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">ログイン</button>
              </div>
              <p class="text-center mb-0">アカウントを作成していませんか？<a href="register.php">新規作成</a></p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include dirname(__DIR__) . '/Admin/parts/footer.php'; ?>