<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['group_id'])) { header('Location: group_select.php'); exit; }

$userId  = (int)$_SESSION['user_id'];
$groupId = (int)$_SESSION['group_id'];

// 所属チェック
$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE user_id = :u AND group_id = :g");
$stmt->execute([':u' => $userId, ':g' => $groupId]);
if (!$stmt->fetchColumn()) { unset($_SESSION['group_id']); header('Location: group_select.php'); exit; }

// 表示する年月（GETで切り替え）
$year  = (int)($_GET['y'] ?? date('Y'));
$month = (int)($_GET['m'] ?? date('n'));
if ($month < 1) { $month = 1; }
if ($month > 12) { $month = 12; }

$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$startOfMonth = $firstDay->format('Y-m-01 00:00:00');
$endOfMonth   = (clone $firstDay)->modify('first day of next month')->format('Y-m-01 00:00:00');

// グループ名
$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = :gid");
$stmt->execute([':gid' => $groupId]);
$groupName = $stmt->fetchColumn() ?: '(不明なグループ)';

// 今月の予定を取得（start_atが今月に入るもの）
$stmt = $pdo->prepare("
  SELECT id, title, start_at, end_at
  FROM group_events
  WHERE group_id = :g
    AND start_at >= :start
    AND start_at <  :end
  ORDER BY start_at ASC
");
$stmt->execute([':g' => $groupId, ':start' => $startOfMonth, ':end' => $endOfMonth]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 日付（Y-m-d）→予定配列 にまとめる
$eventsByDay = [];
foreach ($events as $e) {
    $dayKey = (new DateTime($e['start_at']))->format('Y-m-d');
    $eventsByDay[$dayKey][] = $e;
}

// カレンダー開始（前月の残りを埋める）
$start = clone $firstDay;
$w = (int)$start->format('w'); // 0=日
$start->modify("-{$w} days");

// カレンダー終了（6週分=42マスで固定すると楽）
$days = [];
for ($i=0; $i<42; $i++) {
    $d = (clone $start)->modify("+{$i} days");
    $days[] = $d;
}

// 前月・次月リンク
$prev = (clone $firstDay)->modify('-1 month');
$next = (clone $firstDay)->modify('+1 month');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>カレンダー</title>
  <style>
    body { font-family: sans-serif; }
    .topbar { display:flex; align-items:center; gap:12px; }
    .cal { width: 100%; max-width: 960px; border-collapse: collapse; }
    .cal th, .cal td { border: 1px solid #ddd; vertical-align: top; height: 110px; padding: 6px; }
    .cal th { background: #f5f5f5; text-align: center; }
    .date { font-weight: bold; font-size: 14px; display:flex; justify-content: space-between; }
    .muted { color: #aaa; }
    .event { font-size: 12px; margin-top: 4px; padding: 2px 4px; background:#eef; border-radius: 4px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
    .addlink { font-size: 12px; text-decoration: none; }
    .wrap { max-width: 980px; }
  </style>
</head>
<body>
<div class="wrap">
  <h1>カレンダー（グループ予定）</h1>
  <p>グループ：<?= h($groupName) ?></p>

  <div class="topbar">
    <a href="home.php">← Homeに戻る</a>
    <a href="calendar.php?y=<?= h($prev->format('Y')) ?>&m=<?= h($prev->format('n')) ?>">◀ 前月</a>
    <b><?= h($firstDay->format('Y年n月')) ?></b>
    <a href="calendar.php?y=<?= h($next->format('Y')) ?>&m=<?= h($next->format('n')) ?>">次月 ▶</a>
    <a href="event_add.php">＋予定を追加</a>
  </div>

  <br>

  <table class="cal">
    <thead>
      <tr>
        <th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($row=0; $row<6; $row++): ?>
        <tr>
          <?php for ($col=0; $col<7; $col++): 
            $idx = $row*7 + $col;
            $d = $days[$idx];
            $inMonth = ($d->format('n') == $month);
            $dayKey = $d->format('Y-m-d');
          ?>
            <td>
              <div class="date">
                <span class="<?= $inMonth ? '' : 'muted' ?>"><?= h($d->format('j')) ?></span>
                <a class="addlink" href="event_add.php?date=<?= h($dayKey) ?>">＋</a>
              </div>

              <?php if (!empty($eventsByDay[$dayKey])): ?>
                <?php foreach ($eventsByDay[$dayKey] as $e): ?>
                  <?php
                    $t = new DateTime($e['start_at']);
                    $time = $t->format('H:i');
                  ?>
                  <div class="event"><?= h($time . ' ' . $e['title']) ?></div>
                <?php endforeach; ?>
              <?php endif; ?>
            </td>
          <?php endfor; ?>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <p style="margin-top:10px; color:#666; font-size:12px;">
    ※ 今月に「開始」する予定を表示しています（まずは最小実装）。
  </p>
</div>
</body>
</html>
