<?php
require_once __DIR__ . '/connect_db.php';

$st = $pdo->query("
  SELECT column_name, data_type
  FROM information_schema.columns
  WHERE table_name = 'users'
  ORDER BY ordinal_position
");
header('Content-Type: text/plain; charset=utf-8');
foreach ($st as $row) {
  echo $row['column_name'] . " : " . $row['data_type'] . "\n";
}
