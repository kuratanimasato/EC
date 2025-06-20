<?php include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__, 3) . '/Member/register_members.php';

$pageTitle = "CONFIRM";
// セッションから登録情報を取得
$confirmationData = null;
if (isset($_SESSION['registration_confirmation_data'])) {
  $confirmationData = $_SESSION['registration_confirmation_data'];
}
?>
<main>
  <div class="wrapper last-wrapper register-wrapper">
    <div class="container">
      <div class="register">
        <div class="wrapper-title">
          <h3><?php echo h($pageTitle); ?></h3>
          <p>登録内容の確認</p>
        </div>
        <?php if ($confirmationData): ?>
          <form method="POST" action="regi_end.php" class="regi-form">
            <div class="confirmation-details">
              <p class="confirm-form"><strong>お名前:</strong> <?php echo h($confirmationData['name']); ?></p>
              <input type="hidden" name="name" value="<?php echo h($confirmationData['name']); ?>">
              <p class="confirm-form"><strong>Email:</strong> <?php echo h($confirmationData['email']); ?></p>
              <input type="hidden" name="email" value="<?php echo h($confirmationData['email']); ?>">
              <p class="confirm-form"><strong>ご住所:</strong> <?php echo h($confirmationData['address']); ?></p>
              <input type="hidden" name="address" value="<?php echo h($confirmationData['address']); ?>">
            </div>
            <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>" />
            <input type="hidden" name="form_type" value="confirmation"> <?php // どのフォームからの送信か識別子を追加 ?>
            <p>こちらの内容で送信してよろしいですか？</p>
            <button type="submit" class="btn btn-submit">送信する</button>
          </form>
        <?php else: ?>
          <p>登録情報が見つかりませんでした。お手数ですが、再度お手続きをお願いいたします。</p>
          <p><a href="register_member.php">新規登録ページへ</a></p>
        <?php endif; ?>
      </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>