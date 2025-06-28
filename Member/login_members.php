<?php
require_once dirname(__DIR__) . '/app/database/db_connect.php';
require_once dirname(__DIR__) . '/app/functions.php';
// セッションを開始
startSession();
if (isUserLoggedIn()) {
  header("location: index.php");
  exit;
}
$datas = [
  'email' => '',
  'password' => '',
];
$login_err = "";
//POST通信だった場合はログイン処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  ////CSRF対策
  if (!validateCsrfToken('login_members')) {
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }
  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
    if ($postValue !== null) { // filter_inputがnullや空文字を返す場合も考慮
      $datas[$key] = $postValue;
    }
  }
  // バリデーション
  $errors = validateLoginData($datas);
  if (empty($errors)) {
    try {
      //メールアドレスから該当するユーザー情報を取得
      $sql = "SELECT member_id, name, email, address, password FROM members WHERE email = :email";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue('email', $datas['email'], PDO::PARAM_STR);
      $stmt->execute();
      //ユーザー情報があれば変数に格納
      if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        //パスワードがあっているか確認
        if (password_verify($datas['password'], $row['password'])) {
          //セッションIDをふりなおす
          session_regenerate_id(true);

          //セッション変数にログイン情報を格納
          setLogin_membersSession($row);

          //トップ画面にリダイレクト
          header("location: /index.php");
          exit;
        } else {
          $login_err = 'メールアドレスまたはパスワードが間違っています';
        }
      } else {
        $login_err = 'メールアドレスまたはパスワードが間違っています';
      }
    } catch (PDOException $e) {
      $login_err = 'データベースエラーが発生しました。しばらくしてから再度お試しください。';
      error_log('Login Page PDOException: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    }
  }
}