<?php
$host = 'localhost';
$db = DATABASE;
$dbOverrideFile = __DIR__ . '/../../.tc_database';
if (file_exists($dbOverrideFile)) {
    $dbOverride = trim(file_get_contents($dbOverrideFile));
    if ($dbOverride !== '') {
        $db = $dbOverride;
    }
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     global $pdo;
     $pdo = new PDO($dsn, DATABASE_USER, DATABASE_PASSWORD, $options);
} catch (PDOException $e) {
     throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
