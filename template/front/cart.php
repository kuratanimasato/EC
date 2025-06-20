<?php require_once dirname(__DIR__, 2) . '/app/functions.php';
include dirname(__DIR__) . '/front/header.php';
$detail = '/../index.php';
$checkout = '/template/checkout/checkout-options.php';
//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
}
// POST通信だった場合は削除処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // CSRF対策
  checkToken();
  if (isset($_POST['delete_name'])) {
    // delete_nameがセットされているかを確認
    $delete_name = h($_POST['delete_name']);
    // 商品名で削除する場合は、カートアイテムをループして一致するものを削除
    if (!empty($_SESSION['cart'])) {
      foreach ($_SESSION['cart'] as $key => $cart_item) {
        if (isset($cart_item['name']) && $cart_item['name'] === $delete_name) {
          // 商品名が一致する場合、アイテムを削除
          unset($_SESSION['cart'][$key]);
          break; // ループを抜ける
        }
      }
    }
    header('Location: cart.php');
    exit();
  }
}
// カートに追加成功メッセージがあれば表示
$add_to_cart_message = '';
if (isset($_SESSION['add_to_cart_message'])) {
  $add_to_cart_message = $_SESSION['add_to_cart_message'];
  // メッセージを表示したらセッションから削除
  unset($_SESSION['add_to_cart_message']);
}
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total_price = 0;
$tax_rate = 0.10;
?>
<main>
  <div class="container">
    <div class="wrapper last-wrapper">
      <div class="container">
        <div class="wrapper-title">
          <h3>ショッピングカート</h3>
        </div>
        <?php if (!empty($add_to_cart_message)): ?>
          <div class="cart-message">
            <?php echo h($add_to_cart_message); ?>
          </div>
        <?php endif; ?>
        <?php if (empty($cart_items)): ?>
          <div class="cart-empty-message">
            <p>ショッピングカートに商品はありません</p>
          </div>
          <div class="continue-shopping-btn" style="margin-top: 10px;">
            <button type="button" class="btn btn-gray"> <a href="<?php echo $detail ?>">お買い物を続ける</a></button>
          </div>
        <?php else: ?>
          <table class="cart-list">
            <thead>
              <tr>
                <th>商品名</th>
                <th>価格</th>
                <th>カラー</th>
                <th>個数</th>
                <th>小計</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cart_items as $key => $cart_item): ?>
                <?php
                $price_with_tax = floor($cart_item['price'] * (1 + $tax_rate));
                $subtotal = $price_with_tax * (int) $cart_item['count'];
                $total_price += $subtotal;
                ?>
                <tr>
                  <td label="商品名" class="product-name">
                    <?php
                    $image_file = !empty($cart_item['product_image']) ? basename($cart_item['product_image']) : 'noimage.png';
                    if (strpos($image_file, '..') !== false || strpos($image_file, '/') !== false || strpos($image_file, '\\') !== false) {
                      $image_file = 'noimage.png';
                    }
                    ?>
                    <img class="product-img" src="/uploads/images/<?php echo h($image_file); ?>"
                      alt="<?php echo h($cart_item['name']); ?>" style="width:50px;height:50px;object-fit:cover;">
                    <?php echo h($cart_item['name']) ?>
                  </td>
                  <td label="価格：" class="text-right"> <?php echo h($cart_item['price']) ?></td>
                  <td label="カラー"> <?php echo h($cart_item['color']) ?></td>
                  <td label="個数" class="text-right"> <?php echo h($cart_item['count']) ?></td>
                  <td label="小計" class="text-right"><?php echo number_format($subtotal); ?>円</td>
                  <td>
                    <form action="cart.php" method="POST">
                      <input type="hidden" name="delete_name" value="<?php echo h($cart_item['name']); ?>">
                      <!-- CSRFトークンを追加 -->
                      <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                      <button type="submit" class="btn btn-red">削除</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach ?>
              <tr class="total">
                <th colspan="3">合計</th>
                <th colspan="3" class="text-right">￥ <?php echo number_format($total_price); ?>（税込）</th>
              </tr>
            </tbody>
          </table>
          <div class="cart-btn">
            <button type="button" class="btn btn-blue">
              <a href="<?php echo $checkout ?>">購入手続きへ</button></a>
            <button type="button" class="btn btn-gray"> <a href="<?php echo $detail ?>">お買い物を続ける</a></button>
          </div>
        <?php endif; ?>
      </div>
    </div>
</main>

<?php include dirname(__DIR__) . '/front/footer.php'; ?>