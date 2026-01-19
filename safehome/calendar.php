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
if (!$stmt->fetchColumn()) {
  unset($_SESSION['group_id']);
  header('Location: group_select.php');
  exit;
}

$year  = (int)($_GET['y'] ?? date('Y'));
$month = (int)($_GET['m'] ?? date('n'));
if ($month < 1) { $month = 1; }
if ($month > 12) { $month = 12; }

$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$startOfMonth = $firstDay->format('Y-m-01 00:00:00');
$endOfMonth   = (clone $firstDay)->modify('first day of next month')->format('Y-m-01 00:00:00');

$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = :gid");
$stmt->execute([':gid' => $groupId]);
$groupName = $stmt->fetchColumn() ?: '(不明なグループ)';

$stmt = $pdo->prepare("
  SELECT e.id, e.title, e.start_at, e.end_at, e.created_by,
         u.username AS created_by_name
  FROM group_events e
  JOIN users u ON u.id = e.created_by
  WHERE e.group_id = :g
    AND e.start_at >= :start
    AND e.start_at <  :end
  ORDER BY e.start_at ASC
");
$stmt->execute([':g' => $groupId, ':start' => $startOfMonth, ':end' => $endOfMonth]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventsByDay = [];
foreach ($events as $e) {
  $dayKey = (new DateTime($e['start_at']))->format('Y-m-d');
  $eventsByDay[$dayKey][] = $e;
}

$start = clone $firstDay;
$w = (int)$start->format('w');
$start->modify("-{$w} days");

$days = [];
for ($i=0; $i<42; $i++) {
  $d = (clone $start)->modify("+{$i} days");
  $days[] = $d;
}

$prev = (clone $firstDay)->modify('-1 month');
$next = (clone $firstDay)->modify('+1 month');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>カレンダー | SafeHome</title>
  <style>
  :root{
    --bg0:#07101f;
    --bg1:#0b1730;
    --panel: rgba(255,255,255,.08);
    --panel2: rgba(255,255,255,.12);
    --stroke: rgba(255,255,255,.16);
    --stroke2: rgba(255,255,255,.22);
    --text: rgba(255,255,255,.92);
    --muted: rgba(255,255,255,.70);
    --muted2: rgba(255,255,255,.50);
    --accent1:#2ec5ff;
    --accent2:#6d5efc;
    --today: rgba(46,197,255,.20);
    --eventBg: rgba(46,197,255,.18);
    --eventBd: rgba(46,197,255,.28);
  }

  body{
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    background:
      radial-gradient(900px 600px at 10% 10%, rgba(109,94,252,.40), transparent 60%),
      radial-gradient(900px 600px at 90% 20%, rgba(46,197,255,.30), transparent 55%),
      linear-gradient(180deg, var(--bg0), var(--bg1));
    color: var(--text);
    margin:0;
  }
  a{ color: rgba(255,255,255,.92); text-decoration:none; }
  a:hover{ opacity:.95; }

  .wrap{ max-width: 1100px; margin: 0 auto; padding: 28px 16px 40px; }

  .title{
    font-size: 26px;
    font-weight: 950;
    letter-spacing: .01em;
    margin: 0;
  }
  .sub{ margin-top: 6px; color: var(--muted); }

  .topbar{
    display:flex; flex-wrap:wrap; align-items:center; gap: 10px;
    padding: 14px;
    border-radius: 16px;
    border: 1px solid var(--stroke);
    background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.06));
    box-shadow: 0 16px 40px rgba(0,0,0,.28);
    margin-top: 14px;
  }
  .btn{
    display:inline-flex;
    align-items:center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid var(--stroke2);
    background: rgba(255,255,255,.08);
    color: rgba(255,255,255,.92);
    font-weight: 850;
  }
  .btn:hover{ background: rgba(255,255,255,.12); }
  .btn-primary{
    background: linear-gradient(135deg, var(--accent2), var(--accent1));
    border: 0;
  }
  .monthLabel{
    font-weight: 950;
    font-size: 18px;
    padding: 0 6px;
  }

  .cal{
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 14px;
    overflow:hidden;
    border-radius: 18px;
    border: 1px solid var(--stroke);
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
    background: rgba(255,255,255,.04);
  }
  .cal th{
    background: rgba(255,255,255,.10);
    color: rgba(255,255,255,.92);
    text-align:center;
    padding: 12px 0;
    font-weight: 950;
    border-bottom: 1px solid var(--stroke);
  }
  .cal td{
    vertical-align: top;
    height: 130px;
    padding: 10px;
    border-right: 1px solid rgba(255,255,255,.10);
    border-bottom: 1px solid rgba(255,255,255,.10);
    background: rgba(0,0,0,.10);
  }
  .cal tr td:last-child{ border-right: none; }
  .cal tbody tr:last-child td{ border-bottom: none; }

  .date{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap: 8px;
    font-weight: 950;
    margin-bottom: 6px;
  }
  .daynum{ font-size: 14px; color: rgba(255,255,255,.92); }
  .dim{ color: rgba(255,255,255,.35); }

  .addlink{
    width: 28px; height: 28px;
    border-radius: 999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border: 1px solid rgba(46,197,255,.28);
    background: rgba(46,197,255,.12);
    color: rgba(255,255,255,.95);
    font-weight: 950;
    line-height: 1;
  }
  .addlink:hover{ background: rgba(46,197,255,.18); }

  .event{
    font-size: 12px;
    margin-top: 6px;
    padding: 7px 8px;
    border-radius: 12px;
    border: 1px solid var(--eventBd);
    background: var(--eventBg);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .event small{
    color: rgba(255,255,255,.78);
    font-weight: 800;
    margin-left: 4px;
  }

  td.is-today{
    outline: 2px solid rgba(46,197,255,.40);
    outline-offset: -2px;
    background: var(--today);
  }

  th.sun, td.sun .daynum{ color: rgba(255,180,180,.95); }
  th.sat, td.sat .daynum{ color: rgba(180,210,255,.95); }

  .note{ margin-top: 10px; color: var(--muted2); font-size: 12px; }
</style>

</head>
<body>
  <div class="wrap">
    <div class="title">カレンダー（グループ予定）</div>
    <div class="muted">グループ：<?= h($groupName) ?></div>

    <div class="topbar" style="margin-top:12px;">
      <a class="btn" href="home.php">← Home</a>

      <a class="btn" href="calendar.php?y=<?= h($prev->format('Y')) ?>&m=<?= h($prev->format('n')) ?>">◀ 前月</a>
      <b><?= h($firstDay->format('Y年n月')) ?></b>
      <a class="btn" href="calendar.php?y=<?= h($next->format('Y')) ?>&m=<?= h($next->format('n')) ?>">次月 ▶</a>

      <a class="btn" href="event_add.php">＋予定を追加</a>
    </div>

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
              $inMonth = ((int)$d->format('n') === (int)$month);
              $dayKey = $d->format('Y-m-d');
            ?>
              <td>
                <div class="date">
                  <span class="<?= $inMonth ? '' : 'dim' ?>"><?= h($d->format('j')) ?></span>
                  <a class="addlink" href="event_add.php?date=<?= h($dayKey) ?>">＋</a>
                </div>

                <?php if (!empty($eventsByDay[$dayKey])): ?>
                  <?php foreach ($eventsByDay[$dayKey] as $e): ?>
                    <?php
                      $t = new DateTime($e['start_at']);
                      $time = $t->format('H:i');
                      $by = $e['created_by_name'] ?? '';
                    ?>
                    <div class="event">
                     <?= h($time . ' ' . $e['title']) ?>
                     <a href="event_delete.php?id=<?= h($e['id']) ?>"
                        onclick="return confirm('この予定を削除しますか？')"
                        style="color:#ff6b6b; margin-left:6px; text-decoration:none;">
                        ✕
                    </a>
                    </div>

                  <?php endforeach; ?>
                <?php endif; ?>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>

    <p class="muted" style="margin-top:10px; font-size:12px;">
      ※ 今月に「開始」する予定を表示しています（最小実装）。
    </p>
  </div>
</body>
</html>
