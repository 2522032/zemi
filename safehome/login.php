<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    $error = 'ユーザー名とパスワードを入力してください';
  } else {
    try {
      $stmt = $pdo->prepare("
        SELECT id, username, password_hash
        FROM users
        WHERE username = :u
        LIMIT 1
      ");
      $stmt->execute([':u' => $username]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // ユーザーなし or パスワード不一致
      if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'ユーザー名またはパスワードが違います';
      } else {
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];

        // ログインしたらグループ選択へ
        unset($_SESSION['group_id']);
        header('Location: group_select.php');
        exit;
      }
    } catch (PDOException $e) {
      $error = 'DBエラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
  <title>ログイン | SafeHome</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{
      min-height:100vh;
      background:
        radial-gradient(900px 600px at 10% 10%, rgba(109,94,252,.35), transparent 60%),
        radial-gradient(900px 600px at 90% 20%, rgba(46,197,255,.25), transparent 55%),
        linear-gradient(180deg, #0b1220, #0f2447);
      color: rgba(255,255,255,.92);
    }
    .glass{
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
      border-radius: 20px;
      backdrop-filter: blur(14px);
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
    }
    .btn-grad{
      border:0;
      border-radius: 14px;
      padding: 12px 16px;
      font-weight: 900;
      background: linear-gradient(135deg, #6d5efc, #2ec5ff);
      color:#fff;
    }
    .form-control{
      border-radius: 14px;
      padding: 12px 14px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
      color: rgba(255,255,255,.92);
    }
    .form-control:focus{
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.55);
      box-shadow: 0 0 0 .25rem rgba(46,197,255,.18);
      color: rgba(255,255,255,.92);
    }
    a{ color: rgba(46,197,255,.95); }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="mx-auto" style="max-width:520px;">
      <div class="glass p-4 p-md-5">
        <h1 class="h4 fw-bold mb-2">ログイン</h1>
        <p class="mb-4" style="color: rgba(255,255,255,.7);">SafeHomeを利用するにはログインしてください。</p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label fw-bold">ユーザー名</label>
            <input class="form-control" name="username" maxlength="50" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">パスワード</label>
            <input class="form-control" type="password" name="password" required>
          </div>

          <button class="btn btn-grad w-100">ログイン</button>

          <div class="d-flex justify-content-between mt-3">
            <a href="register.php">新規登録</a>
            <a href="forgot_password.php">パスワードを忘れた</a>
          </div>
        </form>
      </div>

      <p class="text-center mt-3" style="color: rgba(255,255,255,.55);">© SafeHome</p>
    </div>
  </div>
</body>
</html>
