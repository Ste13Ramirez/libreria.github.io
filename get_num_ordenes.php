<?php
include('incluido/db.php');

header('Content-Type: application/json');

$id_tienda = $_GET['id_tienda'] ?? '';

if (!$id_tienda) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT DISTINCT num_orden FROM ventas WHERE id_tienda = ? ORDER BY num_orden");
$stmt->execute([$id_tienda]);
$ordenes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($ordenes);
