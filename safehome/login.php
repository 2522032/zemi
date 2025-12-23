<?php
session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'ユーザー名とパスワードを入力してください';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, username, password FROM users WHERE username = :u"
            );
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'ユーザー名またはパスワードが違います';
            } else {
                session_regenerate_id(true);
                  $_SESSION = [];
                  $_SESSION['user_id'] = (int)$user['id'];
                  $_SESSION['username'] = $user['username'];
                  unset($_SESSION['group_id']);   // ★リセット
  
                  header('Location: group_select.php');
                  exit;
            }
        } catch (PDOException $e) {
            $error = 'DBエラー: ' . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>ログイン</title>
</head>
<body>

<h1>ログイン</h1>

<?php if ($error): ?>
  <p style="color:red;">
    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
  </p>
<?php endif; ?>

<form method="post">
  <p>
    <label>ユーザー名</label><br>
    <input type="text" name="username" required>
  </p>

  <p>
    <label>パスワード</label><br>
    <input type="password" name="password" required>
  </p>

  <button type="submit">ログイン</button>
</form>

<p><a href="register.php">新規登録はこちら</a></p>

</body>
</html>