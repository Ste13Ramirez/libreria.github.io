<?php include('incluido/db.php'); ?>
<?php include('incluido/header.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Listado de Autores</h2>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT * FROM autores");
            while ($row = $stmt->fetch()) {
                echo "<tr>
                        <td>{$row['nombre']}</td>
                        <td>{$row['apellido']}</td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include('incluido/footer.php'); ?>
