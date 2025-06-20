<?php
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
require_once dirname(__DIR__, 3) . '/app/functions.php';
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
  $id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
  if (!$id) {
    // IDが不正な場合はエラーメッセージを設定
    $_SESSION['error'] = "不正なアクセスです。";
    // エラーメッセージを表示するページにリダイレクト
    header("location:orders.php");
    exit;
  }
  $status_labels = [
    'new' => '新規',
    'processing' => '処理中',
    'shipped' => '発送済み',
    'delivered' => '配達済み',
    'cancelled' => 'キャンセル',
  ];
  setToken();
}
//POST通信だった場合は処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
  $order_status = filter_input(INPUT_POST, 'order_status', FILTER_DEFAULT);
  $status_labels = [
    'new' => '新規',
    'processing' => '処理中',
    'shipped' => '発送済み',
    'delivered' => '配達済み',
    'cancelled' => 'キャンセル',
  ];
  ////CSRF対策
  checkToken();
  // ステータス更新
  if ($id && isset($status_labels[$order_status])) {
    $sql = "UPDATE orders SET order_status = :order_status WHERE order_id = :order_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':order_status', $order_status, PDO::PARAM_STR);
    $stmt->bindValue(':order_id', $id, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: view_order.php?order_id=" . $id . "&success=" . urlencode("注文ステータスが更新されました。"));
    exit;
  }

  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    if ($value = filter_input(INPUT_POST, $key, FILTER_DEFAULT)) {
      $datas[$key] = $value;
    }
  }
}
// 注文データを取得とその詳細を取得
try {
  $pdo->beginTransaction();
  $sql = "SELECT
    sa.member_id,
    sa.name AS shipping_name,
    sa.email AS shipping_email,
    sa.postal_code,
    sa.address,
    sa.phone_number,
    o.billing_amount,
    o.order_status,
    o.order_id,
    op.color,
    op.purchase_price_including_tax,
    op.product_name,
    op.quantity,
    o.created_at AS order_created_at,
    o.updated_at AS order_updated_at
  FROM orders o
  INNER JOIN shipping_address sa ON o.shipping_address_id = sa.shipping_address_id
  INNER JOIN order_products op ON o.order_id = op.order_id
  WHERE o.order_id = :order_id";

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':order_id', $id, PDO::PARAM_INT);
  $stmt->execute();
  $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $pdo->commit();
} catch (PDOException $e) {
  // トランザクションのロールバック
  $pdo->rollBack();
  error_log("Error in orders.php: " . $e->getMessage());
  $error_message = "データベースエラーが発生しました。詳細: " . h($e->getMessage());
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
    <div class="container">
      <h1 class="mt-4">注文詳細</h1>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show text-center fade-message" role="alert">
          <?php echo h($_GET['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
          </button>
        </div>
      <?php endif; ?>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>注文ID</th>
            <th>会員ID</th>
            <th>配送先名</th>
            <th>メールアドレス</th>
            <th>郵便番号</th>
            <th>住所</th>
            <th>電話番号</th>
            <th>合計金額</th>
            <th>注文ステータス</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($order_details as $orders): ?>
            <tr>
              <td><?php echo h($orders['order_id']); ?></td>
              <td><?php echo h($orders['member_id']); ?></td>
              <td><?php echo h($orders['shipping_name']); ?></td>
              <td><?php echo h($orders['shipping_email']); ?></td>
              <td><?php echo h($orders['postal_code']); ?></td>
              <td><?php echo h($orders['address']); ?></td>
              <td><?php echo h($orders['phone_number']); ?></td>
              <td><?php echo h(number_format($orders['billing_amount'])); ?>円</td>
              <td>
                <form method="post" action="">
                  <input type="hidden" name="order_id" value="<?php echo h($orders['order_id']); ?>">
                  <select name="order_status" class="form-select form-select-sm d-inline w-auto">
                    <?php foreach ($status_labels as $key => $label): ?>
                      <option value="<?php echo h($key); ?>" <?php if ($orders['order_status'] === $key)
                           echo 'selected'; ?>>
                        <?php echo h($label); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">
                  <button type="submit" class="btn btn-sm btn-primary">変更</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <h2 class="mt-5">商品詳細</h2>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>商品名</th>
              <th>カラー</th>
              <th>数量</th>
              <th>単価（税込）</th>
              <th>小計</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($order_details as $product): ?>
              <tr>
                <td><?php echo h($product['product_name']); ?></td>
                <td><?php echo h($product['color']); ?></td>
                <td><?php echo h($product['quantity']); ?></td>
                <td><?php echo h(number_format($product['purchase_price_including_tax'])); ?>円</td>
                <td><?php echo h(number_format($product['purchase_price_including_tax'] * $product['quantity'])); ?>円</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="d-flex justify-content-end">
          <a href="orders.php" class="btn btn-primary">注文一覧に戻る</a>
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