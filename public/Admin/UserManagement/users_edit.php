<?php
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
require_once dirname(__DIR__, 3) . '/app/functions.php';
// セッションを開始
startSession();
// ログインしていなければログインページへリダイレクト
if (!isAdminLoggedIn()) {
  header("location: login.php");
  exit;
}
// 初期化
$errors = [];
$success_message = '';
$datas = [
  'member_id' => '',
  'name' => '',
  'email' => '',
  'created_at' => '',
  'updated_at' => ''
];

// GET通信だった場合はトークンをセットし、既存の情報を取得
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $datas['member_id'] = $_GET['id'];
    try {
      // 既存の会員情報を取得してフォームに表示
      $pdo->beginTransaction();
      $sql = "SELECT member_id, name, email FROM members WHERE member_id = :member_id";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':member_id', $datas['member_id'], PDO::PARAM_INT);
      $stmt->execute();
      $member_data = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($member_data) {
        $datas['member_id'] = $member_data['member_id'];
        $datas['name'] = $member_data['name'];
        $datas['email'] = $member_data['email'];
      } else {
        $errors['load'] = '指定された会員情報が見つかりません。';
        header("location: users.php" . urldecode($errors["load"]));
        exit;
      }
      $pdo->commit();
    } catch (PDOException $e) {
      $errors['db'] = 'データベースエラーが発生しました。: ' . $e->getMessage();
      header("location: users.php?error=" . urlencode($errors['db']));
      $pdo->rollback();
      exit;
    }
    // GET通信時はトークンをセット
    setToken();

  } else {
    // IDが指定されていない場合は会員一覧へリダイレクト
    header("location: users.php?error=" . urlencode('編集対象の会員IDが指定されていません。'));
    exit;
  }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  checkToken();
  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    if ($value = filter_input(INPUT_POST, $key, FILTER_DEFAULT)) {
      $datas[$key] = $value;
    }
  }
  // バリデーション
  $errors = validateMemberEditData($datas);
  if (empty($errors)) {
    $update_fields = [
      'member_id' => $datas['member_id'],
      'name' => $datas['name'],
      'email' => $datas['email'],
      'updated_at' => date('Y-m-d H:i:s')
    ];

    try {
      $pdo->beginTransaction();
      $sql_update = "UPDATE members SET name = :name, email = :email, updated_at = :updated_at WHERE member_id = :member_id";
      $stmt_update = $pdo->prepare($sql_update);
      foreach ($update_fields as $key => $value) {
        $stmt_update->bindValue(":$key", $value);
      }
      $stmt_update->bindValue(':member_id', $datas['member_id'], PDO::PARAM_INT);
      $stmt_update->execute();
      $pdo->commit();
      $success_message = '会員情報を更新しました。';
      header("location: users.php?success=" . urlencode($success_message));
      exit;
    } catch (PDOException $e) {
      $errors['db'] = 'データベースエラーが発生しました。: ' . $e->getMessage();
    }
  }
}

?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者一覧</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  </head>

  <body>
    <div class="container mt-4">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <h1 class="mb-0 text-center">会員編集</h1>
            </div>
            <div class="card-body">
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
                </div> <?php endif; ?>
              <form action="users_edit.php?id=<?php echo h($datas['member_id']); ?>" method="POST">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">
                <div class="mb-3">
                  <label for="name" class="form-label">ID</label>
                  <input type="text" class="form-control" id="member_id" name="member_id"
                    value="<?php echo h($datas['member_id']); ?>" disabled readonly>
                </div>
                <div class="mb-3">
                  <label for="name" class="form-label">氏名</label>
                  <input type="text" class="form-control" id="name" name="name" value="<?php echo h($datas['name']); ?>"
                    required>
                </div>

                <div class="mb-3">
                  <label for="email" class="form-label">メールアドレス</label>
                  <input type="email" class="form-control" id="email" name="email"
                    value="<?php echo h($datas['email']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="users.php" class="btn btn-secondary">一覧に戻る</a>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous">
      </script>
  </body>
  </body>

</html>