<?php
require_once dirname(__DIR__, 2) . '/app/database/db_connect.php';
require_once dirname(__DIR__, 2) . '/app/functions.php';
startSession();
// ログインしていなければログインページへリダイレクト
if (!isAdminLoggedIn()) {
  header("location: login.php");
  exit;
}
// 初期化
if (isset($_POST["id"])) {
  $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_NUMBER_INT);
  if ($id === false || $id === null) {
    echo "不正なIDです。";
    exit;
  }
  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = :member_id");
    $stmt->bindParam(':member_id', $id, PDO::PARAM_INT);
    $res = $stmt->execute();
    if ($res) {
      $pdo->commit();
      header("location: users.php");
      exit;
    } else {
      $pdo->rollBack();
      echo "削除に失敗しました。";
      exit;
    }

  } catch (PDOException $e) {
    echo "不正な削除です：" . $e->getMessage();
  }
}