<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__) . '/parts/sub-navigation.php';

//ページング設定
$limit = 8;
// 現在のページ番号
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;
$error_message = '';
try {
  $pdo->beginTransaction();
  $total_sql = "SELECT COUNT(*) FROM products WHERE is_deleted = 0 AND sales_status = 'active'";
  $total_stmt = $pdo->query($total_sql);
  $total_products = $total_stmt->fetchColumn();
  $total_pages = ceil($total_products / $limit);
  $pdo->commit();
} catch (PDOException $e) {
  $db_error = 'データベース接続エラー: ' . $e->getMessage();
  error_log($db_error);
  $error_message = "処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
  $total_products = 0;
  $total_pages = 0;
  exit;
}
try {
  $sql = "SELECT p.*, COUNT(op.product_id) AS order_count
  FROM products p
            LEFT JOIN order_products op ON p.product_id = op.product_id
            WHERE p.sales_status = 'active' AND p.is_deleted = 0
            GROUP BY p.product_id
            ORDER BY order_count DESC, p.product_id ASC
            LIMIT :limit OFFSET :offset";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (!$products) {
    if ($page > 1) {
      $error_message = "指定されたページに商品が見つかりませんでした。";
    }
  }
} catch (PDOException $e) {
  $db_error = 'データベース接続エラー: ' . $e->getMessage();
  $products = [];
  error_log($db_error);
  $error_message = "処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
}

?>
<main>
  <div class="container">
    <div class="main-content-wrapper">
      <?php include dirname(__DIR__) . '/parts/sidebar.php'; ?>
      <section class="product-section">
        <h2>人気ランキング</h2>
        <?php if (empty($error_message) && $total_products > 0): ?>
          <div class="product-count-info" style="text-align: center; margin-bottom: 15px; font-size: 0.9em; color: #555;">
            全 <?php echo h($total_products); ?> 件中
            <?php echo h($offset + 1); ?> - <?php echo h($offset + count($products)); ?> 件表示
          </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <div class="product-grid">
          <?php foreach ($products as $product): ?>
            <div class="product-item">
              <a href="detail.php?id=<?php echo h($product['product_id']); ?>" class="product-image">
                <img src="/uploads/images/<?php echo h(basename($product['product_image'] ?: 'noimage.png')); ?>"
                  alt="<?php echo h($product['product_name']); ?>">
                <p class="product-name"><?php echo h($product['product_name']); ?></p>
                <div class="product-footer">
                  <p class="product-price">￥<?php echo number_format($product['price_without_tax']); ?></p>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($total_pages > 1): ?>
          <ul class="Pagination">
            <?php if ($page > 1): ?>
              <li class="Pagination-Item">
                <a class="Pagination-Item-Link" href="?page=<?php echo $page - 1; ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" class="Pagination-Item-Link-Icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                  </svg>
                </a>
              </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="Pagination-Item">
                <a class="Pagination-Item-Link<?php if ($i == $page)
                  echo ' isActive'; ?>" href="?page=<?php echo $i; ?>">
                  <span><?php echo $i; ?></span>
                </a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <li class="Pagination-Item">
                <a class="Pagination-Item-Link" href="?page=<?php echo $page + 1; ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" class="Pagination-Item-Link-Icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </section>
    </div>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>