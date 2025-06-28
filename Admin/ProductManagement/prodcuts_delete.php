<?php
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
require_once dirname(__DIR__, 2) . '/app/functions.php';
startSession();
if (isset($_GET['admin_user']) && $_GET['admin_user'] === 'true') {
  destroySession();
  header("location:login.php");
  exit;
}
if (!isAdminLoggedIn()) {
  header("location:login.php");
  exit;
}
$datas = [];


//POST通信だった場合はログイン処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  ////CSRF対策
  if (!validateCsrfToken('products-delete')) {
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }
  $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
  if (!$product_id) {
    header("location: products.php?error=" . urlencode('削除対象の商品IDが不正です。'));
    exit;
  }
  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE products SET is_deleted =1 WHERE product_id = :product_id");
    $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $pdo->commit();
    header("location: products.php?success=" . urlencode('商品を削除しました。'));
    exit;
  } catch (PDOException $e) {
    if ($pdo->inTransaction())
      $pdo->rollBack();
    header("location: products.php?error=" . urlencode('商品削除に失敗しました: ' . $e->getMessage()));
    exit;
  }
}