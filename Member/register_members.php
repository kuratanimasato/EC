<?php
require_once dirname(__DIR__) . '/app/database/db_connect.php';
require_once dirname(__DIR__) . '/app/functions.php';
startSession();
$datas = $datas ?? [];
$errors = $errors ?? [];
//POSTされてきたデータを格納する変数の定義と初期化
$datas = [
  'name' => '',
  'email' => '',
  'password' => '',
  'confirm_password' => '',
  'address' => '',

];

//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
}
//POST通信だった場合はDBへの新規登録処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  //CSRF対策
  checkToken();

  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
    if ($postValue !== null) {
      $datas[$key] = $postValue;
    }
  }


  // バリデーション
  $errors = validateMemberData($datas);

  //データベースの中に同一のデータが存在していないか確認
  if (empty($errors['name'])) {
    $sql = "SELECT member_id FROM  members WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('name', $datas['name'], PDO::PARAM_STR);
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $errors['name'] = 'この名前は既にに使用されています。';
    }
  }
  if (empty($errors['email'])) {
    $sql = "SELECT member_id FROM  members WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('email', $datas['email'], PDO::PARAM_STR);
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $errors['email'] = 'このメールアドレスは既にに使用されています。';
    }
  }

  //エラーがなかったらDBへの新規登録を実行
  if (empty($errors)) {
    $params = [
      'member_id' => null,
      'name' => $datas['name'],
      'email' => $datas['email'],
      'password' => password_hash($datas['password'], PASSWORD_DEFAULT),
      'address' => $datas['address'],
      'postal_code' => '',
      'phone_number' => '',
      'withdrawal_status' => 0,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    $count = 0;
    $columns = '';
    $values = '';
    foreach (array_keys($params) as $key) {
      if ($count > 0) {
        $columns .= ',';
        $values .= ',';
      }
      $columns .= $key;
      $values .= ':' . $key;
      $count++;
    }

    $pdo->beginTransaction();//トランザクション処理
    try {
      $sql = 'insert into members (' . $columns . ')values(' . $values . ')';
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      // 登録情報をセッションに保存
      $_SESSION['registration_confirmation_data'] = [
        'name' => $datas['name'],
        'email' => $datas['email'],
        'password' => $datas['password'],
        'address' => $datas['address']
      ];
      $pdo->commit();
      header("location:regi_conf.php");
      exit;
    } catch (PDOException $e) {
      echo 'ERROR: Could not register.' . $e->getMessage();
      $pdo->rollBack();
    }
  }
}