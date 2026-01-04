<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['group_id'])) {
    header('Location: group_create.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$gid = (int)$_SESSION['group_id'];

$error = "";
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $window = isset($_POST['window']) ? 1 : 0;
    $gas    = isset($_POST['gas']) ? 1 : 0;
    $aircon = isset($_POST['aircon']) ? 1 : 0;
    $tv     = isset($_POST['tv']) ? 1 : 0;
    $door   = isset($_POST['doorkey']) ? 1 : 0;
    $memo   = trim($_POST['memo'] ?? '');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO home_state
              (group_id, user_id, window_closed, gas_off, aircon_off, tv_off, door_locked, memo)
            VALUES
              (:gid, :uid, :w, :g, :a, :t, :d, :m)
        ");

        $stmt->execute([
            ':gid' => $gid,
            ':uid' => $uid,
            ':w'   => $window,
            ':g'   => $gas,
            ':a'   => $aircon,
            ':t'   => $tv,
            ':d'   => $door,
            ':m'   => ($memo === '' ? null : $memo),
        ]);

        $ok = "保存しました！";
    } catch (PDOException $e) {
        $error = "保存エラー: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SafeHome | 安全チェック入力</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: radial-gradient(1200px 600px at 20% 0%, #dff3ff 0%, rgba(223,243,255,0) 60%),
                  radial-gradient(900px 500px at 80% 20%, #e9fff6 0%, rgba(233,255,246,0) 55%),
                  linear-gradient(180deg, #f6fbff 0%, #ffffff 100%);
      min-height: 100vh;
    }
    .app { max-width: 720px; }
    .card-soft { border: 0; border-radius: 20px; box-shadow: 0 12px 30px rgba(0,0,0,.06); }
    .brand { font-weight: 800; letter-spacing: .02em; }
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
  <div class="mx-auto app">
    <div class="card card-soft">
      <div class="card-body p-4 p-md-5">
        <div class="h4 mb-1">安全チェック入力</div>
        <div class="text-secondary mb-4">確認できた項目にチェックして送信してください</div>

        <form method="post" enctype="multipart/form-data">
          <div class="vstack gap-3">
            <div class="form-check form-switch fs-5">
              <input class="form-check-input" type="checkbox" role="switch" name="window" id="window">
              <label class="form-check-label" for="window">窓</label>
            </div>
            <div class="form-check form-switch fs-5">
              <input class="form-check-input" type="checkbox" role="switch" name="gas" id="gas">
              <label class="form-check-label" for="gas">ガス</label>
            </div>
            <div class="form-check form-switch fs-5">
              <input class="form-check-input" type="checkbox" role="switch" name="tv" id="tv">
              <label class="form-check-label" for="tv">テレビ</label>
            </div>
            <div class="form-check form-switch fs-5">
              <input class="form-check-input" type="checkbox" role="switch" name="electric" id="electric">
              <label class="form-check-label" for="electric">電気</label>
            </div>
            <div class="form-check form-switch fs-5">
              <input class="form-check-input" type="checkbox" role="switch" name="lock" id="lock">
              <label class="form-check-label" for="lock">家の鍵</label>
            </div>

            <div class="d-grid gap-2 mt-3">
              <button type="submit" class="btn btn-primary btn-lg" style="border-radius:14px; padding:.9rem 1rem;">
                送信
              </button>
              <a class="btn btn-outline-secondary" href="home.php">戻る</a>
              <hr class="my-4">

<div class="mb-3">
  <label class="form-label fw-semibold">メモ（家族に伝えたいこと）</label>
  <textarea
    name="comment"
    class="form-control"
    rows="3"
    placeholder="例：エアコンだけつけっぱなしにしておきます / 鍵は写真の通り閉めました"
  ></textarea>
  <div class="form-text">※あとで「履歴」画面に表示できるようにします</div>
</div>

<div class="mb-3">
  <label class="form-label fw-semibold">写真（任意）</label>
  <input class="form-control" type="file" name="photo" accept="image/*">
  <div class="form-text">鍵・窓・ガス元栓などの証拠写真を添付できます（後で実装）</div>
</div>

            </div>
          </div>
        </form>

      </div>
    </div>

    <p class="text-center text-secondary mt-4 mb-0">© SafeHome</p>
  </div>
</main>
</body>
</html>
