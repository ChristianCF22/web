<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar la sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
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

// Inicio del cronómetro al cargar el módulo
$inicioModulo = microtime(true);

function generarFactura($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inicioBackend = microtime(true); // Inicio del tiempo de ejecución del backend

        // Capturar el tiempo de interacción desde el frontend
        $tiempoInteraccion = isset($_POST['tiempo_interaccion']) ? (float)$_POST['tiempo_interaccion'] : 0;

        $clienteId = filter_var($_POST['cliente_id'], FILTER_VALIDATE_INT);
        $productos = $_POST['producto_id'];
        $cantidades = $_POST['cantidad'];
        $nombreCliente = "Desconocido";
        $tipoDocumento = $_POST['tipo_documento'];
        $totalFactura = 0;

        try {
            // Iniciar transacción
            $conexion->beginTransaction();

            // Obtener el nombre del cliente
            $queryCliente = "SELECT nombre FROM clientes WHERE id = :clienteId";
            $stmt = $conexion->prepare($queryCliente);
            $stmt->bindParam(':clienteId', $clienteId, PDO::PARAM_INT);
            $stmt->execute();
            $nombreCliente = $stmt->fetchColumn();
            if (!$nombreCliente) {
                $nombreCliente = "Cliente no encontrado"; // Asignar un valor predeterminado
            }

            // Obtener el último número de documento
            $ultimoNumero = 0; // Inicializar
            $queryUltimoNumero = "SELECT MAX(numero_documento) AS ultimo_numero FROM facturas WHERE tipo_documento = :tipoDocumento";
            $stmt = $conexion->prepare($queryUltimoNumero);
            $stmt->bindParam(':tipoDocumento', $tipoDocumento, PDO::PARAM_STR);
            $stmt->execute();
            $ultimoNumero = $stmt->fetchColumn();

            $nuevoNumero = $ultimoNumero + 1;

            // Insertar factura
            $queryFactura = "INSERT INTO facturas (cliente_id, tipo_documento, numero_documento, total, tiempo_interaccion) VALUES (:clienteId, :tipoDocumento, :nuevoNumero, :totalFactura, :tiempoInteraccion)";
            $stmt = $conexion->prepare($queryFactura);
            $stmt->bindParam(':clienteId', $clienteId, PDO::PARAM_INT);
            $stmt->bindParam(':tipoDocumento', $tipoDocumento, PDO::PARAM_STR);
            $stmt->bindParam(':nuevoNumero', $nuevoNumero, PDO::PARAM_INT);
            $stmt->bindParam(':totalFactura', $totalFactura, PDO::PARAM_STR);
            $stmt->bindParam(':tiempoInteraccion', $tiempoInteraccion, PDO::PARAM_STR); // Aquí insertamos el tiempo de interacción
            $stmt->execute();
            $facturaId = $conexion->lastInsertId();

            // Procesar productos
            $detallesFactura = []; // Almacenar los detalles para mostrar
            foreach ($productos as $i => $productoId) {
                $productoId = filter_var($productoId, FILTER_VALIDATE_INT);
                $cantidad = filter_var($cantidades[$i], FILTER_VALIDATE_INT);

                // Obtener producto
                $queryProducto = "SELECT nombre, precio, stock FROM productos WHERE id = :productoId";
                $stmt = $conexion->prepare($queryProducto);
                $stmt->bindParam(':productoId', $productoId, PDO::PARAM_INT);
                $stmt->execute();
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$producto) {
                    throw new Exception("Producto con ID $productoId no encontrado.");
                }

                if ($producto['stock'] >= $cantidad) {
                    $subtotal = $producto['precio'] * $cantidad;
                    $totalFactura += $subtotal;

                    // Insertar detalle de factura
                    $queryDetalle = "INSERT INTO detalle_facturas (factura_id, producto_id, cantidad, precio, subtotal) VALUES (:facturaId, :productoId, :cantidad, :precio, :subtotal)";
                    $stmtDetalle = $conexion->prepare($queryDetalle);
                    $stmtDetalle->bindParam(':facturaId', $facturaId, PDO::PARAM_INT);
                    $stmtDetalle->bindParam(':productoId', $productoId, PDO::PARAM_INT);
                    $stmtDetalle->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                    $stmtDetalle->bindParam(':precio', $producto['precio'], PDO::PARAM_STR);
                    $stmtDetalle->bindParam(':subtotal', $subtotal, PDO::PARAM_STR);
                    $stmtDetalle->execute();

                    // Actualizar stock
                    $nuevoStock = $producto['stock'] - $cantidad;
                    $queryActualizarStock = "UPDATE productos SET stock = :nuevoStock WHERE id = :productoId";
                    $stmtActualizar = $conexion->prepare($queryActualizarStock);
                    $stmtActualizar->bindParam(':nuevoStock', $nuevoStock, PDO::PARAM_INT);
                    $stmtActualizar->bindParam(':productoId', $productoId, PDO::PARAM_INT);
                    $stmtActualizar->execute();

                    // Agregar detalles para la factura
                    $detallesFactura[] = [
                        'nombreProducto' => $producto['nombre'],
                        'cantidad' => $cantidad,
                        'precio' => $producto['precio'],
                        'subtotal' => $subtotal
                    ];
                } else {
                    throw new Exception("No hay suficiente stock para el producto con ID: $productoId.");
                }
            }

            // Actualizar total de la factura
            $queryActualizarTotal = "UPDATE facturas SET total = :totalFactura WHERE id = :facturaId";
            $stmt = $conexion->prepare($queryActualizarTotal);
            $stmt->bindParam(':totalFactura', $totalFactura, PDO::PARAM_STR);
            $stmt->bindParam(':facturaId', $facturaId, PDO::PARAM_INT);
            $stmt->execute();

            // Confirmar transacción
            $conexion->commit();

            $finBackend = microtime(true); // Fin del tiempo de ejecución del backend
            $tiempoBackend = $finBackend - $inicioBackend;
            $tiempoTotal = $tiempoInteraccion + $tiempoBackend;

            // Mostrar resultados y detalles de la factura
            echo "<div style='font-family: Arial, sans-serif; margin: 20px; padding: 20px; background-color: #f9f9f9; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>";
            echo "<h1 style='color: #28a745; text-align: center;'>Factura generada con éxito</h1>";
            echo "<p><strong>Número de factura:</strong> $nuevoNumero</p>";
            echo "<p><strong>Cliente:</strong> $nombreCliente</p>";
            echo "<p><strong>Total de la factura:</strong> S/ " . number_format($totalFactura, 2) . "</p>";

            echo "<h3 style='margin-top: 20px; color: #343a40;'>Detalles de la compra</h3>";
            echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                    <thead>
                        <tr style='background-color: #343a40; color: #ffffff;'>
                            <th style='padding: 10px; border: 1px solid #ddd;'>Producto</th>
                            <th style='padding: 10px; border: 1px solid #ddd;'>Cantidad</th>
                            <th style='padding: 10px; border: 1px solid #ddd;'>Precio Unitario</th>
                            <th style='padding: 10px; border: 1px solid #ddd;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>";
            foreach ($detallesFactura as $detalle) {
                echo "<tr style='background-color: #f4f4f4;'>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$detalle['nombreProducto']}</td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$detalle['cantidad']}</td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>S/ " . number_format($detalle['precio'], 2) . "</td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>S/ " . number_format($detalle['subtotal'], 2) . "</td>
                    </tr>";
            }
            echo "    </tbody>
                  </table>";

            echo "<p style='margin-top: 20px;'><strong>Tiempo de proceso de facturación:</strong> " . number_format($tiempoTotal, 6) . " segundos.</p>";

            // Botón para generar el PDF
            echo "<form method='POST' action='descargar_pdf.php' style='margin-top: 20px;'>
                    <input type='hidden' name='factura_id' value='$facturaId'>
                    <input type='hidden' name='numero_documento' value='$nuevoNumero'>
                    <button type='submit' style='background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>Descargar PDF</button>
                  </form>";
            echo "</div>";
        } catch (Exception $e) {
            $conexion->rollback();
            echo "Error al generar la factura: " . $e->getMessage();
        }
    } else {
        // Código para mostrar formulario de generación de factura
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
        <style>
            .form-container {
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                background-color: #f9f9f9;
            }
            .form-title {
                margin-bottom: 20px;
                text-align: center;
                color: #333;
            }
            .product-row {
                margin-bottom: 10px;
            }
            .action-buttons {
                display: flex;
                justify-content: space-between;
            }
        </style>
        <div class='container form-container'>
            <h1 class='form-title'>Generar Compra</h1>
            <form method='POST' action=compras.php  id='formProforma'>
                <div class='mb-3'>
                    <label for='cliente_id' class='form-label'>Cliente:</label>
                    <select name='cliente_id' class='form-select' required>";
                    $clientesQuery = "SELECT id, nombre FROM clientes";
                    $clientesResult = $conexion->query($clientesQuery);
                    while ($cliente = $clientesResult->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$cliente['id']}'>{$cliente['nombre']}</option>";
                    }
                    echo "  </select>
                </div>

                <div class='mb-3'>
                    <label for='tipo_documento' class='form-label'>Tipo de Documento:</label>
                    <select name='tipo_documento' class='form-select' required>
                        <option value='factura'>Factura</option>
                        <option value='boleta'>Boleta</option>
                    </select>
                </div>

                <h3 class='mt-4'>Productos</h3>
                <div id='productos'>
                    <div class='row product-row'>
                        <div class='col-md-6'>
                            <label for='producto_id[]' class='form-label'>Producto:</label>
                            <select name='producto_id[]' class='form-select' required>";
                    $productosQuery = "SELECT id, nombre, precio FROM productos";
                    $productosResult = $conexion->query($productosQuery);
                    while ($producto = $productosResult->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$producto['id']}'>{$producto['nombre']} - S/ " . number_format($producto['precio'], 2) . "</option>";
                    }
                    echo "          </select>
                        </div>
                        <div class='col-md-3'>
                            <label for='cantidad[]' class='form-label'>Cantidad:</label>
                            <input type='number' name='cantidad[]' class='form-control' min='1' required>
                        </div>
                        <div class='col-md-3 d-flex align-items-end'>
                            <button type='button' class='btn btn-danger remove-product-btn'>Eliminar</button>
                        </div>
                    </div>
                </div>
                <div class='action-buttons'>
                    <button type='button' class='btn btn-success add-product-btn'>Añadir Producto</button>
                    <button type='submit' class='btn btn-primary'>Generar Compra</button>
                </div>
                <input type='hidden' name='tiempo_interaccion' id='tiempo_interaccion'>
            </form>
        </div>
        <script>
            // Agregar eventos para añadir y eliminar productos
            document.querySelector('.add-product-btn').addEventListener('click', function() {
                const productRow = document.querySelector('.product-row').cloneNode(true);
                document.getElementById('productos').appendChild(productRow);
            });

            document.querySelector('#productos').addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-product-btn')) {
                    e.target.closest('.product-row').remove();
                }
            });

            // Capturar tiempo de interacción
            let tiempoInicio = performance.now();
            document.getElementById('formProforma').addEventListener('submit', function() {
                let tiempoFin = performance.now();
                document.getElementById('tiempo_interaccion').value = (tiempoFin - tiempoInicio) / 1000; // Segundos
            });
        </script>";
    }
}

// Llamar a la función
generarFactura($pdo);
?>
