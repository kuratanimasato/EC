<?php
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
require_once(dirname(__FILE__, 2) . "/../app/functions.php");
startSession();
$flash_message = '';
$flash_type = 'info'; // デフォルトのメッセージタイプ
if (isset($_SESSION['flash_message'])) {
  $flash_message = $_SESSION['flash_message'];
  $flash_type = $_SESSION['flash_type'] ?? 'info';
  unset($_SESSION['flash_message']);
  unset($_SESSION['flash_type']);
}
if (isset($_GET['admin_user']) && $_GET['admin_user'] === 'true') {
  destroySession();
  header("location:login.php");
  exit;
}
if (!isAdminLoggedIn()) {
  header("location:login.php");
  exit;
}

try {
  $pdo->beginTransaction();
  $stmt = $pdo->prepare("SELECT * FROM administrators");
  $stmt->execute();
  $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $pdo->commit();
} catch (PDOException $e) {
  echo "DBエラー：" . $e->getMessage();
  $pdo->rollback();
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
      <?php if ($flash_message): ?>
        <div
          class="alert alert-<?php echo h($flash_type === 'success' ? 'success' : ($flash_type === 'error' ? 'danger' : 'info')); ?> alert-dismissible fade show fade-message"
          role="alert">
          <?php echo h($flash_message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <div class="row">
        <div class="row justify-content-center">
          <div class="col-lg-10 col-xl-9">
            <div class="card">
              <div class="card-header">
                <a href="dashboard.php" class="btn btn-primary">ダッシュボード画面へ戻る</a>
                <h1 class="mb-0 text-center">管理者一覧</h1>
              </div>
              <div class="card-body">
                <?php if (!empty($admins)): ?>
                  <div class="table-responsive　">
                    <table class="table table-striped table-bordered table-hover text-center">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>管理者名</th>
                          <th>メールアドレス</th>
                          <th>パスワード</th>
                          <th>登録日</th>
                          <th>更新日</th>
                          <th>操作</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($admins as $admin): ?>
                          <tr>
                            <td><?php echo h($admin['administrator_id']); ?></td>
                            <td><?php echo h($admin['admin_user']); ?></td>
                            <td><?php echo h($admin['email']); ?></td>
                            <td><?php echo h(str_repeat('*', min(strlen($admin['password']), 12))); ?></td>
                            <td><?php echo h(date('m月d日', strtotime($admin['created_at']))); ?></td>
                            <td><?php echo h(date('m月d日', strtotime($admin['updated_at']))); ?></td>
                            <td class="text-center">
                              <button type="button" class="btn btn-primary  btn-sm"
                                onclick="location.href='admin_edit.php?id=<?php echo h($admin['administrator_id']); ?>'">編集</button>
                              <form action="admin_delete.php" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo h($admin['administrator_id']); ?>">
                                <button type="submit" class="btn btn-danger  btn-sm"
                                  onclick="return confirm('本当に削除しますか？');">削除</button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <p class="text-center">登録されている管理情報はありません。</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous">
      </script>
  </body>

</html>