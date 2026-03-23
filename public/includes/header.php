<?php
if (!file_exists(__DIR__ . '/../config.php')) {
    header('Location: installer.php');
    exit;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

function printHeader($player, $playerTeams, $current_page) {
    $isClubAdmin = $player['is_club_admin'];
    setcookie('last_tab', $current_page, [
            'expires' => time() + COOKIE_LIFETIME,
            'path' => '/',
            "domain" => $_SERVER['SERVER_NAME'],
            "secure" => isSecureServer(),
            "httponly" => true,
            "samesite" => "Strict"
    ]);
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeamControl - <?php echo htmlspecialchars($title ?? 'Termine'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="TeamControl" />
    <link rel="manifest" href="site.webmanifest" />
</head>
<body>
    <header>
        <nav>
            <div class="header-left">
                <div class="header-player-row">
                    <span class="player-name"><?php echo htmlspecialchars($player['name']); ?></span>
                    <button class="copy-link-btn" onclick="copyLoginLink('<?php echo htmlspecialchars($player['hash']); ?>')" title="Login-Link kopieren"><i class="fa-regular fa-clipboard"></i></button>
                </div>
                <div class="team-info-header">
                    <?php if (!empty($playerTeams[0]['logo'])): ?>
                        <img src="uploads/logos/<?php echo htmlspecialchars($playerTeams[0]['logo']); ?>_30.webp" alt="Logo" class="header-team-logo">
                    <?php endif; ?>
                    <?php foreach ($playerTeams as $team): ?>
                        <span class="team-name"><?php echo htmlspecialchars($team['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="header-right">
            </div>
        </nav>
    </header>

    <main>
        <div class="tab-dropdown">
            <select class="tab-select" onchange="window.location.href=this.value">
                <option value="games.php" <?php echo $current_page === 'games' ? 'selected' : ''; ?>>Spiele</option>
                <option value="trainings.php" <?php echo $current_page === 'trainings' ? 'selected' : ''; ?>>Training</option>
                <?php if ($isClubAdmin || isAnyTeamAdmin($player['id'])): ?>
                    <option value="teams.php" <?php echo $current_page === 'teams' ? 'selected' : ''; ?>>Mannschaften</option>
                    <option value="players.php" <?php echo $current_page === 'players' ? 'selected' : ''; ?>>Spieler</option>
                <?php endif; ?>
            </select>
            <?php if ($isClubAdmin || isAnyTeamAdmin($player['id'])): ?>
            <div class="tab-actions">
                <?php if ($current_page === 'games'): ?>
                    <button id="add-match-btn" onclick="openModal('addMatchModal')" class="btn-add" title="Spiel hinzufügen"><i class="fa-solid fa-plus"></i></button>
                <?php elseif ($current_page === 'trainings'): ?>
                    <button id="add-training-btn" onclick="openModal('addTrainingModal')" class="btn-add" title="Training hinzufügen"><i class="fa-solid fa-plus"></i></button>
                <?php elseif ($current_page === 'teams' && $isClubAdmin): ?>
                    <button id="add-team-btn" onclick="openModal('addTeamModal')" class="btn-add" title="Mannschaft hinzufügen"><i class="fa-solid fa-plus"></i></button>
                <?php elseif ($current_page === 'players'): ?>
                    <button id="add-player-btn" onclick="openModal('addPlayerModal')" class="btn-add" title="Spieler hinzufügen"><i class="fa-solid fa-plus"></i></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="tabs">
            <a href="games.php" class="tab-btn <?php echo $current_page === 'games' ? 'active' : ''; ?>">Spiele</a>
            <a href="trainings.php" class="tab-btn <?php echo $current_page === 'trainings' ? 'active' : ''; ?>">Training</a>
            <?php if ($isClubAdmin || isAnyTeamAdmin($player['id'])): ?>
                <a href="teams.php" class="tab-btn <?php echo $current_page === 'teams' ? 'active' : ''; ?>">Mannschaften</a>
                <a href="players.php" class="tab-btn <?php echo $current_page === 'players' ? 'active' : ''; ?>">Spieler</a>
                <div class="tab-actions">
                    <?php if ($current_page === 'games'): ?>
                        <button id="add-match-btn" onclick="openModal('addMatchModal')" class="btn-add" title="Spiel hinzufügen"><i class="fa-solid fa-plus"></i></button>
                    <?php elseif ($current_page === 'trainings'): ?>
                        <button id="add-training-btn" onclick="openModal('addTrainingModal')" class="btn-add" title="Training hinzufügen"><i class="fa-solid fa-plus"></i></button>
                    <?php elseif ($current_page === 'teams' && $isClubAdmin): ?>
                        <button id="add-team-btn" onclick="openModal('addTeamModal')" class="btn-add" title="Mannschaft hinzufügen"><i class="fa-solid fa-plus"></i></button>
                    <?php elseif ($current_page === 'players'): ?>
                        <button id="add-player-btn" onclick="openModal('addPlayerModal')" class="btn-add" title="Spieler hinzufügen"><i class="fa-solid fa-plus"></i></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
<?php
}
?>
