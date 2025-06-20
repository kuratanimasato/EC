<?php include __DIR__ . '/template/front/header.php'; ?>
<?php include __DIR__ . '/template/parts/sub-navigation.php'; ?>

<main>
  <!--メインビジュアル-->
  <div class="container">
    <div class="main-content">
      <?php include __DIR__ . '/template/parts/hero-image.php'; ?>
      <div class="main-content-wrapper">
        <?php include __DIR__ . '/template/parts/sidebar.php'; ?>
        <div class="main-area">
          <?php include __DIR__ . '/template/parts/ranking.php'; ?>
          <?php include __DIR__ . '/template/parts/recommend.php'; ?>
          <?php include __DIR__ . '/template/parts/newproduct.php'; ?>
          <?php include __DIR__ . '/template/parts/feature_section.php'; ?>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/template/front/footer.php'; ?>