<?php
session_start();
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

?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>パスワードを忘れた場合</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{
      min-height: 100vh;
      background: radial-gradient(1200px 500px at 50% -50px, rgba(255,255,255,.9), rgba(255,255,255,0)),
                  linear-gradient(180deg, #cfeeff 0%, #f7fbff 60%, #ffffff 100%);
    }
    .app-card{ max-width: 520px; }
    .brand{ font-weight: 800; letter-spacing: .02em; }
    .muted{ color:#6c757d; font-size:.95rem; }

    .glass{
      background: rgba(255,255,255,.72);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,.55);
      box-shadow: 0 10px 30px rgba(20,50,80,.12);
    }
  </style>
</head>
<body>

<nav class="navbar bg-white border-bottom">
  <div class="container">
    <span class="navbar-brand brand">SafeHome</span>
  </div>
</nav>

<?php if ($error): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if ($code_to_show): ?>
  <div class="alert alert-success">
    再設定コード： <strong class="fs-4"><?php echo htmlspecialchars($code_to_show, ENT_QUOTES, 'UTF-8'); ?></strong><br>
    このコードを使って、次の画面でパスワードを再設定してください（10分有効）。
  </div>
  <a class="btn btn-primary w-100" href="reset_password.php">パスワード再設定へ</a>
<?php else: ?>
  <form method="post">
    <label class="form-label">ユーザー名</label>
    <input class="form-control mb-3" name="username" required>
    <button class="btn btn-primary w-100">再設定コードを発行</button>
  </form>
<?php endif; ?>

<main class="container py-5">
  <div class="mx-auto app-card">

    <div class="card glass border-0 rounded-4">
      <div class="card-body p-4 p-md-5 text-center">

        <h1 class="h4 mb-3">パスワードを忘れた場合</h1>

        <p class="muted mb-4">
          現在この機能は準備中です。<br>
          今後、メールによる再設定機能を追加予定です。
        </p>

        <div class="alert alert-info">
          開発中のため、もうしばらくお待ちください。
        </div>

        <div class="d-grid gap-2 mt-4">
          <a class="btn btn-primary btn-lg" href="login.php">
            ログイン画面に戻る
          </a>
        </div>

      </div>
    </div>

    <p class="text-center muted mt-3 mb-0">© SafeHome</p>
  </div>
</main>

</body>
</html>
