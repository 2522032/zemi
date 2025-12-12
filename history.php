<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>履歴一覧</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;     
            margin: 0;
            background: #f0f4ff;
            font-family: Arial, sans-serif;
        }
        h1 {
            margin-top: 24px;
        }
        table {
            margin-top: 12px;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            font-size: 14px;
            text-align: center;
        }
        th {
            background: #f5f5f5;
        }
        .nav {
            margin-top: 16px;
        }
        .nav a {
            margin: 0 6px;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 6px;
            background: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
<h1>履歴一覧（<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?> さん）</h1>

<?php if (empty($rows)): ?>
    <p>まだ履歴がありません。</p>
<?php else: ?>
<table>
    <tr>
        <th>日時</th>
        <th>窓</th>
        <th>ガス</th>
        <th>テレビ</th>
        <th>電気</th>
        <th>家の鍵</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo mark($r['window']); ?></td>
            <td><?php echo mark($r['gas']); ?></td>
            <td><?php echo mark($r['tv']); ?></td>
            <td><?php echo mark($r['light']); ?></td>
            <td><?php echo mark($r['door']); ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<div class="nav">
    <a href="input.php">入力画面へ</a>
    <a href="home.php">メニューへ</a>
</div>
</body>
</html>