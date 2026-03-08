<?php
session_start([
    'cookie_lifetime' => 31536000,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure' => true
]);
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['hash']);
}

function getLoginHash() {
    return $_SESSION['hash'];
}

function isClubAdmin(): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM players WHERE hash = ? AND is_club_admin = true");
    $stmt->execute([getLoginHash()]);
    return (bool)$stmt->fetch();
}

function isTeamAdmin($teamId, $playerId): bool {
    global $pdo;
    if (isClubAdmin()) return true;

    $stmt = $pdo->prepare("SELECT 1 FROM team_players WHERE team_id = ? AND player_id = ? AND isTeamAdmin = TRUE");
    $stmt->execute([$teamId, $playerId]);
    return (bool)$stmt->fetch();
}

function isAnyTeamAdmin($player_id) {
    global $pdo;
    if (isClubAdmin()) return true;
    if (!isLoggedIn()) return false;


    $stmt = $pdo->prepare("SELECT 1 FROM team_players WHERE player_id = ? AND isTeamAdmin = TRUE");
    $stmt->execute([$player_id]);
    return (bool)$stmt->fetch();
}

function loginByHash($hash) : bool {
    global $pdo;
    if (empty($hash)) return false;
    
    $stmt = $pdo->prepare("SELECT * FROM players WHERE hash = ?");
    $stmt->execute([$hash]);
    $player = $stmt->fetch();

    if ($player) {
        $_SESSION['hash'] = $player['hash'];
        setcookie('hash', $player['hash'], [
            "expires" => time() + 31536000,
            "path" => '/',
            "domain" => $_SERVER['SERVER_NAME'],
            "secure" => true,
            "httponly" => true,
            "samesite" => "Strict"
            ]);
        return true;
    }
    return false;
}

function logout() : void {
    session_destroy();
    unset($_COOKIE['hash']);
    setcookie('hash', '', 1, '/');
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    return isset($_SESSION['csrf_token']) && $token !== null && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}
?>
