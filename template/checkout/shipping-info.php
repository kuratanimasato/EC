<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';
// ログイン済みかチェック
// ログインしていない場合は購入方法選択ページへリダイレクト

$loggedInMemberId = loggedInMemberId();

if (!isUserLoggedIn()) {
  header('Location: /template/checkout/checkout-options.php');
  exit();
}
$detail = '/../index.php';

//POSTされてきたデータを格納する変数の定義と初期化
$datas = [
  'name' => '',
  'email' => '',
  'postal_code' => '',
  'address' => '',
  'phone_number' => '',
];
$errors = [];
$member_data_exists = false;
//ユーザー情報がDBに存在し、フォームにセットされたかのフラグ

// GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
 
  try {
    $stmt = $pdo->prepare("SELECT name, email, postal_code, address, phone_number FROM members WHERE member_id = :member_id");
    $stmt->bindValue(':member_id', $loggedInMemberId, PDO::PARAM_INT);
    $stmt->execute();
    $memberInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($memberInfo) {
      $member_data_exists = true;//ユーザー情報がDBに存在し、フォームにセットされたかのフラグ
      $datas['name'] = $memberInfo['name'] ?? '';
      $datas['email'] = $memberInfo['email'] ?? '';
      $datas['postal_code'] = $memberInfo['postal_code'] ?? '';
      $datas['address'] = $memberInfo['address'] ?? '';
      $datas['phone_number'] = $memberInfo['phone_number'] ?? '';
    }
  } catch (PDOException $e) {
    error_log("Failed to fetch member info for shipping form: " . $e->getMessage());
  }
}

// POST通信の場合はCSRFトークンをチェック
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // CSRF対策
  if (!validateCsrfToken('shipping-info')) {
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }

  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    $postValue = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    if (is_string($postValue)) {
      $datas[$key] = trim($postValue);
    }
  }

  // ここに配送先情報のバリデーションと保存処理を追
  $errors = validateShippingData($datas);
  if (empty($errors['postal_code']) && !empty($datas['postal_code'])) {
      $datas['postal_code'] = preg_replace('/(\d{3})-?(\d{4})/', '$1-$2', $datas['postal_code']);
  }
  // 電話番号のハイフン除去（バリデーション後、DB保存前）
  if (empty($errors['phone_number']) && !empty($datas['phone_number'])) {
      $datas['phone_number'] = preg_replace('/-/', '', $datas['phone_number']);
  }
  //データベースの中に同一のデータが存在していないか確認
  //エラーがなかったらDBへの新規登録を実行
  if (empty($errors)) {
    $params = [
      $_SESSION['shipping_info_for_checkout'] = [
        'name' => $datas['name'],
        'member_id' => $loggedInMemberId,
        'address' => $datas['address'],
        'email' => $datas['email'],
        'postal_code' => $datas['postal_code'],
        'phone_number' => $datas['phone_number'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
      ],
    ];
      header('Location: /template/checkout/pay.php');
      exit();
    }
}
$is_cart_empty = empty($_SESSION['cart']);
?>
<main>
  <div class="container">
    <div class="wrapper last-wrapper">
      <div class="container">
        <div class="wrapper-title">
          <h1>ご購入者入力情報</h1>
        </div>
        <?php if (!empty($errors['database'])): ?>
        <div class="alert alert-danger"><?php echo h($errors['database']); ?></div>
        <?php endif; ?>
        <div class="shipping-info-form">
          <p>ご購入者の情報を入力してください。</p>
          <form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="POST" novalidate>
            <?php if ($member_data_exists): ?>
            <p class="info-message">ご登録済みの会員情報が表示されています。この情報で配送手続きを進めます。</p>
            <?php endif; ?>
            <!-- 氏名 -->
            <div class="form-group">
              <label for="shipping_name">氏名<span class="required">*</span></label>
              <input type="text" id="shipping_name" name="name" required value="<?php echo h($datas['name'] ?? ''); ?>"
                <?php if ($member_data_exists && !empty($datas['name'])): ?>readonly<?php endif; ?>
                aria-describedby="name_error" placeholder="山田 太郎">
              <?php if (!empty($errors['name'])): ?>
              <div class="invalid-feedback" id="name_error"><?php echo h($errors['name']); ?></div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="shipping_email">メールアドレス<span class="required">*</span></label>
              <input type="email" id="shipping_email" name="email" required
                value="<?php echo h($datas['email'] ?? ''); ?>"
                <?php if ($member_data_exists && !empty($datas['email'])): ?>readonly<?php endif; ?>
                aria-describedby="email_error" placeholder="example@example.com">
              <?php if (!empty($errors['email'])): ?>
              <div class="invalid-feedback" id="email_error"><?php echo h($errors['email']); ?></div>
              <?php endif; ?>
            </div>
            <!-- 郵便番号 -->
            <div class="form-group">
              <label for="shipping_postal_code">郵便番号<span class="required">*</span> (例: 123-4567)</label>
              <input type="text" id="shipping_postal_code" name="postal_code" required pattern="\d{3}-?\d{4}"
                title="例: 123-4567 または 1234567" value="<?php echo h($datas['postal_code'] ?? ''); ?>"
                <?php if ($member_data_exists && !empty($datas['postal_code'])): ?>readonly<?php endif; ?>
                aria-describedby="postal_code_error" placeholder="123-4567">
              <?php if (!empty($errors['postal_code'])): ?>
              <div class="invalid-feedback" id="postal_code_error"><?php echo h($errors['postal_code']); ?></div>
              <?php endif; ?>
            </div>
            <!-- 住所 -->
            <div class="form-group">
              <label for="shipping_address">住所<span class="required">*</span></label>
              <input type="text" id="shipping_address_field" name="address" required
                value="<?php echo h($datas['address'] ?? ''); ?>"
                <?php if ($member_data_exists && !empty($datas['address'])): ?>readonly<?php endif; ?>
                aria-describedby="address_error" placeholder="東京都千代田区...">
              <?php if (!empty($errors['address'])): ?>
              <div class="invalid-feedback" id="address_error"><?php echo h($errors['address']); ?></div>
              <?php endif; ?>
            </div>

            <!-- 電話番号 -->
            <div class="form-group">
              <label for="shipping_phone">電話番号<span class="required">*</span></label>
              <input type="tel" id="shipping_phone" name="phone_number" required pattern="\d{10,11}"
                title="ハイフンなし10桁または11桁" value="<?php echo h($datas['phone_number'] ?? ''); ?>"
                <?php if ($member_data_exists && !empty($datas['phone_number'])): ?>readonly<?php endif; ?>
                aria-describedby="phone_number_error" placeholder="09012345678">
              <?php if (!empty($errors['phone_number'])): ?>
              <div class="invalid-feedback" id="phone_number_error"><?php echo h($errors['phone_number']); ?></div>
              <?php endif; ?>
            </div>

            <!-- 支払い方法選択へ進むボタン -->
            <div class="form-group">
              <div class="btn-group">
                <?php echo insertCsrfToken('shipping-info'); ?>
                <button type="submit" class="btn btn-blue"
                  onclick="<?php if (!$is_cart_empty) { ?>location.href='pay.php'<?php } ?>"
                  <?php if ($is_cart_empty) { ?>disabled<?php } ?>>支払い方法選択へ</button>
                <button type="submit" class="btn btn-gray"> <a href="<?php echo $detail ?>">お買い物を続ける</a></button>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>