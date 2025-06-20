<?php include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__, 2) . '/Member/register_members.php'; ?>
<main>
  <div class="wrapper last-wrapper register-wrapper">
    <div class="container">
      <div class="register">
        <div class="wrapper-title">
          <h3>登録完了しました。</h3>
          <p>ご登録ありがとうございました。</p>
        </div>
        <button type="submit" class="btn btn-submit">
          <a href="member_login.php">ログイン画面へ戻る</a></button>
      </div>
    </div>
  </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>