<?php
session_start();
ini_set('display_errors', '1');
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
            $user = $stmt->fetch();

            if (!password_verify($password, $user['password_hash'])) {
                $error = 'ユーザー名またはパスワードが違います';
            } else {
                session_regenerate_id(true);
                  $_SESSION = [];
                  $_SESSION['user_id'] = (int)$user['id'];
                  $_SESSION['username'] = $user['username'];
                  unset($_SESSION['group_id']);   // ★リセット
  
                  header('Location: home.php');
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
  <title>ログイン</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background: #f6f7fb; }
    .app-card { max-width: 520px; }
    .brand { font-weight: 700; letter-spacing: .02em; }
    .muted { color: #6c757d; font-size: .95rem; }
  </style>
</head>
<body>

<nav class="navbar bg-white border-bottom">
  <div class="container">
    <span class="navbar-brand brand">SafeHome</span>
  </div>
</nav>

<main class="container py-5">
  <div class="mx-auto app-card">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4 p-md-5">

        <h1 class="h4 mb-1">ログイン</h1>
        <p class="muted mb-4">ユーザー名とパスワードを入力してください。</p>

        <?php if ($error): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">ユーザー名</label>
            <input type="text" name="username" class="form-control form-control-lg" required>
          </div>

          <div class="mb-3">
            <label class="form-label">パスワード</label>
            <input type="password" name="password" class="form-control form-control-lg" required>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">ログイン</button>
            <a class="btn btn-outline-secondary" href="register.php">新規登録はこちら</a>
            <a class="btn btn-link" href="forgot_password.php">パスワードを忘れた場合</a>
</div>

        </form>

      </div>
    </div>

    <p class="text-center muted mt-3 mb-0">© SafeHome</p>
  </div>
</main>

</body>
</html>
