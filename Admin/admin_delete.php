<?php
require_once dirname(__DIR__) . '/app/database/db_connect.php';
require_once(dirname(__FILE__) . "/../app/functions.php");
startSession();
if (!isAdminLoggedIn()) {
  header("location:login.php");
  exit;
}
if (isset($_POST["id"])) {
  $id = filter_input(INPUT_POST, "id", FILTER_SANITIZE_NUMBER_INT);
  if ($id === false || $id === null) {
    echo "不正なIDです。";
    exit;
  }

  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM administrators WHERE administrator_id = :administrator_id");
    $stmt->bindParam(':administrator_id', $id, PDO::PARAM_INT);
    $res = $stmt->execute();
    if ($res) {
      $pdo->commit();
      header("location: admin_settings.php");
      exit;
    } else {
      $pdo->rollBack();
      echo "削除に失敗しました。";
      exit;
    }
  } catch (PDOException $e) {
    $pdo->rollBack();
    echo "不正な削除です：" . $e->getMessage();
  }
} else {
  echo "削除する管理者IDが指定されていません。";
  exit;
}