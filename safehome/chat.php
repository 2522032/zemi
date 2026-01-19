<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
if (!isset($_SESSION['group_id'])) {
  header('Location: group_select.php');
  exit;
}

require_once __DIR__ . '/connect_db.php';

$gid = (int)$_SESSION['group_id'];

$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = :gid");
$stmt->execute([':gid' => $gid]);
$groupName = $stmt->fetchColumn() ?: '（未設定）';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>グループチャット | SafeHome</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root{
      --bg1:#0b1220;
      --bg2:#0f2447;
      --card: rgba(255,255,255,.10);
      --card2: rgba(255,255,255,.14);
      --stroke: rgba(255,255,255,.18);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.70);
      --primary1:#6d5efc;
      --primary2:#2ec5ff;
    }
    body{
      min-height:100vh;
      color: var(--text);
      background:
        radial-gradient(900px 600px at 10% 10%, rgba(109,94,252,.45), transparent 60%),
        radial-gradient(900px 600px at 90% 20%, rgba(46,197,255,.35), transparent 55%),
        radial-gradient(900px 600px at 50% 110%, rgba(47,231,166,.18), transparent 60%),
        linear-gradient(180deg, var(--bg1), var(--bg2));
      overflow-x:hidden;
    }
    body:before{
      content:"";
      position:fixed;
      inset:0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0);
      background-size: 28px 28px;
      opacity:.35;
      pointer-events:none;
    }
    .topbar{
      position:sticky; top:0;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      background: rgba(11,18,32,.35);
      border-bottom: 1px solid rgba(255,255,255,.08);
      z-index:10;
    }
    .brand{
      display:flex; align-items:center; gap:.75rem;
      font-weight:800; letter-spacing:.02em;
    }
    .brand-badge{
      width:38px; height:38px; border-radius:12px;
      display:grid; place-items:center;
      background: linear-gradient(135deg, var(--primary1), var(--primary2));
      box-shadow: 0 10px 30px rgba(46,197,255,.18);
    }
    .brand small{ display:block; color: var(--muted); font-weight:600; letter-spacing:0; }
    .wrap{
      max-width: 980px;
      margin: 0 auto;
      padding: 28px 16px 28px;
    }
    .card-glass{
      border-radius: 22px;
      background: linear-gradient(180deg, var(--card2), var(--card));
      border: 1px solid var(--stroke);
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }
    .card-body{ padding: 22px; }
    .pill{
      display:inline-flex;
      align-items:center;
      gap:.4rem;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(46,197,255,.12);
      border: 1px solid rgba(46,197,255,.18);
      color: rgba(255,255,255,.88);
      font-weight: 850;
      font-size: .85rem;
      white-space: nowrap;
    }
    .btn-ghost{
      border-radius: 14px;
      padding: 10px 14px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.16);
      color: rgba(255,255,255,.88);
      font-weight: 800;
      text-decoration:none;
    }
    .btn-ghost:hover{ background: rgba(255,255,255,.10); color: rgba(255,255,255,.95); }
  </style>
</head>

<body>
  <nav class="topbar">
    <div class="container py-3 d-flex align-items-center justify-content-between">
      <div class="brand">
        <div class="brand-badge"><i class="bi bi-chat-dots"></i></div>
        <div>
          SafeHome
          <small>グループチャット</small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="pill"><i class="bi bi-people"></i> <?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="btn-ghost btn-sm" href="home.php"><i class="bi bi-house me-1"></i> ホーム</a>
        <a class="btn-ghost btn-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> ログアウト</a>
      </div>
    </div>
  </nav>

  <main class="wrap">
    <section class="card-glass">
      <div class="card-body">
        <?php require_once __DIR__ . '/chat_widget.php'; ?>
      </div>
    </section>
  </main>
</body>
</html>
