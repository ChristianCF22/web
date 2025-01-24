<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) { 
    header('Location: login.php'); 
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

// Manejar la solicitud POST para agregar un producto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $stock = $_POST['stock'];
    $precio = $_POST['precio'];

    // Validar que los campos no estén vacíos
    if (!empty($nombre) && !empty($stock) && !empty($precio)) {
        $query = "INSERT INTO productos (nombre, stock, precio) VALUES (:nombre, :stock, :precio)";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([':nombre' => $nombre, ':stock' => $stock, ':precio' => $precio]);
            $mensaje = "Producto agregado exitosamente.";
        } catch (PDOException $e) {
            $mensaje = "Error al agregar el producto: " . $e->getMessage();
        }
    } else {
        $mensaje = "Por favor, completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Agregar Nuevo Producto</h1>

    <!-- Mostrar mensaje de éxito o error -->
    <?php if (isset($mensaje)): ?>
        <div class="alert alert-info text-center">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form action="agregar_producto.php" method="POST" class="mt-4">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Producto</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stock</label>
            <input type="number" class="form-control" id="stock" name="stock" min="0" required>
        </div>
        <div class="mb-3">
            <label for="precio" class="form-label">Precio (S/)</label>
            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Guardar Producto</button>
            <a href="productos.php" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>
</body>
</html>
