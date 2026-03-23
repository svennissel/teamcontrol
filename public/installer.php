<?php

// Wenn config.php bereits existiert, zur index.php weiterleiten
if (file_exists(__DIR__ . '/config.php')) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = false;
$database = $_POST['database'] ?? '';
$databaseUser = $_POST['database_user'] ?? '';
$databasePassword = $_POST['database_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingaben prüfen
    if (empty($database) || empty($databaseUser)) {
        $error = 'Datenbank und Datenbankbenutzer sind Pflichtfelder.';
    } else {
        // Datenbankverbindung testen
        try {
            $dsn = 'mysql:host=localhost;dbname=' . $database . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $databaseUser, $databasePassword, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo = null;

            // config-template.php lesen und Platzhalter ersetzen
            $template = file_get_contents(__DIR__ . '/config-template.php');
            $csrfKey = bin2hex(random_bytes(16)); // 32 Zeichen Hex-String

            $config = str_replace(
                ['{DATABASE}', '{DATABASE_USER}', '{DATABASE_PASSWORD}', '{CSRF_ENCRYPTION_KEY}'],
                [$database, $databaseUser, $databasePassword, $csrfKey],
                $template
            );

            file_put_contents(__DIR__ . '/config.php', $config);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Datenbankverbindung fehlgeschlagen: ' . htmlspecialchars($e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>TeamControl - Installation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="TeamControl" />
    <link rel="manifest" href="site.webmanifest" />
</head>
<body>
    <div class="login-container">
        <h2>Installation</h2>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-row">
                <label for="database">Datenbank</label>
                <input type="text" id="database" name="database" value="<?= htmlspecialchars($database) ?>" required>
            </div>

            <div class="form-row">
                <label for="database_user">Datenbankbenutzer</label>
                <input type="text" id="database_user" name="database_user" value="<?= htmlspecialchars($databaseUser) ?>" required>
            </div>

            <div class="form-row">
                <label for="database_password">Datenbank Passwort</label>
                <input type="password" id="database_password" name="database_password" value="<?= htmlspecialchars($databasePassword) ?>">
            </div>

            <button type="submit" class="btn-confirm-ok">Speichern</button>
        </form>
    </div>
</body>
</html>
