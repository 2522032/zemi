<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['group_id'])) { header('Location: group_create.php'); exit; }

require_once __DIR__ . '/connect_db.php';

$gid = (int)$_SESSION['group_id'];

$stmt = $pdo->prepare("
  SELECT 
         to_char(h.checked_at, 'YYYY-MM-DD HH24:MI') as checked_at,
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
      --primary1:#a78bfa;
      --primary2:#60a5fa;
      --ok1:#2fe7a6;
      --danger1:#ff4d6d;
      --warn1:#ffd166;
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
      padding: 40px 16px 24px;
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

    .title{ font-weight: 950; margin: 0; font-size: 1.35rem; }
    .subtitle{ margin:.35rem 0 0; color: var(--muted); font-size:.98rem; }

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
      font-weight: 850;
    }
    .btn-ghost:hover{
      background: rgba(255,255,255,.10);
      color: rgba(255,255,255,.95);
    }

    .muted{ color: var(--muted); }

    .mini{
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      padding: 16px;
    }
    .mini .label{
      color: rgba(255,255,255,.70);
      font-weight: 850;
      font-size: .9rem;
      display:flex;
      align-items:center;
      gap:.45rem;
    }
    .mini .value{
      margin-top: 8px;
      font-weight: 950;
      font-size: 1.2rem;
      letter-spacing: .01em;
      word-break: break-word;
    }
    .mono{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      letter-spacing: .03em;
    }

    .timeline{
      position: relative;
      margin-top: 14px;
      padding-left: 22px;
    }
    .timeline:before{
      content:"";
      position:absolute;
      left: 9px;
      top: 6px;
      bottom: 6px;
      width: 2px;
      background: rgba(255,255,255,.14);
      border-radius: 99px;
    }

    .event{
      position: relative;
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      padding: 16px;
      margin-bottom: 12px;
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
    }
    .event:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.28);
    }
    .dot{
      position:absolute;
      left: -22px;
      top: 18px;
      width: 18px;
      height: 18px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.18);
      background: rgba(46,197,255,.14);
      display:grid;
      place-items:center;
      color: rgba(255,255,255,.92);
      box-shadow: 0 10px 20px rgba(0,0,0,.25);
    }

    .chips{
      display:flex;
      flex-wrap:wrap;
      gap: 8px;
      margin-top: 10px;
    }
    .chip{
      display:inline-flex;
      align-items:center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.08);
      color: rgba(255,255,255,.80);
      font-weight: 800;
      font-size: .86rem;
      white-space: nowrap;
    }

    .pill{
      width: 34px; height: 34px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      border: 1px solid rgba(255,255,255,.16);
      background: rgba(255,255,255,.08);
      color: rgba(255,255,255,.88);
    }
    .ok{ background: rgba(47,231,166,.12); border-color: rgba(47,231,166,.22); }
    .na{ background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.16); color: rgba(255,255,255,.78); }

    .memo{
      margin-top: 10px;
      padding: 12px 12px;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.12);
      color: rgba(255,255,255,.86);
    }

    .footer{
      color: rgba(255,255,255,.55);
      text-align:center;
      margin-top: 14px;
      font-size:.9rem;
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
          <small>履歴</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a class="btn btn-ghost btn-sm" href="home.php">
          <i class="bi bi-house me-1"></i> ホーム
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

        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div>
            <h1 class="title">履歴</h1>
            <p class="subtitle">最新50件を表示します。</p>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-primary" href="insert.php">
              <i class="bi bi-plus-circle me-1"></i> 入力する
            </a>
            <a class="btn btn-ghost" href="home.php">
              <i class="bi bi-arrow-left me-1"></i> ホームへ戻る
            </a>
          </div>
        </div>

        <?php if (!$rows): ?>
          <div class="mt-4 p-3 rounded-4" style="border:1px solid rgba(46,197,255,.25); background: rgba(46,197,255,.10);">
            <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> 履歴がありません</div>
            <div class="muted">まだ入力がありません。右上の「入力する」から登録できます。</div>
          </div>
        <?php else: ?>

          <?php
            $latest = $rows[0];
            $doneCount = 0;
            $doneCount += $latest['window_closed'] ? 1 : 0;
            $doneCount += $latest['gas_off'] ? 1 : 0;
            $doneCount += $latest['aircon_off'] ? 1 : 0;
            $doneCount += $latest['tv_off'] ? 1 : 0;
            $doneCount += $latest['door_locked'] ? 1 : 0;
          ?>

          <div class="row g-3 mt-3">
            <div class="col-md-4">
              <div class="mini">
                <div class="label"><i class="bi bi-person"></i> 最新入力者</div>
                <div class="value"><?php echo htmlspecialchars($latest['username'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mini">
                <div class="label"><i class="bi bi-clock"></i> 最新日時</div>
                <div class="value mono"><?php echo htmlspecialchars($latest['checked_at'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mini">
                <div class="label"><i class="bi bi-check2-square"></i> チェック完了</div>
                <div class="value"><?php echo $doneCount; ?>/5</div>
              </div>
            </div>
          </div>

          <hr class="my-4" style="border-color: rgba(255,255,255,.14);">

          <div class="timeline">
            <?php foreach ($rows as $r): ?>
              <?php
                $w = $r['window_closed'] ? 'ok' : 'na';
                $g = $r['gas_off'] ? 'ok' : 'na';
                $a = $r['aircon_off'] ? 'ok' : 'na';
                $t = $r['tv_off'] ? 'ok' : 'na';
                $d = $r['door_locked'] ? 'ok' : 'na';
                $memo = trim((string)($r['memo'] ?? ''));
              ?>
              <div class="event">
  <div class="dot"><i class="bi bi-dot"></i></div>

  <?php
    $done = 0;
    $done += $r['window_closed'] ? 1 : 0;
    $done += $r['gas_off'] ? 1 : 0;
    $done += $r['aircon_off'] ? 1 : 0;
    $done += $r['tv_off'] ? 1 : 0;
    $done += $r['door_locked'] ? 1 : 0;
  ?>

  <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
    <div>
      <div class="fw-bold fs-5">
        <i class="bi bi-person me-1"></i>
        <?php echo htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <div class="muted mono">
        <i class="bi bi-clock me-1"></i>
        <?php echo htmlspecialchars($r['checked_at'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    </div>

    <div class="mini" style="padding:10px 12px; border-radius:14px;">
      <div class="label" style="font-size:.85rem;">
        <i class="bi bi-check2-square"></i> 完了
      </div>
      <div class="value" style="margin-top:4px; font-size:1.05rem;">
        <?php echo $done; ?>/5
      </div>
    </div>
  </div>

  <div class="chips mt-3">
    <span class="chip"><span class="pill <?php echo $w; ?>">窓</span></span>
    <span class="chip"><span class="pill <?php echo $g; ?>">ガス</span></span>
    <span class="chip"><span class="pill <?php echo $a; ?>">電気</span></span>
    <span class="chip"><span class="pill <?php echo $t; ?>">TV</span></span>
    <span class="chip"><span class="pill <?php echo $d; ?>">鍵</span></span>
  </div>

  <?php if ($memo !== ''): ?>
    <div class="memo">
      <div class="fw-bold mb-1"><i class="bi bi-chat-left-text me-1"></i> メモ</div>
      <div><?php echo htmlspecialchars($memo, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
  <?php endif; ?>
</div>


                <div class="chips">
                  <span class="chip"><span class="pill <?php echo $w; ?>">窓</span></span>
                  <span class="chip"><span class="pill <?php echo $g; ?>">ガス</span></span>
                  <span class="chip"><span class="pill <?php echo $a; ?>">エアコン</span></span>
                  <span class="chip"><span class="pill <?php echo $t; ?>">TV</span></span>
                  <span class="chip"><span class="pill <?php echo $d; ?>">鍵</span></span>
                </div>

                <?php if ($memo !== ''): ?>
                  <div class="memo">
                    <div class="fw-bold mb-1"><i class="bi bi-chat-left-text me-1"></i> メモ</div>
                    <div><?php echo htmlspecialchars($memo, ENT_QUOTES, 'UTF-8'); ?></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>

        <div class="footer">© SafeHome</div>
      </div>
    </section>
  </main>

</body>
</html>
