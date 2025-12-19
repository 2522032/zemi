<?php

$host = 'localhost';
$port = '5432';
$dbname = 'yf002023';
$user = 'yf002023';
$pass = 'eGnH4e4X';

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit("DB接続エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
