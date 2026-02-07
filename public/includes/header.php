<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

function printHeader($player, $playerTeams, $current_page) {
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeamControl - <?php echo $title ?? 'Termine'; ?></title>
    <link rel="stylesheet" href="css/style.css">
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
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="player-name"><?php echo htmlspecialchars($player['name']); ?></span>
                    <button class="share-btn" onclick="copyLoginLink('<?php echo $player['hash']; ?>')" title="Login-Link kopieren">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                    </button>
                </div>
                <div class="team-info-header">
                    <?php if (!empty($playerTeams[0]['logo'])): ?>
                        <img src="uploads/logos/<?php echo htmlspecialchars($playerTeams[0]['logo']); ?>" alt="Logo" class="header-team-logo">
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
                <?php if (isClubAdmin() || isAnyTeamAdmin($player['id'])): ?>
                    <option value="teams.php" <?php echo $current_page === 'teams' ? 'selected' : ''; ?>>Mannschaften</option>
                    <option value="players.php" <?php echo $current_page === 'players' ? 'selected' : ''; ?>>Spieler</option>
                <?php endif; ?>
            </select>
            <?php if (isClubAdmin() || isAnyTeamAdmin($player['id'])): ?>
            <div class="tab-actions">
                <?php if ($current_page === 'games'): ?>
                    <button id="add-match-btn-mobile" onclick="document.getElementById('addMatchModal').style.display='block'" class="btn-add" title="Spiel hinzufügen">+</button>
                <?php elseif ($current_page === 'trainings'): ?>
                    <button id="add-training-btn-mobile" onclick="document.getElementById('addTrainingModal').style.display='block'" class="btn-add" title="Training hinzufügen">+</button>
                <?php elseif ($current_page === 'teams' && isClubAdmin()): ?>
                    <button id="add-team-btn-mobile" onclick="document.getElementById('addTeamModal').style.display='block'" class="btn-add" title="Mannschaft hinzufügen">+</button>
                <?php elseif ($current_page === 'players'): ?>
                    <button id="add-player-btn-mobile" onclick="document.getElementById('addPlayerModal').style.display='block'" class="btn-add" title="Spieler hinzufügen">+</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="tabs">
            <a href="games.php" class="tab-btn <?php echo $current_page === 'games' ? 'active' : ''; ?>">Spiele</a>
            <a href="trainings.php" class="tab-btn <?php echo $current_page === 'trainings' ? 'active' : ''; ?>">Training</a>
            <?php if (isClubAdmin() || isAnyTeamAdmin($player['id'])): ?>
                <a href="teams.php" class="tab-btn <?php echo $current_page === 'teams' ? 'active' : ''; ?>">Mannschaften</a>
                <a href="players.php" class="tab-btn <?php echo $current_page === 'players' ? 'active' : ''; ?>">Spieler</a>
                <div class="tab-actions">
                    <?php if ($current_page === 'games'): ?>
                        <button id="add-match-btn" onclick="document.getElementById('addMatchModal').style.display='block'" class="btn-add" title="Spiel hinzufügen">+</button>
                    <?php elseif ($current_page === 'trainings'): ?>
                        <button id="add-training-btn" onclick="document.getElementById('addTrainingModal').style.display='block'" class="btn-add" title="Training hinzufügen">+</button>
                    <?php elseif ($current_page === 'teams' && isClubAdmin()): ?>
                        <button id="add-team-btn" onclick="document.getElementById('addTeamModal').style.display='block'" class="btn-add" title="Mannschaft hinzufügen">+</button>
                    <?php elseif ($current_page === 'players'): ?>
                        <button id="add-player-btn" onclick="document.getElementById('addPlayerModal').style.display='block'" class="btn-add" title="Spieler hinzufügen">+</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
<?php
}
?>
<script>
function copyLoginLink(hash) {
    const url = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/') + 'login.php?hash=' + hash;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.querySelector('.share-btn');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        btn.style.backgroundColor = '#2ecc71';
        btn.style.borderColor = '#2ecc71';
        setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.style.backgroundColor = '';
            btn.style.borderColor = '';
        }, 2000);
    }).catch(err => {
        console.error('Fehler beim Kopieren:', err);
        alert('Fehler beim Kopieren des Links.');
    });
}
</script>