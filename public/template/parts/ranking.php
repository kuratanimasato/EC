<?php
require_once dirname(__DIR__, 3) . '/app/functions.php';
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
startSession();
$datas = [
  'product_id' => '',
  'product_image' => '',
  'product_name' => '',
  'price_without_tax' => '',
  'sales_status' => '',
];
$ranked_products_from_db = [];
$ranking_error_message = '';
//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
  $ranked_products_from_db = [];
  if (isset($pdo))
    try {
      $sql = "SELECT p.product_id, p.product_name, p.price_without_tax, p.product_image, COUNT(op.product_id) AS order_count
            FROM products p
            LEFT JOIN order_products op ON p.product_id = op.product_id
            WHERE p.sales_status = 'active' AND p.is_deleted = 0
            GROUP BY p.product_id
            ORDER BY order_count DESC, p.product_id ASC
            LIMIT 4";
      $stmt = $pdo->prepare($sql);
      $stmt->execute();
      $ranked_products_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
      error_log("Ranking part (parts/ranking.php) PDOException: " . $e->getMessage());
      $ranking_error_message = 'ランキングの読み込み中にエラーが発生しました。';
    }
  //POST通信だった場合はログイン処理を開始
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    ////CSRF対策
    checkToken();
    // POSTされてきたデータを変数に格納
    foreach ($datas as $key => $value) {
      $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
      if ($postValue !== null) { // filter_inputがnullや空文字を返す場合も考慮
        $datas[$key] = $postValue;
      }
    }
  }
}

?>
<!-- ===== ここからランキングセクション ===== -->
<section class="product-section ranking-section">
  <h2>人気ランキング</h2>
  <div class="product-grid">
    <?php if (!empty($ranking_error_message)): ?>
      <p class="text-danger text-center" style="padding: 20px 0;"><?php echo h($ranking_error_message); ?></p>
    <?php elseif (empty($ranked_products_from_db)): ?>
      <p class="text-center" style="padding: 20px 0;">現在、人気ランキング対象の商品はありません。</p>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($ranked_products_from_db as $index => $product): ?>
          <div class="product-item ranking-item">
            <span class="ranking-badge"><?= $index + 1 ?></span>
            <a href="/template/front/detail.php?id=<?php echo h($product['product_id']); ?>">
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
  </div>
  <div class="more-link">
    <a href="/template/front/ranking-list.php">ランキングをもっと見る<i class="fa fa-long-arrow-right"></i></a>
  </div>
</section>