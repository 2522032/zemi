<?php
// chat_widget.php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['group_id'])) {
    return;
}

$userId  = (int)$_SESSION['user_id'];
$groupId = (int)$_SESSION['group_id'];

// 所属チェック
$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE user_id = :u AND group_id = :g");
$stmt->execute([':u' => $userId, ':g' => $groupId]);
if (!$stmt->fetchColumn()) {
    echo "<p>このグループにアクセスできません。</p>";
    return;
}

// 初期表示：最新30件（古い→新しい順にする）
$stmt = $pdo->prepare("
  SELECT cm.id, cm.message, cm.created_at, u.username
  FROM chat_messages cm
  JOIN users u ON u.id = cm.user_id
  WHERE cm.group_id = :g
  ORDER BY cm.id DESC
  LIMIT 30
");
$stmt->execute([':g' => $groupId]);
$rows = array_reverse($stmt->fetchAll());
$lastId = 0;
if (!empty($rows)) $lastId = (int)end($rows)['id'];
?>

<hr>
<h2 id="chat">グループチャット</h2>

<div id="chatBox" style="border:1px solid #ccc; padding:10px; height:260px; overflow:auto; background:#fff;">
  <?php foreach ($rows as $r): ?>
    <div data-id="<?= (int)$r['id'] ?>">
      <b><?= htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8') ?></b>
      <span style="color:#888; font-size:12px; margin-left:6px;">
        <?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?>
      </span>
      <div><?= nl2br(htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8')) ?></div>
      <hr>
    </div>
  <?php endforeach; ?>
</div>

<form method="POST" action="chat_post.php" style="margin-top:10px;">
  <textarea name="message" rows="2" style="width:100%;" placeholder="メッセージを入力"></textarea>
  <button type="submit">送信</button>
</form>

<script>
  let sinceId = <?= (int)$lastId ?>;

  function escapeHtml(s){
    return String(s)
      .replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")
      .replaceAll('"',"&quot;").replaceAll("'","&#039;");
  }

  function appendMessages(msgs){
    const box = document.getElementById("chatBox");
    for (const m of msgs) {
      const wrap = document.createElement("div");
      wrap.setAttribute("data-id", m.id);
      wrap.innerHTML =
        "<b>" + escapeHtml(m.username) + "</b>" +
        ' <span style="color:#888; font-size:12px; margin-left:6px;">' + escapeHtml(m.created_at) + "</span>" +
        "<div>" + escapeHtml(m.message).replaceAll("\n","<br>") + "</div><hr>";
      box.appendChild(wrap);
      sinceId = Math.max(sinceId, parseInt(m.id, 10));
    }
    if (msgs.length > 0) box.scrollTop = box.scrollHeight;
  }

  async function poll(){
    try{
      const res = await fetch(`chat_fetch.php?since_id=${sinceId}`, {cache:"no-store"});
      if (!res.ok) return;
      const data = await res.json();
      if (data.messages && data.messages.length) appendMessages(data.messages);
    } catch (e) {}
  }

  const chatBox = document.getElementById("chatBox");
  chatBox.scrollTop = chatBox.scrollHeight;

  // 3秒更新（LINEっぽく）
  setInterval(poll, 3000);
</script>
