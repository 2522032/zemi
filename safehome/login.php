<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'ユーザー名とパスワードを入力してください';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, username, password_hash FROM users WHERE username = :u"
            );
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'ユーザー名またはパスワードが違います';
            } else {
                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                unset($_SESSION['group_id']);

                header('Location: group_select.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'DBエラー: ' . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ログイン | SafeHome</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Icons -->
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
    }

    body{
      min-height:100vh;
      color: var(--text);
      background:
        radial-gradient(900px 600px at 10% 10%, rgba(109,94,252,.45), transparent 60%),
        radial-gradient(900px 600px at 90% 20%, rgba(46,197,255,.35), transparent 55%),
        radial-gradient(900px 600px at 50% 110%, rgba(255,77,109,.20), transparent 60%),
        linear-gradient(180deg, var(--bg1), var(--bg2));
      overflow-x:hidden;
    }

    /* subtle pattern */
    body:before{
      content:"";
      position:fixed;
      inset:0;
      background-image:
        radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0);
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
      padding: 56px 16px 24px;
    }

    .auth-grid{
      display:grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 28px;
      align-items: stretch;
    }
    @media (max-width: 992px){
      .auth-grid{ grid-template-columns: 1fr; }
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
    }
    .hero p{ color: var(--muted); margin:0; }
    .hero .chips{ margin-top: 18px; display:flex; flex-wrap:wrap; gap:10px; }
    .chip{
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      color: rgba(255,255,255,.82);
      padding: 8px 12px;
      border-radius: 999px;
      font-size: .9rem;
    }

    .card-glass{
      border-radius: 22px;
      background: linear-gradient(180deg, var(--card2), var(--card));
      border: 1px solid var(--stroke);
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }

    .card-body{
      padding: 28px;
    }

    .title{
      font-weight:800;
      margin:0;
      font-size:1.25rem;
    }
    .subtitle{
      margin: .35rem 0 0;
      color: var(--muted);
      font-size:.98rem;
    }

    .input-wrap{
      position:relative;
    }
    .input-wrap .bi{
      position:absolute;
      left: 14px;
      top: 50%;
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
      font-weight: 650;
      margin-bottom: .4rem;
    }

    .btn-primary{
      border: 0;
      border-radius: 14px;
      padding: 12px 16px;
      font-weight: 750;
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
      font-weight: 700;
    }
    .btn-ghost:hover{
      background: rgba(255,255,255,.10);
      color: rgba(255,255,255,.95);
    }

    .link-soft{
      color: rgba(255,255,255,.72);
      text-decoration: none;
      font-weight: 650;
    }
    .link-soft:hover{ color: rgba(255,255,255,.92); text-decoration: underline; }

    .alert-app{
      border: 1px solid rgba(255,77,109,.35);
      background: rgba(255,77,109,.12);
      color: rgba(255,255,255,.92);
      border-radius: 14px;
      padding: 12px 14px;
      display:flex;
      gap:10px;
      align-items:flex-start;
    }
    .alert-app .bar{
      width: 6px; border-radius: 99px;
      background: linear-gradient(180deg, var(--danger1), rgba(255,77,109,.35));
      flex: 0 0 auto;
      height: 100%;
      margin-top: 2px;
    }

    .footer{
      color: rgba(255,255,255,.55);
      text-align:center;
      margin-top: 18px;
      font-size:.9rem;
    }
  </style>
</head>

<body>
  <nav class="topbar">
    <div class="container py-3">
      <div class="brand">
        <div class="brand-badge"><i class="bi bi-shield-check"></i></div>
        <div>
          SafeHome
          <small>家族の安全を、ひとつの場所で。</small>
        </div>
      </div>
    </div>
  </nav>

  <main class="wrap">
    <div class="auth-grid">

      <section class="hero">
        <h1>おかえりなさい 👋</h1>
        <p>SafeHomeにログインして、グループの状況共有をはじめましょう。</p>

        <div class="chips">
          <span class="chip"><i class="bi bi-geo-alt me-1"></i> 位置・安否の共有</span>
          <span class="chip"><i class="bi bi-people me-1"></i> グループ管理</span>
          <span class="chip"><i class="bi bi-bell me-1"></i> 状態更新通知</span>
        </div>
      </section>

      <section class="card-glass">
        <div class="card-body">
          <h2 class="title">ログイン</h2>
          <p class="subtitle">ユーザー名とパスワードを入力してください。</p>

          <?php if ($error): ?>
            <div class="alert-app mt-3" role="alert">
              <div class="bar"></div>
              <div>
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> エラー</div>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </div>
          <?php endif; ?>

          <form method="post" class="mt-4">
            <div class="mb-3">
              <label class="form-label">ユーザー名</label>
              <div class="input-wrap">
                <i class="bi bi-person"></i>
                <input type="text" name="username" class="form-control form-control-lg" required autocomplete="username">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">パスワード</label>
              <div class="input-wrap">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" class="form-control form-control-lg" required autocomplete="current-password">
              </div>
            </div>

            <div class="d-grid gap-2 mt-4">
              <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right me-1"></i> ログイン
              </button>

              <a class="btn btn-ghost btn-lg" href="register.php">
                <i class="bi bi-person-plus me-1"></i> 新規登録
              </a>

              <div class="text-center mt-2">
                <a class="link-soft" href="forgot_password.php">
                  パスワードを忘れた場合 →
                </a>
              </div>
            </div>
          </form>

          <div class="footer">© SafeHome</div>
        </div>
      </section>

    </div>
  </main>
</body>
</html>
