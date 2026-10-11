<?php
declare(strict_types=1);

// Password and token helpers. Keep these in one place so login and reset stay in sync.

function hash_password(string $password): string
{
    return hash("sha256", $password);
}

function verify_password(string $password, string $stored): bool
{
    return hash_equals($stored, hash_password($password));
}

function issue_reset_token(string $email): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);
    $pdo = db();
    $pdo->prepare("DELETE FROM reset_tokens WHERE email = ?")->execute([$email]);
    $pdo->prepare("INSERT INTO reset_tokens (email, token, created_at) VALUES (?, ?, datetime('now'))")
        ->execute([$email, $tokenHash]);
    return $token;
}

function consume_reset_token(string $email, string $token): bool
{
    $stmt = db()->prepare("
        DELETE FROM reset_tokens
        WHERE email = ?
          AND token = ?
          AND created_at >= datetime('now', '-1 hour')
    ");
    $stmt->execute([$email, hash("sha256", $token)]);
    return $stmt->rowCount() === 1;
}
