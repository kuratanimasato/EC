<?php include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__, 3) . '/Member/login_members.php';
?>
<main>
  <div class="wrapper last-wrapper register-wrapper">
    <div class="container">
      <div class="register">
        <div class="wrapper-title">
          <h3>Login</h3>
          <p>ログイン</p>
          <?php if (!empty($login_err)): ?>
          <div class="alert alert-danger" style="margin-top: 15px;"><?php echo h($login_err); ?></div>
          <?php endif; ?>
        </div>
        <form class="regi-form" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'];?> ">
          <div class="form-group">
            <label for="email">Email <span class="required-star">*</span></label>
            <input type="email" id="email" name="email" required value="<?php echo h($datas['email'] ?? ''); ?>"
              <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>>
            <?php if (!empty($errors['email'])): ?>
            <div class="invalid-feedback"><?php echo h($errors['email']); ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label for="password">パスワード<span class="required-star">*</span></label>
            <input type="password" id="password" name="password" required
              value="<?php echo h($datas['password'] ?? ''); ?>"
              <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>>
            <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback"><?php echo h($errors['password']); ?></div>
            <?php endif; ?>
          </div>
          <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>" />
          <button type="submit" class="btn btn-submit">ログイン</button>
        </form>
      </div>
    </div>
  </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>