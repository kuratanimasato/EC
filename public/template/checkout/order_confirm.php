<?php
require_once dirname(__DIR__, 3) . '/app/functions.php';
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';

if (!isUserLoggedIn()) {
  header('Location: /template/checkout/checkout-options.php');
  exit();
}
$paymentMethodKeyMap = [
  'bank_transfer' => 1,
  'credit_card' => 2,
  'cash_on_delivery' => 3,
  'convenience_store' => 4,
  'paypay' => 5,
];
// セッションから注文情報を取得
$cart = $_SESSION['cart'] ?? [];
$shippingInfo = $_SESSION['shipping_info_for_checkout'] ?? [];
$payment_method_key = $shippingInfo['payment_method'] ?? '';
$payment_method_id = $paymentMethodKeyMap[$payment_method_key] ?? null;
$member_id_for_order = $shippingInfo['member_id'] ?? 'null';
$shipping_carrier_key = $shippingInfo['shipping_carrier'] ?? '';
// 配送業者のキーをマッピング
$paymentMethodMap = [
  1 => '銀行振込',
  2 => 'クレジットカード',
  3 => '代金引換',
  4 => 'コンビニ決済',
  5 => 'PayPay',
];

$shippingCarrierMap = [
  'yamato' => 'ヤマト運輸',
  'sagawa' => '佐川急便',
  'japan_post' => '日本郵便',
];
$error_message = '';
// 日本語表記に変換
$paymentLabel = $paymentMethodMap[$payment_method_id] ?? '不明な支払方法';
$shippingCarrierLabel = $shippingCarrierMap[$shipping_carrier_key] ?? '不明な配送業者';
// 合計金額を計算
$total_amount = 0;
if (!empty($cart) && is_array($cart)) {
  foreach ($cart as $item) {
    $item_price = isset($item['price']) ? (float) $item['price'] : 0;
    $item_count = isset($item['count']) ? (int) $item['count'] : 0;
    $total_amount += $item_price * $item_count;
  }
}
// GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
}

