<?php
// Este es un sistema web en PHP para optimizar el proceso de ventas con análisis de datos.
// Incluye un módulo para calcular tiempos de procesos como generación de proformas, facturación y registro de clientes.

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
//    header("Location: principal.php");
    exit;
}


// Configuración de la base de datos
$host = 'localhost';
$dbname = 'sistema_ventas';
$user = 'root';  // Cambia si tienes un usuario diferente
$password = '';  // Cambia si tienes una contraseña diferente

// Conexión a la base de datos
$conexion = new mysqli($host, $user, $password, $dbname);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Rutas
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';


// Controlador principal
switch ($action) {
    case 'dashboard':
        dashboard($conexion);
        break;
    case 'productos':
        listarProductos($conexion);
        break;
    case 'comprar':
        comprarProducto($conexion);
        break;
    case 'reporte':
        generarReporte($conexion);
        break;
    case 'clientes':
        registrarCliente($conexion);
        break;
    case 'facturacion':
        generarFactura($conexion);
        break;
    case 'proformas':
        generarProforma($conexion);
        break;
        default:
        echo "<h1>Página no encontrada</h1>";
        break;
    }



// Función para comprar productos
function comprarProducto($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $productoId = $_POST['producto_id'];
        $cantidad = $_POST['cantidad'];

        // Inicializar la variable stock
        $stock = 0;

        // Verificar stock
        $query = "SELECT stock FROM productos WHERE id = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param('i', $productoId);
        $stmt->execute();
        $stmt->bind_result($stock);

        if ($stmt->fetch()) { // Si el producto existe y fetch tiene éxito
            $stmt->close();
            
            if ($stock >= $cantidad) {
                // Actualizar stock
                $nuevoStock = $stock - $cantidad;
                $query = "UPDATE productos SET stock = ? WHERE id = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param('ii', $nuevoStock, $productoId);
                $stmt->execute();
                $stmt->close();

                // Registrar venta
                $queryVenta = "INSERT INTO ventas (producto_id, cantidad) VALUES (?, ?)";
                $stmt = $conexion->prepare($queryVenta);
                $stmt->bind_param('ii', $productoId, $cantidad);
                $stmt->execute();
                $stmt->close();

                echo "<p>Compra realizada con éxito. Nuevo stock: $nuevoStock</p>";
            } else {
                echo "<p>Stock insuficiente para completar la compra.</p>";
            }
        } else {
            // El producto no existe
            echo "<p>Producto no encontrado.</p>";
            $stmt->close();
        }
    } else {
        echo "<form method='POST'>
                <label for='producto_id'>ID del Producto:</label>
                <input type='number' name='producto_id' required>
                <label for='cantidad'>Cantidad:</label>
                <input type='number' name='cantidad' required>
                <button type='submit'>Comprar</button>
              </form>";
    }
}

// Función para generar el reporte y mostrar tiempos promedio por proceso
function generarReporte($conexion) {
    // Reporte de ventas
    $queryVentas = "SELECT p.nombre, SUM(v.cantidad) AS total_vendido, SUM(v.cantidad * p.precio) AS total_ganancia
                     FROM ventas v
                     JOIN productos p ON v.producto_id = p.id
                     GROUP BY p.id";

    $resultVentas = $conexion->query($queryVentas);

    echo "<h1>Reporte de Ventas</h1>";
    echo "<table><tr><th>Producto</th><th>Total Vendido</th><th>Total Ganancia</th></tr>";

    while ($fila = $resultVentas->fetch_assoc()) {
        echo "<tr><td>{$fila['nombre']}</td><td>{$fila['total_vendido']}</td><td>\${$fila['total_ganancia']}</td></tr>";
    }

    echo "</table>";

    // Reporte de tiempos de procesos
    $queryTiempos = "SELECT proceso, AVG(duracion) AS promedio_duracion
                      FROM tiempos_procesos
                      GROUP BY proceso";

    $resultTiempos = $conexion->query($queryTiempos);

    echo "<h2>Promedio de Tiempos por Proceso</h2>";
    echo "<table><tr><th>Proceso</th><th>Duración Promedio (segundos)</th></tr>";

    while ($fila = $resultTiempos->fetch_assoc()) {
        echo "<tr><td>{$fila['proceso']}</td><td>{$fila['promedio_duracion']} segundos</td></tr>";
    }

    echo "</table>";
}

