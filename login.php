<?php
// Habilitar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
$host = "localhost";
$dbname = "sistema_ventas";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

// Inicia sesión al enviar el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if (empty($user) || empty($pass)) {
        $error = "Por favor, rellene ambos campos.";
    } else {
        // Verificar credenciales
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username");
        $stmt->bindParam(':username', $user);
        $stmt->execute();

        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data && password_verify($pass, $user_data['password_hash'])) {
            session_start();
            $_SESSION['username'] = $user_data['username'];
            header("Location: principal.php");
            exit;
        } else {
            $error = "Nombre de usuario o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">
    <div class="container mt-5">
        <form method="POST" class="p-5 border rounded shadow" style="max-width: 500px; margin: auto; background: white;">
            <div class="text-center mb-4">
                <!-- Logo del carrito -->
                <img src="https://cdn-icons-png.flaticon.com/512/1170/1170576.png" alt="Carrito de Ventas" style="width: 200px; height: 200px;">
                <!-- Título -->
                <h2 class="mt-3">Sistema de Ventas</h2>
            </div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="mb-4">
                <label for="username" class="form-label">Usuario</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Ingresar</button>
            </div>
        </form>
    </div>
</body>
</html>
