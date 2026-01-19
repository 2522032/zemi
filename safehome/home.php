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
      --ok1:#2fe7a6;
      --danger1:#ff4d6d;
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
      max-width: 1100px;
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
    .card-body{ padding: 26px; }

    .title{
      font-weight: 900;
      margin: 0;
      font-size: 1.45rem;
      letter-spacing: .01em;
    }
    .subtitle{
      margin: .4rem 0 0;
      color: var(--muted);
      font-size: .98rem;
    }

    .pill{
      display:inline-flex;
      align-items:center;
      gap:.4rem;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(46,197,255,.12);
      border: 1px solid rgba(46,197,255,.18);
      color: rgba(255,255,255,.88);
      font-weight: 800;
      font-size: .85rem;
      white-space: nowrap;
    }
    .pill-ok{
      background: rgba(47,231,166,.10);
      border-color: rgba(47,231,166,.22);
    }

    .btn-primary{
      border:0;
      border-radius: 14px;
      padding: 10px 14px;
      font-weight: 850;
      background: linear-gradient(135deg, var(--primary1), var(--primary2));
      box-shadow: 0 14px 28px rgba(109,94,252,.18);
      transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
    }
    .btn-primary:hover{
      transform: translateY(-1px);
      box-shadow: 0 18px 40px rgba(109,94,252,.22);
      filter: brightness(1.04);
    }

    .btn-ghost{
      border-radius: 14px;
      padding: 10px 14px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.16);
      color: rgba(255,255,255,.88);
      font-weight: 800;
    }
    .btn-ghost:hover{
      background: rgba(255,255,255,.10);
      color: rgba(255,255,255,.95);
    }

    .muted{ color: var(--muted); }

    .kpi{
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      padding: 16px;
    }
    .kpi .label{
      color: rgba(255,255,255,.70);
      font-weight: 800;
      font-size: .9rem;
      display:flex;
      align-items:center;
      gap:.45rem;
    }
    .kpi .value{
      margin-top: 8px;
      font-weight: 950;
      font-size: 1.4rem;
      letter-spacing: .02em;
      word-break: break-all;
    }
    .mono{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      letter-spacing: .03em;
    }

    .action-grid{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }
    @media (max-width: 992px){
      .action-grid{ grid-template-columns: 1fr; }
    }

    .action-card{
      display:block;
      text-decoration:none;
      color: inherit;
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      padding: 18px;
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
      height: 100%;
    }
    .action-card:hover{
      transform: translateY(-2px);
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.28);
    }
    .action-card .icon{
      width: 44px; height: 44px;
      border-radius: 16px;
      display:grid; place-items:center;
      background: rgba(46,197,255,.12);
      border: 1px solid rgba(46,197,255,.18);
      margin-bottom: 12px;
    }
    .action-card .name{
      font-weight: 900;
      font-size: 1.05rem;
      margin: 0;
    }
    .action-card .desc{
      color: rgba(255,255,255,.68);
      margin-top: 6px;
      font-size: .95rem;
    }
    .action-card .go{
      margin-top: 14px;
      display:inline-flex;
      align-items:center;
      gap:.4rem;
      font-weight: 850;
      color: rgba(255,255,255,.90);
    }

    .member-box{
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      padding: 16px;
    }
    .member-item{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.10);
      margin-top: 10px;
    }
    .role-badge{
      font-size:.82rem;
      padding: .25rem .55rem;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.16);
      background: rgba(255,255,255,.08);
      color: rgba(255,255,255,.82);
      font-weight: 800;
      white-space: nowrap;
    }
    .role-owner{
      border-color: rgba(255,200,80,.30);
      background: rgba(255,200,80,.12);
      color: rgba(255,240,210,.92);
    }

    .footer{
      color: rgba(255,255,255,.55);
      text-align:center;
      margin-top: 16px;
      font-size:.9rem;
    }
.invite-pill{
  display:inline-flex;
  align-items:center;
  gap:.55rem;
  padding: 8px 10px;
  border-radius: 999px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.16);
  color: rgba(255,255,255,.88);
  font-weight: 900;
  font-size: .85rem;
  white-space: nowrap;
}
.invite-code{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
  letter-spacing: .12em;
  padding: 4px 10px;
  border-radius: 12px;
  background: rgba(0,0,0,.18);
  border: 1px solid rgba(255,255,255,.12);
}

.big-grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
@media (max-width: 992px){
  .big-grid{ grid-template-columns: 1fr; }
}
.big-card{
  display:block;
  text-decoration:none;
  color: inherit;
  border-radius: 22px;
  border: 1px solid rgba(255,255,255,.14);
  background: rgba(255,255,255,.07);
  padding: 22px;
  transition: transform .12s ease, background .12s ease, border-color .12s ease;
}
.big-card:hover{
  transform: translateY(-2px);
  background: rgba(255,255,255,.10);
  border-color: rgba(46,197,255,.28);
}
.big-card .row1{
  display:flex; align-items:center; justify-content:space-between; gap: 12px;
}
.big-card .bicon{
  width: 54px; height: 54px;
  border-radius: 18px;
  display:grid; place-items:center;
  background: rgba(46,197,255,.12);
  border: 1px solid rgba(46,197,255,.18);
  font-size: 1.35rem;
}
.big-card .btitle{
  font-weight: 950;
  font-size: 1.25rem;
  margin: 0;
}
.big-card .bdesc{
  color: rgba(255,255,255,.72);
  margin-top: 8px;
  font-size: .98rem;
}
.big-card .bcta{
  margin-top: 14px;
  display:inline-flex;
  align-items:center;
  gap:.4rem;
  font-weight: 900;
}

  </style>
