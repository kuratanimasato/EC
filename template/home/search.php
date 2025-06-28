<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__) . '/parts/sub-navigation.php';


$raw_keyword = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
$normalized_keyword = preg_replace('/　/u', ' ', $raw_keyword); // 全角スペースを半角に
$normalized_keyword = trim($normalized_keyword);

// 分割キーワードを取得
$keywords = preg_split('/\s+/', $normalized_keyword);

// ページング設定
$limit = 8;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;

$products = [];
$total = 0;
$total_pages = 0;
$db_error = null;

if (!empty($normalized_keyword)) {
  try {
    // AND検索用条件とパラメータを作成
    $search_conditions = [];
    $params = [];
    foreach ($keywords as $i => $word) {
      $param = ":kw{$i}";
      $search_conditions[] = "(product_name LIKE $param OR description LIKE $param)";
      $params[$param] = '%' . $word . '%';
    }

    // 全件数の取得
    $count_sql = "SELECT COUNT(*) FROM products WHERE is_deleted = 0 AND " . implode(" AND ", $search_conditions);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    $total_pages = ceil($total / $limit);

    // 検索結果の取得
    $sql = "SELECT product_id, product_name, product_image, description, price_without_tax, stock
            FROM products
            WHERE is_deleted = 0 AND " . implode(" AND ", $search_conditions) . "
            ORDER BY product_id DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
      $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

  } catch (PDOException $e) {
    $db_error = "データベースエラーが発生しました: " . h($e->getMessage());
  }
}
?>

<main>
  <div class="container">
    <div class="main-content-wrapper">
      <?php include dirname(__DIR__) . '/parts/sidebar.php'; ?>
      <section class="product-section">
        <h2>検索結果 <?php if (!empty($normalized_keyword))
          echo "：「" . h($normalized_keyword) . "」"; ?></h2>

        <?php if ($db_error): ?>
          <div class="alert alert-danger"><?php echo $db_error; ?></div>
        <?php endif; ?>

        <?php if (empty($normalized_keyword)): ?>
          <p>検索キーワードを入力してください。</p>
        <?php elseif (empty($products) && !$db_error): ?>
          <p>「<?php echo h($normalized_keyword); ?>」に一致する商品が見つかりませんでした。</p>
        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($products as $product): ?>
              <div class="product-item">
                <a href="/template/front/detail.php?id=<?php echo h($product['product_id']); ?>" class="product-image-link">
                  <?php
                  $image_file = 'noimage.png';
                  if (!empty($product['product_image'])) {
                    $basename = basename($product['product_image']);
                    if (strpos($basename, '..') === false && strpos($basename, '/') === false && strpos($basename, '\\') === false) {
                      $image_file = $basename;
                    }
                  }
                  ?>
                  <img src="/uploads/images/<?php echo h($image_file); ?>" alt="<?php echo h($product['product_name']); ?>">
                  <p class="product-name"><?php echo h($product['product_name']); ?></p>
                  <div class="product-footer">
                    <p class="product-price">￥<?php echo number_format($product['price_without_tax']); ?> <span
                        style="font-size:0.8em;">(税抜)</span></p>
                    <?php if (isset($product['stock']) && $product['stock'] <= 0): ?>
                      <p class="stock-status out-of-stock">在庫切れ</p>
                    <?php endif; ?>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
              <ul class="pagination justify-content-center mt-4">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a class="page-link"
                      href="?search=<?php echo urlencode($normalized_keyword); ?>&page=<?php echo $page - 1; ?>">前へ</a>
                  </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <li class="page-item <?php if ($i == $page)
                    echo 'active'; ?>">
                    <a class="page-link"
                      href="?search=<?php echo urlencode($normalized_keyword); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                  </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                  <li class="page-item">
                    <a class="page-link"
                      href="?search=<?php echo urlencode($normalized_keyword); ?>&page=<?php echo $page + 1; ?>">次へ</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    </div>
  </div>
</main>

<?php include dirname(__DIR__) . '/front/footer.php'; ?>