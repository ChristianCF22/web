<?php
// Archivo: proformas.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
 //   header('Location: consulta_proformas.php'); // Redirige al login si no ha iniciado sesión
    exit();
}

// Conexión a la base de datos (modificar según tu configuración)
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

function generarProforma($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inicio = microtime(true); // Inicia el cronómetro para el backend

        // Capturar el tiempo de interacción desde el frontend
        $tiempoInteraccion = isset($_POST['tiempo_interaccion']) ? (float)$_POST['tiempo_interaccion'] : 0;

        $clienteId = $_POST['cliente_id'];
        $productos = $_POST['producto_id'];
        $cantidades = $_POST['cantidad'];
        $totalProforma = 0;

        $pdo->beginTransaction(); // Inicia la transacción
        try {
            // Insertar proforma
            $queryProforma = "INSERT INTO proformas (cliente_id, total) VALUES (:cliente_id, :total)";
            $stmt = $pdo->prepare($queryProforma);
            $stmt->execute(['cliente_id' => $clienteId, 'total' => $totalProforma]);
            $proformaId = $pdo->lastInsertId();

            // Cargar todos los productos de una vez
            $productoIds = implode(',', array_map('intval', $productos));
            $queryProductos = "SELECT id, precio, stock FROM productos WHERE id IN ($productoIds)";
            $resultado = $pdo->query($queryProductos);

            $productosData = [];
            while ($producto = $resultado->fetch(PDO::FETCH_ASSOC)) {
                $productosData[$producto['id']] = $producto;
            }

            foreach ($productos as $index => $productoId) {
                $productoId = (int)$productoId;
                $cantidad = (int)$cantidades[$index];

                if (!isset($productosData[$productoId])) {
                    throw new Exception("Producto con ID $productoId no encontrado.");
                }

                $precio = $productosData[$productoId]['precio'];
                $stock = $productosData[$productoId]['stock'];

                if ($stock < $cantidad) {
                    throw new Exception("Stock insuficiente para el producto con ID: $productoId.");
                }

                $subtotal = $precio * $cantidad;
                $totalProforma += $subtotal;

                $queryDetalle = "INSERT INTO detalle_proformas (proforma_id, producto_id, cantidad, precio, subtotal) 
                                 VALUES (:proforma_id, :producto_id, :cantidad, :precio, :subtotal)";
                $stmt = $pdo->prepare($queryDetalle);
                $stmt->execute([
                    'proforma_id' => $proformaId,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal,
                ]);
            }

            $queryUpdateProforma = "UPDATE proformas SET total = :total WHERE id = :id";
            $stmt = $pdo->prepare($queryUpdateProforma);
            $stmt->execute(['total' => $totalProforma, 'id' => $proformaId]);

            $pdo->commit(); // Confirmar la transacción

            $fin = microtime(true); // Detener el cronómetro para el backend
            $tiempoEjecucion = $fin - $inicio;

            // Tiempo total de interacción y backend
            $tiempoTotal = $tiempoInteraccion + $tiempoEjecucion;

            echo "<div class='alert alert-success'>Proforma generada con éxito.</div>";
            echo "<p>Total de la proforma: <strong>S/ " . number_format($totalProforma, 2) . "</strong></p>";
            echo "<p>Tiempo de proceso de generación de proformas: " . number_format($tiempoTotal, 6) . " segundos.</p>";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>Error al generar la proforma: " . $e->getMessage() . "</div>";
        }
    } else {
        // Incluir Bootstrap y CSS personalizado
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
              <style>
                  .form-container {
                      max-width: 800px;
                      margin: 50px auto;
                      padding: 20px;
                      background: #ffffff;
                      border-radius: 10px;
                      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                  }
                  .form-title {
                      text-align: center;
                      margin-bottom: 20px;
                      color: #333;
                  }
                  .product-item {
                      display: flex;
                      gap: 10px;
                      margin-bottom: 10px;
                      align-items: center;
                  }
              </style>";

        // Mostrar el formulario con Bootstrap
        echo "<div class='form-container'>
                <h2 class='form-title'>Generar Proforma</h2>
                <form method='POST' id='formProforma'>
                    <div class='mb-3'>
                        <label for='cliente_id' class='form-label'>Cliente</label>
                        <select name='cliente_id' id='cliente_id' class='form-select' required>";
        $clientesQuery = "SELECT id, nombre FROM clientes";
        $clientesResult = $pdo->query($clientesQuery);
        while ($cliente = $clientesResult->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$cliente['id']}'>{$cliente['nombre']}</option>";
        }
        echo "      </select>
                    </div>
                    <h4>Productos</h4>
                    <div id='productos'>
                        <div class='product-item'>
                            <select name='producto_id[]' class='form-select' required>";
        $productosQuery = "SELECT id, nombre, precio FROM productos";
        $productosResult = $pdo->query($productosQuery);
        while ($producto = $productosResult->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$producto['id']}'>{$producto['nombre']} - S/ " . number_format($producto['precio'], 2) . "</option>";
        }
        echo "          </select>
                            <input type='number' name='cantidad[]' class='form-control' min='1' placeholder='Cantidad' required>
                            <button type='button' class='btn btn-secondary' onclick='agregarProducto()'>Añadir</button>
                        </div>
                    </div>
                    <input type='hidden' id='tiempo_interaccion' name='tiempo_interaccion'>
                    <button type='submit' class='btn btn-primary w-100 mt-4'>Generar Proforma</button>
                </form>
              </div>";

        // Script para manejar el tiempo de interacción y añadir productos
        echo "<script>
                let inicioModulo = Date.now();

                document.querySelector('form').addEventListener('submit', function () {
                    let finInteraccion = Date.now();
                    let tiempoInteraccion = (finInteraccion - inicioModulo) / 1000;
                    document.getElementById('tiempo_interaccion').value = tiempoInteraccion;
                });

                function agregarProducto() {
                    const contenedor = document.getElementById('productos');
                    const nuevoProducto = contenedor.firstElementChild.cloneNode(true);
                    nuevoProducto.querySelector('input').value = '';
                    contenedor.appendChild(nuevoProducto);
                }
              </script>";
    }
}
// Llamar a la función
generarProforma($pdo);
?>
