<?php
require_once './includes/auth.php';

$allowedTabs = ['games', 'trainings', 'teams', 'players'];
$lastTab = $_COOKIE['last_tab'] ?? 'games';
$target = in_array($lastTab, $allowedTabs, true) ? $lastTab . '.php' : 'games.php';

if (isLoggedIn()) {
    header('Location: ' . $target);
} else {
    header('Location: login.php');
}
exit;
