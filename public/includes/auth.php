<?php
function isSecureServer() : bool {
    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        return true;
    }
    return false;
}

require_once __DIR__ . '/config.php';

session_start([
    'cookie_lifetime' => COOKIE_LIFETIME,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure' => isSecureServer() //Safari block session on localhost if it is secure but running on http
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

    $twoMinutes = 120;
    if(isset($_SESSION['isClubAdmin']) && isset($_SESSION['isClubAdmin_update']) && $_SESSION['isClubAdmin_update'] + $twoMinutes > time() ) {
        return $_SESSION['isClubAdmin'];
    }
    $stmt = $pdo->prepare("SELECT 1 FROM players WHERE hash = ? AND is_club_admin = true");
    $stmt->execute([getLoginHash()]);

    $_SESSION['isClubAdmin'] = (bool)$stmt->fetch();
    $_SESSION['isClubAdmin_update'] = time();

    return $_SESSION['isClubAdmin'];
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
            "expires" => time() + COOKIE_LIFETIME,
            "path" => '/',
            "domain" => $_SERVER['SERVER_NAME'],
            "secure" => isSecureServer(),
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
    setcookie('hash_lastupdate', '', 1, '/');
    setcookie('last_tab', '', 1, '/');
}

function generateCsrfToken(): string {
    $key = 'V#&xgkvL5/]>BVbhbUg,qLLYVfvXs7zu';
    $random = bin2hex(random_bytes(16));
    $timestamp = time();
    $payload = $random . '|' . $timestamp;

    $cipher = 'aes-256-cbc';
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($payload, $cipher, $key, 0, $iv);

    $token = base64_encode($iv . '::' . $encrypted);
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function validateCsrfToken(?string $token): bool {
    if ($token === null) return false;

    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    $key = 'V#&xgkvL5/]>BVbhbUg,qLLYVfvXs7zu';
    $cipher = 'aes-256-cbc';

    $decoded = base64_decode($token, true);
    if ($decoded === false) return false;

    $parts = explode('::', $decoded, 2);
    if (count($parts) !== 2) return false;

    [$iv, $encrypted] = $parts;
    $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    if ($decrypted === false) return false;

    $segments = explode('|', $decrypted, 2);
    if (count($segments) !== 2) return false;

    [$random, $timestamp] = $segments;

    if (strlen($random) !== 32) return false;

    $oneYear = 365 * 24 * 60 * 60;
    if (!is_numeric($timestamp) || (time() - (int)$timestamp) > $oneYear) return false;

    return true;
}

function csrfField(): string {
    $fields = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
    if (isLoggedIn()) {
        $fields .= '<input type="hidden" name="hash" value="' . htmlspecialchars(getLoginHash()) . '">';
    }
    return $fields;
}
?>
