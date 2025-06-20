<?php
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
require_once dirname(__DIR__, 2) . '/app/functions.php';
startSession();
if (isset($_GET['admin_user']) && $_GET['admin_user'] === 'true') {
  destroySession();
  header("location:login.php");
  exit;
}
if (!isAdminLoggedIn()) {
  header("location:login.php");
  exit;
}

//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  $status_labels = [
    'new' => '新規',
    'processing' => '処理中',
    'shipped' => '発送済み',
    'delivered' => '配達済み',
    'cancelled' => 'キャンセル'
  ];
  setToken();

}
//ページング設定
$limit = 10;
// 現在のページ番号
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;

try {
  $pdo->beginTransaction();
  $total_sql = "SELECT COUNT(*)  FROM orders";
  $total_stmt = $pdo->query($total_sql);
  $total_orders = $total_stmt->fetchColumn();
  $total_pages = ceil($total_orders / $limit);
  $pdo->commit();
} catch (PDOException $e) {
  $db_error = 'データベース接続エラー: ' . $e->getMessage();
  error_log($db_error);
  $error_message = "処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
  exit;
}
//POST通信だった場合は処理を開始
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
}
try {
  $pdo->beginTransaction();
  $sql = "SELECT 
    o.order_id,
    o.member_id,
    sa.name AS shipping_name,
    sa.phone_number,
    o.billing_amount,
    o.order_status,
    o.created_at AS order_created_at,
    o.updated_at AS order_updated_at
FROM orders o
INNER JOIN shipping_address sa
    ON o.shipping_address_id = sa.shipping_address_id
ORDER BY o.created_at DESC
LIMIT :limit OFFSET :offset";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $pdo->commit();
} catch (Exception $e) {
  $pdo->rollback();
  // エラーログを記録
  error_log("Error in orders.php: " . $e->getMessage());
  // ユーザーにエラーメッセージを表示
  $error_message = "注文処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
  exit;
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
      <?php if (isset($error_message)): ?>
        <div class="alert alert-danger text-center">
          <?php echo h($error_message); ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show text-center fade-message" role="alert">
          <?php echo h($_GET['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
          </button>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center fade-message" role="alert">
          <?php echo h($_GET['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <div class="row">
        <div class="row justify-content-center">
          <div class="col-lg-12 col-xl-12">
            <div class="card">
              <div class="card-header">
                <a href="../dashboard.php" class="btn btn-primary">ダッシュボード画面へ戻る</a>
                <h1 class="mb-0 text-center">注文管理</h1>
              </div class="card-body">
              <?php if (!empty($order_data)): ?>
                <div class="table-responsive　">
                  <table class="table table-striped table-bordered table-hover text-center">
                    <thead class="table-dark">
                      <tr>
                        <th>注文ID</th>
                        <th>会員ID</th>
                        <th>氏名</th>
                        <th>電話番号</th>
                        <th>合計金額</th>
                        <th>注文日時</th>
                        <th>更新日時</th>
                        <th>注文ステータス</th>
                        <th>操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($order_data as $order_datas): ?>
                        <tr>
                          <td><?php echo h(str_pad($order_datas['order_id'], 4, '0', STR_PAD_LEFT)); ?></td>
                          <td><?php echo h($order_datas['member_id']); ?></td>
                          <td><?php echo h($order_datas['shipping_name']); ?></td>
                          <td><?php echo h($order_datas['phone_number']); ?></td>
                          <td><?php echo h(number_format($order_datas['billing_amount'])); ?>円</td>
                          <td><?php echo h(date('Y年m月d日 H:i:s', strtotime($order_datas['order_created_at']))); ?></td>
                          <td><?php echo h(date('Y年m月d日 H:i:s', strtotime($order_datas['order_updated_at']))); ?></td>
                          <td>
                            <?php
                            $status = $order_datas['order_status'];
                            echo h($status_labels[$status] ?? $status);
                            ?>
                          </td>
                          <td class="text-center">
                            <a href="view_order.php?order_id=<?php echo h($order_datas['order_id']); ?>"
                              class="btn btn-primary btn-sm">詳細</a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php if ($total_pages > 1): ?>
                    <nav>
                      <ul class="pagination justify-content-center mt-3">
                        <?php if ($page > 1): ?>
                          <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">前へ</a>
                          </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                          <li class="page-item<?php if ($i == $page)
                            echo ' active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                          </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                          <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">次へ</a>
                          </li>
                        <?php endif; ?>
                      </ul>
                    </nav>
                  <?php endif; ?>
                </div>
              <?php elseif (empty($order_data)): ?>
                <div class="alert alert-info text-center">
                  現在、受注データはありません。
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGCdivZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous">
      </script>
    <script>
      window.addEventListener('DOMContentLoaded', () => {
        const messages = document.querySelectorAll('.fade-message');
        messages.forEach(msg => {
          setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500); // DOMから削除
          }, 5000);
        });
      });
    </script>
  </body>

</html>