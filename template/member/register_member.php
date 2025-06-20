<?php include dirname(__DIR__) . '/front/header.php';
include dirname(__DIR__, 2) . '/Member/register_members.php';?> <main>
  <div class="wrapper last-wrapper register-wrapper">
    <div class="container">
      <div class="register">
        <div class="wrapper-title">
          <h3>REGISTER</h3>
          <p>新規登録</p>
        </div>
        <form class="regi-form" method="POST" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
          <div class="form-group">
            <label for="name">お名前（フルネーム） <span class="required-star">*</span>
            </label>
            <input type="text" id="name" name="name" required <?php echo h($datas['name'] ?? ''); ?>
              value="<?php echo h($datas['name'] ?? ''); ?>">
            <?php if (!empty($errors['name'])): ?>
            <div class="invalid-feedback"><?php echo h($errors['name']); ?></div>
            <?php endif; ?>
          </div>
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
          <div class="form-group">
            <label for="password">確認パスワード<span class="required-star">*</span> </label>
            <input type="password" id="confirm_password" name="confirm_password" required
              value="<?php echo h($datas['confirm_password'] ?? ''); ?>"
              <?php echo !empty($errors['confirm_password']) ? 'is-invalid' : ''; ?>>
            <?php if (!empty($errors['confirm_password'])): ?>
            <div class="invalid-feedback"><?php echo h($errors['confirm_password']); ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label for="address">住所 <span class="required-star">*</span></label>
            <input type="text" id="address" name="address" required value="<?php echo h($datas['address'] ?? ''); ?>"
              <?php echo !empty($errors['address']) ? 'is-invalid' : ''; ?>>
            <?php if (!empty($errors['address'])): ?>
            <div class="invalid-feedback"><?php echo h($errors['address']); ?></div>
            <?php endif; ?>
          </div>
          <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>" />
          <button type="submit" class="btn btn-submit">内容を確認する</button>
        </form>
      </div>
    </div>
  </div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>