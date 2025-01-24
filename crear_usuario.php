<?php
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

// Crear un usuario
$nuevo_usuario = 'admin';
$nueva_contraseña = 'admin123';
$hash_contraseña = password_hash($nueva_contraseña, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO usuarios (username, password_hash) VALUES (:username, :password_hash)");
$stmt->bindParam(':username', $nuevo_usuario);
$stmt->bindParam(':password_hash', $hash_contraseña);

if ($stmt->execute()) {
    echo "Usuario creado exitosamente.";
} else {
    echo "Error al crear el usuario.";
}
?>
