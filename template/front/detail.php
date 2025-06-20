<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__) . '/parts/sub-navigation.php';
$product = null;
$error_message = '';

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($product_id) {
  try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :product_id AND is_deleted = 0");
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
// 商品カラー一覧を取得
$color_options = [];
if ($product && !empty($product['product_id'])) {
  $stmt = $pdo->prepare(
    "SELECT c.color_name, c.color_code FROM product_colors pc
      JOIN colors c ON pc.color_id = c.color_id
      WHERE pc.product_id = :pid"
  );
  $stmt->execute([':pid' => $product['product_id']]);
  $color_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
}
// 商品取得時にジャンル名も取得
if ($product_id) {
  try {
    $stmt = $pdo->prepare(
      "SELECT p.*, g.genre_name 
      FROM products p 
      LEFT JOIN genres g ON p.genre_id = g.genre_id 
      WHERE p.product_id = :product_id AND p.is_deleted = 0"
    );
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
//POST通信だった場合はカート追加処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = h($_POST['name'] ?? '');
  $price = h($_POST['price'] ?? '');
  $count = h($_POST['count'] ?? '');
  $color = h($_POST['color'] ?? '');
  $product_image = h($_POST['product_image'] ?? '');
  $id = h($_POST['id'] ?? '');
  if (!isUserLoggedIn()) {
    $_SESSION['add_to_cart_after_login_attempt'] = [
      'id' => $_POST['id'] ?? null,
      'name' => $_POST['name'] ?? null,
      'price' => $_POST['price'] ?? null,
      'color' => $_POST['color'] ?? null,
      'count' => $_POST['count'] ?? null,
      'product_image' => $product['product_image'] ?? null,
      'original_page' => $_SERVER['REQUEST_URI'] ?? null
    ];
    header('Location: /template/member/member_login.php?redirect_to_after_login=' . urlencode($_SERVER['REQUEST_URI']) . '&redirect_message=' . urlencode('カートに商品を追加するにはログインが必要です。'));
    exit;
  }

  ////CSRF対策
  checkToken();
  //受け取ったデータをセッションに保存
  if ($name != '' && $price != '' && $count != '' && $color != '' && $id != '') {
    $item_found = false;
    if (!isset($_SESSION['cart'])) {
      $_SESSION['cart'] = [];
    }
    foreach ($_SESSION['cart'] as $key => $cart_item) {
      if ($cart_item['id'] == $id && $cart_item['color'] == $color) {
        $cart_item['count'] += $count;
        $_SESSION['cart'][$key] = $cart_item;
        $item_found = true;
        break;
      }
    }
    if (!$item_found) {
      $_SESSION['cart'][] = [
        'id' => $product['product_id'],
        'name' => $product['product_name'],
        'price' => $product['price_without_tax'],
        'color' => $color,
        'count' => $count,
        'product_image' => $product['product_image'],
      ];
    }
    $_SESSION['add_to_cart_message'] = 'カートに商品を追加しました';
    header('Location: cart.php');
    exit;
  }
  $id = $_GET['id'] ?? $_GET['product_id'] ?? null;
}

$image_file = !empty($product['product_image']) ? basename($product['product_image']) : 'noimage.png';
if (strpos($image_file, '..') !== false || strpos($image_file, '/') !== false || strpos($image_file, '\\') !== false) {
  $image_file = 'noimage.png';
}
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
?>

<main>
  <div class="container">
    <div class="main-content-wrapper">
      <?php include dirname(__DIR__) . '/parts/sidebar.php'; ?>
      <?php if ($error_message): ?>
        <div class="alert alert-danger">
          <?php echo h($error_message); ?>
        </div>
      <?php elseif ($product): ?>
        <div class="product-details">
          <!-- 左側：画像＋説明 -->
          <div class="product-image-desc" style="flex: 1; min-width: 320px;">
            <img src="/uploads/images/<?php echo h(basename($product['product_image'] ?: 'noimage.png')); ?>"
              alt="<?php echo h($product['product_name']); ?>" style="max-width:100%; height:auto;">
            <p class="description mt-3"><?php echo nl2br(h($product['description'])); ?></p>
          </div>
          <!-- 右側：タイトル・価格・カラー・数量・カートボタン -->
          <div class="cart-actions">
            <?php if (!empty($product['genre_name'])): ?>
              <div class="text-muted" style="font-size: 0.9em; margin-bottom: 4px;">
                品名：<?php echo h($product['genre_name']);
                ?>
              </div>
            <?php endif; ?>
            <div class="cart-actions">
              <h1><?php echo h($product['product_name']); ?></h1>
              <p class="price">価格：¥<?php echo number_format($product['price_without_tax']); ?>（税抜き）</p>
              <?php if ((int) $product['stock'] <= 0): ?>
                <div class="alert alert-danger mb-3">
                  在庫切れ
                </div>
                <div style="height:120px;"></div>
              <?php else: ?>
                <?php if (isUserLoggedIn()): ?>
                  <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?id=' . h($product['product_id']); ?>" method="POST">
                    <input type="hidden" name="name" value="<?php echo h($product['product_name']); ?>">
                    <input type="hidden" name="id" value="<?php echo h($product['product_id']); ?>">
                    <input type="hidden" name="price" value="<?php echo h($product['price_without_tax']); ?>">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                    <div class="color-select-wrap">
                      <label for="color-select-form">カラー:</label>
                      <?php if (!empty($color_options)): ?>
                        <div class="color-selector" id="color-selector-container">
                          <?php foreach ($color_options as $color): ?>
                            <span class="color-option" style="background-color: <?php echo h($color['color_code']); ?>;
                        display:inline-block;width:28px;height:28px;
                        border-radius:50%;border:1.5px solid #333;
                        margin-right:8px;vertical-align:middle;
                        box-shadow:0 1px 4px rgba(0, 0, 0, 0.08);" title="<?php echo h($color['color_name']); ?>">
                            </span>
                          <?php endforeach; ?>
                        </div>
                        <select name="color" id="color-select-form" class="color-select" required>
                          <option value="" disabled selected>選択してください</option>
                          <?php foreach ($color_options as $color): ?>
                            <option value="<?php echo h($color['color_name']); ?>">
                              <?php echo h($color['color_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      <?php else: ?>
                        <span class="text-muted">
                          選択可能なカラーはありません
                        </span>
                      <?php endif; ?>
                      <label for="quantity-select-form">数量:</label>
                      <select id="quantity-select-form" name="count">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                          <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                      </select>
                      <button type="submit" class="add-to-cart">
                        <i class="fa-solid fa-cart-shopping"></i>カートに追加
                      </button>
                    <?php else: ?>
                      <a href="/template/member/member_login.php?redirect_to_after_login=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"
                        class="add-to-cart btn btn-warning w-100" style="color:#fff;">
                        ログインしてカートに入れる
                      </a>
                      <div class="mt-2 text-danger">
                        カートに追加するにはログインが必要です。
                      </div>
                    <?php endif; ?>
                </form>
              </div>
            </div>
          <?php endif; ?>
        <?php endif ?>
      </div>
    </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>