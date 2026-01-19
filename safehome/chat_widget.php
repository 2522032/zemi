<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['group_id'])) {
  echo '<div class="alert alert-danger">ログイン/グループ設定が必要です</div>';
  return;
}

$uid = (int)$_SESSION['user_id'];
$gid = (int)$_SESSION['group_id'];

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_send'])) {
  $msg = trim((string)($_POST['message'] ?? ''));

  if ($msg === '') {
    $error = "メッセージを入力してください";
  } elseif (mb_strlen($msg) > 500) {
    $error = "メッセージが長すぎます（500文字まで）";
  } else {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO chat_messages (group_id, user_id, message)
        VALUES (:gid, :uid, :msg)
      ");
      $stmt->execute([
        ':gid' => $gid,
        ':uid' => $uid,
        ':msg' => $msg
      ]);

      header('Location: /~shunya22/safehome/chat.php');
      exit;

    } catch (PDOException $e) {
      $error = "送信エラー: " . $e->getMessage();
    }
  }
}

$stmt = $pdo->prepare("
  SELECT m.user_id, m.message,
         to_char(m.created_at, 'MM/DD HH24:MI') AS created_at,
         u.username
  FROM chat_messages m
  JOIN users u ON u.id = m.user_id
  WHERE m.group_id = :gid
  ORDER BY m.created_at ASC
  LIMIT 200
");
$stmt->execute([':gid' => $gid]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .chat-shell{
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.06);
    overflow: hidden;
  }
  .chat-head{
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,.10);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 12px;
  }
  .chat-title{
    font-weight: 950;
    margin:0;
    font-size: 1.1rem;
  }
  .chat-sub{
    color: rgba(255,255,255,.65);
    font-size: .92rem;
    margin-top: 2px;
  }

  .chat-body{
    height: 520px;
    overflow:auto;
    padding: 14px 14px 18px;
  }

  .msg-row{
    display:flex;
    gap: 10px;
    margin: 10px 0;
    align-items:flex-end;
  }
  .msg-row.me{ justify-content:flex-end; }
  .msg-row.other{ justify-content:flex-start; }

  .avatar{
    width: 34px; height: 34px;
    border-radius: 12px;
    display:grid; place-items:center;
    background: rgba(46,197,255,.14);
    border: 1px solid rgba(46,197,255,.18);
    color: rgba(255,255,255,.9);
    font-weight: 900;
    flex: 0 0 auto;
  }

  .bubble{
    max-width: min(720px, 78%);
    padding: 10px 12px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(0,0,0,.18);
    color: rgba(255,255,255,.92);
    white-space: pre-wrap;
    word-break: break-word;
  }
  .me .bubble{
    background: rgba(46,197,255,.18);
    border-color: rgba(46,197,255,.25);
  }

  .meta{
    color: rgba(255,255,255,.55);
    font-size: .8rem;
    margin-bottom: 3px;
    display:flex;
    gap: 10px;
    align-items:center;
  }
  .me .meta{ justify-content:flex-end; }
  .other .meta{ justify-content:flex-start; }

  .chat-foot{
    padding: 12px;
    border-top: 1px solid rgba(255,255,255,.10);
    background: rgba(0,0,0,.10);
  }
  .chat-input{
    display:flex;
    gap: 10px;
    align-items:flex-end;
  }
  .chat-input textarea{
    resize: none;
    min-height: 44px;
    max-height: 160px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.08);
    color: rgba(255,255,255,.92);
    padding: 10px 12px;
  }
  .chat-input textarea:focus{
    outline: none;
    border-color: rgba(46,197,255,.55);
    box-shadow: 0 0 0 .25rem rgba(46,197,255,.18);
  }

  .chat-send{
    border:0;
    border-radius: 16px;
    padding: 10px 14px;
    font-weight: 900;
    background: linear-gradient(135deg, #6d5efc, #2ec5ff);
    color: #fff;
    white-space: nowrap;
  }
  .chat-send:hover{ filter: brightness(1.05); }

  .chat-error{
    margin: 12px 0 0;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid rgba(255,77,109,.35);
    background: rgba(255,77,109,.12);
    color: rgba(255,255,255,.92);
  }
</style>

<div class="chat-shell">
  <div class="chat-head">
    <div>
      <div class="chat-title">グループチャット</div>
      <div class="chat-sub"></div>
    </div>
    <div style="color: rgba(255,255,255,.65); font-size:.9rem;">
      更新：<span id="chatTick">—</span>
    </div>
  </div>

  <div class="chat-body" id="chatBody">
    <?php if (empty($messages)): ?>
      <div style="color: rgba(255,255,255,.65); padding: 10px 6px;">
        まだメッセージがありません。最初の一言を送ってみよう。
      </div>
    <?php else: ?>
      <?php foreach ($messages as $m): ?>
        <?php
          $isMe = ((int)$m['user_id'] === $uid);
          $rowClass = $isMe ? 'me' : 'other';
          $initial = mb_substr((string)$m['username'], 0, 1);
        ?>
        <div class="msg-row <?php echo $rowClass; ?>">
          <?php if (!$isMe): ?>
            <div class="avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <div>
            <div class="meta">
              <?php if (!$isMe): ?>
                <span><?php echo htmlspecialchars($m['username'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
              <span><?php echo htmlspecialchars($m['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="bubble"><?php echo htmlspecialchars($m['message'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>

          <?php if ($isMe): ?>
            <div class="avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="chat-foot">
    <form method="post" class="chat-input">
      <textarea
        name="message"
        placeholder="メッセージを入力（Enterで送信 / Shift+Enterで改行）"
        required
        id="chatText"
      ></textarea>

      <button type="submit" name="chat_send" value="1" class="chat-send">
        送信
      </button>
    </form>

    <?php if ($error): ?>
      <div class="chat-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
  </div>
</div>

<script>
  const body = document.getElementById('chatBody');
  body.scrollTop = body.scrollHeight;

  const ta = document.getElementById('chatText');

  function tick(){
    const d = new Date();
    const s =
      String(d.getHours()).padStart(2,'0') + ':' +
      String(d.getMinutes()).padStart(2,'0') + ':' +
      String(d.getSeconds()).padStart(2,'0');
    document.getElementById('chatTick').textContent = s;
  }
  tick();

  // 5秒ごと更新（入力中は更新しない）
  setInterval(() => {
    tick();
    if (document.activeElement !== ta) {
      location.reload();
    }
  }, 5000);
</script>

