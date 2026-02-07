<?php
require_once './includes/auth.php';

if (isLoggedIn()) {
    header('Location: games.php');
} else {
    header('Location: login.php');
}
exit;
