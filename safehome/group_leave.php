<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = (int)$_SESSION['user_id'];
$gid = (int)($_GET['gid'] ?? 0);

if ($gid <= 0) {
  header('Location: group_select.php');
  exit;
}

$stmt = $pdo->prepare("SELECT role FROM group_members WHERE user_id=:u AND group_id=:g");
$stmt->execute([':u'=>$userId, ':g'=>$gid]);
$role = $stmt->fetchColumn();

if (!$role) {
  header('Location: group_select.php');
  exit;
}

if ($role === 'owner') {
  echo "owner は退会できません（グループ削除を使ってください）";
  exit;
}

try {
  $stmt = $pdo->prepare("DELETE FROM group_members WHERE user_id=:u AND group_id=:g");
  $stmt->execute([':u'=>$userId, ':g'=>$gid]);

  if (isset($_SESSION['group_id']) && (int)$_SESSION['group_id'] === $gid) {
    unset($_SESSION['group_id']);
  }

  header('Location: group_select.php');
  exit;

} catch (PDOException $e) {
  echo "DBエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
