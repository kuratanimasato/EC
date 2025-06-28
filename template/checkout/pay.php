<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';

if (!isUserLoggedIn()) {
  header('Location: /template/checkout/checkout-options.php');
  exit();
}


// POST通信の場合はCSRFトークンをチェック
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // CSRF対策
  if (!validateCsrfToken('pay')) {
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }
  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    $postValue = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    if (is_string($postValue)) {
      $datas[$key] = trim($postValue);
    }
  }
  if (isset($_POST['shipping_carrier'])) {
    $_SESSION['shipping_info_for_checkout']['shipping_carrier'] = filter_input(INPUT_POST, 'shipping_carrier', FILTER_SANITIZE_SPECIAL_CHARS);
  }
  if (isset($_POST['payment_method'])) {
    $_SESSION['shipping_info_for_checkout']['payment_method'] = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_SPECIAL_CHARS);
  }
  //注文確認ページへリダイレクト
  header('Location: order_confirm.php');
  exit();
}
?>
<main>
  <div class="container">
    <div class="wrapper last-wrapper">
      <div class="container">
        <div class="wrapper-title">
          <h1>ご注文手続き(デモ)</h1>
          <form action="<?php echo h($_SERVER['SCRIPT_NAME']); ?>" method="POST">
            <p class="order-note">これはデモサイトです。実際の決済や配送手配は行われません。</p>
            <form action="order_confirm_demo.php" method="POST"> <?php // デモ用の確認ページを想定 ?>
              <div class="form-section" style="margin-bottom: 30px;">
                <label for="shipping_carrier_select" class="form-label">
                  <h2>配送業者選択</h2>
                </label>
                <select class="form-select" id="shipping_carrier_select" name="shipping_carrier">
                  <option value="yamato" selected>ヤマト運輸 </option>
                  <option value="sagawa">佐川急便</option>
                  <option value="japan_post">日本郵便</option>
                </select>
                <div class="form-text" style="font-size: 0.9em; color: #555; margin-top: 5px;">
                  ご希望の配送業者をお選びください。
                </div>
                <div class="from-section" style="margin-bottom: 30px;">
                  <label for="payment_method_select" class="form-label">
                    <h2>お支払い方法選択</h2>
                  </label>
                  <select class="form-select" id="payment_method_select" name="payment_method">
                    <option value="bank_transfer" selected>銀行振込</option>
                    <option value="cash_on_delivery">代金引換</option>
                    <option value="convenience_store">コンビニ決済</option>
                    <option value="credit_card">クレジットカード</option>
                    <option value="paypay">PayPay </option>
                  </select>
                </div>
                <div class="form-actions" style="margin-top: 30px; text-align: center;">
                  <?php echo insertCsrfToken('pay'); ?>
                  <button type="submit" class="btn btn-primary btn-lg">ご注文内容確認へ </button>
                </div>
            </form>
        </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>