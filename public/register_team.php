<?php
require_once './includes/auth.php';
require_once './includes/functions.php';

$error = '';
$success = false;
$team = null;

if (isset($_GET['hash'])) {
    $team = getTeamByHash($_GET['hash']);
}

if (!$team) {
    die('Ungültiger Link.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $player_id = isset($_POST['player_id']) ? trim($_POST['player_id']) : '';
    $player = getPlayer($player_id);

    if ($player) {
        // Bestehender Spieler
        if ($player['is_club_admin']) {
            $error = 'Dieser Spieler ist ein Vereinsadmin und kann sich hier nicht anmelden.';
        } else {
            // Prüfen ob der Spieler in der Mannschaft ist
            $playerTeams = getPlayerTeams($player['id']);
            $inTeam = false;
            foreach ($playerTeams as $pt) {
                if ($pt['id'] == $team['id']) {
                    $inTeam = true;
                    break;
                }
            }
            
            if (!$inTeam) {
                // Wenn nicht in der Mannschaft, hinzufügen
                addPlayerToTeam($team['id'], $player['id']);
            }

            if (loginByHash($player['hash'])) {
                header('Location: games.php?hash=' . $player['hash']);
                exit;
            } else {
                $error = 'Fehler beim Login.';
            }
        }
    } elseif (!empty($name)) {
        // Neuer Spieler
        $player = getPlayerByName($name);
        global $pdo;
        $playerHash = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO players (name, hash, is_club_admin) VALUES (?, ?, 0)");
        if ($stmt->execute([$name, $playerHash])) {
            $playerId = $pdo->lastInsertId();
            addPlayerToTeam($team['id'], $playerId);
            
            if (loginByHash($playerHash)) {
                header('Location: games.php?hash=' . $playerHash);
                exit;
            }
        } else {
            $error = 'Fehler beim Erstellen des Spielers.';
        }
    } else {
        $error = 'Bitte wählen Sie einen Spieler aus oder geben Sie einen Namen ein.';
    }
}

$teamPlayers = getTeamPlayers($team['id']);
// Filtere Vereinsadmins aus der Liste
$selectablePlayers = array_filter($teamPlayers, function($p) {
    return !$p['is_club_admin'];
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>TeamControl - Mannschaftsanmeldung</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="login-container">
        <h2>Anmeldung für <?php echo htmlspecialchars($team['name']); ?></h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($selectablePlayers)): ?>
            <p>Wählen Sie einen bestehenden Spieler aus der Mannschaft:</p>
            <div style="display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 20px;">
                <?php foreach ($selectablePlayers as $p): ?>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="player_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" style="width: 100%; padding: 10px; text-align: left; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; color: #333;">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            <p>Oder als neuer Spieler anmelden:</p>
        <?php else: ?>
            <p>Willkommen! Bitte geben Sie Ihren Namen ein, um sich für die Mannschaft anzumelden.</p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="name" placeholder="Dein Name" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="btn-add" style="width: 100%; padding: 10px; float: none;">Anmelden</button>
        </form>
    </div>
</body>
</html>
