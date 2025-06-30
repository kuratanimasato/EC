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

$error_messages = [];
$success_message = '';
//POSTされてきたデータを格納する変数の定義と初期化
$datas = [
  'product_id' => '',
  'genre_id' => '',
  'product_name' => '',
  'product_image' => '',
  'description' => '',
  'price_without_tax' => '',
  'sales_status' => '',
  'is_recommended' => 0,
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
// --- カラー一覧取得
$colors = [];
try {
  $pdo->beginTransaction();
  $stmt = $pdo->query("SELECT color_id, color_name, color_code FROM colors ORDER BY color_id ASC");
  $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $pdo->commit();
} catch (PDOException $e) {
  $error_message = "カラーの取得に失敗しました: " . h($e->getMessage());
}

// --- 編集対象商品のカラー取得 ---
$selected_colors = [];
if ($_SERVER['REQUEST_METHOD'] != 'POST' && !empty($_GET['id'])) {
  $stmt = $pdo->prepare("SELECT color_id FROM product_colors WHERE product_id = ?");
  $stmt->execute([$_GET['id']]);
  $selected_colors = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'color_id');
}
// --- POST時はフォームから取得 ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['colors'])) {
  $selected_colors = array_map('intval', $_POST['colors']);
}

//GET通信だった場合はセッション変数にトークンを追加
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $datas['product_id'] = $_GET['id'];
    try {
      // 既存の製品情報を取得してフォームに表示
      $pdo->beginTransaction();
      $sql = "SELECT product_id, genre_id, product_name, product_image, description, price_without_tax, sales_status, stock ,is_recommended
                FROM products WHERE product_id = :product_id";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':product_id', $datas['product_id'], PDO::PARAM_INT);
      $stmt->execute();
      $product_data = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($product_data) {
        $datas['product_id'] = $product_data['product_id'];
        $datas['genre_id'] = $product_data['genre_id'];
        $datas['product_name'] = $product_data['product_name'];
        $datas['product_image'] = $product_data['product_image'];
        $datas['description'] = $product_data['description'];
        $datas['price_without_tax'] = $product_data['price_without_tax'];
        $datas['sales_status'] = $product_data['sales_status'];
        $datas['is_recommended'] = $product_data['is_recommended'];
        $datas['stock'] = $product_data['stock'];
      } else {
        $errors['load'] = '指定された製品情報が見つかりません。';
        $pdo->rollBack();
        header("location: products.php?error=" . urldecode($errors["load"]));
        exit;
      }
      $pdo->commit();
    } catch (PDOException $e) {
      header("location: products.php?error=" . urlencode('データベースエラーが発生しました。: ' . h($e->getMessage())));
      error_log('データベースエラー: ' . h($e->getMessage()));
      exit;
    }
  } else {
    header("location: products.php?error=" . urlencode('編集対象の製品IDが指定されていません。' . h($e->getMessage())));
    exit;
  }
}
//POST通信だった場合はログイン処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  //// --- デバッグコード終了 ---
  if (!validateCsrfToken('products-edit')) {
    // CSRFトークンが無効な場合はエラーメッセージを表示
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }
  $upload_dir = dirname(__DIR__, 2) . '/uploads/images/';
  $image_name = '';
  if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['product_image']['size'] > 2 * 1024 * 1024) {
      $error_messages[] = '画像ファイルは5MB以下でなければなりません。';
    } else {
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
    $datas['product_image'] = filter_input(INPUT_POST, 'current_product_image', FILTER_DEFAULT) ?? '';
  }
  // おすすめ商品フラグの処理
  $datas['is_recommended'] = isset($_POST['is_recommended']) ? 1 : 0;

  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    if ($key === 'product_image')
      continue;
    if ($key === 'product_id') {
      $datas['product_id'] = filter_input(INPUT_POST, 'product_id', FILTER_DEFAULT) ?? $datas['product_id'];
      continue;
    }
    $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
    if ($postValue !== null) {
      $datas[$key] = $postValue;
    }
  }
  // バリデーション呼び出し
  $error_messages = product_EditData($datas);
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
      'stock' => $datas['stock'],
      'is_recommended' => $datas['is_recommended'],
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];
    if (empty($error_messages)) {
      $datas['updated_at'] = date('Y-m-d H:i:s');
      try {
        $pdo->beginTransaction();
        $sql_update = "UPDATE products SET genre_id = :genre_id, product_name = :product_name, product_image = :product_image, description = :description, sales_status = :sales_status, price_without_tax = :price_without_tax, stock = :stock, is_recommended = :is_recommended, updated_at = :updated_at WHERE product_id = :product_id";
        $stmt_update = $pdo->prepare($sql_update);
        foreach ($datas as $key => $value) {
          if ($key === 'product_id' || $key === 'created_at')
            continue; // product_idはWHERE句、created_atは更新しない
          $stmt_update->bindValue(":$key", $value);
        }
        $stmt_update->bindValue(':is_recommended', $datas['is_recommended'], PDO::PARAM_INT);
        $stmt_update->bindValue(':product_id', $datas['product_id'], PDO::PARAM_INT);

        $stmt_update->execute();

        // --- カラー紐付けを一度削除して再登録 ---
        $stmt = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
        $stmt->execute([$datas['product_id']]);
        if (!empty($selected_colors)) {
          $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
          foreach ($selected_colors as $color_id) {
            $stmt->execute([$datas['product_id'], $color_id]);
          }
        }
        $pdo->commit();
        header("location: products.php?success=商品を更新しました");
        exit;
      } catch (PDOException $e) {
        $pdo->rollBack();
        $error_messages[] = 'データベースエラーが発生しました。: ' . $e->getMessage();
      }
    }
  }
}
?>
<?php include dirname(__DIR__, 2) . '/Admin/parts/header.php'; ?>