// POST通信の場合はCSRFトークンをチェック
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  checkToken();
  global $pdo;
  if (!$pdo) {
    $error_message = "データベース接続に失敗しました。システム管理者にお問い合わせください。" . $e->getMessage();
  } else {
    try {
      // 注文に必要な情報が揃っているか確認
      if (empty($cart) || empty($shippingInfo) || $member_id_for_order === null || empty($payment_method_key) || empty($shipping_carrier_key)) {
        throw new Exception("注文に必要な情報が不足しています。カート、配送情報、またはログイン状態を確認してください。");
      }
      $pdo->beginTransaction();
      // 1. 配送先情報をshipping_addressテーブルに保存
      $stmt_shipping = $pdo->prepare(
        "INSERT INTO shipping_address (member_id, name, email, postal_code, address, phone_number, created_at, updated_at) " .
        "VALUES (:member_id, :name, :email, :postal_code, :address, :phone_number, NOW(), NOW())"
      );
      // 注文情報をデータベースに挿入
      $stmt_shipping->execute([
        ':member_id' => $member_id_for_order,
        ':name' => $shippingInfo['name'] ?? '',
        ':email' => $shippingInfo['email'] ?? '',
        ':postal_code' => $shippingInfo['postal_code'] ?? '',
        ':address' => $shippingInfo['address'] ?? '',
        ':phone_number' => $shippingInfo['phone_number'] ?? ''
      ]);
      $shipping_address_id = $pdo->lastInsertId();
      // 2. 注文データをordersテーブルに保存
      $stmt_order = $pdo->prepare(
        "INSERT INTO orders (member_id, shipping_address_id, billing_amount, payment_method, delivery, order_status, created_at, updated_at) " .
        "VALUES (:member_id, :shipping_address_id, :billing_amount, :payment_method, :delivery, :order_status, NOW(), NOW())"
      );
      // 注文情報をデータベースに挿入
      $stmt_order->execute([
        ':member_id' => $member_id_for_order,
        ':shipping_address_id' => $shipping_address_id,
        ':billing_amount' => $total_amount,
        ':payment_method' => $payment_method_id,
        ':delivery' => $shipping_carrier_key,
        ':order_status' => 'new'// 初期状態は「保留中」
      ]);
      $order_id = $pdo->lastInsertId();
      // 3. 注文商品情報をorder_productsテーブルに挿入
      $stmt_details = $pdo->prepare(
        "INSERT INTO order_products (order_id, product_id,color,purchase_price_including_tax, product_name,  quantity, production_status, created_at, updated_at) " .
        "VALUES (:order_id, :product_id,:color,:purchase_price_including_tax,  :product_name, :quantity, :production_status, NOW(), NOW())"
      );
      foreach ($cart as $item) {
        $stmt_details->execute([
          ':order_id' => $order_id,
          ':product_id' => $item['id'],
          ':product_name' => $item['name'],
          ':purchase_price_including_tax' => $item['price'],
          ':quantity' => $item['count'],
          ':color' => $item['color'],
          ':production_status' => 'pending'
        ]);
      }
      $pdo->commit();
      // 注文関連のセッション情報をクリア
      unset($_SESSION['cart']);
      unset($_SESSION['shipping_info_for_checkout']);
      header('Location: /template/checkout/order_complete.php?order_id=' . $order_id);
      exit();
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('Order creation failed (PDOException): ' . $e->getMessage());
      $error_message = "注文処理中にデータベースエラーが発生しました。詳細: " . h($e->getMessage());
    } catch (PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('Order creation failed (PDOException): ' . $e->getMessage());
      $error_message = "注文処理中に予期せぬエラーが発生しました<br>詳細: " . h($e->getMessage());
    }
  }
}
?>
<main>
  <div class="container">
    <div class="wrapper last-wrapper">
      <div class="container">
        <div class="wrapper-title">
          <h1>ご注文内容確認</h1>
        </div>
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger" role="alert"><?php echo h($error_message); ?></div>
        <?php endif; ?>
        <div class="order-confirm-details">
          <section class="shipping-details ">
            <h2>配送先情報</h2>
            <p><strong>お名前:</strong> <?php echo h($shippingInfo['name'] ?? ''); ?></p>
            <p><strong>メールアドレス:</strong> <?php echo h($shippingInfo['email'] ?? ''); ?></p>
            <p><strong>郵便番号:</strong> <?php echo h($shippingInfo['postal_code'] ?? ''); ?></p>
            <p><strong>ご住所:</strong> <?php echo h($shippingInfo['address'] ?? ''); ?></p>
            <p><strong>電話番号:</strong> <?php echo h($shippingInfo['phone_number'] ?? ''); ?></p>
            <p><strong>配送業者:</strong><?php echo h($shippingCarrierLabel); ?></p>
          </section>

          <section class="payment-details">
            <h2>お支払い方法</h2>
            <p><?php echo h($paymentLabel); ?></p>
          </section>

          <section class="order-summary">
            <h2>ご注文商品</h2>
            <?php if (!empty($cart) && is_array($cart)): ?>
              <table class="cart-list">
                <thead>
                  <tr>
                    <th>商品名</th>
                    <th>価格</th>
                    <th>カラー</th>
                    <th>数量</th>
                    <th>小計</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cart as $item): ?>
                    <?php
                    $item_price = isset($item['price']) ? (float) $item['price'] : 0;
                    $item_count = isset($item['count']) ? (int) $item['count'] : 0;
                    $subtotal = $item_price * $item_count;
                    ?>
                    <tr>
                      <td label="商品名"><?php echo h($item['name'] ?? ''); ?></td>
                      <td label="価格：" class="text-right"><?php echo number_format($item_price); ?>円</td>
                      <td label="カラー"><?php echo h($item['color'] ?? ''); ?></td>
                      <td label="数量" class="text-right"><?php echo h($item_count); ?></td>
                      <td label="小計" class="text-right"><?php echo number_format($subtotal); ?>円</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <div class="order-total">
                <h3><strong>ご注文合計: ¥<?php echo number_format($total_amount); ?></strong></h3>
              </div>
            <?php else: ?>
              <p>カートに商品がありません。ご注文を続けるには、商品をカートに追加してください。</p>
            <?php endif; ?>
          </section>
          <form action="<?php echo h($_SERVER['SCRIPT_NAME']); ?>" method="POST">
            <?php if (!empty($cart) && is_array($cart)): // Only show confirm button if cart is not empty ?>
              <div class="form-actions" style="margin-top: 30px; text-align: center;">
                <input type="hidden" name="token" value="<?php echo h($_SESSION['token'] ?? ''); ?>" />
                <button type="submit" class="btn btn-primary btn-lg">この内容で注文する</button>
              </div>
            <?php endif; ?>
          </form>
          <div class="navigation-buttons">
            <a href="/template/checkout/pay.php" class="btn btn-gray">お支払い方法選択に戻る</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>