<?php
require_once dirname(__DIR__, 3) . '/app/functions.php';
include dirname(__DIR__) . '/front/header.php';
// ログイン済みかチェック
if (isUserLoggedIn()) {
  // ログイン済みの場合は、直接配送先入力などの次のステップへリダイレクト
  header('Location: /template/checkout/shipping-info.php'); // 例: 配送先入力ページ
  exit();
}
//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // CSRF対策
  checkToken();
}
?>
<main>
  <div class="container">
    <div class="wrapper last-wrapper">
      <div class="container">
        <div class="wrapper-title">
          <h1>購入手続き</h1>
        </div>
        <div class="checkout-options">
          <!-- ログインフォーム -->
          <div class="login-section">
            <h2><a href="/template/member/member_login.php">
                会員の方はこちらからログイン
              </a></h2>
          </div>
          <hr>

          <!-- 新規会員登録へのリンク -->
          <div class="register-section">
            <h3>初めてご利用の方・会員登録をご希望の方</h3>
            <p>会員登録すると、次回からのお買い物がスムーズになります。</p>
            <a href="/template/member/register_member.php" class="btn btn-gray">新規会員登録する</a>
          </div>
          <hr>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>