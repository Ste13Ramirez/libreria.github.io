<?php 
header("ngrok-skip-browser-warning: true");
include('incluido/db.php');
include('incluido/header.php'); ?>

<div class="container mt-5">
    <h1 class="mb-4">Librería Online</h1>


    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="busqueda" class="form-control" placeholder="Buscar libros por título..." value="<?= isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '' ?>">
            <button type="submit" class="btn btn-success">Buscar</button>
        </div>
    </form>

    <?php
    if (isset($_GET['busqueda']) && trim($_GET['busqueda']) !== '') {
        $busqueda = '%' . $_GET['busqueda'] . '%';

        $sql = "SELECT t.titulo, GROUP_CONCAT(CONCAT(a.nombre, ' ', a.apellido) SEPARATOR ', ') AS autores, DATE_FORMAT(t.fecha_pub, '%Y') as anio
                FROM titulos t
                JOIN titulo_autor ta ON t.id_titulo = ta.id_titulo
                JOIN autores a ON ta.id_autor = a.id_autor
                WHERE t.titulo LIKE :busqueda
                GROUP BY t.id_titulo
                LIMIT 20";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['busqueda' => $busqueda]);
        $resultados = $stmt->fetchAll();

        if ($resultados) {
            echo "<h3>Resultados de búsqueda:</h3>";
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>Título</th><th>Autor(es)</th><th>Año</th></tr></thead><tbody>";
            foreach ($resultados as $libro) {
                echo "<tr><td>" . htmlspecialchars($libro['titulo']) . "</td><td>" . htmlspecialchars($libro['autores']) . "</td><td>" . htmlspecialchars($libro['anio']) . "</td></tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p class='text-danger'>No se encontraron libros con ese título.</p>";
        }
    }
    ?>
</div>

<?php include('incluido/footer.php'); ?>
