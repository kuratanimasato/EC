<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__) . '/parts/sub-navigation.php';
startSession();

$genre_id = isset($_GET['genre_id']) ? (int) $_GET['genre_id'] : 0;
$genre_name = '';
$products = [];

// ページング設定
$limit = 8;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;
$error_message = '';
$total_pages = 0;

if ($genre_id > 0) {
  try {
    // ジャンル名の取得
    $stmt = $pdo->prepare("SELECT genre_name FROM genres WHERE genre_id = :genre_id AND is_deleted = 0");
    $stmt->execute([':genre_id' => $genre_id]);
    $genre = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($genre) {
      $genre_name = $genre['genre_name'];

      // 商品総数の取得（ジャンルごと）
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE genre_id = :genre_id AND is_deleted = 0");
      $stmt->execute([':genre_id' => $genre_id]);
      $total_products = $stmt->fetchColumn();
      $total_pages = ceil($total_products / $limit);

      // 商品一覧の取得（ページ分け）
      $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE genre_id = :genre_id AND is_deleted = 0 ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
      );
      $stmt->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
      $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
      $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (PDOException $e) {
    error_log("Category product list error: " . $e->getMessage());
    $error_message = "処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
  }
}
?>
<main>
  <div class="container">
    <div class="main-content-wrapper">
      <?php include dirname(__DIR__) . '/parts/sidebar.php'; ?>
      <section class="product-section">
        <h2><?php echo h($genre_name); ?>の商品一覧</h2>

        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($products)): ?>
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
        <?php else: ?>
          <p>このカテゴリーには商品がありません。</p>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
          <ul class="Pagination">
            <?php if ($page > 1): ?>
              <li class="Pagination-Item">
                <a class="Pagination-Item-Link" href="?genre_id=<?php echo $genre_id; ?>&page=<?php echo $page - 1; ?>">
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
                  echo ' isActive'; ?>" href="?genre_id=<?php echo $genre_id; ?>&page=<?php echo $i; ?>">
                  <span><?php echo $i; ?></span>
                </a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <li class="Pagination-Item">
                <a class="Pagination-Item-Link" href="?genre_id=<?php echo $genre_id; ?>&page=<?php echo $page + 1; ?>">
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
<?php include dirname(__DIR__) . '/front/footer.php'; ?>