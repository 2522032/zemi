<?php
session_start();
require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['group_id'])) {
    header('Location: group_select.php');
    exit;
}

$gid = (int)$_SESSION['group_id'];

$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = :gid");
$stmt->execute([':gid' => $gid]);
$groupName = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT invite_code FROM groups WHERE id = :gid");
$stmt->execute([':gid' => $gid]);
$inviteCode = $stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT u.username, gm.role
  FROM group_members gm
  JOIN users u ON u.id = gm.user_id
  WHERE gm.group_id = :gid
  ORDER BY 
    CASE gm.role WHEN 'owner' THEN 0 ELSE 1 END,
    u.username
");
$stmt->execute([':gid' => $gid]);
$members = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SafeHome | ホーム</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{
      background: radial-gradient(1200px 500px at 50% -50px, rgba(255,255,255,.95), rgba(255,255,255,0)),
                  linear-gradient(180deg, #cfeeff 0%, #f7fbff 60%, #ffffff 100%);
      min-height: 100vh;
    }
    .brand { font-weight: 800; letter-spacing: .02em; }
    .muted { color:#6c757d; font-size:.95rem; }
    .app-wrap { max-width: 980px; }

    .glass {
      background: rgba(255,255,255,.72);
      border: 1px solid rgba(255,255,255,.6);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 18px;
      box-shadow: 0 12px 30px rgba(16, 24, 40, .08);
    }

    .pill {
      display:inline-block;
      padding: .35rem .65rem;
      border-radius: 999px;
      font-size: .85rem;
      background: rgba(13,110,253,.12);
      color: #0d6efd;
      border: 1px solid rgba(13,110,253,.20);
    }

    .code {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      letter-spacing: .03em;
    }

    .action-card {
      border: 1px solid rgba(255,255,255,.65);
      border-radius: 16px;
      background: rgba(255,255,255,.68);
      transition: transform .12s ease, box-shadow .12s ease;
    }
    .action-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(16, 24, 40, .10);
    }

    .member-item{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding: .75rem .9rem;
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 12px;
      background: rgba(255,255,255,.65);
      margin-bottom: .6rem;
    }
    .role-badge{
      font-size:.8rem;
      padding: .25rem .5rem;
      border-radius: 999px;
      border: 1px solid rgba(0,0,0,.08);
      background: rgba(255,255,255,.8);
    }
    .role-owner{
      border-color: rgba(255,140,0,.25);
      background: rgba(255,140,0,.12);
      color: #b35a00;
      font-weight: 600;
    }
  </style>
</head>
<body>

<nav class="navbar bg-white border-bottom">
  <div class="container app-wrap">
    <span class="navbar-brand brand">SafeHome</span>
    <div class="d-flex align-items-center gap-2">
      <span class="muted d-none d-md-inline">
        <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> さん
      </span>
      <a class="btn btn-outline-secondary btn-sm" href="group_select.php">グループ変更</a>
      <a class="btn btn-outline-danger btn-sm" href="logout.php">ログアウト</a>
    </div>
  </div>
</nav>

<main class="container py-4 app-wrap">

  <div class="glass p-4 p-md-5 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
      <div>
        <h1 class="h3 mb-1">おかえりなさい、<?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> さん</h1>
      </div>

      <div class="text-md-end">
        <div class="pill mb-2">現在のグループ</div><br>
        <div class="fs-5 fw-semibold">
          <?php echo htmlspecialchars($groupName ?: '（未設定）', ENT_QUOTES, 'UTF-8'); ?>
        </div>
      </div>
    </div>

    <hr class="my-4">

    <div class="row g-3">
      <div class="col-12 col-lg-6">
        <div class="p-3 rounded-4 bg-white bg-opacity-50 border" style="border-color: rgba(255,255,255,.7) !important;">
          <div class="d-flex justify-content-between align-items-center gap-2">
            <div>
              <div class="pill mb-2">招待コード</div>
              <div class="code fs-4 fw-semibold">
                <?php echo htmlspecialchars($inviteCode ?: '—', ENT_QUOTES, 'UTF-8'); ?>
              </div>
            </div>
            <div class="d-flex flex-column gap-2">
              <a class="btn btn-outline-primary" href="group_join.php">参加する</a>
              <a class="btn btn-outline-secondary" href="group_create.php">新規作成</a>
            </div>
          </div>
        </div>
      </div>

<<<<<<< HEAD
      <div class="col-12 col-lg-6">
        <div class="p-3 rounded-4 bg-white bg-opacity-50 border" style="border-color: rgba(255,255,255,.7) !important;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="pill">参加メンバー</div>
            <div class="muted"><?php echo count($members); ?>人</div>
          </div>

          <?php foreach ($members as $m): ?>
            <?php $isOwner = ($m['role'] === 'owner'); ?>
            <div class="member-item">
              <div class="fw-semibold">
                <?php echo htmlspecialchars($m['username'], ENT_QUOTES, 'UTF-8'); ?>
              </div>
              <div class="role-badge <?php echo $isOwner ? 'role-owner' : ''; ?>">
                <?php echo $isOwner ? 'owner' : 'member'; ?>
              </div>
            </div>
          <?php endforeach; ?>
=======
<?php
    $members = [];
    if (isset($_SESSION['group_id'])) {
        $stmt = $pdo->prepare("
            SELECT u.username
            FROM users u
            JOIN group_members gm ON u.id = gm.user_id
            WHERE gm.group_id = :gid
            ORDER BY u.username ASC
        ");
        $stmt->execute([':gid' => $_SESSION['group_id']]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
?>
<?php if ($members): ?>
  <h4>グループメンバー</h4>
  <ul>
    <?php foreach ($members as $m): ?>
      <li><?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<hr>

<ul>
  
  <li><a href="insert.php">安全チェック入力</a></li>
  <li><a href="history.php">履歴を見る</a></li>
  <li><a href="group_select.php">グループ選択</a></li>
  <li><a href="chat.php">グループチャット</a></li>
  <li><a href="calendar.php">カレンダー（予定共有）</a></li>


>>>>>>> origin/main

        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-md-4">
      <a class="text-decoration-none text-dark" href="insert.php">
        <div class="action-card p-4 h-100">
          <div class="fw-bold fs-5 mb-1">安全チェック入力</div>
          <div class="muted">窓 / ガス / テレビ / 電気 / 鍵</div>
          <div class="mt-3">
            <span class="btn btn-primary">入力する →</span>
          </div>
        </div>
      </a>
    </div>

    <div class="col-12 col-md-4">
      <a class="text-decoration-none text-dark" href="history.php">
        <div class="action-card p-4 h-100">
          <div class="fw-bold fs-5 mb-1">履歴</div>
          <div class="muted">家族の入力履歴を確認</div>
          <div class="mt-3">
            <span class="btn btn-outline-primary">履歴へ →</span>
          </div>
        </div>
      </a>
    </div>

    <div class="col-12 col-md-4">
      <a class="text-decoration-none text-dark" href="group_select.php">
        <div class="action-card p-4 h-100">
          <div class="fw-bold fs-5 mb-1">グループ設定</div>
          <div class="muted">変更 / 参加 / 作成</div>
          <div class="mt-3">
            <span class="btn btn-outline-secondary">設定へ →</span>
          </div>
        </div>
      </a>
    </div>
  </div>

  <form method="POST" action="leave_group.php"
      onsubmit="return confirm('このグループから退会しますか？');">
  <input type="hidden" name="group_id" value="<?= (int)($_SESSION['group_id'] ?? 0) ?>">
  <button type="submit">グループ退会</button>
</form>




  <p class="text-center muted mt-4 mb-0">© SafeHome</p>
</main>

</body>
</html>
