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
$datas = [];
//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  //販売ステータス日本語
  $sales_status_labels = [
    'active' => '販売中',
    'inactive' => '販売停止中',
  ];
}

//POST通信だった場合はログイン処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!validateCsrfToken('products')) {
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }
  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
    if ($postValue !== null) {
      $datas[$key] = $postValue;
    }
  }
}
//ページング設定
$limit = 8;
// 現在のページ番号
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;

// 総件数を取得
try {
  $pdo->beginTransaction();
  $total_sql = "SELECT COUNT(*)  FROM products";
  $total_stmt = $pdo->query($total_sql);
  $total_products = $total_stmt->fetchColumn();
  $total_pages = ceil($total_products / $limit);

  $pdo->commit();
} catch (PDOException $e) {
  $db_error = 'データベース接続エラー: ' . $e->getMessage();
  error_log($db_error);
  $error_message = "処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
  exit;
}
try {
  $pdo->beginTransaction();
  $sql = "SELECT p.*, g.genre_name, p.is_recommended FROM products p LEFT JOIN genres g ON p.genre_id = g.genre_id WHERE p.is_deleted = 0 ORDER BY p.product_id ASC LIMIT :limit OFFSET :offset";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
  $stmt->execute();
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $pdo->commit();
} catch (PDOException $e) {
  $db_error = 'データベース接続エラー: ' . $e->getMessage();
  error_log($db_error);
  $error_message = "処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
  exit;
}
$product_ids = array_column($products, 'product_id');
$colors_map = [];
if ($product_ids) {
  // 商品IDが存在する場合のみ、色情報を取得
  $in = str_repeat('?,', count($product_ids) - 1) . '?';
  $sql = "SELECT pc.product_id, c.color_name, c.color_code
            FROM product_colors pc
            JOIN colors c ON pc.color_id = c.color_id
            WHERE pc.product_id IN ($in)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($product_ids);
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $colors_map[$row['product_id']][] = [
      'color_name' => $row['color_name'],
      'color_code' => $row['color_code']
    ];
  }
}
?>
<?php include dirname(__DIR__, 2) . '/Admin/parts/header.php'; ?>

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
              <h1 class="mb-0 text-center">商品管理</h1>
              <div class="text-end">
                <a href="products_create.php" class="btn btn-success">新規作成</a>
              </div>
            </div>
            <div class="card-body">
              <?php if (!empty($products)): ?>
              <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover text-center">
                  <thead class="table-dark">
                    <tr>
                      <th>製品ID</th>
                      <th>ジャンルID</th>
                      <th>商品名</th>
                      <th>商品画像</th>
                      <th>カラー</th>
                      <th>販売状況</th>
                      <th>おすすめ</th>
                      <th>在庫状況</th>
                      <th>価格</th>
                      <th>説明</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                      <td><?php echo h($product['product_id']); ?></td>
                      <td><?php echo h($product['genre_name'] ?? $product['genre_id']); ?></td>
                      <td><?php echo h($product['product_name']); ?></td>
                      <td>
                        <?php if (!empty($product['product_image'])): ?>
                        <img src="/uploads/images/<?php echo h(basename($product['product_image'])); ?>" alt="商品画像"
                          width="60" height="60" style="object-fit:cover; border-radius:4px;">
                        <?php else: ?>
                        <span class="text-muted">画像なし</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php
                            $colors = $colors_map[$product['product_id']] ?? [];
                            if ($colors):
                              foreach ($colors as $color):
                                ?>
                        <span style="display:inline-block;vertical-align:middle;margin-right:6px;">
                          <?php if ($color['color_code']): ?>
                          <span
                            style="display:inline-block;width:16px;height:16px;border-radius:50%;background:<?php echo h($color['color_code']); ?>;border:1px solid #ccc;vertical-align:middle;margin-right:2px;"></span>
                          <?php endif; ?>
                          <?php echo h($color['color_name']); ?>
                        </span>
                        <?php
                              endforeach;
                            else:
                              echo '<span class="text-muted">なし</span>';
                            endif;
                            ?>
                      </td>

                      <td>
                        <?php
                            $status = $product['sales_status'];
                            echo isset($sales_status_labels[$status]) ? h($sales_status_labels[$status]) : h($status);
                            ?>
                      </td>
                      <td>
                        <?php if ($product['is_recommended'] == 1): ?>
                        <span class="badge bg-success">●</span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php
                            if ((string) $product['stock'] === "0") {
                              echo '<span class="text-danger">在庫切れ</span>';
                            } else {
                              echo h($product['stock']);
                            }
                            ?>
                      </td>
                      <td><?php echo number_format($product['price_without_tax']); ?> 円</td>
                      <td> <?php
                          $desc = strip_tags($product['description']);
                          echo h(mb_strimwidth($desc, 0, 20, '...'));
                          ?></td>
                      <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                          <button type=" button" class="btn btn-secondary btn-sm"
                            onclick=" location.href='products_detail.php?id=<?php echo h($product['product_id']); ?>&page=<?php echo $page; ?>'">詳細</button>
                          <button type="button" class="btn btn-primary btn-sm"
                            onclick="location.href='products_edit.php?id=<?php echo h($product['product_id']); ?>&page=<?php echo $page; ?>'">
                            編集
                          </button>
                          <form action="prodcuts_delete.php" method="post" class="m-0">
                            <?php echo insertCsrfToken('products'); ?>
                            <input type="hidden" name="product_id" value="<?php echo h($product['product_id']); ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                              onclick="return confirm('本当に削除しますか？');">削除</button>
                          </form>
                        </div>
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
              <?php else: ?>
              <div class=" text-center">
                現在、登録されている商品はありません。
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php include dirname(__DIR__, 2) . '/Admin/parts/script.php'; ?>