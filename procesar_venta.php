
<?php include('incluido/db.php'); ?>
<?php include('incluido/header.php'); ?>
<?php
// procesar_venta.php

// Configuración de la conexión a la base de datos (ajusta según tu servidor)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'dblibreria';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para obtener el descuento aplicable según id_tienda y cantidad
function obtenerDescuento($conn, $id_tienda, $cantidad) {
    // Primero buscamos descuento específico para la tienda
    $sql = "SELECT descuento FROM descuento WHERE id_tienda = ? 
            AND cant_min <= ? AND (cant_max >= ? OR cant_max = 0)
            ORDER BY cant_min DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $id_tienda, $cantidad, $cantidad);
    $stmt->execute();
    $stmt->bind_result($descuento);
    if ($stmt->fetch()) {
        $stmt->close();
        return $descuento;
    }
    $stmt->close();

    // Si no hay descuento específico para tienda, buscamos descuento general (id_tienda = '')
    $sql = "SELECT descuento FROM descuento WHERE id_tienda = '' 
            AND cant_min <= ? AND (cant_max >= ? OR cant_max = 0)
            ORDER BY cant_min DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $cantidad, $cantidad);
    $stmt->execute();
    $stmt->bind_result($descuento);
    if ($stmt->fetch()) {
        $stmt->close();
        return $descuento;
    }
    $stmt->close();

    // Si no hay descuento, devolvemos 0
    return 0;
}

// Supongamos que recibimos estos datos vía POST
// Para este ejemplo, usaremos un array manual (cambia para tu formulario)
$id_tienda = $_POST['id_tienda'] ?? '8042';
$num_orden = $_POST['num_orden'] ?? 'ORD-123456';
$fecha = $_POST['fecha'] ?? date('Y-m-d H:i:s');
$libros = $_POST['libros'] ?? [
    ['id_titulo' => 'TC3218', 'cantidad' => 120],
    ['id_titulo' => 'PS3333', 'cantidad' => 10],
];

// Insertamos la venta
$stmt = $conn->prepare("INSERT INTO ventas (id_tienda, num_orden, fecha) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $id_tienda, $num_orden, $fecha);
if (!$stmt->execute()) {
    die("Error al insertar venta: " . $stmt->error);
}
$stmt->close();

echo "<h2>Resumen de la venta: Orden $num_orden</h2>";
echo "<table border='1' cellpadding='5'>
        <tr><th>ID Título</th><th>Cantidad</th><th>% Descuento</th><th>Cantidad con descuento</th></tr>";

// ** BORRAR DETALLES ANTERIORES PARA EVITAR DUPLICADOS **
$conn->query("DELETE FROM detalle_venta WHERE id_tienda='$id_tienda' AND num_orden='$num_orden'");

// Insertamos los detalles de venta con el descuento aplicado
$stmt_detalle = $conn->prepare("INSERT INTO detalle_venta (id_tienda, num_orden, id_titulo, cantidad, descuento) VALUES (?, ?, ?, ?, ?)");

foreach ($libros as $item) {
    $id_titulo = $item['id_titulo'];
    $cantidad = intval($item['cantidad']);
    $descuento = obtenerDescuento($conn, $id_tienda, $cantidad);

    // CORRECCIÓN bind_param con tipo double 'd' para $descuento
    $stmt_detalle->bind_param('sssid', $id_tienda, $num_orden, $id_titulo, $cantidad, $descuento);
    if (!$stmt_detalle->execute()) {
        echo "Error al insertar detalle para $id_titulo: " . $stmt_detalle->error . "<br>";
    }

    // Mostramos resumen
    echo "<tr>
            <td>$id_titulo</td>
            <td>$cantidad</td>
            <td>$descuento %</td>
            <td>" . ($cantidad - ($cantidad * $descuento / 100)) . "</td>
          </tr>";
}

echo "</table>";

$stmt_detalle->close();
$conn->close();
?>

<?php include('incluido/footer.php'); ?>

