<?php
session_start();
require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

if (!isset($_SESSION['group_id'])) {
  header('Location: calendar.php');
  exit;
}

$userId  = (int)$_SESSION['user_id'];
$groupId = (int)$_SESSION['group_id'];

$eventId = (int)($_GET['id'] ?? 0);

if ($eventId <= 0) {
  header('Location: calendar.php');
  exit;
}

$stmt = $pdo->prepare("
  SELECT start_at
  FROM group_events
  WHERE id = :id AND group_id = :gid
");
$stmt->execute([
  ':id' => $eventId,
  ':gid' => $groupId
]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
  header('Location: calendar.php');
  exit;
}

$stmt = $pdo->prepare("
  DELETE FROM group_events
  WHERE id = :id
");
$stmt->execute([':id' => $eventId]);

$dt = new DateTime($event['start_at']);
$y = $dt->format('Y');
$m = $dt->format('n');

header("Location: calendar.php?y={$y}&m={$m}");
exit;