</head>

<body>

  <nav class="topbar">
    <div class="container py-3 d-flex align-items-center justify-content-between">
      <div class="brand">
        <div class="brand-badge"><i class="bi bi-shield-check"></i></div>
        <div>
          SafeHome
          <small>ホーム</small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="muted d-none d-md-inline">
          <i class="bi bi-person-circle me-1"></i>
          <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> さん
        </span>

        <a class="btn btn-ghost btn-sm" href="group_select.php">
          <i class="bi bi-arrow-left-right me-1"></i> グループ変更
        </a>
        <a class="btn btn-ghost btn-sm" href="logout.php">
          <i class="bi bi-box-arrow-right me-1"></i> ログアウト
        </a>
      </div>
    </div>
  </nav>

  <main class="wrap">

    <section class="card-glass">
      <div class="card-body">

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="title">おかえりなさい、<?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> さん</h1>
            <p class="subtitle">今日の安全チェックや、グループの状況を確認できます。</p>
          </div>

          <div class="text-lg-end">
            <div class="pill"><i class="bi bi-people"></i> 現在のグループ</div>
            <div class="mt-2 fs-5 fw-semibold">
              <?php echo htmlspecialchars($groupName ?: '（未設定）', ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>
        </div>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-3">
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <span class="invite-pill">
      <i class="bi bi-ticket-perforated"></i> 招待コード
      <span class="invite-code" id="inviteCodeText">
        <?php echo htmlspecialchars($inviteCode ?: '—', ENT_QUOTES, 'UTF-8'); ?>
      </span>
      <button type="button" class="btn btn-ghost btn-sm"
        onclick="
          const t=document.getElementById('inviteCodeText').innerText.trim();
          if(!t || t==='—') return;
          navigator.clipboard.writeText(t);
          this.innerHTML='<i class=&quot;bi bi-check2 me-1&quot;></i>コピー済';
          setTimeout(()=>{this.innerHTML='<i class=&quot;bi bi-clipboard me-1&quot;></i>コピー';},1200);
        ">
        <i class="bi bi-clipboard me-1"></i>コピー
      </button>
    </span>

    <a class="btn btn-ghost btn-sm" href="group_join.php">
      <i class="bi bi-person-add me-1"></i> 招待で参加
    </a>

  </div>

  <div class="pill pill-ok">
    <i class="bi bi-people"></i>
    <?php echo isset($members) ? (is_countable($members) ? count($members) : 0) : 0; ?> 人参加中
  </div>
</div>

<div class="big-grid mb-3">
  <a class="big-card" href="insert.php">
    <div class="row1">
      <div class="d-flex align-items-center gap-3">
        <div class="bicon"><i class="bi bi-check2-square"></i></div>
        <div>
          <p class="btitle">安全チェック入力</p>
          <div class="bdesc">窓 / ガス / テレビ / 電気 / 鍵 を一括でチェック</div>
        </div>
      </div>
      <div class="pill"><i class="bi bi-lightning-charge"></i> 今日</div>
    </div>
    <div class="bcta">入力する <i class="bi bi-arrow-right"></i></div>
  </a>

  <a class="big-card" href="history.php">
    <div class="row1">
      <div class="d-flex align-items-center gap-3">
        <div class="bicon"><i class="bi bi-clock-history"></i></div>
        <div>
          <p class="btitle">履歴</p>
          <div class="bdesc">家族の入力履歴を確認（最新50件）</div>
        </div>
      </div>
      <div class="pill"><i class="bi bi-graph-up"></i> チェック</div>
    </div>
    <div class="bcta">履歴を見る <i class="bi bi-arrow-right"></i></div>
  </a>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-6">
    <div class="member-box">
      <div class="d-flex align-items-center justify-content-between">
        <div class="label"><i class="bi bi-person-lines-fill"></i> 参加メンバー</div>
        <div class="pill pill-ok"><i class="bi bi-people"></i> <?php echo count($members); ?>人</div>
      </div>

      <?php if (!empty($members) && is_array($members)): ?>
        <?php foreach ($members as $m): ?>
          <?php
            $name = $m['username'] ?? '';
            $role = $m['role'] ?? '';
            $isOwner = ($role === 'owner');
          ?>
          <div class="member-item">
            <div class="fw-semibold">
              <i class="bi bi-person me-1"></i>
              <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php if ($role !== ''): ?>
              <div class="role-badge <?php echo $isOwner ? 'role-owner' : ''; ?>">
                <?php echo htmlspecialchars($isOwner ? 'owner' : 'member', ENT_QUOTES, 'UTF-8'); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="mt-2 muted">メンバー情報がまだありません。</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="member-box">
      <div class="label"><i class="bi bi-grid"></i> その他</div>
      <div class="mt-2 d-grid gap-2">
        <a class="btn btn-ghost" href="group_select.php"><i class="bi bi-gear me-1"></i> グループ設定</a>
        <a class="btn btn-ghost" href="chat.php"><i class="bi bi-chat-dots me-1"></i> グループチャット</a>
        <a class="btn btn-ghost" href="calendar.php"><i class="bi bi-calendar3 me-1"></i> カレンダー</a>
      </div>
    </div>
  </div>
</div>

      </div>
    </section>

    <div class="footer">© SafeHome</div>
  </main>

</body>
</html>