//Registrar tiempo
function registrarTiempo($conexion, $proceso, $duracion) {
    $query = "INSERT INTO tiempos_procesos (proceso, duracion) VALUES (?, ?)";
    $stmt = $conexion->prepare($query);

    if ($stmt) {
        $stmt->bind_param('sd', $proceso, $duracion);
        if (!$stmt->execute()) {
            echo "<p>Error al registrar el tiempo del proceso: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p>Error al preparar la consulta para registrar tiempo: " . $conexion->error . "</p>";
    }
}



//dashboard

// Función Dashboard
function Dashboard($conexion) {
    $datos = [
        "productos" => [],
        "stocks" => [],
        "ventasProductos" => [],
        "cantidadesVendidas" => [],
        "totalFacturas" => 0,
        "totalVentas" => 0.0,
        "totalClientes" => 0,
    ];

    // Productos y su stock
    $queryProductos = "SELECT nombre, stock FROM productos";
    $resultProductos = $conexion->query($queryProductos);
    while ($fila = $resultProductos->fetch_assoc()) {
        $datos["productos"][] = $fila["nombre"];
        $datos["stocks"][] = $fila["stock"];
    }

    // Generar Factura
    $queryVentas = "SELECT p.nombre, SUM(v.cantidad) AS cantidad_vendida
                    FROM ventas v
                    JOIN productos p ON v.producto_id = p.id
                    GROUP BY p.id";
    $resultVentas = $conexion->query($queryVentas);
    while ($fila = $resultVentas->fetch_assoc()) {
        $datos["ventasProductos"][] = $fila["nombre"];
        $datos["cantidadesVendidas"][] = $fila["cantidad_vendida"];
    }

    // Total de facturas
    $queryFacturas = "SELECT COUNT(*) AS total_facturas, SUM(total) AS total_ventas FROM facturas";
    $resultFacturas = $conexion->query($queryFacturas);
    if ($fila = $resultFacturas->fetch_assoc()) {
        $datos["totalFacturas"] = $fila["total_facturas"];
        $datos["totalVentas"] = $fila["total_ventas"];
    }

    // Total de clientes
    $queryClientes = "SELECT COUNT(*) AS total_clientes FROM clientes";
    $resultClientes = $conexion->query($queryClientes);
    if ($fila = $resultClientes->fetch_assoc()) {
        $datos["totalClientes"] = $fila["total_clientes"];
    }

    return $datos;
}

// Obtener datos para los gráficos


if (!isset($action) || $action !== 'dashboard') {
    die("");
}
$datos = dashboard($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Análisis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1 class="my-4">Dashboard de Análisis</h1>

    <!-- Primera fila: Gráficos de Stock y Generar Factura -->
    <div class="row">
        <!-- Gráfico de Stock -->
        <div class="col-md-12">
            <h3>Stock de Productos</h3>
            <canvas id="stockChart"></canvas>
        </div>

    </div>

    <!-- Segunda fila: Resumen General y Gráfico de Total de Facturas -->
    <div class="row my-4">
        <div class="col-md-6">
            <h3>Resumen General</h3>
            <p>Total de Facturas: <?php echo $datos['totalFacturas']; ?></p>
            <p>Total de Ventas: S/ <?php echo number_format($datos['totalVentas'], 2); ?></p>
            <p>Total de Clientes: <?php echo $datos['totalClientes']; ?></p>
        </div>
        <div class="col-md-6">
            <h3>Total de Compras</h3>
            <canvas id="facturasChart"></canvas>
        </div>
    </div>
</div>

<!-- Script para los gráficos -->
<script>
    // Cargando datos de PHP y depuración en consola
    const datos = {
        productos: <?php echo json_encode($datos['productos']); ?>,
        stocks: <?php echo json_encode($datos['stocks']); ?>,
        ventasProductos: <?php echo json_encode($datos['ventasProductos']); ?>,
        cantidadesVendidas: <?php echo json_encode($datos['cantidadesVendidas']); ?>,
        totalFacturas: <?php echo json_encode($datos['totalFacturas']); ?>
    };

    console.log("Datos cargados:", datos);

    // Gráfico de Stock de Productos
    const stockChartCtx = document.getElementById('stockChart').getContext('2d');
    new Chart(stockChartCtx, {
        type: 'bar',
        data: {
            labels: datos.productos,
            datasets: [{
                label: 'Stock Disponible',
                data: datos.stocks,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Total de Facturas Generadas
    const facturasChartCtx = document.getElementById('facturasChart').getContext('2d');
    new Chart(facturasChartCtx, {
        type: 'bar',
        data: {
            labels: ['Total de Facturas'],
            datasets: [{
                label: 'Número Total',
                data: [datos.totalFacturas],
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Gráfico de Total de Facturas Generadas'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Facturas'
                    }
                }
            }
        }
    });
</script>
</body>
</html>
?>