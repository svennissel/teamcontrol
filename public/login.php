<?php
require_once './includes/auth.php';

$error = '';
$hash = '';
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
        localStorage.setItem('playerHash', '<?=$hash?>');
        window.location.href = 'games.php';
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
            window.location.href = 'login.php?hash=' + storedHash;
        }
    </script>
</body>
</html>
