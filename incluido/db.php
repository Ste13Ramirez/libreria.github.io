<?php
$host = 'dpg-d22hmm7diees73de7ibg-a.oregon-postgres.render.com';
$db   = 'dblibreria'; 
$user = 'root';
$pass = '123456789';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "❌ Error en la conexión: " . $e->getMessage();
    exit;
}
?>
