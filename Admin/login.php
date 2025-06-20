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

//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
}

//POST通信だった場合はログイン処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  ////CSRF対策
  checkToken();

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
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>管理画面ログイン</title>
    <link rel="icon" href="favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous">
      </script>
  </head>

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
                <div class="mb-3">
                  <label for="email" class="form-label">メールアドレス</label>
                  <input type="text" name="email" id="email"
                    class="form-control <?php echo (!empty($errors['email'])) ? 'is-invalid' : ''; ?>"
                    value="<?php echo h($datas['email'] ?? ''); ?>">
                  <?php if (!empty($errors['email'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['email']); ?></div>
                  <?php endif; ?>
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">パスワード</label>
                  <input type="password" name="password" id="password"
                    class="form-control <?php echo (!empty($errors['password'])) ? 'is-invalid' : ''; ?>"
                    value="<?php echo h($datas['password'] ?? ''); ?>">
                  <?php if (!empty($errors['password'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['password']); ?></div>
                  <?php endif; ?>
                </div>
                <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">
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
  </body>

</html>