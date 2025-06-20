<?php
require_once dirname(__DIR__) . '/app/functions.php';
//セッションの開始
startSession();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}
//POSTされてきたデータを格納する変数の定義と初期化
destroySession();

//ログインページへリダイレクト
header("location: /index.php");