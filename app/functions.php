<?php
//XSS対策
function h($s)
{
  return htmlspecialchars($s ?? '', ENT_QUOTES, "UTF-8");
}

//================================================================
// CSRF対策
//================================================================

/**
 * セッションに安全なトークンをセットする
 */
function setToken()
{
  // cryptographically secureな乱数を生成
  $_SESSION['token'] = bin2hex(random_bytes(32));
}

/**
 * セッション変数のトークンとPOSTされたトークンをチェック
 * @throws Exception トークンが無効な場合
 */
function checkToken()
{
  $token = filter_input(INPUT_POST, 'token');

  if (
    empty($_SESSION['token']) ||
    $token === null ||
    !hash_equals($_SESSION['token'], $token)
  ) {
    // エラーメッセージは呼び出し元で処理する
    throw new Exception('Invalid POST request');
  }
}


//================================================================
// バリデーション (共通パーツ)
//================================================================

/**
 * メールアドレスのバリデーション
 * @param string|null $email
 * @return string|null エラーメッセージ or null
 */
function validateEmail($email)
{
  if (empty($email)) {
    return 'メールアドレスを入力してください。';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return 'メールアドレスの形式で入力してください。';
  }
  return null;
}

/**
 * パスワードのバリデーション
 * @param string|null $password
 * @return string|null エラーメッセージ or null
 */
function validatePassword($password)
{
  if (empty($password)) {
    return "パスワードを入力してください。";
  }
  if (!preg_match('/\A[a-z\d]{8,100}+\z/i', $password)) {
    return "パスワードは半角英数字8文字以上で入力してください。";
  }
  return null;
}

/**
 * 確認用パスワードのバリデーション
 * @param string|null $password
 * @param string|null $confirmPassword
 * @return string|null エラーメッセージ or null
 */
function validateConfirmPassword($password, $confirmPassword)
{
  if (empty($confirmPassword)) {
    return "パスワード（確認用）を入力してください。";
  }
  if ($password !== $confirmPassword) {
    return "パスワードが一致しません。";
  }
  return null;
}

//================================================================
// バリデーション (管理者用)
//================================================================

function validation_admin_login($datas)
{
  $errors = [];

  if ($error = validateEmail($datas['email'])) {
    $errors['email'] = $error;
  }
  if ($error = validatePassword($datas['password'])) {
    $errors['password'] = $error;
  }

  return $errors;
}

function validation_admin_register($datas)
{
  $errors = [];

  //ユーザー名のチェック
  if (empty($datas['admin_user'])) {
    $errors['admin_user'] = 'ユーザー名を入力してください。';
  } else if (mb_strlen($datas['admin_user']) > 20) {
    $errors['admin_user'] = 'ユーザー名は20文字以内で入力してください。';
  }

  // メールアドレスのチェック
  if ($error = validateEmail($datas['email'])) {
    $errors['email'] = $error;
  }

  //パスワードのチェック
  if ($error = validatePassword($datas['password'])) {
    $errors['password'] = $error;
  }

  //パスワード入力確認チェック
  if (empty($errors['password'])) {
    if ($error = validateConfirmPassword($datas['password'], $datas['confirm_password'])) {
      $errors['confirm_password'] = $error;
    }
  }

  return $errors;
}

function validateAdmin_editData($datas)
{
  $errors = [];
  // 管理者名のチェック
  if (empty($datas['admin_user'])) {
    $errors['admin_user'] = '管理者名を入力してください。';
  } elseif (mb_strlen($datas['admin_user']) > 20) {
    $errors['admin_user'] = '管理者名は20文字以内で入力してください。';
  }

  // メールアドレスのチェック
  if ($error = validateEmail($datas['email'])) {
    $errors['email'] = $error;
  }

  // 新しいパスワードが入力されている場合のみ、現在のパスワードは必須
  if (!empty($datas['new_password']) && empty($datas['current_password'])) {
    $errors['current_password'] = '新しいパスワードを設定するには現在のパスワードを入力してください。';
  }

  // 新しいパスワードのチェック
  if (!empty($datas['new_password'])) {
    if ($error = validatePassword($datas['new_password'])) {
      $errors['new_password'] = $error;
    }
    if ($datas['new_password'] !== $datas['confirm_new_password']) {
      $errors['confirm_new_password'] = '新しいパスワードが一致しません。';
    }
    if ($datas['new_password'] === $datas['current_password']) {
      $errors['new_password'] = '新しいパスワードと現在のパスワードは同じにできません。';
    }
  }
  return $errors;
}

//================================================================
// バリデーション (会員用)
//================================================================

