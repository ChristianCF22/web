<?php  
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
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

$filtro = $_GET['filtro'] ?? 'dia';
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-d');
$fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');

// Asegurarse de que las fechas están en el formato correcto, y si no, establecer la fecha actual
if (!strtotime($fechaInicio)) {
    $fechaInicio = date('Y-m-d');  // Fecha de hoy
}

if (!strtotime($fechaFin)) {
    $fechaFin = date('Y-m-d');  // Fecha de hoy
}

// Añadir tiempo completo (hora) a las fechas para asegurar que no haya confusión
$fechaInicio .= " 00:00:00";
$fechaFin .= " 23:59:59";

// Consultas para compras
$queryCompras = "
    SELECT 
        f.numero_documento, 
        f.total, 
        f.fecha, 
        c.nombre, 
        f.tiempo_interaccion
    FROM facturas f
    JOIN clientes c ON f.cliente_id = c.id
    WHERE f.fecha BETWEEN :fechaInicio AND :fechaFin
    ORDER BY f.fecha DESC
";

try {
    $stmtCompras = $pdo->prepare($queryCompras);
    $stmtCompras->bindParam(':fechaInicio', $fechaInicio);
    $stmtCompras->bindParam(':fechaFin', $fechaFin);
    $stmtCompras->execute();
    $compras = $stmtCompras->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error en la consulta de compras: " . $e->getMessage();
    exit();
}

// Consultas para proformas
$queryProformas = "
    SELECT DATE(fecha_creacion) as fecha, COUNT(*) as cantidad
    FROM proformas
    WHERE fecha_creacion BETWEEN :fechaInicio AND :fechaFin
    GROUP BY DATE(fecha_creacion)
    ORDER BY fecha ASC
";

try {
    $stmtProformas = $pdo->prepare($queryProformas);
    $stmtProformas->bindParam(':fechaInicio', $fechaInicio);
    $stmtProformas->bindParam(':fechaFin', $fechaFin);
    $stmtProformas->execute();
    $proformas = $stmtProformas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error en la consulta de proformas: " . $e->getMessage();
    exit();
}

// Consultas para clientes
$queryClientes = "
    SELECT DATE(fecha_registro) as fecha, COUNT(*) as cantidad
    FROM clientes
    WHERE fecha_registro BETWEEN :fechaInicio AND :fechaFin
    GROUP BY DATE(fecha_registro)
    ORDER BY fecha ASC
";

try {
    $stmtClientes = $pdo->prepare($queryClientes);
    $stmtClientes->bindParam(':fechaInicio', $fechaInicio);
    $stmtClientes->bindParam(':fechaFin', $fechaFin);
    $stmtClientes->execute();
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error en la consulta de clientes: " . $e->getMessage();
    exit();
}

// Preparar datos para los gráficos
$comprasLabels = json_encode(array_column($compras, 'numero_documento'));
$comprasData = json_encode(array_map('floatval', array_column($compras, 'total')));

$proformasLabels = json_encode(array_column($proformas, 'fecha'));
$proformasData = json_encode(array_map('intval', array_column($proformas, 'cantidad')));

$clientesLabels = json_encode(array_column($clientes, 'fecha'));
$clientesData = json_encode(array_map('intval', array_column($clientes, 'cantidad')));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center">Analisis de datos</h2>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="filtro" class="form-label">Filtrar por</label>
            <select name="filtro" id="filtro" class="form-select">
                <option value="dia" <?= $filtro === 'dia' ? 'selected' : '' ?>>Día</option>
                <option value="mes" <?= $filtro === 'mes' ? 'selected' : '' ?>>Mes</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="fechaInicio" class="form-label">Fecha Inicio</label>
            <input type="date" name="fechaInicio" id="fechaInicio" class="form-control" value="<?= htmlspecialchars($_GET['fechaInicio'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
            <label for="fechaFin" class="form-label">Fecha Fin</label>
            <input type="date" name="fechaFin" id="fechaFin" class="form-control" value="<?= htmlspecialchars($_GET['fechaFin'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <div>
        <canvas id="comprasChart"></canvas>
        <canvas id="proformasChart"></canvas>
        <canvas id="clientesChart"></canvas>
    </div>

    <script>
        // Compras
        const comprasCtx = document.getElementById('comprasChart').getContext('2d');
        new Chart(comprasCtx, {
            type: 'bar',
            data: {
                labels: <?= $comprasLabels ?>,
                datasets: [{
                    label: 'Total de Compras',
                    data: <?= $comprasData ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // Proformas
        const proformasCtx = document.getElementById('proformasChart').getContext('2d');
        new Chart(proformasCtx, {
            type: 'line',
            data: {
                labels: <?= $proformasLabels ?>,
                datasets: [{
                    label: 'Proformas Generadas',
                    data: <?= $proformasData ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // Clientes
        const clientesCtx = document.getElementById('clientesChart').getContext('2d');
        new Chart(clientesCtx, {
            type: 'line',
            data: {
                labels: <?= $clientesLabels ?>,
                datasets: [{
                    label: 'Clientes Registrados',
                    data: <?= $clientesData ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    </script>
</div>
</body>
</html>
