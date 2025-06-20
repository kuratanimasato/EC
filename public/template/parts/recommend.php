<?php
require_once dirname(__DIR__, 3) . '/app/functions.php';
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
startSession();
$db_error_recommend = '';
$limit = 8;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;

$total_recommended_products = 0;
$total_pages = 0;

try {
  $total_sql = "SELECT COUNT(*) FROM products WHERE is_recommended = 1 AND sales_status = 'active' AND is_deleted = 0";
  $total_stmt = $pdo->query($total_sql);
  $total_recommended_products = $total_stmt->fetchColumn();
  $total_pages = ceil($total_recommended_products / $limit);

} catch (PDOException $e) {
  $recommended_products = [];
  $total_recommended_products = 0;
  $total_pages = 0;
}
if (isset($pdo)) {
  try {
    $sql = "SELECT product_id, product_name, price_without_tax, product_image
                FROM products
                WHERE is_recommended = 1 AND sales_status = 'active'
                ORDER BY created_at DESC
                LIMIT 4";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recommended_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

  } catch (PDOException $e) {
    $db_error_recommend = 'おすすめ商品の読み込み中にエラーが発生しました: ' . h($e->getMessage());
  }
}
?>
<?php if (!empty($recommended_products)): ?>
  <section class="product-section">
    <h2>おすすめ商品</h2>
    <?php if ($db_error_recommend): ?>
      <p class="text-danger text-center"><?php echo $db_error_recommend; ?></p>
    <?php elseif (empty($recommended_products)): ?>
      <p class="text-center">現在おすすめ商品はありません。</p>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($recommended_products as $product): ?>
          <div class="product-item">
            <a href="/template/front/detail.php?id=<?php echo h($product['product_id']); ?>" class="product-image-link">
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
  </section>
<?php endif; ?>