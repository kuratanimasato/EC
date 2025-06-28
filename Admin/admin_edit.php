<?php
require_once dirname(__DIR__) . '/app/database/db_connect.php';
require_once dirname(__DIR__) . '/app/functions.php';

// セッションを開始
if (session_status() === PHP_SESSION_NONE) {
  startSession();
}

// ログインしていなければログインページへリダイレクト
if (!isAdminLoggedIn()) {
  header("location: login.php");
  exit;
}

// 初期化
$errors = [];
$success_message = '';
$datas = [
  'administrator_id' => '',
  'admin_user' => '',
  'email' => '',
  'current_password' => '',
  'new_password' => '',
  'confirm_new_password' => ''
];

// GETリクエストからIDを取得
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("location: admin_settings.php");
  exit;
}

$datas['administrator_id'] = $_GET['id'];

// GET通信の場合
if ($_SERVER['REQUEST_METHOD'] != 'POST') {

  $sql = "SELECT admin_user, email FROM administrators WHERE administrator_id = :administrator_id";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':administrator_id', $datas['administrator_id'], PDO::PARAM_INT);
  $stmt->execute();
  $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($admin_data) {
    $datas['admin_user'] = $admin_data['admin_user'];
    $datas['email'] = $admin_data['email'];
  }
}

// POST通信の場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if (!validateCsrfToken('admin-edit')) {
    // CSRFトークンが無効な場合はエラーメッセージを表示
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }

  foreach ($datas as $key => $value) {
    if ($value = filter_input(INPUT_POST, $key, FILTER_DEFAULT)) {
      $datas[$key] = $value;
    }
  }

  $errors = validateAdmin_editData($datas, false);

  if (empty($errors)) {
    $password_verified = true;

    if (!empty($datas['new_password'])) {
      $sql = "SELECT password FROM administrators WHERE administrator_id = :administrator_id";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':administrator_id', $datas['administrator_id'], PDO::PARAM_INT);
      $stmt->execute();
      $admin = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$admin || !password_verify($datas['current_password'], $admin['password'])) {
        $errors['current_password'] = 'パスワードを入力してください';
        $password_verified = false;
      }
    }

    if ($password_verified) {
      try {
        $pdo->beginTransaction();
        $original_session_admin_user = $_SESSION['admin_user'];

        $sql = "UPDATE administrators SET 
                admin_user = :admin_user,
                email = :email,
                updated_at = :updated_at";

        $params = [
          ':admin_user' => $datas['admin_user'],
          ':email' => $datas['email'],
          ':updated_at' => date('Y-m-d H:i:s'),
          ':administrator_id' => $datas['administrator_id']
        ];
        if (!empty($datas['new_password'])) {
          $sql .= ", password = :password";
          $params[':password'] = password_hash($datas['new_password'], PASSWORD_DEFAULT);
        }
        $sql .= " WHERE administrator_id = :administrator_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pdo->commit();
        if ($original_session_admin_user !== $datas['admin_user']) {
          $_SESSION['admin_user'] = $datas['admin_user'];
        }
        $_SESSION['flash_message'] = '管理者情報を更新しました。';
        $_SESSION['flash_type'] = 'success'; // メッセージの種類（例: success, error, info）
        header("location: admin_settings.php");
        exit;
      } catch (PDOException $e) {
        $pdo->rollBack();
        $errors['db'] = 'データベースエラーが発生しました。';
      }
    }
  }
}
?>
<?php include dirname(__DIR__) . '/Admin/parts/header.php'; ?>

<body>
  <div class="container mt-4">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo h($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
      <div class="alert alert-success">
        <?php echo h($success_message); ?>
      </div>
    <?php endif; ?>
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card">
          <div class="card-header">
            <h1 class="mb-0 text-center">管理者情報編集</h1>
          </div>
          <div class="card-body">

            <form action="<?php echo h($_SERVER['SCRIPT_NAME']) . '?id=' . h($datas['administrator_id']); ?>"
              method="POST">
              <?php echo insertCsrfToken('admin-edit'); ?>
              <input type="hidden" name="administrator_id" value="<?php echo h($datas['administrator_id']); ?>">

              <div class="row mb-3">
                <label for="admin_user" class="col-sm-4 col-form-label text-sm-end">管理者名:</label>
                <div class="col-sm-8">
                  <input type="text"
                    class="form-control <?php echo (!empty($errors['admin_user'])) ? 'is-invalid' : ''; ?>"
                    id="admin_user" name="admin_user" value="<?php echo h($datas['admin_user']); ?>" required>
                  <?php if (!empty($errors['admin_user'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['admin_user']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="row mb-3">
                <label for="email" class="col-sm-4 col-form-label text-sm-end">メールアドレス:</label>
                <div class="col-sm-8">
                  <input type="email" class="form-control <?php echo (!empty($errors['email'])) ? 'is-invalid' : ''; ?>"
                    id="email" name="email" value="<?php echo h($datas['email']); ?>" required>
                  <?php if (!empty($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['email']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="row mb-3">
                <label for="current_password" class="col-sm-4 col-form-label text-sm-end">現在のパスワード:</label>
                <div class="col-sm-8">
                  <input type="password"
                    class="form-control <?php echo (!empty($errors['current_password'])) ? 'is-invalid' : ''; ?>"
                    id="current_password" name="current_password" value="">
                  <small class="form-text text-muted">パスワードを変更する場合のみ入力してください。</small>
                  <?php if (!empty($errors['current_password'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['current_password']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="row mb-3">
                <label for="new_password" class="col-sm-4 col-form-label text-sm-end">新しいパスワード:</label>
                <div class="col-sm-8">
                  <input type="password"
                    class="form-control <?php echo (!empty($errors['new_password'])) ? 'is-invalid' : ''; ?>"
                    id="new_password" name="new_password" placeholder="(変更する場合のみ入力)">
                  <?php if (!empty($errors['new_password'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['new_password']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="row mb-3">
                <label for="confirm_new_password" class="col-sm-4 col-form-label text-sm-end">新しいパスワード (確認):</label>
                <div class="col-sm-8">
                  <input type="password"
                    class="form-control <?php echo (!empty($errors['confirm_new_password'])) ? 'is-invalid' : ''; ?>"
                    id="confirm_new_password" name="confirm_new_password" placeholder="(変更する場合のみ入力)">
                  <?php if (!empty($errors['confirm_new_password'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['confirm_new_password']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                <a href="admin_settings.php" class="btn btn-secondary order-sm-1  btn-sm">キャンセル</a>
                <button type="submit" class="btn btn-primary order-sm-2  btn-sm">更新</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include dirname(__DIR__) . '/Admin/parts/script.php'; ?>