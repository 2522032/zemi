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
  <title>グループ参加</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{
      min-height: 100vh;
      background: radial-gradient(1200px 500px at 50% -50px, rgba(255,255,255,.9), rgba(255,255,255,0)) ,
                  linear-gradient(180deg, #cfeeff 0%, #f7fbff 60%, #ffffff 100%);
    }
    .app-card{ max-width: 520px; }
    .brand{ font-weight: 800; letter-spacing: .02em; }
    .muted{ color:#6c757d; font-size:.95rem; }

    /* 透き通るカード */
    .glass{
      background: rgba(255,255,255,.72);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,.55);
      box-shadow: 0 10px 30px rgba(20,50,80,.12);
    }

    /* オレンジのアクセント */
    .accent{
      color:#f76707;
    }
    .btn-orange{
      background:#ff7a18;
      border-color:#ff7a18;
      color:#fff;
    }
    .btn-orange:hover{
      background:#f76707;
      border-color:#f76707;
      color:#fff;
    }
  </style>
</head>
<body>

<nav class="navbar bg-white border-bottom">
  <div class="container">
    <span class="navbar-brand brand">SafeHome</span>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="home.php">ホーム</a>
      <a class="btn btn-outline-danger btn-sm" href="logout.php">ログアウト</a>
    </div>
  </div>
</nav>

<main class="container py-5">
  <div class="mx-auto app-card">

    <div class="card glass border-0 rounded-4">
      <div class="card-body p-4 p-md-5">

        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <h1 class="h4 mb-1">グループ参加</h1>
            <p class="muted mb-0">招待コードを入力して参加します。</p>
          </div>
          <span class="badge rounded-pill text-bg-warning">招待コード</span>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <?php if ($ok): ?>
          <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <div class="d-grid gap-2 mb-3">
            <a class="btn btn-orange btn-lg" href="home.php">ホームへ</a>
          </div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">招待コード <span class="accent">*</span></label>
            <input type="text" name="invite_code" class="form-control form-control-lg"
                   required placeholder="例：521f123cd3250ae0">
            <div class="form-text">グループ作成者から共有されたコードを貼り付けてください。</div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-orange btn-lg">参加する</button>
            <a class="btn btn-outline-secondary" href="home.php">戻る</a>
          </div>
        </form>

      </div>
    </div>

    <p class="text-center muted mt-3 mb-0">© SafeHome</p>
  </div>
</main>

</body>
</html>
