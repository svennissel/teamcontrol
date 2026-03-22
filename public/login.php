<?php
require_once './includes/auth.php';

$error = '';
$hash = '';
$redirect = isset($_GET['redirect']) ? basename($_GET['redirect']) : '';
$allowedRedirects = ['games.php', 'trainings.php', 'players.php', 'teams.php'];
if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = '';
}
if (isset($_GET['hash']) || isset($_COOKIE['hash']) || isset($_SESSION['hash'])) {
    $hash = $_GET['hash'] ?? $_COOKIE['hash'] ?? $_SESSION['hash'];
    if (!loginByHash($hash)) {
        $error = 'Ungültiger Login-Link.';
        $hash = '';
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>TeamControl - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="TeamControl" />
    <link rel="manifest" href="site.webmanifest" />
</head>
<body>
    <?php if($hash): ?>
    <script>
        // If the hash is manipulated, it will be converted with htmlspecialchars().
        // Normally it is not necessary, because it is base64 encoded.
        localStorage.setItem('playerHash', '<?=htmlspecialchars($hash)?>');
        //Redirect to index.php. There will be redirect to the last visited page.
        window.location.href = '<?= $redirect ? htmlspecialchars($redirect) : 'index.php' ?>';
    </script>
    <?php endif; ?>

    <div class="login-container">
        <h2>Anmelden</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php else: ?>
            <p>Bitte nutzen Sie Ihren persönlichen Login-Link.</p>
        <?php endif; ?>
    </div>

    <script>
        // Prüfen ob ein Hash im localStorage ist
        const storedHash = localStorage.getItem('playerHash');
        const urlParams = new URLSearchParams(window.location.search);
        const urlHash = urlParams.get('hash');

        if (storedHash && !urlHash && !document.querySelector('.error')) {
            // Nur weiterleiten, wenn kein Fehler angezeigt wird und kein Hash in der URL ist
            window.location.href = 'login.php?hash=' + storedHash + '<?= $redirect ? '&redirect=' . urlencode($redirect) : '' ?>';
        }
    </script>
</body>
</html>
