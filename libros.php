<?php include('incluido/db.php'); ?>
<?php include('incluido/header.php'); ?>

<div class="container mt-5">
    <h2>Listado de Libros</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Título</th>
                <th>Autores(as)</th>
                <th>Fecha Publicación</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "
                SELECT t.titulo, GROUP_CONCAT(CONCAT(a.nombre, ' ', a.apellido) SEPARATOR ', ') AS autores, t.fecha_pub
                FROM titulos t
                JOIN titulo_autor ta ON t.id_titulo = ta.id_titulo
                JOIN autores a ON ta.id_autor = a.id_autor
                GROUP BY t.id_titulo, t.titulo, t.fecha_pub
                ORDER BY t.titulo
            ";

            $stmt = $pdo->query($sql);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
                echo "<td>" . htmlspecialchars($row['autores']) . "</td>";
                echo "<td>" . htmlspecialchars(date('Y', strtotime($row['fecha_pub']))) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include('incluido/footer.php'); ?>
