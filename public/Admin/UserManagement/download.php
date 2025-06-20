<?php
require_once dirname(__DIR__, 3) . '/app/database/db_connect.php';
require_once dirname(__DIR__, 3) . '/app/functions.php';
startSession();
// ログインしていなければログインページへリダイレクト
if (!isAdminLoggedIn()) {
  header("location: login.php");
  exit;
}
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_SPECIAL_CHARS);
//SQL作成
$sql = "SELECT member_id, name, name_kana, postal_code, address, phone_number, email, created_at, updated_at FROM members";
$params = [];
if (!empty($keyword)) {
  $sql .= " WHERE name LIKE :keyword 
              OR name_kana LIKE :keyword 
              OR email LIKE :keyword 
              OR postal_code LIKE :keyword 
              OR address LIKE :keyword 
              OR phone_number LIKE :keyword";
  $params[':keyword'] = '%' . $keyword . '%';
}
$sql .= " ORDER BY member_id ASC";
try {
  $stmt = $pdo->prepare($sql);
  foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val, PDO::PARAM_STR);
  }
  $stmt->execute();
  $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  exit("DBエラー：" . $e->getMessage());
}
// ファイル名設定
$filename = 'members_' . date('Ymd_His') . '.csv';
// ヘッダー設定
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
// Excelで文字化けしないようBOMをつける
echo "\xEF\xBB\xBF";
// 出力先をphp://outputに設定
$output = fopen('php://output', 'w');
// CSVのヘッダー行
fputcsv($output, ['ID', '氏名', 'フリガナ', 'メールアドレス', '郵便番号', '住所', '電話番号', '登録日', '更新日']);
// データ行を書き出し
// データ行を書き出し
foreach ($members as $member) {
  // 日付は見やすくフォーマット（例: 2023-05-16）
  $row = [
    $member['member_id'],
    $member['name'],
    $member['name_kana'],
    $member['email'],
    $member['postal_code'],
    $member['address'],
    $member['phone_number'],
    date('Y-m-d', strtotime($member['created_at'])),
    date('Y-m-d', strtotime($member['updated_at'])),
  ];
  fputcsv($output, $row);
}
// 出力を終了
fclose($output);
exit;