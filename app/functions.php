<?php
//XSS対策
function h($s)
{
  return htmlspecialchars($s ?? '', ENT_QUOTES, "UTF-8");
}

//セッションにトークンセット
function setToken()
{
  $token = sha1(uniqid(mt_rand(), true));
  $_SESSION['token'] = $token;
}

//セッション変数のトークンとPOSTされたトークンをチェック
function checkToken()
{
  if (empty($_SESSION['token']) || ($_SESSION['token'] != $_POST['token'])) {
    echo 'Invalid POST', PHP_EOL;
    exit;
  }
}

//POSTされた値のバリデーション (管理者用)
function validation_admin_login($datas, $confirm = true)
{
  $errors = [];
  // メールアドレスのチェック (新規登録時のみ)
  {
    if (empty($datas['email'])) {
      $errors['email'] = 'メールアドレスを入力してください。';
    } else if (!filter_var($datas['email'], FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = 'メールアドレスの形式で入力してください。';
    }
  }
  //パスワードのチェック（正規表現）
  if (empty($datas["password"])) {
    $errors['password'] = "パスワードを入力してください。";
  } else if (!preg_match('/\A[a-z\d]{8,100}+\z/i', $datas["password"])) {
    $errors['password'] = "パスワードは半角英数字8文字以上で入力してください。";
  }

  return $errors;
}

function validation_admin_register($datas, $confirm = true)
{
  $errors = [];

  //ユーザー名のチェック
  if (empty($datas['admin_user'])) {
    $errors['admin_user'] = 'ユーザー名を入力してください。';
  } else if (mb_strlen($datas['admin_user']) > 20) {
    $errors['admin_user'] = 'ユーザー名は20文字以内で入力してください。';
  }
  //パスワードのチェック（正規表現）
  if (empty($datas["password"])) {
    $errors['password'] = "パスワードを入力してください。";
  } else if (!preg_match('/\A[a-z\d]{8,100}+\z/i', $datas["password"])) {
    $errors['password'] = "パスワードは半角英数字8文字以上で入力してください。";
  }
  //パスワード入力確認チェック（ユーザー新規登録時のみ使用）
  if ($confirm) {
    if (empty($datas["confirm_password"])) {
      $errors['confirm_password'] = "パスワードを入力してください。";
    } else if (empty($errors['password']) && ($datas["password"] != $datas["confirm_password"])) {
      $errors['confirm_password'] = "パスワードが一致しません。";
    }
  }

  // メールアドレスのチェック
  if (empty($datas['email'])) {
    $errors['email'] = 'メールアドレスを入力してください。';
  } elseif (!filter_var($datas['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'メールアドレスの形式で入力してください。';
  }
  return $errors;
}
// POSTされた値のバリデーション (管理編集者用)
function validateAdmin_editData($datas, $confirm = true)
{
  $errors = [];
  // 管理者名のチェック
  if (empty($datas['admin_user'])) {
    $errors['admin_user'] = '管理者名を入力してください。';
  } elseif (mb_strlen($datas['admin_user']) > 20) {
    $errors['admin_user'] = '管理者名は20文字以内で入力してください。';
  }

  // メールアドレスのチェック
  if (empty($datas['email'])) {
    $errors['email'] = 'メールアドレスを入力してください。';
  } elseif (!filter_var($datas['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'メールアドレスの形式で入力してください。';
  }

  // 現在のパスワードのチェック (新しいパスワードが入力されている場合のみ必須)
  if (!empty($datas['new_password']) && empty($datas['current_password'])) {
    $errors['current_password'] = '新しいパスワードを設定するには現在のパスワードを入力してください。';
  }

  // 新しいパスワードのチェック
  if (!empty($datas['new_password'])) {
    if (!preg_match('/\A[a-z\d]{8,100}+\z/i', $datas["new_password"])) {
      $errors['new_password'] = "新しいパスワードは半角英数字8文字以上で入力してください。";
    }
    if ($datas['new_password'] !== $datas['confirm_new_password']) {
      $errors['confirm_new_password'] = '新しいパスワードが一致しません。';
    }
    if (!empty($datas['new_password'] && $datas['new_password'] === $datas['current_password'])) {
      $errors['new_password'] = '新しいパスワードと現在のパスワードは同じにできません。';
    }
  }
  return $errors;
}
// POSTされた値のバリデーション (会員登録用)
function validateMemberData($datas)
{
  $errors = [];
  // お名前のチェック (キーを 'name' に変更)
  if (empty($datas['name'])) {
    $errors['name'] = 'お名前を入力してください。';
  } else if (mb_strlen($datas['name']) > 50) { // 文字数制限は適宜調整
    $errors['name'] = 'お名前は50文字以内で入力してください。';
  } else if (mb_strlen($datas['name']) < 3) {
    $errors['name'] = 'お名前は3文字以上で入力してください。';
  } else if (preg_match('/^[ぁ-んァ-ヶー]$/u', $datas['name'])) { // 1文字のひらがな・カタカナのみの場合
    $errors['name'] = '有効なお名前を入力してください。';
  }

  // メールアドレスのチェック
  if (empty($datas['email'])) {
    $errors['email'] = 'メールアドレスを入力してください。';
  } else if (!filter_var($datas['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'メールアドレスの形式で入力してください。';
  } else if (mb_strlen($datas['address']) < 5) { // 住所の最低文字数を5文字とします
    $errors['address'] = '正しい形式で入力してください';
  }
  // パスワードのチェック
  if (empty($datas["password"])) {
    $errors['password'] = "パスワードを入力してください。";
  } else if (!preg_match('/\A[a-z\d]{8,100}+\z/i', $datas["password"])) {
    $errors['password'] = "パスワードは半角英数字8文字以上で入力してください。";
  }

  // 確認用パスワードのチェック
  if (empty($datas["confirm_password"])) {
    $errors['confirm_password'] = "確認用パスワードを入力してください。";
  } else if (empty($errors['password']) && ($datas["password"] != $datas["confirm_password"])) {
    $errors['confirm_password'] = "パスワードが一致しません。";
  }

  // 住所のチェック (会員登録に住所が必要な場合)
  if (empty($datas['address'])) {
    $errors['address'] = '住所を入力してください。';
  } else if (mb_strlen($datas['address']) < 5) {
    $errors['address'] = '正しい形式で入力してください';
  } else if (mb_strlen($datas['address']) > 255) { // 文字数制限は適宜調整
    $errors['address'] = '住所は255文字以内で入力してください。';
  } else if (!preg_match('/[0-9０-９]/u', $datas['address'])) { // 半角・全角数字が含まれていない場合
    $errors['address'] = '住所には番地を含めてください。';
  } else if (preg_match('/^[ぁ-んァ-ヶー]+$/u', $datas['address'])) { // ひらがな・カタカナのみの場合
    $errors['address'] = '有効な住所を入力してください。';
  }

  return $errors;
}
// POSTされた値のバリデーション (会員ログイン用)
function validateLoginData($datas, $confirm = true)
{
  $errors = [];
  if (empty($datas['email'])) {
    $errors['email'] = 'メールアドレスを入力してください。';

  } else if (!filter_var($datas['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'メールアドレスの形式で入力してください。';
  }
  // パスワードのチェック
  if (empty($datas['password'])) {
    $errors['password'] = 'パスワードを入力してください。';
  } else if (!preg_match('/\A[a-z\d]{8,100}+\z/i', $datas["password"])) {
    $errors['password'] = "パスワードは半角英数字8文字以上で入力してください。";
  }
  return $errors;
}
// POSTされた値のバリデーション (会員編集用)
function validateMemberEditData($datas)
{
  $errors = [];

  // お名前のチェック
  if (empty($datas['name'])) {
    $errors['name'] = 'お名前を入力してください。';
  } else if (mb_strlen($datas['name']) < 2) { // 文字数制限は適宜調整
    $errors['name'] = 'お名前は2文字以上で入力してください。';

  } else if (preg_match('/^[ぁ-んァ-ヶー]$/u', $datas['name'])) { // 1文字のひらがな・カタカナのみの場合
    $errors['name'] = '有効なお名前を入力してください。';
  }

  // メールアドレスのチェック
  if (empty($datas['email'])) {
    $errors['email'] = 'メールアドレスを入力してください。';
  } else if (!filter_var($datas['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'メールアドレスの形式で入力してください。';
  }

  return $errors;
}

// 配送先情報のバリデーション
function validateShippingData($datas)
{
  $errors = [];
  // お名前のチェック
  if (empty($datas['name'])) {
    $errors['name'] = 'お名前を入力してください。';
  }
  // メールアドレスチェック
  if (empty($datas['email'])) {
    $errors['email'] = 'メールアドレスを入力してください。';
  }
  // 郵便番号のチェック (必要であれば追加)
  if (empty($datas['postal_code'])) {
    $errors['postal_code'] = '郵便番号を入力してください。';
  } else if (!preg_match('/^\d{3}-?\d{4}$/', $datas['postal_code'])) { // 形式チェック (ハイフンあり/なし)
    $errors['postal_code'] = '郵便番号はXXX-XXXXの形式で入力してください。';
  }

  // 住所のチェック
  if (empty($datas['address'])) {
    $errors['address'] = '住所を入力してください。';
  } else if (mb_strlen($datas['address']) < 5) {
    $errors['address'] = '正しい形式で入力してください';
  } else if (mb_strlen($datas['address']) > 255) { // 文字数制限は適宜調整
    $errors['address'] = '住所は255文字以内で入力してください。';
  } else if (!preg_match('/[0-9０-９]/u', $datas['address'])) { // 半角・全角数字が含まれていない場合
    $errors['address'] = '住所には番地を含めてください。';
  } else if (preg_match('/^[ぁ-んァ-ヶー]+$/u', $datas['address'])) { // ひらがな・カタカナのみの場合
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

/**商品登録バリデーション */
function product_Data($datas)
{
  $errors = [];
  // 商品名のチェック
  if (empty($datas['product_name'])) {
    $errors['product_name'] = '商品名を入力してください。';
  } else if (mb_strlen($datas['product_name']) > 255) { // 文字数制限は適宜調整
    $errors['product_name'] = '商品名は255文字以内で入力してください。';
  }
  //ジャンルのチェック
  if (empty($datas['genre_id'])) {
    $errors['genre_id'] = 'ジャンルを選択してください。';
  }
  // 商品説明のチェック
  if (empty($datas['description'])) {
    $errors['description'] = '商品説明を入力してください。';
  } else if (mb_strlen($datas['description']) > 1000) { // 文字数制限は適宜調整
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
  } else if (!is_numeric($datas['stock']) || $datas['stock'] <= 0) {
    $errors['stock'] = '在庫数は正の数で入力してください。';
  }
  // 画像のチェック
  if (isset($datas['product_image']) && is_array($datas['product_image'])) {
    if ($datas['product_image']['error'] !== UPLOAD_ERR_OK) {
      $errors['product_image'] = '画像を選択してください。';
    } else {
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
  }
  return $errors;
}
function selected_colors($datas)
{
  $errors = [];
  if (empty($selected_colors)) {
    $errors[] = 'カラーを1つ以上選択してください。';
  }
  return $errors;
}
/**商品編集バリデーション（画像は任意） */
function product_EditData($datas)
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
  // 画像は任意（アップロードされた場合のみバリデーション）
  if (isset($datas['product_image']) && is_array($datas['product_image']) && $datas['product_image']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($datas['product_image']['type'], $allowedTypes)) {
      $errors['product_image'] = 'JPEG、PNG、GIF形式の画像を選択してください。';
    }
    if ($datas['product_image']['size'] > 2 * 1024 * 1024) {
      $errors['product_image'] = '画像は2MB以下でアップロードしてください。';
    }
    $fileName = basename($datas['product_image']['name']);
    if (preg_match('/[\/\\\:\*\?\"\<\>\|]/', $fileName)) {
      $errors['product_image'] = '無効なファイル名が含まれています。';
    }
  }
  return $errors;
}
/**セッションを開始する
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
  if (session_destroy()) {
    echo "セッションが正常に破棄されました。";
  } else {
    echo "セッションの破棄に失敗しました。";
  }
}
/**
 * セッションIDを再生成する
 */
function regenerateSessionId($deleteOldSession = true)
{
  session_regenerate_id($deleteOldSession);
}
/**
 * ログインセッションを設定する
 * @param array $userData ユーザー情報の配列
 */
function setLoginSession($userData)
{
  session_regenerate_id(true);
  if ($_SESSION["loggedIn"] = true) {
  } else {
    ($_SESSION["loggedIn"] = false);
    header("location: dashboard.php");
    exit;
  }
  $_SESSION["administrator_id"] = $userData['administrator_id'];
  $_SESSION["admin_user"] = $userData['admin_user'];
}
function setLogin_membersSession($userData)
{
  session_regenerate_id(true);
  $_SESSION["loggedIn"] = true;
  $_SESSION["member_id"] = $userData['member_id'];
  $_SESSION["name"] = $userData['name'];
}

/**
 * ログイン状態をチェックする
 * @return bool ログイン状態の場合はtrue、そうでない場合はfalse
 */
function isAdminLoggedIn()
{
  return isset($_SESSION['administrator_id']) && isset($_SESSION['admin_user']);
}
function isUserLoggedIn()
{
  return isset($_SESSION['member_id']) && isset($_SESSION['name']);
}
/**
 * ログインしているメンバーのIDを取得する
 * @return int|null ログインしているメンバーのID、ログインしていない場合はnull
 */
function loggedInMemberId()
{
  return isset($_SESSION['member_id']) ? $_SESSION['member_id'] : null;
}