function validateMemberData($datas)
{
  $errors = [];
  // お名前のチェック
  if (empty($datas['name'])) {
    $errors['name'] = 'お名前を入力してください。';
  } else if (mb_strlen($datas['name']) > 50) {
    $errors['name'] = 'お名前は50文字以内で入力してください。';
  } else if (mb_strlen($datas['name']) < 3) {
    $errors['name'] = 'お名前は3文字以上で入力してください。';
  } else if (preg_match('/^[ぁ-んァ-ヶー]+$/u', $datas['name'])) { // ひらがな・カタカナのみの場合
    $errors['name'] = '有効なお名前を入力してください。';
  }

  // メールアドレスのチェック
  if ($error = validateEmail($datas['email'])) {
    $errors['email'] = $error;
  }

  // パスワードのチェック
  if ($error = validatePassword($datas['password'])) {
    $errors['password'] = $error;
  }

  // 確認用パスワードのチェック
  if (empty($errors['password'])) {
    if ($error = validateConfirmPassword($datas['password'], $datas['confirm_password'])) {
      $errors['confirm_password'] = $error;
    }
  }

  // 住所のチェック
  if (empty($datas['address'])) {
    $errors['address'] = '住所を入力してください。';
  } else if (mb_strlen($datas['address']) < 5) {
    $errors['address'] = '正しい形式で入力してください';
  } else if (mb_strlen($datas['address']) > 255) {
    $errors['address'] = '住所は255文字以内で入力してください。';
  } else if (!preg_match('/[0-9０-９]/u', $datas['address'])) {
    $errors['address'] = '住所には番地を含めてください。';
  } else if (preg_match('/^[ぁ-んァ-ヶー]+$/u', $datas['address'])) {
    $errors['address'] = '有効な住所を入力してください。';
  }

  return $errors;
}

function validateLoginData($datas)
{
  $errors = [];
  if ($error = validateEmail($datas['email'])) {
    $errors['email'] = $error;
  }
  if ($error = validatePassword($datas['password'])) {
    $errors['password'] = $error;
  }
  return $errors;
}

function validateMemberEditData($datas)
{
  $errors = [];

  // お名前のチェック
  if (empty($datas['name'])) {
    $errors['name'] = 'お名前を入力してください。';
  } else if (mb_strlen($datas['name']) < 2) {
    $errors['name'] = 'お名前は2文字以上で入力してください。';
  } else if (preg_match('/^[ぁ-んァ-ヶー]+$/u', $datas['name'])) {
    $errors['name'] = '有効なお名前を入力してください。';
  }

  // メールアドレスのチェック
  if ($error = validateEmail($datas['email'])) {
    $errors['email'] = $error;
  }

  return $errors;
}

//================================================================
// バリデーション (配送先・商品)
//================================================================

function validateShippingData($datas)
{
  $errors = [];
  // お名前のチェック
  if (empty($datas['name'])) {
    $errors['name'] = 'お名前を入力してください。';
  }
  // メールアドレスチェック
  if ($error = validateEmail($datas['email'])) {
    $errors['email'] = $error;
  }
  // 郵便番号のチェック
  if (empty($datas['postal_code'])) {
    $errors['postal_code'] = '郵便番号を入力してください。';
  } else if (!preg_match('/^\d{3}-?\d{4}$/', $datas['postal_code'])) {
    $errors['postal_code'] = '郵便番号はXXX-XXXXの形式で入力してください。';
  }

  // 住所のチェック
  if (empty($datas['address'])) {
    $errors['address'] = '住所を入力してください。';
  } else if (mb_strlen($datas['address']) < 5) {
    $errors['address'] = '正しい形式で入力してください';
  } else if (mb_strlen($datas['address']) > 255) {
    $errors['address'] = '住所は255文字以内で入力してください。';
  } else if (!preg_match('/[0-9０-９]/u', $datas['address'])) {
    $errors['address'] = '住所には番地を含めてください。';
  } else if (preg_match('/^[ぁ-んァ-ヶー]+$/u', $datas['address'])) {
    $errors['address'] = '有効な住所を入力してください。';
  }
  // 電話番号のチェック
  if (empty($datas['phone_number'])) {
    $errors['phone_number'] = '電話番号を入力してください。';
  } else if (!preg_match('/^\d{10,11}$/', preg_replace('/-/', '', $datas['phone_number']))) {
    $errors['phone_number'] = '電話番号は10桁または11桁の半角数字で入力してください（ハイフンは任意です）。';
  }
  return $errors;
}

/**
 * 商品データのバリデーション
 * @param array $datas 商品データ
 * @param bool $isEdit 編集モードかどうか (trueなら画像は任意)
 * @return array エラー配列
 */
