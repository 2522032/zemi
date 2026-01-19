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

    $window   = isset($_POST['window'])   ? 1 : 0;
    $gas      = isset($_POST['gas'])      ? 1 : 0;

    $aircon   = isset($_POST['electric']) ? 1 : 0;

    $tv       = isset($_POST['tv'])       ? 1 : 0;
    $door     = isset($_POST['lock'])     ? 1 : 0;

    $memo     = trim($_POST['comment'] ?? '');

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
      --primary1:#2fe7a6;
      --primary2:#2ec5ff;
      --ok1:#2fe7a6;
      --danger1:#ff4d6d;
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

    .wrap{
      max-width: 980px;
      margin: 0 auto;
      padding: 40px 16px 24px;
    }

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

    .section-title{
      margin-top: 18px;
      margin-bottom: 10px;
      font-weight: 900;
      color: rgba(255,255,255,.90);
      display:flex;
      align-items:center;
      gap:.55rem;
    }

    .checklist{
      display:flex;
      flex-direction:column;
      gap: 10px;
      margin-top: 14px;
    }
    .check-item{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      padding: 12px 14px;
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.07);
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
    }
    .check-item:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.28);
    }
    .check-left{
      display:flex;
      align-items:center;
      gap: 12px;
      min-width: 0;
    }
    .check-icon{
      width: 40px; height: 40px;
      border-radius: 16px;
      display:grid; place-items:center;
      background: rgba(46,197,255,.12);
      border: 1px solid rgba(46,197,255,.18);
      flex: 0 0 auto;
    }
    .check-text{
      min-width: 0;
    }
    .check-name{
      font-weight: 950;
      margin: 0;
      line-height: 1.2;
    }
    .check-desc{
      margin-top: 3px;
      color: rgba(255,255,255,.65);
      font-size: .92rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 420px;
    }

    .form-switch .form-check-input{
      width: 3.1rem;
      height: 1.6rem;
      cursor: pointer;
      border-color: rgba(255,255,255,.22);
      background-color: rgba(255,255,255,.18);
    }
    .form-switch .form-check-input:focus{
      box-shadow: 0 0 0 .25rem rgba(46,197,255,.18);
      border-color: rgba(46,197,255,.45);
    }
    .form-switch .form-check-input:checked{
      background-color: rgba(46,197,255,.85);
      border-color: rgba(46,197,255,.85);
    }

    .input-wrap{ position:relative; }
    .input-wrap .bi{
      position:absolute; left:14px; top:14px;
      color: rgba(255,255,255,.65);
      font-size: 1.05rem;
      pointer-events:none;
    }
    textarea.form-control{
      border-radius: 16px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
      color: var(--text);
      padding: 12px 14px;
    }
    textarea.form-control:focus{
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.55);
      box-shadow: 0 0 0 .25rem rgba(46,197,255,.18);
      color: var(--text);
    }

    input[type="file"].form-control{
      border-radius: 16px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
      color: rgba(255,255,255,.85);
    }
    input[type="file"].form-control:focus{
      background: rgba(255,255,255,.10);
      border-color: rgba(46,197,255,.55);
      box-shadow: 0 0 0 .25rem rgba(46,197,255,.18);
      color: rgba(255,255,255,.92);
    }

    .hint{
      color: rgba(255,255,255,.60);
      font-size: .9rem;
      margin-top: .4rem;
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
    }
    .btn-ghost:hover{ background: rgba(255,255,255,.10); color: rgba(255,255,255,.95); }

    .footer{
      color: rgba(255,255,255,.55);
      text-align:center;
      margin-top: 14px;
      font-size:.9rem;
    }
  </style>
</head>

<body>

  <nav class="topbar">
    <div class="container py-3 d-flex align-items-center justify-content-between">
      <div class="brand">
        <div class="brand-badge"><i class="bi bi-shield-check"></i></div>
        <div>
          SafeHome
          <small>安全チェック入力</small>
        </div>
      </div>

      <div class="d-flex gap-2">
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
        <h1>安全チェック</h1>
        <p>確認できた項目をオンにして送信してください。メモや写真の添付もできます。</p>
        <div class="chips">
          <span class="chip"><i class="bi bi-toggles me-1"></i> かんたん入力</span>
          <span class="chip"><i class="bi bi-chat-left-text me-1"></i> メモ共有</span>
          <span class="chip"><i class="bi bi-camera me-1"></i> 写真添付</span>
        </div>
      </section>

      <section class="card-glass">
        <div class="card-body">

          <h2 class="title">入力フォーム</h2>
          <p class="subtitle">スイッチをオンにして、最後に送信してください。</p>

          <form method="post" enctype="multipart/form-data" class="mt-4">

            <div class="section-title"><i class="bi bi-check2-square"></i> チェック項目</div>

            <div class="checklist">

              <div class="check-item">
                <div class="check-left">
                  <div class="check-icon"><i class="bi bi-window"></i></div>
                  <div class="check-text">
                    <p class="check-name">窓</p>
                    <div class="check-desc">施錠・閉め忘れの確認</div>
                  </div>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" role="switch" name="window" id="window">
                </div>
              </div>

              <div class="check-item">
                <div class="check-left">
                  <div class="check-icon"><i class="bi bi-fire"></i></div>
                  <div class="check-text">
                    <p class="check-name">ガス</p>
                    <div class="check-desc">元栓・コンロの確認</div>
                  </div>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" role="switch" name="gas" id="gas">
                </div>
              </div>

              <div class="check-item">
                <div class="check-left">
                  <div class="check-icon"><i class="bi bi-tv"></i></div>
                  <div class="check-text">
                    <p class="check-name">テレビ</p>
                    <div class="check-desc">消し忘れの確認</div>
                  </div>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" role="switch" name="tv" id="tv">
                </div>
              </div>

              <div class="check-item">
                <div class="check-left">
                  <div class="check-icon"><i class="bi bi-lightning-charge"></i></div>
                  <div class="check-text">
                    <p class="check-name">電気</p>
                    <div class="check-desc">不要な照明の消灯</div>
                  </div>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" role="switch" name="electric" id="electric">
                </div>
              </div>

              <div class="check-item">
                <div class="check-left">
                  <div class="check-icon"><i class="bi bi-key"></i></div>
                  <div class="check-text">
                    <p class="check-name">家の鍵</p>
                    <div class="check-desc">玄関の施錠確認</div>
                  </div>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" role="switch" name="lock" id="lock">
                </div>
              </div>

            </div>

            <hr class="my-4" style="border-color: rgba(255,255,255,.14);">

            <div class="section-title"><i class="bi bi-chat-left-text"></i> メモ</div>
            <textarea
              name="comment"
              class="form-control"
              rows="3"
              placeholder="例：エアコンだけつけっぱなしにしておきます / 鍵は写真の通り閉めました"
            ></textarea>
            <div class="hint">家族に伝えたいことがあれば入力してください。</div>

            <div class="mt-4 section-title"><i class="bi bi-camera"></i> 写真（任意）</div>
            <input class="form-control" type="file" name="photo" accept="image/*">
            <div class="hint">鍵・窓・ガス元栓などの証拠写真を添付できます（後で実装でもOK）。</div>

            <div class="d-grid gap-2 mt-4">
              <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-send me-1"></i> 送信
              </button>
              <a class="btn btn-ghost btn-lg" href="home.php">
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
