<?php
// Archivo principal para gestionar proformas
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Configuración de la base de datos
$host = "localhost";
$dbname = "sistema_ventas";
$username_db = "root";
$password_db = "";

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

// Procesar formulario para crear una nueva proforma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        procesarProforma($_POST, $pdo);
    } catch (Exception $e) {
        echo "Error al procesar la proforma: " . $e->getMessage();
    }
}

// Función para procesar el envío del formulario
function procesarProforma($data, $pdo)
{
    // Validar datos del formulario
    if (empty($data['cliente_id']) || empty($data['producto_id']) || empty($data['cantidad']) || !isset($data['tiempo_interaccion'])) {
        throw new Exception("Todos los campos son obligatorios.");
    }

    $clienteId = $data['cliente_id'];
    $productos = $data['producto_id'];
    $cantidades = $data['cantidad'];
    $Tiempointeraccion = $data['tiempo_interaccion']; // Tiempo de interacción en segundos
    $totalProforma = 0;
    try {
        $pdo->beginTransaction();

        // Insertar en la tabla `proformas`
        $stmt = $pdo->prepare("INSERT INTO proformas (cliente_id, total, tiempo_interaccion) VALUES (:cliente_id, :total, :tiempo_interaccion)");
        if (!$stmt->execute(['cliente_id' => $clienteId, 'total' => $totalProforma, 'tiempo_interaccion' => $Tiempointeraccion])) {
            throw new Exception("Error al insertar en la tabla `proformas`: " . implode(", ", $stmt->errorInfo()));
        }
        $proformaId = $pdo->lastInsertId();
        if (!$proformaId) {
            throw new Exception("No se generó el ID de la proforma.");
        }

        // Insertar productos en `detalle_proformas`
        foreach ($productos as $index => $productoId) {
            $cantidad = $cantidades[$index];
            $precio = obtenerPrecioProducto($productoId, $pdo);

            if ($precio === null) {
                throw new Exception("Producto con ID $productoId no encontrado.");
            }

            if (!verificarStock($productoId, $cantidad, $pdo)) {
                throw new Exception("Stock insuficiente para el producto ID $productoId.");
            }

            $subtotal = $precio * $cantidad;
            $totalProforma += $subtotal;

            $stmt = $pdo->prepare("INSERT INTO detalle_proformas (proforma_id, producto_id, cantidad, subtotal) 
                                   VALUES (:proforma_id, :producto_id, :cantidad, :subtotal)");
            if (!$stmt->execute([ 
                'proforma_id' => $proformaId,
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'subtotal' => $subtotal
            ])) {
                throw new Exception("Error al insertar en `detalle_proformas`: " . implode(", ", $stmt->errorInfo()));
            }

            // Actualizar stock del producto
            actualizarStock($productoId, $cantidad, $pdo);
        }

        // Actualizar total en la tabla `proformas`
        $stmt = $pdo->prepare("UPDATE proformas SET total = :total WHERE id = :id");
        if (!$stmt->execute(['total' => $totalProforma, 'id' => $proformaId])) {
            throw new Exception("Error al actualizar el total en `proformas`: " . implode(", ", $stmt->errorInfo()));
        }

        $pdo->commit();

        // Guardar mensaje de éxito en la sesión
        $_SESSION['mensaje_exito'] = "Proforma creada correctamente con ID: $proformaId.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Transacción fallida: " . $e->getMessage();
    }
}

// Función para obtener el precio del producto
function obtenerPrecioProducto($productoId, $pdo)
{
    $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id = :id");
    $stmt->execute(['id' => $productoId]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    return $producto ? $producto['precio'] : null;
}

// Función para verificar el stock del producto
function verificarStock($productoId, $cantidad, $pdo)
{
    $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = :id");
    $stmt->execute(['id' => $productoId]);
    $stock = $stmt->fetchColumn();
    return $stock >= $cantidad;
}

// Función para actualizar el stock del producto
function actualizarStock($productoId, $cantidad, $pdo)
{
    $stmt = $pdo->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
    $stmt->execute(['cantidad' => $cantidad, 'id' => $productoId]);
}

// Mostrar mensaje de éxito si está disponible
if (isset($_SESSION['mensaje_exito'])) {
    echo "<div class='alert alert-success'>{$_SESSION['mensaje_exito']}</div>";
    // Limpiar el mensaje de éxito de la sesión
    unset($_SESSION['mensaje_exito']);
}

// Función para mostrar las proformas agrupadas por fecha
// Función para mostrar las proformas agrupadas por fecha
// Función para mostrar las proformas agrupadas por fecha
function mostrarProformasPorFecha($pdo)
{
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>";
    echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";

    echo "<div class='container mt-4'>
            <h2 class='text-center'>Proformas Generadas</h2>
            <button class='btn btn-primary mb-4' data-bs-toggle='modal' data-bs-target='#modalAgregarProforma'>Agregar Nuevo</button>";

    // Obtener las proformas ordenadas por fecha y hora más recientes
    $fechasQuery = "SELECT DISTINCT DATE(fecha) AS fecha FROM proformas ORDER BY fecha DESC";
    $fechasStmt = $pdo->query($fechasQuery);

    if ($fechasStmt->rowCount() > 0) {
        echo "<div class='accordion' id='accordionProformas'>";
        while ($fecha = $fechasStmt->fetch(PDO::FETCH_ASSOC)) {
            $fechaFormat = $fecha['fecha'];

            echo "<div class='accordion-item'>
                    <h2 class='accordion-header' id='heading-{$fechaFormat}'>
                        <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-{$fechaFormat}' aria-expanded='false' aria-controls='collapse-{$fechaFormat}'>
                            Proformas del Día: {$fechaFormat}
                        </button>
                    </h2>
                    <div id='collapse-{$fechaFormat}' class='accordion-collapse collapse' aria-labelledby='heading-{$fechaFormat}' data-bs-parent='#accordionProformas'>
                        <div class='accordion-body'>";

            // Obtener las proformas para esa fecha, junto con los productos y el tiempo de interacción
            $proformasQuery = "SELECT p.id AS proforma_id, c.nombre AS cliente, p.total, p.tiempo_interaccion, dp.producto_id, pr.nombre AS producto, dp.cantidad, dp.subtotal 
                               FROM proformas p 
                               INNER JOIN clientes c ON p.cliente_id = c.id 
                               LEFT JOIN detalle_proformas dp ON dp.proforma_id = p.id
                               LEFT JOIN productos pr ON dp.producto_id = pr.id
                               WHERE DATE(p.fecha) = :fecha
                               ORDER BY p.fecha DESC";  // Asegura que se muestren las más recientes
            $proformasStmt = $pdo->prepare($proformasQuery);
            $proformasStmt->execute(['fecha' => $fechaFormat]);

            if ($proformasStmt->rowCount() > 0) {
                while ($proforma = $proformasStmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<div class='card mb-3'>
                            <div class='card-header'>
                                <h6>Proforma ID: {$proforma['proforma_id']}</h6>
                                <p>Cliente: {$proforma['cliente']}</p>
                                <p>Total: S/ " . number_format($proforma['total'], 2) . "</p>
                                <p>Tiempo de Interacción: " . gmdate("H:i:s", $proforma['tiempo_interaccion']) . "</p>
                            </div>
                            <div class='card-body'>
                                <h5>Productos:</h5>";

                    // Mostrar los productos relacionados con la proforma
                    echo "<ul class='list-group'>";
                    $productosQuery = "SELECT pr.nombre AS producto, dp.cantidad, dp.subtotal
                                       FROM detalle_proformas dp
                                       INNER JOIN productos pr ON dp.producto_id = pr.id
                                       WHERE dp.proforma_id = :proforma_id";
                    $productosStmt = $pdo->prepare($productosQuery);
                    $productosStmt->execute(['proforma_id' => $proforma['proforma_id']]);

                    while ($producto = $productosStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<li class='list-group-item'>
                                Producto: {$producto['producto']}<br>
                                Cantidad: {$producto['cantidad']}<br>
                                Subtotal: S/ " . number_format($producto['subtotal'], 2) . "
                              </li>";
                    }
                    echo "</ul></div></div>";
                }
            } else {
                echo "<p>No se encontraron proformas para esta fecha.</p>";
            }

            echo "</div></div></div>";
        }
        echo "</div>";
    } else {
        echo "<p class='text-center'>No se encontraron proformas.</p>";
    }

    echo "</div>";

    incluirModalAgregarProforma($pdo);
}

// Función para incluir el modal de agregar proformas
function incluirModalAgregarProforma($pdo)
{
    echo "<div class='modal fade' id='modalAgregarProforma' tabindex='-1' aria-labelledby='modalAgregarProformaLabel' aria-hidden='true'>
            <div class='modal-dialog modal-lg'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' id='modalAgregarProformaLabel'>Agregar Nueva Proforma</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body'>";

    include 'formulario_proforma.php';

    echo "</div></div></div></div>";
}

// Mostrar las proformas agrupadas por fecha
mostrarProformasPorFecha($pdo);
?>

<script>
    let startTime;

    document.getElementById('modalAgregarProforma').addEventListener('shown.bs.modal', function () {
        startTime = Date.now();
    });

    function calcularTiempoInteraccion() {
        const endTime = Date.now();
        const interactionTime = Math.round((endTime - startTime) / 1000);
        document.getElementById('tiempo_interaccion').value = interactionTime;
    }
</script>
