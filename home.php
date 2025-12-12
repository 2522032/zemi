<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>SafeHome ホーム</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center;     
            height: 100vh;           
            margin: 0;
            background: #f0f4ff;
            font-family: Arial, sans-serif;
        }
        .container {
            background: white;
            padding: 24px 32px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            width: 360px;
            text-align: center;
        }
        h1 {
            margin-top: 0;
        }
        .links a {
            display: block;
            margin: 8px 0;
            text-decoration: none;
            padding: 8px;
            border-radius: 6px;
            background: #4CAF50;
            color: white;
        }
        .links a:hover {
            opacity: 0.9;
        }
        .logout {
            margin-top: 14px;
            display: inline-block;
            text-decoration: none;
            color: #555;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>SafeHome</h1>
    <p><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?> さん、こんにちは。</p>
    <div class="links">
        <a href="input.php">チェック入力画面へ</a>
        <a href="history.php">履歴を見る</a>
    </div>
    <a class="logout" href="logout.php">ログアウト</a>
</div>
</body>
</html>