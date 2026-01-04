<?php
session_start();
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