function validateProductData($datas, $isEdit = false)
{
  $errors = [];
  // 商品名のチェック
  if (empty($datas['product_name'])) {
    $errors['product_name'] = '商品名を入力してください。';
  } else if (mb_strlen($datas['product_name']) > 255) {
    $errors['product_name'] = '商品名は255文字以内で入力してください。';
  }
  //ジャンルのチェック
  if (empty($datas['genre_id'])) {
    $errors['genre_id'] = 'ジャンルを選択してください。';
  }
  // 商品説明のチェック
  if (empty($datas['description'])) {
    $errors['description'] = '商品説明を入力してください。';
  } else if (mb_strlen($datas['description']) > 1000) {
    $errors['description'] = '商品説明は1000文字以内で入力してください。';
  }
  // 価格のチェック
  if (empty($datas['price_without_tax'])) {
    $errors['price_without_tax'] = '価格を入力してください。';
  } else if (!is_numeric($datas['price_without_tax']) || $datas['price_without_tax'] <= 0) {
    $errors['price_without_tax'] = '価格は正の数で入力してください。';
  }
  // 販売状況のチェック
  if (empty($datas['sales_status']) || !in_array($datas['sales_status'], ['active', 'inactive'], true)) {
    $errors['sales_status'] = '販売状況を選択してください。';
  }
  //在庫のチェック
  if (empty($datas['stock'])) {
    $errors['stock'] = '在庫数を入力してください。';
  } else if (!is_numeric($datas['stock']) || $datas['stock'] < 0) { // 0を許可
    $errors['stock'] = '在庫数は0以上の数で入力してください。';
  }

  // 画像のチェック
  $isImageUploaded = isset($datas['product_image']) && is_array($datas['product_image']) && $datas['product_image']['error'] === UPLOAD_ERR_OK;
  if (!$isEdit && !$isImageUploaded) {
    // 新規登録時は画像必須
    $errors['product_image'] = '画像を選択してください。';
  } else if ($isImageUploaded) {
    // 画像がアップロードされた場合の共通バリデーション
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($datas['product_image']['type'], $allowedTypes)) {
      $errors['product_image'] = 'JPEG、PNG、GIF形式の画像を選択してください。';
    }
    if ($datas['product_image']['size'] > 2 * 1024 * 1024) { // 2MB制限
      $errors['product_image'] = '画像は2MB以下でアップロードしてください。';
    }
    $fileName = basename($datas['product_image']['name']);
    if (preg_match('/[\/\\\:\*\?\"\<\>\|]/', $fileName)) {
      $errors['product_image'] = '無効なファイル名が含まれています。';
    }
  }
  return $errors;
}

// product_Data と product_EditData は validateProductData を使う
function product_Data($datas)
{
  return validateProductData($datas, false);
}

function product_EditData($datas)
{
  return validateProductData($datas, true);
}


function selected_colors($datas)
{
  $errors = [];
  if (empty($datas)) { // 引数が直接選択された色の配列だと仮定
    $errors[] = 'カラーを1つ以上選択してください。';
  }
  return $errors;
}


//================================================================
// セッション管理
//================================================================

/**
 * セッションを開始する
 * セッションが既に開始されている場合は何もしない
 */
function startSession()
{
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
}

/**
 * セッションを破棄する
 */
function destroySession()
{
  // セッション変数をすべて解除する
  $_SESSION = [];

  // Cookieに保存されているセッションIDを無効化
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

  // 最終的に、セッションを破壊する
  session_destroy();
}

/**
 * ログインセッションを設定する (管理者用)
 * @param array $userData ユーザー情報の配列
 */
function setLoginSession($userData)
{
  session_regenerate_id(true);
  $_SESSION["loggedIn"] = true;
  $_SESSION["administrator_id"] = $userData['administrator_id'];
  $_SESSION["admin_user"] = $userData['admin_user'];
}

/**
 * ログインセッションを設定する (会員用)
 * @param array $userData ユーザー情報の配列
 */
function setLogin_membersSession($userData)
{
  session_regenerate_id(true);
  $_SESSION["loggedIn"] = true;
  $_SESSION["member_id"] = $userData['member_id'];
  $_SESSION["name"] = $userData['name'];
}

/**
 * ログイン状態をチェックする (管理者用)
 * @return bool ログイン状態の場合はtrue、そうでない場合はfalse
 */
function isAdminLoggedIn()
{
  return isset($_SESSION['loggedIn'], $_SESSION['administrator_id'], $_SESSION['admin_user']) && $_SESSION['loggedIn'];
}

/**
 * ログイン状態をチェックする (会員用)
 * @return bool ログイン状態の場合はtrue、そうでない場合はfalse
 */
function isUserLoggedIn()
{
  return isset($_SESSION['loggedIn'], $_SESSION['member_id'], $_SESSION['name']) && $_SESSION['loggedIn'];
}

/**
 * ログインしているメンバーのIDを取得する
 * @return int|null ログインしているメンバーのID、ログインしていない場合はnull
 */
function loggedInMemberId()
{
  return $_SESSION['member_id'] ?? null;
}