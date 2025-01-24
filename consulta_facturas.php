<?php
// Archivo: compras_historial.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);



// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header('Location: compras.php');
    exit();
}

// Conexión a la base de datos
$host = "localhost";
$dbname = "sistema_ventas";
$username_db = "root";
$password_db = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

// Obtener el historial de compras
$queryCompras = "
    SELECT f.numero_documento, f.tipo_documento, f.total, c.nombre AS cliente, 
           GROUP_CONCAT(CONCAT(p.nombre, ' (', df.cantidad, ')') SEPARATOR ', ') AS productos,
           f.tiempo_interaccion
    FROM facturas f
    JOIN clientes c ON f.cliente_id = c.id
    JOIN detalle_facturas df ON f.id = df.factura_id
    JOIN productos p ON df.producto_id = p.id
    GROUP BY f.id
    ORDER BY f.id DESC
";
$compras = $pdo->query($queryCompras)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Compras</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4 text-center">Compras Realizadas</h1>
    <div class="text-start mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal">
            Agregar Nuevo
        </button>
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Número de Documento</th>
                <th>Tipo de Documento</th>
                <th>Cliente</th>
                <th>Productos</th>
                <th>Total</th>
                <th>Tiempo de Interacción</th> <!-- Nueva columna -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($compras as $compra): ?>
                <tr>
                    <td><?= htmlspecialchars($compra['numero_documento']) ?></td>
                    <td><?= htmlspecialchars($compra['tipo_documento']) ?></td>
                    <td><?= htmlspecialchars($compra['cliente']) ?></td>
                    <td><?= htmlspecialchars($compra['productos']) ?></td>
                    <td>S/ <?= number_format($compra['total'], 2) ?></td>
                    <td><?= number_format($compra['tiempo_interaccion'], 2) ?> segundos</td> <!-- Mostrar el tiempo de interacción -->
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalLabel">Formulario de Compras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php include 'compras.php'; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
