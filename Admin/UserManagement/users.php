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

//POST通信だった場合は処理を開始
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // CSRFトークンの検証
  if (!validateCsrfToken('users')) {
    $errors['csrf'] = '不正なリクエストです。(CSRFトークンエラー)';
  }

  // POSTされてきたデータを変数に格納
  foreach ($datas as $key => $value) {
    $postValue = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
    if ($postValue !== null) {
      $datas[$key] = $postValue;
    }
  }
}
//ページング設定
$limit = 8;
// 現在のページ番号
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$offset = ($page - 1) * $limit;

//検索キーワードの取得
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_SPECIAL_CHARS);
//全件のレコード数。
$count_sql = "SELECT COUNT(*)  FROM members";
$count_params = [];
if (!empty($keyword)) {
  $count_sql .= " WHERE name LIKE :keyword
                OR name_kana LIKE :keyword 
                OR email LIKE :keyword
                OR postal_code LIKE :keyword
                OR address LIKE :keyword
                OR phone_number LIKE :keyword";
  $count_params[':keyword'] = '%' . $keyword . '%';
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);


/// 会員情報を取得（LIMIT・OFFSET付き）
try {
  $pdo->beginTransaction();

  $sql = "SELECT member_id, name, name_kana, postal_code, address, phone_number, email, created_at, updated_at 
          FROM members";
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

  $sql .= " ORDER BY member_id ASC LIMIT :limit OFFSET :offset";
  $stmt = $pdo->prepare($sql);

  foreach ($params as $key => &$val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
  }

  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

  $stmt->execute();
  $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $pdo->commit();
} catch (PDOException $e) {
  $db_error = "DBエラー：" . $e->getMessage();
  $pdo->rollback();
}
?>
<?php include dirname(__DIR__, 2) . '/Admin/parts/header.php'; ?>

<body>
  <div class="container mt-4">
    <?php if (isset($db_error)): ?>
      <div class="alert alert-danger text-center">
        <?php echo h($db_error); ?>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show text-center fade-message" role="alert">
        <?php echo h($_GET['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show text-center fade-message" role="alert">
        <?php echo h($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <div class="row">
      <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-12">
          <div class="card">
            <div class="card-header">
              <a href="../dashboard.php" class="btn btn-primary">ダッシュボード画面へ戻る</a>
              <h1 class="mb-0 text-center">会員管理一覧</h1>
              <div class="position-absolute top-0 end-0">
                <form action="users.php" class="input-group rounded-pill ms-auto  mt-2" style="max-width: 350px;"
                  method="GET">
                  <input type="text" name="keyword" class="form-control" placeholder="キーワードを入力"
                    value="<?php echo h($keyword ?? ''); ?>" required>
                  <button type="submit" class="btn btn-primary" id="button-addon2">
                    <i class="fas fa-search"></i>
                    検索
                  </button>
                </form>
                <a href="download.php?<?php echo http_build_query(['keyword' => $keyword ?? '']); ?>"
                  class="btn btn-success ms-2 mt-2">
                  CSVダウンロード
                </a>
              </div>
            </div>
            <div class="card-body">
              <?php if (!empty($members)): ?>
                <div class="table-responsive　">
                  <table class="table table-striped table-bordered table-hover text-center">
                    <thead class="table-dark">
                      <tr>
                        <th>ID</th>
                        <th>氏名</th>
                        <th>メールアドレス</th>
                        <th>登録日</th>
                        <th>更新日</th>
                        <th>操作</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($members as $member): ?>
                        <tr>
                          <td><?php echo h($member['member_id']); ?></td>
                          <td><?php echo h($member['name']); ?></td>
                          <td><?php echo h($member['email']); ?></td>
                          <td><?php echo h(date('m月d日', strtotime($member['created_at']))); ?></td>
                          <td><?php echo h(date('m月d日', strtotime($member['updated_at']))); ?></td>
                          <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                              <button type="button" class="btn btn-primary btn-sm"
                                onclick="location.href='users_edit.php?id=<?php echo h($member['member_id']); ?>'">編集</button>
                              <form action="users_delete.php" method="post" class="m-0">
                                <input type="hidden" name="id" value="<?php echo h($member['member_id']); ?>">
                                <?php echo insertCsrfToken('users'); ?>
                                <button type="submit" class="btn btn-danger btn-sm"
                                  onclick="return confirm('本当に削除しますか？');">削除</button>
                              </form>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                  <?php if ($total_pages > 1): ?>
                    <nav>
                      <ul class="pagination justify-content-center mt-3">
                        <?php if ($page > 1): ?>
                          <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">前へ</a>
                          </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                          <li class="page-item<?php if ($i == $page)
                            echo ' active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                          </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                          <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">次へ</a>
                          </li>
                        <?php endif; ?>
                      </ul>
                    </nav>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <p class=" text-center">
                  <?php echo empty($keyword) ? '登録されている会員情報はありません。' : '該当する会員情報は見つかりませんでした。'; ?>
                </p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include dirname(__DIR__, 2) . '/Admin/parts/script.php'; ?>