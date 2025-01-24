<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar si el usuario ha iniciado sesión
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

// Manejar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = ['success' => false, 'message' => ''];

    // Agregar nuevo producto
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        $nombre = $_POST['nombre'];
        $stock = $_POST['stock'];
        $precio = $_POST['precio'];

        if (!empty($nombre) && is_numeric($stock) && is_numeric($precio)) {
            $query = "INSERT INTO productos (nombre, stock, precio) VALUES (:nombre, :stock, :precio)";
            $stmt = $pdo->prepare($query);

            try {
                $stmt->execute([':nombre' => $nombre, ':stock' => $stock, ':precio' => $precio]);
                $response['success'] = true;
                $response['message'] = "Producto agregado exitosamente.";
            } catch (PDOException $e) {
                $response['message'] = "Error al agregar el producto: " . $e->getMessage();
            }
        } else {
            $response['message'] = "Datos inválidos. Verifica los campos.";
        }

        echo json_encode($response);
        exit();
    }

    // Eliminar producto
    if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar') {
        $id = $_POST['id'];

        if (!empty($id)) {
            $query = "DELETE FROM productos WHERE id = :id";
            $stmt = $pdo->prepare($query);

            try {
                $stmt->execute([':id' => $id]);
                $response['success'] = true;
                $response['message'] = "Producto eliminado exitosamente.";
            } catch (PDOException $e) {
                $response['message'] = "Error al eliminar el producto: " . $e->getMessage();
            }
        } else {
            $response['message'] = "ID de producto inválido.";
        }

        echo json_encode($response);
        exit();
    }

    // Actualizar stock
    if (isset($_POST['accion']) && $_POST['accion'] == 'editar') {
        $id = $_POST['id'];
        $nuevoStock = $_POST['stock'];

        if (!empty($id) && is_numeric($nuevoStock)) {
            $query = "UPDATE productos SET stock = :stock WHERE id = :id";
            $stmt = $pdo->prepare($query);

            try {
                $stmt->execute([':stock' => $nuevoStock, ':id' => $id]);
                $response['success'] = true;
                $response['message'] = "Stock actualizado correctamente.";
            } catch (PDOException $e) {
                $response['message'] = "Error al actualizar el stock: " . $e->getMessage();
            }
        } else {
            $response['message'] = "Datos inválidos. Verifica los campos.";
        }

        echo json_encode($response);
        exit();
    }
}

// Función para listar productos
function listarProductos($conexion)
{
    $query = "SELECT * FROM productos";
    $result = $conexion->query($query);

    while ($producto = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
                <td>{$producto['id']}</td>
                <td>{$producto['nombre']}</td>
                <td>{$producto['stock']}</td>
                <td>S/ " . number_format($producto['precio'], 2) . "</td>
                <td>
                    <button class='btn btn-warning btn-editar' data-id='{$producto['id']}' data-stock='{$producto['stock']}'>Editar</button>
                    <button class='btn btn-danger btn-eliminar' data-id='{$producto['id']}'>Eliminar</button>
                </td>
              </tr>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Productos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Listado de Productos</h1>

    <div id="mensaje-accion" class="alert d-none" role="alert"></div>

    <div class="text-start mb-3">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#agregarProductoModal">
            Agregar Nuevo
        </button>
    </div>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Nombre</th>
                <th scope="col">Stock</th>
                <th scope="col">Precio (S/)</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-productos">
            <?php listarProductos($pdo); ?>
        </tbody>
    </table>

    <!-- Modal para editar stock -->
    <div class="modal fade" id="editarProductoModal" tabindex="-1" aria-labelledby="editarProductoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarProductoModalLabel">Editar Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="form-editar-producto">
                    <input type="hidden" id="editar-id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editar-stock" class="form-label">Nuevo Stock</label>
                            <input type="number" class="form-control" id="editar-stock" name="stock" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para agregar producto -->
    <div class="modal fade" id="agregarProductoModal" tabindex="-1" aria-labelledby="agregarProductoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarProductoModalLabel">Agregar Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="form-agregar-producto">
                    <div class="modal-body">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function mostrarMensaje(tipo, mensaje) {
        const contenedor = $("#mensaje-accion");
        contenedor.removeClass("d-none alert-success alert-danger").addClass(`alert-${tipo}`).text(mensaje);
        setTimeout(() => contenedor.addClass("d-none"), 3000);
    }

    $(document).ready(function () {
        // Agregar nuevo producto
        $("#form-agregar-producto").off("submit").on("submit", function (e) {
            e.preventDefault();

            $.post("productos.php", $(this).serialize() + "&ajax=1", function (response) {
                if (response.success) {
                    $("#tabla-productos").load("productos.php #tabla-productos > *");
                    mostrarMensaje("success", response.message);
                    $("#agregarProductoModal").modal("hide");
                    $("#form-agregar-producto")[0].reset();
                } else {
                    mostrarMensaje("danger", response.message);
                }
            }, "json").fail(function () {
                mostrarMensaje("danger", "Error al procesar la solicitud.");
            });
        });

        // Eliminar producto
        $(document).on("click", ".btn-eliminar", function () {
            const id = $(this).data("id");

            if (confirm("¿Estás seguro de que deseas eliminar este producto?")) {
                $.post("productos.php", { id, accion: "eliminar" }, function (response) {
                    if (response.success) {
                        $("#tabla-productos").load("productos.php #tabla-productos > *");
                        mostrarMensaje("success", response.message);
                    } else {
                        mostrarMensaje("danger", response.message);
                    }
                }, "json").fail(function () {
                    mostrarMensaje("danger", "Error al procesar la solicitud.");
                });
            }
        });

        // Abrir modal para editar stock
        $(document).on("click", ".btn-editar", function () {
            const id = $(this).data("id");
            const stock = $(this).data("stock");

            $("#editar-id").val(id);
            $("#editar-stock").val(stock);
            $("#editarProductoModal").modal("show");
        });

        // Editar producto (stock)
        $("#form-editar-producto").off("submit").on("submit", function (e) {
            e.preventDefault();

            $.post("productos.php", $(this).serialize() + "&accion=editar", function (response) {
                if (response.success) {
                    $("#tabla-productos").load("productos.php #tabla-productos > *");
                    mostrarMensaje("success", response.message);
                    $("#editarProductoModal").modal("hide");
                } else {
                    mostrarMensaje("danger", response.message);
                }
            }, "json").fail(function () {
                mostrarMensaje("danger", "Error al procesar la solicitud.");
            });
        });
    });
</script>
</body>
</html>
