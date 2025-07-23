<?php
include('incluido/db.php');

$errores = [];
$exito = false;
$venta_exito = false;

// Consulta tiendas
$stmt = $pdo->query("SELECT id_tienda, nombre_tienda FROM tiendas ORDER BY nombre_tienda");
$tiendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta títulos únicos de libros
$stmt = $pdo->query("SELECT DISTINCT id_titulo FROM titulo_autor ORDER BY id_titulo");
$titulos = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Para JavaScript (usamos luego)
$opciones_titulos = "";
foreach ($titulos as $titulo) {
    $opciones_titulos .= "<option value='" . htmlspecialchars($titulo) . "'>" . htmlspecialchars($titulo) . "</option>";
}

// Función PHP para obtener derechos
function obtenerDerechos($pdo, $id_titulo, $cantidad) {
    $sql = "SELECT derechos FROM derechos WHERE id_titulo = ? AND rango_bajo <= ? AND rango_alto >= ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_titulo, $cantidad, $cantidad]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? (int)$fila['derechos'] : 0;
}

// Procesamiento del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');
    $fecha = date("Y-m-d H:i:s");

    if (empty($nombre)) $errores[] = "El nombre es obligatorio.";
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo no es válido.";
    if (empty($asunto)) $errores[] = "El asunto es obligatorio.";
    if (empty($comentario)) $errores[] = "El comentario es obligatorio.";

    $id_tienda = trim($_POST['id_tienda'] ?? '');
    $num_orden = trim($_POST['num_orden'] ?? '');
    $libros = $_POST['libros'] ?? [];

    // Resumen
    $resumen_venta = "";
    if ($id_tienda && $num_orden && !empty($libros)) {
        $resumen_venta .= "Tienda: $id_tienda\nOrden: $num_orden\nProductos:\n";
        $stmt_detalle = $pdo->prepare("INSERT INTO detalle_venta (id_tienda, num_orden, id_titulo, cantidad, descuento) VALUES (?, ?, ?, ?, ?)");

        foreach ($libros as $item) {
            $id_titulo = trim($item['id_titulo']);
            $cantidad = (int)$item['cantidad'];
            if ($id_titulo && $cantidad > 0) {
                $resumen_venta .= "- $id_titulo: $cantidad\n";
            }
        }
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("INSERT INTO contacto (fecha, correo, nombre, asunto, comentario, resumen_venta) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fecha, $correo, $nombre, $asunto, $comentario, $resumen_venta]);
        $exito = true;

        if ($id_tienda && $num_orden && !empty($libros)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO ventas (id_tienda, num_orden, fecha) VALUES (?, ?, ?)");
                $stmt->execute([$id_tienda, $num_orden, $fecha]);

$sql_detalle = "INSERT INTO detalle_venta (id_tienda, num_orden, id_titulo, cantidad, derechos, descuento) 
                VALUES (?, ?, ?, ?, ?, 0)";

                foreach ($libros as $item) {
                    $id_titulo = trim($item['id_titulo']);
                    $cantidad = (int)$item['cantidad'];
                    if ($id_titulo && $cantidad > 0) {
                        $descuento = obtenerDerechos($pdo, $id_titulo, $cantidad);
$derechos_val = obtenerDerechos($pdo, $id_titulo, $cantidad);
$stmt_detalle->execute([$id_tienda, $num_orden, $id_titulo, $cantidad, $derechos_val]);
                    }
                }

                $pdo->commit();
                $venta_exito = true;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errores[] = "Error al registrar la venta: " . $e->getMessage();
            }
        }
    }
}
?>

<?php include('incluido/header.php'); ?>

