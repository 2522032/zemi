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
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['invite_code'] ?? '');

    if ($code === '') {
        $error = '招待コードを入力してください';
    } else {
        try {
            
            $stmt = $pdo->prepare("SELECT id, name FROM groups WHERE invite_code = :c");
            $stmt->execute([':c' => $code]);
            $g = $stmt->fetch();

            if (!$g) {
                $error = '招待コードが見つかりません';
            } else {
                $gid = (int)$g['id'];

                
                $stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = :gid AND user_id = :uid");
                $stmt->execute([':gid' => $gid, ':uid' => $uid]);
                $already = (bool)$stmt->fetchColumn();

                if (!$already) {
                    $stmt = $pdo->prepare("
                        INSERT INTO group_members (group_id, user_id, role)
                        VALUES (:gid, :uid, 'member')
                    ");
                    $stmt->execute([':gid' => $gid, ':uid' => $uid]);
                }

                
                $_SESSION['group_id'] = $gid;

                $ok = '参加しました：' . $g['name'];
            }
        } catch (PDOException $e) {
            $error = '参加エラー: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>グループ参加 | SafeHome</title>

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

    .grid{
      display:grid;
      grid-template-columns: 1.05fr .95fr;
      gap: 28px;
      align-items: start;
    }
    @media (max-width: 992px){
      .grid{ grid-template-columns: 1fr; }
    }

    .hero{
      padding: 26px 24px;
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 22px;
      background: rgba(255,255,255,.06);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    .hero h1{
      font-size: 2rem;
      margin: 0 0 .5rem;
      line-height:1.2;
      font-weight: 900;
    }
    .hero p{ color: var(--muted); margin:0; }
    .chips{ margin-top: 18px; display:flex; flex-wrap:wrap; gap:10px; }
    .chip{
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      color: rgba(255,255,255,.82);
      padding: 8px 12px;
      border-radius: 999px;
      font-size: .9rem;
      font-weight: 700;
    }

    .card-glass{
      border-radius: 22px;
      background: linear-gradient(180deg, var(--card2), var(--card));
      border: 1px solid var(--stroke);
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }
    .card-body{ padding: 28px; }

    .title{ font-weight:900; margin:0; font-size:1.25rem; }
    .subtitle{ margin:.35rem 0 0; color: var(--muted); font-size:.98rem; }

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

    .input-wrap{ position:relative; }
    .input-wrap .bi{
      position:absolute; left:14px; top:50%;
      transform: translateY(-50%);
      color: rgba(255,255,255,.65);
      font-size: 1.05rem;
      pointer-events:none;
    }
    .form-control{
      border-radius: 14px;
      padding: 12px 14px 12px 42px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
      color: var(--text);
    }
    .form-control:focus{
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.55);
      box-shadow: 0 0 0 .25rem rgba(46,197,255,.18);
      color: var(--text);
    }
    .form-label{
      color: rgba(255,255,255,.85);
      font-weight: 750;
      margin-bottom: .4rem;
    }
    .hint{
      color: rgba(255,255,255,.60);
      font-size: .9rem;
      margin-top: .35rem;
    }

    .btn-primary{
      border:0;
      border-radius: 14px;
      padding: 12px 16px;
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
      padding: 12px 16px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.16);
      color: rgba(255,255,255,.88);
      font-weight: 800;
    }
    .btn-ghost:hover{ background: rgba(255,255,255,.10); color: rgba(255,255,255,.95); }

    .alert-app{
      border-radius: 14px;
      padding: 12px 14px;
      display:flex;
      gap:10px;
      align-items:flex-start;
      margin-top: 14px;
    }
    .alert-app .bar{
      width: 6px; border-radius: 99px;
      flex: 0 0 auto;
      height: 100%;
      margin-top: 2px;
    }
    .alert-danger-app{
      border: 1px solid rgba(255,77,109,.35);
      background: rgba(255,77,109,.12);
    }
    .alert-danger-app .bar{
      background: linear-gradient(180deg, var(--danger1), rgba(255,77,109,.35));
    }
    .alert-success-app{
      border: 1px solid rgba(47,231,166,.28);
      background: rgba(47,231,166,.10);
    }
    .alert-success-app .bar{
      background: linear-gradient(180deg, rgba(47,231,166,1), rgba(46,197,255,.35));
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
          <small>招待コードで参加</small>
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
    <div class="grid">

      <section class="hero">
        <h1>グループに参加</h1>
        <p>グループ作成者から共有された招待コードを入力して参加します。</p>
        <div class="chips">
          <span class="chip"><i class="bi bi-clipboard-check me-1"></i> コードを貼り付け</span>
          <span class="chip"><i class="bi bi-people me-1"></i> 参加後に共有開始</span>
          <span class="chip"><i class="bi bi-shield-lock me-1"></i> 安全に参加</span>
        </div>
      </section>

      <section class="card-glass">
        <div class="card-body">

          <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div>
              <h2 class="title">グループ参加</h2>
              <p class="subtitle">招待コードを入力して参加します。</p>
            </div>
            <span class="pill"><i class="bi bi-ticket-perforated"></i> 招待コード</span>
          </div>

          <?php if ($error): ?>
            <div class="alert-app alert-danger-app" role="alert">
              <div class="bar"></div>
              <div>
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> エラー</div>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($ok): ?>
            <div class="alert-app alert-success-app" role="alert">
              <div class="bar"></div>
              <div>
                <div class="fw-bold mb-1"><i class="bi bi-check2-circle me-1"></i> 完了</div>
                <div><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
            <div class="d-grid gap-2 mt-3">
              <a class="btn btn-primary btn-lg" href="home.php">
                <i class="bi bi-house me-1"></i> ホームへ
              </a>
            </div>
          <?php endif; ?>

          <form method="post" class="mt-4">
            <div class="mb-3">
              <label class="form-label">招待コード</label>
              <div class="input-wrap">
                <i class="bi bi-ticket-detailed"></i>
                <input
                  type="text"
                  name="invite_code"
                  class="form-control form-control-lg"
                  required
                  placeholder="例：521f123cd3250ae0"
                >
              </div>
              <div class="hint">グループ作成者から共有されたコードを貼り付けてください。</div>
            </div>

            <div class="d-grid gap-2 mt-4">
              <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-person-add me-1"></i> 参加する
              </button>
              <a class="btn btn-ghost btn-lg" href="home.php">
                <i class="bi bi-arrow-left me-1"></i> 戻る
              </a>
            </div>
          </form>

          <div class="footer">© SafeHome</div>
        </div>
      </section>

    </div>
  </main>

</body>
</html>
