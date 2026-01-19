<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['group_id'])) { header('Location: group_select.php'); exit; }

$userId  = (int)$_SESSION['user_id'];
$groupId = (int)$_SESSION['group_id'];

$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE user_id = :u AND group_id = :g");
$stmt->execute([':u' => $userId, ':g' => $groupId]);
if (!$stmt->fetchColumn()) { unset($_SESSION['group_id']); header('Location: group_select.php'); exit; }

$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = :gid");
$stmt->execute([':gid' => $groupId]);
$groupName = $stmt->fetchColumn() ?: '(不明なグループ)';

$error = "";

$prefDate = trim($_GET['date'] ?? '');
$defaultStart = '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $prefDate)) {
  $defaultStart = $prefDate . 'T18:00';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $start = trim($_POST['start_at'] ?? '');
  $end   = trim($_POST['end_at'] ?? '');

  if ($title === '' || $start === '') {
    $error = "タイトルと開始日時は必須です";
  } else {
    $startSql = str_replace('T', ' ', $start);
    $endSql   = ($end !== '') ? str_replace('T', ' ', $end) : null;

    try {
      $stmt = $pdo->prepare("
        INSERT INTO group_events (group_id, created_by, title, description, start_at, end_at)
        VALUES (:g, :u, :t, :d, :s, :e)
      ");
      $stmt->execute([
        ':g' => $groupId,
        ':u' => $userId,
        ':t' => $title,
        ':d' => ($desc === '' ? null : $desc),
        ':s' => $startSql,
        ':e' => $endSql
      ]);

      $dt = new DateTime($startSql);
      $y = $dt->format('Y');
      $m = $dt->format('n');
      header("Location: calendar.php?y={$y}&m={$m}");
      exit;

    } catch (PDOException $e) {
      $error = "保存エラー: " . $e->getMessage();
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
  <title>予定追加 | SafeHome</title>

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

    .wrap{ max-width: 980px; margin: 0 auto; padding: 40px 16px 24px; }

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
    textarea.form-control{ border-radius: 16px; }

    .hint{ color: rgba(255,255,255,.60); font-size: .9rem; margin-top: .35rem; }

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

    .panel{
      margin-top: 14px;
      padding: 14px;
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
    }
    .panel .k{ color: rgba(255,255,255,.70); font-weight: 900; }
    .panel .v{ margin-top: 6px; font-weight: 950; font-size: 1.1rem; }
    .mono{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      letter-spacing: .03em;
    }

    .footer{ color: rgba(255,255,255,.55); text-align:center; margin-top: 14px; font-size:.9rem; }
  </style>
</head>

<body>

  <nav class="topbar">
    <div class="container py-3 d-flex align-items-center justify-content-between">
      <div class="brand">
        <div class="brand-badge"><i class="bi bi-calendar3"></i></div>
        <div>
          SafeHome
          <small>予定追加</small>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a class="btn btn-ghost btn-sm" href="calendar.php">
          <i class="bi bi-arrow-left me-1"></i> カレンダー
        </a>
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
        <h1>予定を追加</h1>
        <p>グループの予定を登録します。誰が追加したか（created_by）は自動で保存されます。</p>
        <div class="chips">
          <span class="chip"><i class="bi bi-people me-1"></i> <?= h($groupName) ?></span>
          <span class="chip"><i class="bi bi-clock me-1"></i> 日時指定</span>
          <span class="chip"><i class="bi bi-chat-left-text me-1"></i> メモ可</span>
        </div>

        <div class="panel">
          <div class="k"><i class="bi bi-info-circle me-1"></i> ヒント</div>
          <div class="v">
            <?php if ($defaultStart !== ''): ?>
              選択した日付：<span class="mono"><?= h($prefDate) ?></span>（開始日時に自動入力済み）
            <?php else: ?>
              カレンダーの「＋」から来ると、その日付が自動入力されます
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="card-glass">
        <div class="card-body">
          <h2 class="title">入力フォーム</h2>
          <p class="subtitle">タイトルと開始日時は必須です。</p>

          <?php if ($error): ?>
            <div class="alert-app alert-danger-app" role="alert">
              <div class="bar"></div>
              <div>
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> エラー</div>
                <div><?= h($error) ?></div>
              </div>
            </div>
          <?php endif; ?>

          <form method="post" class="mt-4">

            <div class="mb-3">
              <label class="form-label">タイトル（必須）</label>
              <input type="text" name="title" class="form-control form-control-lg" maxlength="100" required
                     placeholder="例：鍵を閉める / ゴミ出し / 帰宅予定">
              <div class="hint">短く具体的に書くと見やすいです（100文字まで）。</div>
          </div>

            <div class="mb-3">
              <label class="form-label">開始日時（必須）</label>
              <input type="datetime-local" name="start_at" class="form-control" required value="<?= h($defaultStart) ?>">
              <div class="hint">カレンダーの「＋」から来た場合は自動入力されています。</div>
            </div>

            <div class="mb-3">
              <label class="form-label">終了日時（任意）</label>
              <input type="datetime-local" name="end_at" class="form-control">
              <div class="hint">入れなくてもOK（最小実装）。</div>
            </div>

            <div class="mb-3">
              <label class="form-label">メモ（任意）</label>
              <textarea name="description" class="form-control" rows="4"
                        placeholder="例：玄関の鍵を閉めたらスタンプ的に残す / 誰がやったか確認用"></textarea>
              <div class="hint">家族に共有したい補足があれば入力してください。</div>
            </div>

            <div class="d-grid gap-2 mt-4">
              <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save me-1"></i> 保存
              </button>
              <a class="btn btn-ghost btn-lg" href="calendar.php">
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