<div class="container mt-5">
    <h2>Contacto y Registro de Venta</h2>

    <?php if ($exito): ?>
        <div class="alert alert-success">Gracias por contactarnos.</div>
    <?php endif; ?>

    <?php if ($venta_exito): ?>
        <div class="alert alert-success">Venta registrada con éxito.</div>
    <?php endif; ?>

    <?php if ($errores): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <br>
        <h3>Datos de Contacto</h3>
        <div class="mb-3"><label>Nombre:</label><input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required></div>
        <div class="mb-3"><label>Correo:</label><input type="email" class="form-control" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required></div>
        <div class="mb-3"><label>Asunto:</label><input type="text" class="form-control" name="asunto" value="<?= htmlspecialchars($_POST['asunto'] ?? '') ?>" required></div>
        <div class="mb-3"><label>Comentario:</label><textarea class="form-control" name="comentario" required><?= htmlspecialchars($_POST['comentario'] ?? '') ?></textarea></div>

        <h3>Datos de la Venta (opcional)</h3>
        <div class="mb-3">
            <label>Tienda:</label>
            <select name="id_tienda" class="form-control">
                <option value="">Selecciona una tienda</option>
                <?php foreach ($tiendas as $tienda): ?>
                    <option value="<?= $tienda['id_tienda'] ?>" <?= ($_POST['id_tienda'] ?? '') == $tienda['id_tienda'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tienda['nombre_tienda']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
      <div class="mb-3">
    <label>Número de Orden:</label>
    <select name="num_orden" id="num_orden" class="form-control" disabled>
        <option value="">Selecciona una tienda primero</option>
    </select>
</div>


        <h4>Libros</h4>
        <div id="libros-container">
            <?php
            $libros_post = $_POST['libros'] ?? [['id_titulo' => '', 'cantidad' => '', 'derechos' => '']];
            foreach ($libros_post as $i => $libro): ?>
                <div class="mb-3 libro">
                    <label>Título:</label>
                    <select name="libros[<?= $i ?>][id_titulo]" class="form-control" required>
                        <option value="">Selecciona un título</option>
                        <?php foreach ($titulos as $titulo): ?>
                            <option value="<?= $titulo ?>" <?= ($libro['id_titulo'] ?? '') == $titulo ? 'selected' : '' ?>><?= $titulo ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Cantidad:</label>
                    <input type="number" name="libros[<?= $i ?>][cantidad]" class="form-control" min="1" value="<?= $libro['cantidad'] ?? '' ?>" required>
                    <label>Derechos:</label>
                    <input type="text" class="form-control derechos" readonly>
                    <button type="button" class="btn btn-danger mt-2" onclick="this.parentNode.remove()">Eliminar</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn btn-secondary mb-3" onclick="agregarLibro()">Agregar otro libro</button><br>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</div>

<script>
let contador = <?= count($libros_post) ?>;

function agregarLibro() {
    const contenedor = document.getElementById('libros-container');
    const div = document.createElement('div');
    div.className = 'libro mb-3';
    div.innerHTML = `
        <label>ID Título:</label>
        <select name="libros[\${contador}][id_titulo]" class="form-control" required>
            <option value="">Selecciona un título</option>
            <?php foreach ($titulos as $titulo): ?>
            <option value="<?= htmlspecialchars($titulo) ?>"><?= htmlspecialchars($titulo) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Cantidad:</label>
        <input type="number" name="libros[\${contador}][cantidad]" class="form-control" min="1" required>

        <label>Derechos:</label>
        <input type="text" name="libros[\${contador}][derechos]" class="form-control derechos" readonly>

        <button type="button" class="btn btn-danger mt-2" onclick="this.parentNode.remove()">Eliminar</button>
    `;
    contenedor.appendChild(div);
    contador++;
}

function actualizarDerechos() {
    const libros = document.querySelectorAll('#libros-container .libro');
    libros.forEach(libro => {
        const selectTitulo = libro.querySelector('select[name$="[id_titulo]"]');
        const inputCantidad = libro.querySelector('input[name$="[cantidad]"]');
        const inputDerechos = libro.querySelector('input.derechos');

        const id_titulo = selectTitulo.value;
        const cantidad = parseInt(inputCantidad.value) || 0;

        if (!id_titulo || cantidad <= 0) {
            inputDerechos.value = '';
            return;
        }

        fetch(`obtener_derechos.php?id_titulo=${encodeURIComponent(id_titulo)}&cantidad=${cantidad}`)
            .then(res => res.json())
            .then(data => {
                inputDerechos.value = data.derechos ?? '';
            })
            .catch(() => {
                inputDerechos.value = '';
            });
    });
}

document.getElementById('libros-container').addEventListener('change', e => {
    if (e.target.matches('select[name$="[id_titulo]"], input[name$="[cantidad]"]')) {
        actualizarDerechos();
    }
});

window.addEventListener('load', actualizarDerechos);

document.querySelector('select[name="id_tienda"]').addEventListener('change', function() {
    const tiendaId = this.value;
    const numOrdenSelect = document.getElementById('num_orden');
    numOrdenSelect.innerHTML = ''; // Limpiar opciones

    if (!tiendaId) {
        numOrdenSelect.disabled = true;
        numOrdenSelect.innerHTML = '<option value="">Selecciona una tienda primero</option>';
        return;
    }

    fetch(`get_num_ordenes.php?id_tienda=${encodeURIComponent(tiendaId)}`)
        .then(response => response.json())
        .then(data => {
            numOrdenSelect.disabled = false;
            if (data.length === 0) {
                numOrdenSelect.innerHTML = '<option value="">No hay órdenes para esta tienda</option>';
            } else {
                numOrdenSelect.innerHTML = '<option value="">Selecciona un número de orden</option>';
                data.forEach(orden => {
                    const option = document.createElement('option');
                    option.value = orden;
                    option.textContent = orden;
                    // Mantener seleccionado si ya hay valor enviado
                    if (orden === "<?= htmlspecialchars($_POST['num_orden'] ?? '') ?>") {
                        option.selected = true;
                    }
                    numOrdenSelect.appendChild(option);
                });
            }
        })
        .catch(() => {
            numOrdenSelect.disabled = true;
            numOrdenSelect.innerHTML = '<option value="">Error cargando órdenes</option>';
        });
});

// Si ya hay tienda seleccionada al cargar la página, disparar el cambio para llenar órdenes
window.addEventListener('load', () => {
    const tiendaSelect = document.querySelector('select[name="id_tienda"]');
    if (tiendaSelect.value) {
        tiendaSelect.dispatchEvent(new Event('change'));
    }
});

</script>





<?php include('incluido/footer.php'); ?>
