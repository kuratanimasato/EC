<?php
require_once dirname(__DIR__, 3) . '/app/functions.php';
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
include dirname(__DIR__) . '/front/header.php';
$index = '/../index.php';
?>
<main>
  <div class="container">
    <div class="wrapper last-wrapper">
      <div class="container">
        <div class="wrapper-title">
          <h1>ご注文完了</h1>
          <p>ご注文ありがとうございました。</p>
        </div>
      </div>
    </div>
  </div>
  <div class="navigation-buttons"> <button type="submit" class="btn btn-gray">
      <a href="<?php echo $index ?>">トップページに戻る</a></button></div>
</main>
<?php include dirname(__DIR__) . '/front/footer.php'; ?>