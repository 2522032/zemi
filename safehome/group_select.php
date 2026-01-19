<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/connect_db.php';

$uid = (int)$_SESSION['user_id'];
$error = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gid = (int)($_POST['group_id'] ?? 0);

    if ($gid <= 0) {
        $error = "グループを選んでください";
    } else {
        
        $stmt = $pdo->prepare("
            SELECT 1
            FROM group_members
            WHERE user_id = :uid AND group_id = :gid
        ");
        $stmt->execute([':uid' => $uid, ':gid' => $gid]);

        if (!$stmt->fetchColumn()) {
            $error = "そのグループに参加していません";
        } else {
            $_SESSION['group_id'] = $gid;
            header('Location: home.php');
            exit;
        }
    }
}


$stmt = $pdo->prepare("
SELECT g.id, g.name, MIN(gm.role) AS role, g.invite_code
FROM group_members gm
JOIN groups g ON g.id = gm.group_id
WHERE gm.user_id = :uid
GROUP BY g.id, g.name, g.invite_code
ORDER BY g.id ASC
");
$stmt->execute([':uid' => $uid]);
$groups = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>グループ選択 | SafeHome</title>

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
      max-width: 980px;
      margin: 0 auto;
      padding: 40px 16px 24px;
    }

    .app-shell{
      display:grid;
      grid-template-columns: 1fr;
      gap: 18px;
      align-items: start;
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
      font-weight: 850;
      margin: 0;
      font-size: 1.35rem;
      letter-spacing: .01em;
    }
    .subtitle{
      margin: .4rem 0 0;
      color: var(--muted);
      font-size: .98rem;
    }

    .btn-primary{
      border:0;
      border-radius: 14px;
      padding: 10px 14px;
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary1), var(--primary2));
      box-shadow: 0 14px 28px rgba(109,94,252,.18);
      transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
    }
    .btn-primary:hover{
      transform: translateY(-1px);
      box-shadow: 0 18px 40px rgba(109,94,252,.22);
      filter: brightness(1.04);
    }

    .btn-success{
      border:0;
      border-radius: 14px;
      padding: 12px 16px;
      font-weight: 850;
      background: linear-gradient(135deg, rgba(47,231,166,1), rgba(46,197,255,.9));
      box-shadow: 0 14px 28px rgba(47,231,166,.18);
      transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
    }
    .btn-success:hover{
      transform: translateY(-1px);
      box-shadow: 0 18px 40px rgba(47,231,166,.22);
      filter: brightness(1.03);
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

    .alert-app{
      border: 1px solid rgba(255,77,109,.35);
      background: rgba(255,77,109,.12);
      color: rgba(255,255,255,.92);
      border-radius: 14px;
      padding: 12px 14px;
      display:flex;
      gap:10px;
      align-items:flex-start;
      margin-top: 14px;
    }
    .alert-app .bar{
      width: 6px; border-radius: 99px;
      background: linear-gradient(180deg, var(--danger1), rgba(255,77,109,.35));
      flex: 0 0 auto;
      height: 100%;
      margin-top: 2px;
    }

    .alert-info-app{
      border: 1px solid rgba(46,197,255,.30);
      background: rgba(46,197,255,.10);
      color: rgba(255,255,255,.92);
      border-radius: 14px;
      padding: 12px 14px;
      display:flex;
      gap:10px;
      align-items:flex-start;
      margin-top: 14px;
    }
    .alert-info-app .bar{
      width: 6px; border-radius: 99px;
      background: linear-gradient(180deg, rgba(46,197,255,1), rgba(109,94,252,.35));
      flex: 0 0 auto;
      height: 100%;
      margin-top: 2px;
    }

    .section-label{
      font-weight: 800;
      color: rgba(255,255,255,.90);
      margin: 18px 0 10px;
      display:flex;
      align-items:center;
      gap:.5rem;
    }

    .group-list{
      display:flex;
      flex-direction:column;
      gap: 10px;
    }
    .group-item{
      display:flex;
      align-items:center;
      gap: 12px;
      padding: 12px 12px;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
      cursor: pointer;
    }
    .group-item:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.28);
    }

    .group-radio{
      width: 18px;
      height: 18px;
      accent-color: rgba(46,197,255,1);
      flex: 0 0 auto;
    }

    .group-icon{
      width: 38px; height: 38px;
      border-radius: 14px;
      display:grid; place-items:center;
      background: rgba(46,197,255,.12);
      border: 1px solid rgba(46,197,255,.18);
      color: rgba(255,255,255,.92);
      flex: 0 0 auto;
    }

    .group-name{
      font-weight: 850;
      line-height: 1.2;
      margin: 0;
    }
    .group-meta{
      color: rgba(255,255,255,.65);
      font-size: .92rem;
      margin-top: 2px;
    }
    .pill{
      display:inline-flex;
      align-items:center;
      gap:.35rem;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
      color: rgba(255,255,255,.78);
      font-size: .85rem;
      font-weight: 750;
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
          <small>グループを選んで開始</small>
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
    <div class="app-shell">

      <section class="card-glass">
        <div class="card-body">

          <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
              <h1 class="title">グループ選択</h1>
              <p class="subtitle">利用するグループを選んでください。</p>
            </div>
            <div class="d-flex gap-2">
              <a class="btn btn-primary btn-sm" href="group_create.php">
                <i class="bi bi-plus-circle me-1"></i> 作成
              </a>
              <a class="btn btn-ghost btn-sm" href="group_join.php">
                <i class="bi bi-person-add me-1"></i> 参加
              </a>
            </div>
          </div>

          <?php if ($error): ?>
            <div class="alert-app" role="alert">
              <div class="bar"></div>
              <div>
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> エラー</div>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if (count($groups) === 0): ?>
            <div class="alert-info-app" role="alert">
              <div class="bar"></div>
              <div>
                <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> まだ参加していません</div>
                <div>グループを作成するか、招待コードで参加してください。</div>
              </div>
            </div>

            <div class="d-grid gap-2 mt-4">
              <a class="btn btn-primary btn-lg" href="group_create.php">
                <i class="bi bi-plus-circle me-1"></i> グループを作成
              </a>
              <a class="btn btn-ghost btn-lg" href="group_join.php">
                <i class="bi bi-person-add me-1"></i> 招待コードで参加
              </a>
            </div>

          <?php else: ?>
            <div class="section-label">
              <i class="bi bi-people"></i>
              参加しているグループ
            </div>

            <form method="post" class="mt-2">
              <div class="group-list">
                <?php foreach ($groups as $g): ?>
                <?php
                $checked = (isset($_SESSION['group_id']) && (int)$_SESSION['group_id'] === (int)$g['id']);
                $isOwner = ($g['role'] === 'owner');
                ?><label class="group-item">
                  <input
                  class="group-radio"
                  type="radio"
                  name="group_id"
                  value="<?php echo (int)$g['id']; ?>"
                  <?php echo $checked ? 'checked' : ''; ?>
                  >
                  <div class="group-icon">
                    <i class="bi bi-house-heart"></i>
                  </div>
                  
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                      
                    <p class="group-name mb-0">
                      <?php echo htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <div class="d-flex align-items-center gap-2">

                    <?php if ($checked): ?>
                      <span class="pill">
                        <i class="bi bi-check2-circle"></i> 選択中
                      </span>
                      <?php endif; ?>

                      <?php if ($isOwner): ?>
                        <a
                        href="group_delete.php?gid=<?php echo (int)$g['id']; ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('このグループを削除します。\nチャット・予定・履歴もすべて消えます。\n本当に削除しますか？');"
                        >
                        <i class="bi bi-trash"></i>
                      </a>
                      <?php else: ?>
                        <a
                        href="group_leave.php?gid=<?php echo (int)$g['id']; ?>"
                        class="btn btn-sm btn-outline-warning"
                        onclick="return confirm('このグループから退会しますか？');"
                        >
                        <i class="bi bi-box-arrow-right"></i>
                      </a>
                      <?php endif; ?>

                    </div>
                  </div>

                  <div class="group-meta">
                    role: <?php echo htmlspecialchars($g['role'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>

                </div>
              </label>
              <?php endforeach; ?>


              </div>

              <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="bi bi-check2-circle me-1"></i> このグループを使う
                </button>

                <div class="d-flex gap-2">
                  <a class="btn btn-ghost w-100" href="group_create.php">
                    <i class="bi bi-plus-circle me-1"></i> 作成
                  </a>
                  <a class="btn btn-ghost w-100" href="group_join.php">
                    <i class="bi bi-person-add me-1"></i> 参加
                  </a>
                </div>
              </div>
            </form>
          <?php endif; ?>

          <div class="footer">© SafeHome</div>
        </div>
      </section>

    </div>
  </main>

</body>
</html>
