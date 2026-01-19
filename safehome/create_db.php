<?php
require_once __DIR__ . '/connect_db.php';

try {
    $pdo->beginTransaction();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY, 
            username VARCHAR(50) NOT NULL UNIQUE,
            password TEXT NOT NULL
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS groups (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            invite_code VARCHAR(16) NOT NULL UNIQUE,
            owner_user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS group_members (
            group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
            user_id  INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role VARCHAR(20) NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP NOT NULL DEFAULT NOW(),
            PRIMARY KEY (group_id, user_id)
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS home_state (
            id SERIAL PRIMARY KEY,
            group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            checked_at TIMESTAMP NOT NULL DEFAULT NOW(),
            window_closed BOOLEAN NOT NULL DEFAULT FALSE,
            gas_off BOOLEAN NOT NULL DEFAULT FALSE,
            aircon_off BOOLEAN NOT NULL DEFAULT FALSE,
            tv_off BOOLEAN NOT NULL DEFAULT FALSE,
            door_locked BOOLEAN NOT NULL DEFAULT FALSE,
            memo TEXT,
            photo_path TEXT
        );
    ");
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS chat_messages (
      id SERIAL PRIMARY KEY,
      group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
      user_id  INTEGER NOT NULL REFERENCES users(id)  ON DELETE CASCADE,
      message  TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_chat_group_time
        ON chat_messages(group_id, created_at DESC);
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS group_events (
        id SERIAL PRIMARY KEY,
        group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
        created_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        start_at TIMESTAMP NOT NULL,
        end_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_group_events_group_start
        ON group_events(group_id, start_at);
    ");

    $pdo->exec("
  CREATE TABLE IF NOT EXISTS password_reset_codes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    code_hash TEXT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP
  );
");

$pdo->exec("
  CREATE INDEX IF NOT EXISTS idx_reset_user_expires
  ON password_reset_codes(user_id, expires_at DESC);
");


    $pdo->commit();
    echo "OK: テーブル作成完了";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
