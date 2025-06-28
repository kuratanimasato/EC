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

$product = null;
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  //販売ステータス日本語
  $sales_status_labels = [
    'active' => '販売中',
    'inactive' => '販売停止中',
  ];
  // GETでidが渡されているかチェック
  $product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
  if (!$product_id) {
    $error_message = '商品IDが不正です。';
  } else {
    // 商品情報を取得
    try {
      $stmt = $pdo->prepare("SELECT p.*, g.genre_name, p.is_recommended FROM products p LEFT JOIN genres g ON p.genre_id = g.genre_id WHERE p.product_id = :product_id AND p.is_deleted = 0");
      $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
      $stmt->execute();
      $product = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$product) {
        $error_message = '商品が見つかりません。';
      }
    } catch (PDOException $e) {
      $error_message = 'データベースエラー: ' . h($e->getMessage());
    }
  }
}
$colors = [];
if (!empty($product['product_id'])) {
  $stmt = $pdo->prepare(
    "SELECT c.color_name, c.color_code FROM product_colors pc
     JOIN colors c ON pc.color_id = c.color_id
     WHERE pc.product_id = :pid"
  );
  $stmt->execute([':pid' => $product['product_id']]);
  $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include dirname(__DIR__, 2) . '/Admin/parts/header.php'; ?>

<body>
  <div class="container mt-4">
    <h1 class="mb-4">商品詳細</h1>
    <?php if ($error_message): ?>
      <div class="alert alert-danger"><?php echo h($error_message); ?></div>
    <?php elseif ($product): ?>
      <table class="table table-bordered">
        <colgroup>
          <col style="width: 140px;">
          <col>
        </colgroup>
        <tr>
          <th>商品ID</th>
          <td><?php echo h($product['product_id']); ?></td>
        </tr>
        <tr>
          <th>ジャンル</th>
          <td><?php echo h($product['genre_name'] ?? $product['genre_id']); ?></td>
        </tr>
        <tr>
          <th>商品名</th>
          <td><?php echo h($product['product_name']); ?></td>
        </tr>
        <tr>
          <th>商品画像</th>
          <td>
            <?php if (!empty($product['product_image'])): ?>
              <img src="/uploads/images/<?php echo h(basename($product['product_image'])); ?>" alt="商品画像"
                style="max-width:250px;">
            <?php else: ?>
              <span class="text-muted">画像なし</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>カラー</th>
          <td>
            <?php if ($colors): ?>
              <?php foreach ($colors as $color): ?>
                <span style="display:inline-block;vertical-align:middle;margin-right:8px;">
                  <?php if ($color['color_code']): ?>
                    <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:<?php echo h($color['color_code']); ?>;border:1px
    solid #ccc;vertical-align:middle;margin-right:3px;"></span>
                  <?php endif; ?>
                  <?php echo h($color['color_name']); ?>
                </span> <?php endforeach; ?>   <?php else: ?>
              <span class="text-muted">なし</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>説明</th>
          <td><?php echo nl2br(h($product['description'])); ?></td>
        </tr>
        <tr>
          <th>販売状況</th>
          <td><?php
          $status = $product['sales_status'];
          ;
          echo isset($sales_status_labels[$status]) ? h($sales_status_labels[$status]) : h($status);
          ?></td>
        </tr>
        <th>おすすめ</th>
        <td>
          <?php if ($product['is_recommended'] == 1): ?>
            <span class="badge bg-success">おすすめ商品</span>
          <?php else: ?>
            <span class="text-muted">通常商品</span>
          <?php endif; ?>
        </td>
        <tr>
          <th>在庫</th>
          <td><?php echo h($product['stock']); ?></td>
        </tr>
        <tr>
          <th>価格</th>
          <td><?php echo number_format($product['price_without_tax']); ?> 円</td>
        </tr>
      </table>
      <a href="products.php" class="btn btn-secondary btn-sm">一覧へ戻る</a>
    <?php endif; ?>
  </div>
  <?php include dirname(__DIR__, 2) . '/Admin/parts/footer.php'; ?>