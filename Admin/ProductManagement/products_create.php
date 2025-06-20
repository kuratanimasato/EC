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
//POSTされてきたデータを格納する変数の定義と初期化
$datas = [
  'product_id' => '',
  'genre_id' => '',
  'product_name' => '',
  'product_image' => '',
  'description' => '',
  'price_without_tax' => '',
  'is_recommended'=>0,
  'sales_status' => '',
  'stock' => '',
  'created_at' => date('Y-m-d H:i:s'),
  'updated_at' => date('Y-m-d H:i:s'),
];
// ジャンル一覧取得
$genres = [];
try {
  $stmt = $pdo->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_id ASC");
  $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $error_message = "ジャンルの取得に失敗しました: " . h($e->getMessage());
}

  // おすすめ商品フラグの処理
$datas['is_recommended'] = isset($_POST['is_recommended']) ? 1 : 0;
//カラー一覧取得
$colors = [];
try {
  $stmt = $pdo->query("SELECT color_id, color_name, color_code, color_image FROM colors ORDER BY color_id ASC");
  $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $error_message = "カラーの取得に失敗しました: " . h($e->getMessage());
}
//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  setToken();
}

//POST通信だった場合は処理を開始
$error_messages = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  ////CSRF対策
  checkToken();
  $upload_dir = dirname(__DIR__, 2) . '/uploads/images/';
  $image_name = '';
  if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['product_image']['size'] > 2 * 1024 * 1024){ 
      $error_messages[] = '画像ファイルは5MB以下でなければなりません。';
    }else{
        $tmp_name = $_FILES['product_image']['tmp_name'];
      $original_name = basename($_FILES['product_image']['name']);
      $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
      $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
      if (in_array($ext, $allowed_ext, true)) {
        $image_name = uniqid('img_', true) . '.' . $ext;
        move_uploaded_file($tmp_name, $upload_dir . $image_name);
        $datas['product_image'] = 'uploads/images/' . $image_name;
      } else {
        $error_messages[] = '画像ファイルはjpg, jpeg, png, gifのみアップロードできます。';
      }
    }
  } else {
    $datas['product_image'] = '';
  }

  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    if ($key !== 'product_image') {
      $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
      if ($postValue !== null) {
        $datas[$key] = $postValue;
      }
    }
  }
    // カラー選択値を取得
    $selected_colors = [];
    if(!empty($_POST['colors'])) {
        $selected_colors = array_map('intval', $_POST['colors']);
    }
    //カラーバリデーション呼び出し
  $error_messages = selected_colors($datas);
  if (!empty($selected_colors)) {
    // 選択されたカラーIDが存在するかチェック
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM colors WHERE color_id = ?");
    foreach ($selected_colors as $color_id) {
      $stmt->execute([$color_id]);
      if ($stmt->fetchColumn() == 0) {
        $error_messages[] = '選択されたカラーIDは存在しません。';
      }
    }
  }

  
  if (!empty($datas['product_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_id = ?");
    $stmt->execute([$datas['product_id']]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
      $error_messages[] = 'この商品IDは既に登録されています。';
    }
  }
  // バリデーション呼び出し
  $error_messages = product_Data($datas);
  //エラーがなかったらDBへの新規登録を実行
  if (empty($error_messages)) {
      $datas = [
      'product_id' => $datas['product_id'],
      'genre_id' => $datas['genre_id'],
      'product_name' => $datas['product_name'],
      'product_image' => $datas['product_image'],
      'description' => $datas['description'],
      'sales_status' => $datas['sales_status'],
      'price_without_tax' => $datas['price_without_tax'],
      'is_recommended' => $datas['is_recommended'],
      'stock' => $datas['stock'],
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];
    try {
        $pdo->beginTransaction();
        if(!empty($datas['product_id'])){
        $stmt = $pdo->prepare("INSERT INTO products (product_id, genre_id, product_name, product_image, description, stock, sales_status, price_without_tax, is_recommended, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
          $stmt->execute([
          $datas['product_id'],
          $datas['genre_id'],
          $datas['product_name'],
          $datas['product_image'],
          $datas['description'],
          $datas['stock'],
          $datas['sales_status'],
          $datas['price_without_tax'],
          $datas['is_recommended'],
      ]);
      $new_product_id = $datas['product_id'];
      // product_idが空の場合（自動採番）
      } else {
          $stmt = $pdo->prepare("INSERT INTO products (genre_id, product_name, product_image, description, stock, sales_status, price_without_tax, is_recommended, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

            $stmt->execute([
              $datas['genre_id'],
              $datas['product_name'],
              $datas['product_image'],
              $datas['description'],
              $datas['stock'],
              $datas['sales_status'],
              $datas['price_without_tax'],
              $datas['is_recommended'],
      ]);
          $new_product_id = $pdo->lastInsertId();
        }
       // カラーの紐付けを保存
      if (!empty($selected_colors)) {
        $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
        foreach ($selected_colors as $color_id) {
          $stmt->execute([$new_product_id, $color_id]);
        }
      }
      
      $pdo->commit();
      header("Location: products.php?success=商品を登録しました");
      exit;

    } catch (PDOException $e) {
      echo 'ERROR: Could not register.';
      $pdo->rollBack();
      $error_message = "商品登録中にエラーが発生しました: " . h($e->getMessage());
      error_log($error_message);
    }
  }
}

?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8">
    <title>商品新規作成</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>

  <body>
    <div class="container mt-4">
      <h1 class="mb-4">商品新規作成</h1>
      <?php if (!empty($error_messages)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($error_messages as $msg): ?>
          <li><?php echo h($msg); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger">
        <?php echo h($error_message); ?>
      </div>
      <?php endif; ?>
      <form method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" enctype="multipart/form-data">
        <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">
        <div class="mb-3">
          <div class="mb-3">
            <label for="product_id" class="form-label">商品ID（任意・ユニーク）</label>
            <input type="text" class="form-control" id="product_id" name="product_id" maxlength="255"
              value="<?php echo h($datas['product_id'] ?? ''); ?>">
            <div class="form-text">指定しない場合は自動採番されます。</div>
          </div>
          <label for="product_name" class="form-label">商品名</label>
          <input type="text" class="form-control" id="product_name" name="product_name" maxlength="255"
            value="<?php echo h($datas['product_name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
          <label for="genre_id" class="form-label">ジャンル</label>
          <select class="form-select" id="genre_id" name="genre_id" required>
            <option value="">選択してください</option>
            <?php foreach ($genres as $genre): ?>
            <option value="<?php echo h($genre['genre_id']); ?>" <?php if (($datas['genre_id'] ?? '') == $genre['genre_id'])
                   echo 'selected'; ?>>
              <?php echo h($genre['genre_name']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="product_image" class="form-label">商品画像</label>
          <input type="file" class="form-control" id="product_image" name="product_image" accept=".jpg,.jpeg,.png,.gif">
          <div class="form-text">画像ファイル(jpg, png, gif)を選択してください。</div>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">説明</label>
          <textarea class="form-control" id="description" name="description" rows="4"
            required><?php echo h($datas['description'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">販売状況</label>
          <div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="sales_status" id="active" value="active" <?php if (($datas['sales_status'] ?? 'active') === 'active')
                echo 'checked'; ?>>
              <label class="form-check-label" for="active">販売中</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="sales_status" id="inactive" value="inactive"
                <?php if (($datas['sales_status'] ?? '') === 'inactive') echo 'checked'; ?>>
              <label class="form-check-label" for="inactive">販売停止中</label>
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="is_recommended" name="is_recommended" value="1"
                <?php if (!empty($datas['is_recommended']) && $datas['is_recommended'] == 1) echo 'checked'; ?>>
              <label class="form-check-label" for="is_recommended">おすすめ商品にする</label>
            </div>
            <div class="mb-3">
              <label for="stock" class="form-label">在庫数</label>
              <select class="form-select" id="stock" name="stock" required>
                <option value="">選択してください</option>
                <option value="<?php echo $i; ?>" <?php if (($datas['stock'] ?? '') == "0") echo 'selected'; ?>>売り切れ
                </option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php if (($datas['stock'] ?? '') == $i)
                       echo 'selected'; ?>>
                  <?php echo $i; ?>
                </option>
                <?php endfor; ?>
              </select>
              <div class="form-text">0を選択すると「在庫切れ」になります。</div>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label for="price_without_tax" class="form-label">価格（税抜）</label>
          <input type="number" class="form-control" id="price_without_tax" name="price_without_tax" min="0"
            value="<?php echo h($datas['price_without_tax'] ?? ''); ?>" required>
        </div>
        <!-- カラー選択欄 -->
        <div class="mb-3">
          <label for="colors" class="form-label">カラー（複数選択可）</label>
          <select class="form-select" id="colors" name="colors[]" multiple>
            <?php foreach ($colors as $color): ?>
            <option value="<?php echo h($color['color_id']); ?>"
              <?php if (!empty($selected_colors) && in_array($color['color_id'], $selected_colors)) echo 'selected'; ?>>
              <?php echo h($color['color_name']); ?>
              <?php if ($color['color_code']): ?>（<?php echo h($color['color_code']); ?>）<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">CtrlまたはShiftキーで複数選択できます。</div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">登録</button>
        <a href="products.php" class="btn btn-secondary btn-sm">戻る</a>
      </form>
    </div>
  </body>

</html>