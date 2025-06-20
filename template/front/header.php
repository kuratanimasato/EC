<?php require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
startSession();
// カートの合計数量を計算
$cart_total_count = 0;
if (isset($_SESSION['cart'])) {
  foreach ($_SESSION['cart'] as $cart_item) {
    if (isset($cart_item['count']) && is_numeric($cart_item['count'])) {
      $cart_total_count += (int) $cart_item['count'];
    }
  }
}

?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/reset.css">
    <link rel="stylesheet" href="/css/form.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/checkout.css">
    <link rel="stylesheet" href="/css/cart.css">
    <link rel="stylesheet" href="/css/member.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <link href=" https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css " rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>ONE LEATHER </title>
  </head>

  <body>
    <header>
      <div class="container">
        <a href="/index.php" class="logo">ONE LEATHER </a>
        <nav class="nav">
          <ul>
            <?php if (isUserLoggedIn() && isset($_SESSION["name"])): ?>
              <li>ようこそ、<?php echo h($_SESSION["name"]); ?>さん</li>
              <li><a href="/template/member/logout_member.php"><i class="fa-solid fa-right-from-bracket"></i>ログアウト</a>
              </li>
            <?php else: ?>
              <li><a href="/template/member/register_member.php"><i class="fa-solid fa-right-to-bracket"></i>会員登録</a></li>
              <li><a href="/template/member/member_login.php"><i class="fa-solid fa-sign-in-alt"></i>ログイン</a></li>
            <?php endif; ?>
            <li><a href="/template/front/cart.php"><i class="fa-solid fa-cart-shopping"></i>
                購入商品
                <span class="cart-count"><?php echo $cart_total_count; ?></span>
                個
              </a>
            </li>
          </ul>
        </nav>
        <div class="search-bar">
          <form action="/template/home/search.php" method="GET" class="form-input">
            <input type="text" name="search" placeholder="商品を検索...">
            <button type="submit" class="search-button">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </button>
          </form>
        </div>
        <button type="button" class="hamburger">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <nav class="sp-nav">
          <ul class="sp-list">
            <li class="sp-item js-accordion-trigger">
              <a href="#">カテゴリー<i class="fa-solid fa-chevron-down"></i></a>
              <ul class="sub-category-list js-accordion-content">
                <li><a href="/template/front/category-product-list.php?genre_id=5">がま口財布</a></li>
                <li><a href="/template/front/category-product-list.php?genre_id=3">ミニ財布・コンパクト財布</a></li>
                <li><a href="/template/front/category-product-list.php?genre_id=1">二つ折り財布</a></li>
                <li><a href="/template/front/category-product-list.php?genre_id=2">長財布</a></li>
              </ul>
            </li>
            <li class="sp-item"><a href="/template/front/new-product-list.php"><i class="fa-solid fa-tags"></i>新着商品</a>
            </li>
            <li class="sp-item"><a href="/template/front/recommend-list.php"><i
                  class="fa-solid fa-snowflake"></i>おすすめ商品</a></li>
          </ul>
        </nav>
      </div>
    </header>