<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
include __DIR__ . '/header.php';
include dirname(__DIR__) . '/parts/sub-navigation.php';
startSession();

$db_error_new = '';
$new_products = [];
$limit = 8;
// 現在のページ番号取得（1ページ目がデフォルト）
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;

try {
  // 総商品数を取得（削除されていないものだけ）
  $total_sql = "SELECT COUNT(*) FROM products WHERE is_deleted = 0 AND sales_status = 'active'";
  $total_stmt = $pdo->query($total_sql);
  $total_products = $total_stmt->fetchColumn();
  $total_pages = ceil($total_products / $limit);

  // ページネーション用にLIMITとOFFSETを指定して新着商品取得
  $sql = "SELECT product_id, product_name, price_without_tax, product_image
          FROM products
          WHERE is_deleted = 0 AND sales_status = 'active'
          ORDER BY created_at DESC
          LIMIT :limit OFFSET :offset";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $new_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  $db_error_new = '商品一覧の取得中にエラーが発生しました: ' . h($e->getMessage());
  $total_products = 0;
  $total_pages = 0;
}
?>
<main>
  <div class="container">
    <div class="main-content-wrapper">
      <?php include dirname(__DIR__) . '/parts/sidebar.php'; ?>
      <section class="product-section">
        <h2>新着商品一覧</h2>
        <?php if (!$db_error_new && $total_products > 0): ?>
          <div class="product-count-info" style="text-align: center; margin-bottom: 15px; font-size: 0.9em; color: #555;">
            全 <?php echo h($total_products); ?> 件中
            <?php echo h($offset + 1); ?> - <?php echo h($offset + count($new_products)); ?> 件表示
          </div>
        <?php endif; ?>
        <?php if ($db_error_new): ?>
          <p class="text-danger text-center"><?php echo $db_error_new; ?></p>
        <?php elseif (empty($new_products)): ?>
          <p class="text-center">現在、新着商品はありません。</p>
        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($new_products as $product): ?>
              <div class="product-item">
                <a href="detail.php?id=<?php echo h($product['product_id']); ?>" class="product-image-link">
                  <div class="product-image">
                    <img src="/uploads/images/<?php echo h(basename($product['product_image'] ?: 'noimage.png')); ?>"
                      alt="<?php echo h($product['product_name']); ?>">
                  </div>
                  <p class="product-name"><?php echo h($product['product_name']); ?></p>
                  <div class="product-footer">
                    <p class="product-price">￥<?php echo h(number_format($product['price_without_tax'])); ?></p>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
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