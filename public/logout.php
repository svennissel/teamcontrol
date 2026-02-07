<?php
require_once './includes/auth.php';
logout();
?>
<!DOCTYPE html>
<html>
<body>
    <script>
        localStorage.removeItem('playerHash');
        window.location.href = 'login.php';
    </script>
</body>
</html>
<?php
exit;
?>
