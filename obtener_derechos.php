<?php
include('incluido/db.php');

$id_titulo = $_GET['id_titulo'] ?? '';
$cantidad = (int)($_GET['cantidad'] ?? 0);

header('Content-Type: application/json');

if ($id_titulo === '' || $cantidad <= 0) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

$sql = "SELECT derechos FROM derechos 
        WHERE id_titulo = ? 
          AND rango_bajo <= ? 
          AND rango_alto >= ?
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_titulo, $cantidad, $cantidad]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

if ($resultado) {
    echo json_encode(['derechos' => (int)$resultado['derechos']]);
} else {
    echo json_encode(['derechos' => 0]);
}
