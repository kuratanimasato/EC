<?php
require_once dirname(__DIR__, 2) . '/app/functions.php';
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
startSession();

$genres_for_sidebar_menu = [];
$genre_sidebar_menu_error = '';

try {
  $stmt = $pdo->query("SELECT genre_id, genre_name FROM genres WHERE is_deleted = 0 ORDER BY genre_name ASC");
  $all_genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 同じ名前のカテゴリーを除外
  $unique_genres = [];
  foreach ($all_genres as $genre) {
    $unique_genres[$genre['genre_name']] = $genre; // 最後のgenre_idが優先される
  }

} catch (PDOException $e) {
  $genre_sidebar_menu_error = "カテゴリーの読み込みに失敗しました。";
  error_log("Sidebar genre fetch PDOException: " . $e->getMessage());
}
?>

<aside class="sidebar">
  <h3>カテゴリー</h3>
  <?php if ($genre_sidebar_menu_error): ?>
    <p style="color: red; padding: 10px;"><?php echo h($genre_sidebar_menu_error); ?></p>
  <?php elseif (!empty($unique_genres)): ?>
    <ul class="sidebar-category-list">
      <?php foreach ($unique_genres as $genre): ?>
        <li>
          <a href="/template/front/category-product-list.php?genre_id=<?php echo h($genre['genre_id']); ?>">
            <?php echo h($genre['genre_name']); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p style="padding: 10px;">カテゴリーはありません。</p>
  <?php endif; ?>

</aside>