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
  <title>グループ選択</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    min-height: 100vh;
    background:
      linear-gradient(135deg,
        rgba(173, 216, 255, 0.6),
        rgba(200, 230, 255, 0.6)
      );
    backdrop-filter: blur(6px);
  }

  .app-card {
    max-width: 460px;
  }

  .card {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.4);
  }

  .brand {
    font-weight: 700;
    letter-spacing: .03em;
  }

  .muted {
    color: #6c757d;
    font-size: .9rem;
  }

  .group-item {
    background: rgba(255, 245, 235, 0.9);
    border: 1px solid rgba(255, 180, 100, 0.6);
    border-radius: 12px;
    transition: all .15s ease;
  }

  .group-item:hover {
    background: rgba(255, 230, 200, 0.95);
  }

  .group-radio {
    transform: scale(1.15);
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

<main class="container py-4">
  <div class="mx-auto app-card">

    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4 p-md-5">
        <h1 class="h4 mb-1">グループ選択</h1>
        <p class="muted mb-4">利用するグループを選んでください。</p>

        <?php if ($error): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <?php if (count($groups) === 0): ?>
          <div class="alert alert-info" role="alert">
            まだどのグループにも参加していません。
          </div>

          <div class="d-grid gap-2">
            <a class="btn btn-primary btn-lg" href="group_create.php">グループを作成</a>
            <a class="btn btn-outline-primary btn-lg" href="group_join.php">グループ参加（招待コード）</a>
          </div>

        <?php else: ?>
          <form method="post">
            <div class="mb-3">
              <div class="fw-semibold mb-2">参加しているグループ</div>

              <div class="list-group">
                <?php foreach ($groups as $g): ?>
                  <?php
                    $checked = (isset($_SESSION['group_id']) && (int)$_SESSION['group_id'] === (int)$g['id']);
                  ?>
                  <label class="list-group-item d-flex align-items-center gap-3">
                    <input
                      class="form-check-input m-0"
                      type="radio"
                      name="group_id"
                      value="<?php echo (int)$g['id']; ?>"
                      <?php echo $checked ? 'checked' : ''; ?>
                    >
                    <div class="flex-grow-1">
                      <div class="fw-semibold">
                        <?php echo htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?>
                      </div>
                      <div class="muted">
                        role: <?php echo htmlspecialchars($g['role'], ENT_QUOTES, 'UTF-8'); ?>
                      </div>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-success btn-lg">このグループを使う</button>
              <div class="d-flex gap-2">
                <a class="btn btn-outline-primary w-100" href="group_create.php">グループを作成</a>
                <a class="btn btn-outline-primary w-100" href="group_join.php">招待コードで参加</a>
              </div>
            </div>
          </form>
        <?php endif; ?>

      </div>
    </div>

    <p class="text-center muted mt-3 mb-0">© SafeHome</p>
  </div>
</main>

</body>
</html>