<body>
  <div class="container mt-4">
    <h1 class="mb-4">商品編集</h1>
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
      </div> <?php endif; ?>
    <form method="POST" action="" enctype="multipart/form-data">
      <?php echo insertCsrfToken('products-edit'); ?>
      <div class="mb-3">
        <label for="product_id" class="form-label">商品ID（変更不可）</label>
        <input type="hidden" name="product_id" value="<?php echo h($datas['product_id'] ?? ''); ?>">
        <input type="text" class="form-control" id="product_id" name="product_id" maxlength="255"
          value="<?php echo h($datas['product_id'] ?? ''); ?>" disabled readonly>
      </div>
      <div class="mb-3">
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
            </option> <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="product_image" class="form-label">商品画像（任意）</label>
        <?php if (!empty($datas['product_image'])): ?>
          <div class="mb-2">
            <img src="../../uploads/<?php echo h($datas['product_image']); ?>" alt="現在の画像" style="max-width:150px;">
            <div class="form-text">現在の画像</div>
          </div>
        <?php endif; ?>
        <input type="hidden" name="current_product_image" value="<?php echo h($datas['product_image']); ?>">
        <input type="file" class="form-control" id="product_image" name="product_image" accept=".jpg,.jpeg,.png,.gif">
        <div class="form-text">画像を変更する場合のみ選択してください（jpg, png, gif）。</div>
      </div>
      <!-- カラー選択欄 -->
      <div class="mb-3">
        <label for="colors" class="form-label">カラー（複数選択可）</label>
        <select class="form-select" id="colors" name="colors[]" multiple>
          <?php foreach ($colors as $color): ?>
            <option value="<?php echo h($color['color_id']); ?>" <?php if (!empty($selected_colors) && in_array($color['color_id'], $selected_colors))
                 echo 'selected'; ?>>
              <?php echo h($color['color_name']); ?>
              <?php if ($color['color_code']): ?>（<?php echo h($color['color_code']); ?>）<?php endif; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">CtrlまたはShiftキーで複数選択できます。</div>
      </div>
      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="is_recommended" name="is_recommended" value="1" <?php if (!empty($datas['is_recommended']) && $datas['is_recommended'] == 1)
          echo 'checked'; ?>>
        <label class="form-check-label" for="is_recommended">おすすめ商品にする</label>
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
            <input class="form-check-input" type="radio" name="sales_status" id="inactive" value="inactive" <?php if (($datas['sales_status'] ?? '') === 'inactive')
              echo 'checked'; ?>>
            <label class="form-check-label" for="inactive">販売停止中</label>
          </div>
        </div>
      </div>
      <div class="mb-3">
        <label for="stock" class="form-label">在庫数</label>
        <select class="form-select" id="stock" name="stock" required>
          <option value="">選択してください</option>
          <option value="0" <?php if (($datas['stock'] ?? '') == "0")
            echo 'selected'; ?>>在庫切れ</option>
          <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="<?php echo $i; ?>" <?php if (($datas['stock'] ?? '') == $i)
                 echo 'selected'; ?>>
              <?php echo $i; ?>
            </option>
          <?php endfor; ?>
        </select>
        <div class="form-text">0を選択すると「在庫切れ」になります。</div>
      </div>
      <div class=" mb-3">
        <label for="price_without_tax" class="form-label">価格（税抜）</label>
        <input type="number" class="form-control" id="price_without_tax" name="price_without_tax" min="0"
          value="<?php echo h($datas['price_without_tax'] ?? ''); ?>" required>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">更新</button>
      <a href="products.php" class="btn btn-secondary btn-sm">戻る</a>
    </form>
  </div>
  <?php include dirname(__DIR__, 2) . '/Admin/parts/footer.php'; ?>