<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['group_id'])) { header('Location: group_create.php'); exit; }

require_once __DIR__ . '/connect_db.php';

$gid = (int)$_SESSION['group_id'];

$stmt = $pdo->prepare("
  SELECT h.checked_at,
         u.username,
         h.window_closed, h.gas_off, h.aircon_off, h.tv_off, h.door_locked,
         h.memo
  FROM home_state h
  JOIN users u ON u.id = h.user_id
  WHERE h.group_id = :gid
  ORDER BY h.checked_at DESC
  LIMIT 50
");
$stmt->execute([':gid' => $gid]);
$rows = $stmt->fetchAll();


?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>履歴 | SafeHome</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{
      background: radial-gradient(1200px 600px at 20% 0%, #d9f0ff 0%, rgba(217,240,255,0) 60%),
                  radial-gradient(1000px 600px at 100% 20%, #e7fff2 0%, rgba(231,255,242,0) 55%),
                  linear-gradient(180deg, #f7fbff 0%, #f6f7fb 100%);
      min-height: 100vh;
    }
    .app-shell{ max-width: 980px; }
    .brand{ font-weight: 800; letter-spacing: .02em; }
    .muted{ color:#6c757d; font-size:.95rem; }
    .glass{
      background: rgba(255,255,255,.72);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,.55);
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,.08);
    }
    .pill{
      width: 34px; height: 34px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      border: 1px solid rgba(0,0,0,.06);
      background: rgba(255,255,255,.8);
    }
    .ok{ background: rgba(25,135,84,.14); border-color: rgba(25,135,84,.18); }
    .ng{ background: rgba(220,53,69,.12); border-color: rgba(220,53,69,.18); }
    .na{ background: rgba(108,117,125,.10); border-color: rgba(108,117,125,.16); }
    .history-item{ border: 1px solid rgba(0,0,0,.06); border-radius: 16px; }
    .history-item + .history-item{ margin-top: 12px; }
    .chip{
      display:inline-flex; gap:8px; align-items:center;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(0,0,0,.04);
      font-size: .9rem;
    }
  </style>
</head>

<body>
<nav class="navbar bg-white border-bottom">
  <div class="container app-shell">
    <span class="navbar-brand brand">SafeHome</span>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="home.php">ホーム</a>
      <a class="btn btn-outline-danger btn-sm" href="logout.php">ログアウト</a>
    </div>
  </div>
</nav>

<main class="container app-shell py-4">
  <div class="glass p-4 p-md-5">

    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
      <div>
        <h1 class="h4 mb-1">履歴</h1>
        <div class="muted">最新50件</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-primary" href="insert.php">入力する</a>
        <a class="btn btn-outline-secondary" href="home.php">ホームへ戻る</a>
      </div>
    </div>

    <?php if (!$rows): ?>
      <div class="alert alert-info mb-0">履歴がまだありません。</div>
    <?php else: ?>

      <?php
        // ちょいアプリっぽい“サマリー”用（見た目だけ）
        $latest = $rows[0];
        $doneCount = 0;
        $doneCount += $latest['window_closed'] ? 1 : 0;
        $doneCount += $latest['gas_off'] ? 1 : 0;
        $doneCount += $latest['aircon_off'] ? 1 : 0;
        $doneCount += $latest['tv_off'] ? 1 : 0;
        $doneCount += $latest['door_locked'] ? 1 : 0;
      ?>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="glass p-3">
            <div class="muted">最新入力者</div>
            <div class="fw-semibold fs-5"><?php echo htmlspecialchars($latest['username'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="glass p-3">
            <div class="muted">最新日時</div>
            <div class="fw-semibold">
              <?php echo htmlspecialchars($latest['checked_at'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="glass p-3">
            <div class="muted">チェック完了数</div>
            <div class="fw-semibold fs-5"><?php echo $doneCount; ?>/5</div>
          </div>
        </div>
      </div>

      <div>
        <?php foreach ($rows as $r): ?>
          <?php
            $w = $r['window_closed'] ? 'ok' : 'na';
            $g = $r['gas_off'] ? 'ok' : 'na';
            $a = $r['aircon_off'] ? 'ok' : 'na';
            $t = $r['tv_off'] ? 'ok' : 'na';
            $d = $r['door_locked'] ? 'ok' : 'na';

            $memo = trim((string)($r['memo'] ?? ''));
          ?>
          <div class="history-item p-3 p-md-4 bg-white">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
              <div class="d-flex flex-wrap gap-2">
                <span class="chip">👤 <?php echo htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="chip">🕒 <?php echo htmlspecialchars($r['checked_at'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-2">
              <span class="chip"><span class="pill <?php echo $w; ?>">窓</span></span>
              <span class="chip"><span class="pill <?php echo $g; ?>">ガス</span></span>
              <span class="chip"><span class="pill <?php echo $a; ?>">エアコン</span></span>
              <span class="chip"><span class="pill <?php echo $t; ?>">TV</span></span>
              <span class="chip"><span class="pill <?php echo $d; ?>">鍵</span></span>
            </div>

            <?php if ($memo !== ''): ?>
              <div class="mt-2 muted">
                📝 <?php echo htmlspecialchars($memo, ENT_QUOTES, 'UTF-8'); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </div>

  <p class="text-center muted mt-3 mb-0">© SafeHome</p>
</main>
</body>
</html>
