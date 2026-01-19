<?php
session_start();
require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$userId = (int)$_SESSION['user_id'];
$gid = (int)($_GET['gid'] ?? 0);

if ($gid <= 0) {
  die('不正なリクエスト');
}

$stmt = $pdo->prepare("
  SELECT role
  FROM group_members
  WHERE user_id = :u AND group_id = :g
");
$stmt->execute([
  ':u' => $userId,
  ':g' => $gid
]);

$role = $stmt->fetchColumn();

if ($role !== 'owner') {
  die('削除権限がありません');
}

try {

  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
    DELETE FROM groups
    WHERE id = :g
  ");
  $stmt->execute([':g' => $gid]);

  if (isset($_SESSION['group_id']) && (int)$_SESSION['group_id'] === $gid) {
    unset($_SESSION['group_id']);
  }

  $pdo->commit();

  header('Location: group_select.php');
  exit;

} catch (PDOException $e) {

  $pdo->rollBack();
  echo "削除エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
