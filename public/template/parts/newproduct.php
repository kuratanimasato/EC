<?php
require_once dirname(__DIR__, 3) . '/app/functions.php';
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
startSession();

$db_error_new = '';

if (isset($pdo)) {
  try {
    $sql = "SELECT product_id, product_name, price_without_tax, product_image
            FROM products
            WHERE sales_status = 'active'
            ORDER BY created_at DESC
            LIMIT 4";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $new_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    $db_error_new = '新着商品の読み込み中にエラーが発生しました: ' . h($e->getMessage());
  }
}
?>

<section class="product-section">
  <h2>新着商品</h2>

  <?php if ($db_error_new): ?>
    <p class="text-danger text-center"><?php echo $db_error_new; ?></p>
  <?php elseif (empty($new_products)): ?>
    <p class="text-center">現在新着商品はありません。</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($new_products as $product): ?>
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
    <div class="more-link">
      <a href="/template/front/new-product-list.php">新着商品をもっと見る<i class="fa fa-long-arrow-right"></i></a>
    </div>
  <?php endif; ?>
</section>