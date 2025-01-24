<?php
// Mostrar errores PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {  // Cambia 'usuario' por 'username'
    header('Location: consulta_clientes.php'); // Redirige al login si no ha iniciado sesión
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

// Funcion para registrar clientes
function registrarCliente($pdo) {
    // Inicio del cronómetro al cargar el módulo
    $inicioModulo = microtime(true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inicioBackend = microtime(true); // Inicio del tiempo de ejecución del backend

        // Capturar el tiempo de interacción desde el frontend
        $tiempoInteraccion = isset($_POST['tiempo_interaccion']) ? (float)$_POST['tiempo_interaccion'] : 0;

        // Obtener los datos del formulario
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $tiempoInteraccion = trim($_POST['tiempo_interaccion']);

        // Validar que los campos no estén vacíos
        if (empty($nombre) || empty($email)) {
            echo "<div class='alert alert-danger'>El nombre y el email son obligatorios.</div>";
            return;
        }

        // Verificar si el email ya existe en la base de datos
        $query = "SELECT id FROM clientes WHERE email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "<div class='alert alert-warning'>El email ya está registrado.</div>";
            return;
        }

        // Insertar el cliente en la base de datos
        $queryInsert = "INSERT INTO clientes (nombre, email, telefono, direccion, tiempo_interaccion) VALUES (:nombre, :email, :telefono, :direccion, :tiempo_interaccion)";
        $stmt = $pdo->prepare($queryInsert);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':tiempo_interaccion', $tiempoInteraccion);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Cliente registrado con éxito.</div>";
        } else {
            echo "<div class='alert alert-danger'>Ocurrió un error al registrar al cliente.</div>";
        }

        $finBackend = microtime(true); // Fin del tiempo de ejecución del backend
        $tiempoBackend = $finBackend - $inicioBackend; // Duración en segundos

        // Tiempo total de interacción y backend
        $tiempoTotal = $tiempoInteraccion + $tiempoBackend;

        // Mostrar únicamente el tiempo total
        echo "<p>Tiempo de proceso del registro de cliente: " . number_format($tiempoTotal, 6) . " segundos.</p>";
    } else {
        // Incluir Bootstrap y CSS personalizado
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
              <style>
                  body {
                      background-color: #f8f9fa;
                      font-family: Arial, sans-serif;
                  }
                  .form-container {
                      max-width: 600px;
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
              </style>";

        // Mostrar el formulario de registro con Bootstrap
        echo "<div class='form-container'>
                <h2 class='form-title'>Registro de Cliente</h2>
                <form method='POST' id='formRegistro'>
                    <div class='mb-3'>
                        <label for='nombre' class='form-label'>Nombre</label>
                        <input type='text' name='nombre' id='nombre' class='form-control' required>
                    </div>
                    <div class='mb-3'>
                        <label for='email' class='form-label'>Correo Electrónico</label>
                        <input type='email' name='email' id='email' class='form-control' required>
                    </div>
                    <div class='mb-3'>
                        <label for='telefono' class='form-label'>Teléfono</label>
                        <input type='tel' name='telefono' id='telefono' class='form-control' pattern='[0-9]{9}' 
                               title='Debe contener 9 dígitos' placeholder='123456789'>
                    </div>
                    <div class='mb-3'>
                        <label for='direccion' class='form-label'>Dirección</label>
                        <textarea name='direccion' id='direccion' class='form-control' rows='3'></textarea>
                    </div>
                    <input type='hidden' id='tiempo_interaccion' name='tiempo_interaccion'>
                    <button type='submit' class='btn btn-primary w-100'>Registrar</button>
                </form>
              </div>";

        // Script para calcular el tiempo de interacción en el frontend
        echo "<script>
                let inicioModulo = Date.now();
                document.getElementById('formRegistro').addEventListener('submit', function () {
                    let finInteraccion = Date.now();
                    let tiempoInteraccion = (finInteraccion - inicioModulo) / 1000; // Tiempo en segundos
                    document.getElementById('tiempo_interaccion').value = tiempoInteraccion;
                });
              </script>";
    }
}

// Llamar a la función de registrar cliente
registrarCliente($pdo);

?>