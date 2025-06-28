<?php
require_once dirname(__DIR__) . '/app/database/db_connect.php';
require_once dirname(__DIR__) . '/app/functions.php';
//セッションの開始
startSession();
$datas = [];
$errors = [];
//POSTされてきたデータを格納する変数の定義と初期化
$datas = [
  'admin_user' => '',
  'email' => '',
  'password' => '',
  'confirm_password' => ''
];

//POST通信だった場合はDBへの新規登録処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!validateCsrfToken('admin-register')) {
    // CSRFトークンが無効な場合はエラーメッセージを表示
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }
  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    if ($value = filter_input(INPUT_POST, $key, FILTER_DEFAULT)) {
      $datas[$key] = $value;
    }
  }

  // バリデーション
  $errors = validation_admin_register($datas);

  //データベースの中に同一ユーザー名が存在していないか確認
  if (empty($errors['admin_user'])) {
    $sql = "SELECT administrator_id FROM  administrators WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('email', $datas['email'], PDO::PARAM_STR);
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $errors['admin_user'] = 'このメールアドレスはすでに使用されています。';
      $errors['admin_user'] = 'このユーザー名はすでに使用されています。';
    }
  }
  //エラーがなかったらDBへの新規登録を実行
  if (empty($errors)) {
    $params = [
      'administrator_id' => null,
      'admin_user' => $datas['admin_user'],
      'email' => $datas['email'],
      'password' => password_hash($datas['password'], PASSWORD_DEFAULT),
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    $count = 0;
    $columns = '';
    $values = '';
    foreach (array_keys($params) as $key) {
      if ($count > 0) {
        $columns .= ',';
        $values .= ',';
      }
      $columns .= $key;
      $values .= ':' . $key;
      $count++;
    }

    try {
      $pdo->beginTransaction();
      $sql = 'insert into administrators (' . $columns . ')values(' . $values . ')';
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $pdo->commit();
      header("location: login.php");
      exit;
    } catch (PDOException $e) {
      echo 'ERROR: Could not register.';
      $pdo->rollBack();
    }
  }
}
?>
<?php require_once dirname(__DIR__) . '/Admin/parts/header.php'; ?>

<body class="bg-light py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm p-4">
          <h2 class="mb-4 text-center">新規登録</h2>
          <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post">
            <div class="mb-3">
              <label for="name" class="form-label">管理者名</label>
              <input type="text" class="form-control <?php echo !empty($errors['admin_user']) ? 'is-invalid' : ''; ?>"
                id="admin_user" name="admin_user" value="<?php echo h($datas['admin_user'] ?? ''); ?>">
              <?php if (!empty($errors['admin_user'])): ?>
                <div class="invalid-feedback"><?php echo h($errors['admin_user']); ?></div>
              <?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">メールアドレス</label>
              <input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>"
                id="email" name="email" value="<?php echo h($datas['email'] ?? ''); ?>">
              <?php if (!empty($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo h($errors['email']); ?></div>
              <?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">パスワード</label>
              <input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>"
                id="password" name="password" value="<?php echo h($datas['password'] ?? ''); ?>">
              <?php if (!empty($errors['password'])): ?>
                <div class="invalid-feedback"><?php echo h($errors['password']); ?></div>
              <?php endif; ?>
            </div>
            <div class="mb-4">
              <label for="confirm_password" class="form-label">パスワード確認</label>
              <input type="password"
                class="form-control <?php echo !empty($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                id="confirm_password" name="confirm_password"
                value="<?php echo h($datas['confirm_password'] ?? ''); ?>">
              <?php if (!empty($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?php echo h($errors['confirm_password']); ?></div>
              <?php endif; ?>
            </div>
            <?php echo insertCsrfToken('admin-login'); ?>
            <div class="d-grid gap-2 d-md-block">
              <button type="submit" class="btn btn-primary">新規登録</button>
            </div>
          </form>
          <p class="mt-3 text-left">既に登録済みの方はこちらから <a href="login.php">ログイン</a></p>
        </div>
      </div>
    </div>
  </div>
</body>
<?php require_once dirname(__DIR__) . '/Admin/parts/footer.php'; ?>