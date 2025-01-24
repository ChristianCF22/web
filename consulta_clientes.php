<?php  
// Mostrar errores PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
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

// Procesar envío del formulario
// Procesar envío del formulario
// Verificar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $tiempoInteraccion = isset($_POST['tiempo_interaccion']) ? floatval($_POST['tiempo_interaccion']) : 0.0;

    // Debug: Verificar valores recibidos
    error_log("POST recibido: " . print_r($_POST, true));
    error_log("Tiempo de interacción procesado: " . $tiempoInteraccion);

    try {
        // Guardar en la base de datos
        $query = "INSERT INTO clientes (nombre, email, telefono, direccion, tiempo_interaccion) 
                  VALUES (:nombre, :email, :telefono, :direccion, :tiempo_interaccion)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':tiempo_interaccion', $tiempoInteraccion);

        if ($stmt->execute()) {
            error_log("Registro exitoso. Tiempo guardado: " . $tiempoInteraccion);
            header('Location: clientes.php?tiempo=' . $tiempoInteraccion);
            exit();
        } else {
            $error = $stmt->errorInfo();
            error_log("Error al registrar cliente: " . implode(", ", $error));
        }
    } catch (PDOException $e) {
        error_log("Error de base de datos: " . $e->getMessage());
    }
}



// Mostrar lista de clientes
function mostrarClientes($pdo) {
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
          <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
          <style>
              body {
                  background-color: #f8f9fa;
                  font-family: Arial, sans-serif;
              }
              .container {
                  max-width: 90%;
                  margin: 50px auto;
                  padding: 20px;
                  background: #ffffff;
                  border-radius: 10px;
                  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
              }
              .table th, .table td {
                  text-align: center;
                  vertical-align: middle;
              }
              .table th {
                  background-color: #343a40;
                  color: #ffffff;
              }
              .table td {
                  padding: 15px;
                  font-size: 16px;
              }
          </style>";

    echo "<div class='container'>
            <h2 class='text-center mb-4'>Clientes Registrados</h2>
            <button type='button' class='btn btn-success mb-3' data-bs-toggle='modal' data-bs-target='#modalAgregarCliente'>
                Agregar Nuevo
            </button>";

    if (isset($_GET['tiempo'])) {
        echo "<div class='alert alert-info'>Tiempo total de registro de cliente: " . htmlspecialchars(number_format($_GET['tiempo'], 2)) . " segundos.</div>";
    }

    $query = "SELECT id, nombre, email, telefono, direccion, tiempo_interaccion FROM clientes";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($clientes) {
        echo "<div class='table-responsive'>
                <table class='table table-bordered'>
                    <thead>
                        <tr>
                            <th style='width: 10%;'>ID</th>
                            <th style='width: 20%;'>Nombre</th>
                            <th style='width: 20%;'>Email</th>
                            <th style='width: 15%;'>Teléfono</th>
                            <th style='width: 25%;'>Dirección</th>
                            <th style='width: 10%;'>Tiempo (s)</th>
                        </tr>
                    </thead>
                    <tbody>";
        foreach ($clientes as $cliente) {
            echo "<tr>
                    <td>{$cliente['id']}</td>
                    <td>{$cliente['nombre']}</td>
                    <td>{$cliente['email']}</td>
                    <td>{$cliente['telefono']}</td>
                    <td>{$cliente['direccion']}</td>
                    <td>" . number_format($cliente['tiempo_interaccion'] ?? 0, 2) . "</td>
                  </tr>";
        }
        echo "</tbody>
              </table>
              </div>";
    } else {
        echo "<div class='alert alert-warning'>No hay clientes registrados.</div>";
    }
    echo "</div>";

    // Modal para agregar nuevo cliente
    echo "<div class='modal fade' id='modalAgregarCliente' tabindex='-1' aria-labelledby='modalAgregarClienteLabel' aria-hidden='true'>
            <div class='modal-dialog'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title' id='modalAgregarClienteLabel'>Registrar Nuevo Cliente</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Cerrar'></button>
                    </div>
                    <form method='post' action='clientes.php' id='formRegistroCliente'>
                        <div class='modal-body'>
                            <div class='mb-3'>
                                <label for='nombre' class='form-label'>Nombre</label>
                                <input type='text' class='form-control' id='nombre' name='nombre' required>
                            </div>
                            <div class='mb-3'>
                                <label for='email' class='form-label'>Email</label>
                                <input type='email' class='form-control' id='email' name='email' required>
                            </div>
                            <div class='mb-3'>
                                <label for='telefono' class='form-label'>Teléfono</label>
                                <input type='text' class='form-control' id='telefono' name='telefono' required>
                            </div>
                            <div class='mb-3'>
                                <label for='direccion' class='form-label'>Dirección</label>
                                <input type='text' class='form-control' id='direccion' name='direccion' required>
                            </div>
                            <!-- Campo oculto para registrar tiempo total -->
                            <input type='hidden' id='tiempo_interaccion' name='tiempo_interaccion'>
                        </div>
                        <div class='modal-footer'>
                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button>
                            <button type='submit' class='btn btn-primary' id='btnGuardar'>Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
          </div>";
}

// Llamar a la función para mostrar clientes
mostrarClientes($pdo);
?>

<script>
    let tiempoInicio = 0;

    // Registrar el tiempo de inicio al abrir el modal
    document.getElementById('modalAgregarCliente').addEventListener('shown.bs.modal', function () {
        tiempoInicio = Date.now();
    });

    // Capturar el tiempo total antes de enviar el formulario
    document.getElementById('formRegistroCliente').addEventListener('submit', function () {
        const tiempoFin = Date.now();
        const tiempoTotal = (tiempoFin - tiempoInicio) / 1000; // Tiempo en segundos
        const tiempoInteraccion = tiempoTotal.toFixed(2);
        document.getElementById('tiempo_interaccion').value = tiempoInteraccion;

        // Debugging: Loguear el tiempo en la consola del navegador
        console.log("Tiempo de interacción enviado:", tiempoInteraccion);
    });
</script>
