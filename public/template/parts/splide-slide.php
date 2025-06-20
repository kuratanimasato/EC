<?php
// detail.php から $product['product_image'] を受け取る想定
$image_file = !empty($product['product_image']) ? basename($product['product_image']) : 'noimage.png';
if (strpos($image_file, '..') !== false || strpos($image_file, '/') !== false || strpos($image_file, '\\') !== false) {
  $image_file = 'noimage.png';
}
?>
<div class="splide-wrapper">
  <div class="splide splide-main" aria-label="メインスライダー">
    <div class="splide__track">
      <ul class="splide__list">
        <li class="splide__slide">
          <a href="/uploads/images/<?php echo h($image_file); ?>" data-lightbox="product-gallery"
            data-title="<?php echo h($product['product_name']); ?>">
            <img src="/uploads/images/<?php echo h($image_file); ?>"
              alt="<?php echo h($product['product_name']); ?> サムネイル" class="main-splide-image">
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>