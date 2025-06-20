<?php
//Fakerの設定
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once dirname(__DIR__) . '/constants.php'; //--ダミーデーター挿入--//
$faker = Faker\Factory::create('ja_JP');

//商品カテゴリー
function seedGenres($pdo)
{
  echo "ジャンルデータを挿入中...\n";
  $genres = [
    ["genre_name" => "二つ折り財布"],
    ["genre_name" => "長財布"],
    ["genre_name" => "ミニ財布・コンパクト財布"],
    ["genre_name" => "ラウンドファスナー"],
    ["genre_name" => "がま口財布"],
  ];
  $stmt = $pdo->prepare("INSERT INTO genres (genre_name) VALUES (:genre_name)");
  foreach ($genres as $genre) {
    try {
      $stmt->execute($genre);
      echo "ジャンル '{$genre['genre_name']}' を追加しました。\n";
    } catch (PDOException $e) {
      echo "  エラー: ジャンル '{$genre['genre_name']}' の追加に失敗しました。 " . $e->getMessage() . "\n";
    }
  }
  echo "ジャンルデータの挿入完了。\n\n";
  // 挿入されたジャンルのIDを取得 (商品データで使用するため)
  return $pdo->query("SELECT genre_id FROM genres")->fetchAll(PDO::FETCH_COLUMN);
}
//会員(members)
function seedMembers($pdo)
{
  try {
    // 既存の会員データを削除 (注意: 外部キー制約がある場合は削除順序に注意)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;"); // 外部キー制約を一時的に無効化
    $pdo->exec("TRUNCATE TABLE members;");      // members テーブルを空にする
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); // 外部キー制約を再度有効化
    echo "既存の会員データをクリアしました。\n";
  } catch (PDOException $e) {
    echo "会員データのクリア中にエラーが発生しました: " . $e->getMessage() . "\n";
    // エラーが発生しても処理を続行するか、ここで停止するかは要件によります
  }
  echo "会員データを挿入中...\n";
  echo "会員データを挿入中...\n";
  $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
  $members = [
    [
      'name' => '山田太郎',
      'name_kana' => 'ヤマダタロウ',
      'postal_code' => '100-0001',
      'address' => '東京都千代田区千代田1-1',
      'phone_number' => '090-1234-5678',
      'email' => 'taro.yamada@example.com',
      'password' => $hashedPassword,
      'withdrawal_status' => 0
    ],
    [
      'name' => '佐藤花子',
      'name_kana' => 'サトウハナコ',
      'postal_code' => '530-0001',
      'address' => '大阪府大阪市北区梅田1-1-1',
      'phone_number' => '080-8765-4321',
      'email' => 'hanako.sato@example.com',
      'password' => $hashedPassword,
      'withdrawal_status' => 0
    ],
    [
      'name' => '鈴木一郎',
      'name_kana' => 'スズキイチロウ',
      'postal_code' => '460-0001',
      'address' => '愛知県名古屋市中区三の丸1-1-1',
      'phone_number' => '070-1122-3344',
      'email' => 'ichiro.suzuki@example.com',
      'password' => $hashedPassword,
      'withdrawal_status' => 1 // 退会済み
    ],
  ];

  $stmt = $pdo->prepare("
        INSERT INTO members (name, name_kana, postal_code, address, phone_number, email, password, withdrawal_status)
        VALUES (:name, :name_kana,  :postal_code, :address, :phone_number, :email, :password, :withdrawal_status)
    ");

  foreach ($members as $member) {
    try {
      $stmt->execute($member);
      echo "  会員 '{$member['name']} {$member['name']}' を追加しました。\n";
    } catch (PDOException $e) {
      echo "  エラー: 会員 '{$member['name']} {$member['name']}' の追加に失敗しました。 " . $e->getMessage() . "\n";
    }
  }
  echo "会員データの挿入完了。\n\n";
}
// 商品 (products)
function seedProducts($pdo, $genreIds, $faker) // Fakerのインスタンスを引数として受け取る
{
  try {
    // 既存の商品データを削除 (注意: 外部キー制約がある場合は削除順序に注意)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;"); // 外部キー制約を一時的に無効化
    $pdo->exec("TRUNCATE TABLE products;");    // products テーブルを空にする
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); // 外部キー制約を再度有効化
    echo "既存の商品データをクリアしました。\n";
  } catch (PDOException $e) {
    echo "商品データのクリア中にエラーが発生しました: " . $e->getMessage() . "\n";
  }

  if (empty($genreIds)) {
    echo "商品データを挿入できません: 有効なジャンルIDがありません。\n";
    return;
  }
  echo "商品データを挿入中...\n";
  $productsData = [ // 商品の基本情報を定義
    [
      'product_name_base' => '本革二つ折り財布',
      'description_base' => '上質な本革を使用したクラシックなデザインの二つ折り財布。',
      'price_without_tax' => 18000
    ],
    [
      'product_name_base' => 'レディース長財布',
      'description_base' => '収納力抜群のレディース向け長財布。',
      'price_without_tax' => 22000
    ],
    [
      'product_name_base' => 'コンパクト三つ折り財布',
      'description_base' => 'キャッシュレス時代に最適なコンパクトな三つ折り財布。',
      'price_without_tax' => 15000
    ],
    [
      'product_name_base' => 'ラウンドファスナー長財布',
      'description_base' => '耐久性に優れたカーボンレザーを使用したラウンドファスナー長財布。',
      'price_without_tax' => 25000
    ],
    [
      'product_name_base' => 'レトロがま口財布',
      'description_base' => '懐かしさと新しさを兼ね備えたレトロながま口財布。',
      'price_without_tax' => 12000
    ],
    [
      'product_name_base' => 'メンズ二つ折り財布',
      'description_base' => '「革のダイヤモンド」とも呼ばれるコードバンを使用した高級二つ折り財布。',
      'price_without_tax' => 35000
    ],
    [
      'product_name_base' => '薄型L字ファスナーミニ財布',
      'description_base' => 'ポケットにすっきり収まる薄型のL字ファスナーミニ財布。',
      'price_without_tax' => 9800
    ],
  ];

  $products = [];
  $colors = ['クラシックブラック', 'パステルピンク', 'ネイビー', 'カーボン', '花柄模様', 'コードバン', 'スカイブルー', 'レッド', 'グリーン', 'イエロー'];
  $salesStatuses = ['active', 'inactive'];

  foreach ($productsData as $data) {
    $color = $faker->randomElement($colors);
    $products[] = [
      'product_name' => $data['product_name_base'] . ' ' . $color,
      // Fakerを使ってダミー画像のURLを生成 (幅300px, 高さ300px, カテゴリ 'fashion')
      // 必要に応じてサイズやカテゴリを調整してください
      'product_image' => $faker->imageUrl(300, 300, 'fashion', true, '財布'),
      'description' => $data['description_base'] . $faker->realText(50),
      'sales_status' => $faker->randomElement($salesStatuses),
      'price_without_tax' => $data['price_without_tax']
    ];
  }

  $stmt = $pdo->prepare("
        INSERT INTO products (genre_id, product_name, product_image, description, sales_status, price_without_tax)
        VALUES (:genre_id, :product_name, :product_image, :description, :sales_status, :price_without_tax)
    ");

  foreach ($products as $product) {
    // ... existing code ...
    if (!empty($genreIds)) {
      $product['genre_id'] = $genreIds[array_rand($genreIds)];
    } else {
      // ... existing code ...
      continue;
    }

    try {
      $stmt->execute($product);
      echo "  商品 '{$product['product_name']}' を追加しました。\n";
    } catch (PDOException $e) {
      echo "  エラー: 商品 '{$product['product_name']}' の追加に失敗しました。 " . $e->getMessage() . "\n";
    }
  }
  echo "商品データの挿入完了。\n\n";
}
// 管理者 (administrators)
function seedAdministrators($pdo)
{
  try {
    // 既存の管理者データを削除 (注意: 外部キー制約がある場合は削除順序に注意)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;"); // 外部キー制約を一時的に無効化
    $pdo->exec("TRUNCATE TABLE administrators;");  // administrators テーブルを空にする
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); // 外部キー制約を再度有効化
    echo "既存の管理者データをクリアしました。\n";
  } catch (PDOException $e) {
    echo "管理者データのクリア中にエラーが発生しました: " . $e->getMessage() . "\n";
    // エラーが発生しても処理を続行するか、ここで停止するかは要件によります
  }
  echo "管理者データを挿入中...\n";
  $hashedPassword = password_hash('adminpass', PASSWORD_DEFAULT); // 管理者用パスワード
  $admins = [
    ['admin_user' => 'admin', 'email' => 'admin@example.com', 'password' => $hashedPassword],
  ];

  $stmt = $pdo->prepare("INSERT INTO administrators (admin_user, email, password) VALUES (:admin_user, :email, :password)");
  foreach ($admins as $admin) {
    try {
      $stmt->execute($admin);
      echo "  管理者 '{$admin['email']}' を追加しました。\n";
    } catch (PDOException $e) {
      echo "  エラー: 管理者 '{$admin['email']}' の追加に失敗しました。 " . $e->getMessage() . "\n";
    }
  }
  echo "管理者データの挿入完了。\n\n";
}
// --- ダミーデータ挿入実行 ---
try {
  $pdo->beginTransaction();

  $insertedGenreIds = seedGenres($pdo);
  seedMembers($pdo);
  seedProducts($pdo, $insertedGenreIds, $faker);// ジャンルIDを渡す
  seedAdministrators($pdo);

  $pdo->commit();
  echo "全てのダミーデータの挿入が正常に完了しました。\n";

} catch (PDOException $e) {
  $pdo->rollBack();
  die("エラーが発生したため、処理をロールバックしました: " . $e->getMessage() . "\n");
} finally {
  $pdo = null; // 接続を閉じる
  echo "データベース接続を閉じました。\n";
}