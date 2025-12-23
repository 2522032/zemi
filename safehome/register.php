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
        $error = '未入力の項目があります';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
            $stmt->execute([':u' => $username]);

            if ($stmt->fetch()) {
                $error = 'そのユーザー名は既に使われています';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, password) VALUES (:u, :p) RETURNING id"
                );
                $stmt->execute([':u' => $username, ':p' => $hash]);

                $user_id = $stmt->fetchColumn();

               session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['user_id'] = (int)$user_id;
                $_SESSION['username'] = $username;
                unset($_SESSION['group_id']);   

                header('Location: home.php');
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
  <title>新規登録</title>
</head>
<body>

<h1>新規登録</h1>

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

  <button type="submit">登録</button>
</form>

<p><a href="login.php">ログインへ</a></p>

</body>
</html>
