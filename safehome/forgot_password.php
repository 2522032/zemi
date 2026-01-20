<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

$error = '';
$code_to_show = '';

function generate_reset_code(): string {
  return strval(random_int(100000, 999999));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');

  if ($username === '') {
    $error = 'ユーザー名を入力してください';
  } else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $user_id = $stmt->fetchColumn();

    if (!$user_id) {
      $error = 'そのユーザー名は存在しません';
    } else {
      $code = generate_reset_code();
      $code_hash = password_hash($code, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare("
        INSERT INTO password_reset_codes (user_id, code_hash, expires_at)
        VALUES (:uid, :ch, NOW() + INTERVAL '10 minutes')
      ");
      $stmt->execute([
        ':uid' => (int)$user_id,
        ':ch'  => $code_hash
      ]);

      $code_to_show = $code;
      $_SESSION['reset_username'] = $username;
    }
  }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>パスワード再設定コード | SafeHome</title>

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
      --danger1:#ff4d6d;
      --ok1:#2fe7a6;
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
    .brand small{
      display:block; color: var(--muted); font-weight:600; letter-spacing:0;
    }

    .wrap{ max-width: 980px; margin: 0 auto; padding: 44px 16px 24px; }

    .card-glass{
      border-radius: 22px;
      background: linear-gradient(180deg, var(--card2), var(--card));
      border: 1px solid var(--stroke);
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }
    .card-body{ padding: 30px; }

    .title{ font-weight: 950; margin: 0; font-size: 1.35rem; }
    .subtitle{ margin:.35rem 0 0; color: var(--muted); font-size:.98rem; }

    .form-label{ color: rgba(255,255,255,.85); font-weight: 850; margin-bottom: .4rem; }
    .form-control{
      border-radius: 14px;
      padding: 12px 14px;
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

    .btn-primary{
      border:0;
      border-radius: 14px;
      padding: 12px 16px;
      font-weight: 900;
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
      font-weight: 850;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:.4rem;
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
    .alert-ok-app{
      border: 1px solid rgba(47,231,166,.30);
      background: rgba(47,231,166,.12);
    }
    .alert-ok-app .bar{
      background: linear-gradient(180deg, var(--ok1), rgba(47,231,166,.30));
    }

    .codebox{
      margin-top: 10px;
      padding: 14px;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.16);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
    }
    .code{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size: 1.4rem;
      letter-spacing: .15em;
      font-weight: 950;
    }
    .hint{ color: rgba(255,255,255,.62); font-size: .9rem; margin-top: .35rem; }
    .footer{ color: rgba(255,255,255,.55); text-align:center; margin-top: 14px; font-size:.9rem; }
  </style>
</head>

<body>
  <nav class="topbar">
    <div class="container py-3 d-flex align-items-center justify-content-between">
      <div class="brand">
        <div class="brand-badge"><i class="bi bi-shield-lock"></i></div>
        <div>
          SafeHome
          <small>パスワード再設定</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a class="btn btn-ghost btn-sm" href="login.php">
          <i class="bi bi-box-arrow-in-right"></i> ログイン
        </a>
      </div>
    </div>
  </nav>

  <main class="wrap">
    <section class="card-glass mx-auto" style="max-width:720px;">
      <div class="card-body">
        <h1 class="title">パスワード再設定コードを発行</h1>
        <p class="subtitle">ユーザー名を入力すると、10分間有効なコードを発行します。</p>

        <?php if ($error): ?>
          <div class="alert-app alert-danger-app" role="alert">
            <div class="bar"></div>
            <div>
              <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> エラー</div>
              <div><?= h($error) ?></div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($code_to_show): ?>
          <div class="alert-app alert-ok-app" role="alert">
            <div class="bar"></div>
            <div style="flex:1;">
              <div class="fw-bold mb-1"><i class="bi bi-check-circle me-1"></i> コードを発行しました</div>
              <div class="hint">このコードを次の画面で入力してください（10分有効）。</div>

              <div class="codebox">
                <div>
                  <div class="hint mb-1">再設定コード</div>
                  <div class="code"><?= h($code_to_show) ?></div>
                </div>
                <button class="btn btn-ghost" type="button" id="copyBtn">
                  <i class="bi bi-clipboard"></i> コピー
                </button>
              </div>

              <div class="d-grid gap-2 mt-4">
                <a class="btn btn-primary btn-lg" href="reset_password.php">
                  <i class="bi bi-arrow-right-circle me-1"></i> パスワード再設定へ
                </a>
                <a class="btn btn-ghost btn-lg" href="login.php">
                  <i class="bi bi-arrow-left me-1"></i> ログインへ戻る
                </a>
              </div>
            </div>
          </div>

          <script>
            const btn = document.getElementById('copyBtn');
            btn?.addEventListener('click', async () => {
              try {
                await navigator.clipboard.writeText("<?= h($code_to_show) ?>");
                btn.innerHTML = '<i class="bi bi-check2"></i> コピー済み';
                setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i> コピー', 1400);
              } catch (e) {
                alert('コピーできませんでした。手動でコピーしてください。');
              }
            });
          </script>

        <?php else: ?>
          <form method="post" class="mt-4">
            <div class="mb-3">
              <label class="form-label">ユーザー名</label>
              <input class="form-control form-control-lg" name="username" required maxlength="50"
                     placeholder="例：test / shunya22 など">
              <div class="hint">登録したユーザー名を入力してください。</div>
            </div>

            <div class="d-grid gap-2 mt-4">
              <button class="btn btn-primary btn-lg" type="submit">
                <i class="bi bi-key me-1"></i> 再設定コードを発行
              </button>
              <a class="btn btn-ghost btn-lg" href="login.php">
                <i class="bi bi-arrow-left me-1"></i> ログインへ戻る
              </a>
            </div>
          </form>
        <?php endif; ?>

        <div class="footer">© SafeHome</div>
      </div>
    </section>
  </main>
</body>
</html>
