<?


// Conexión a la base de datos
$host = "localhost";
$dbname = "sistema_ventas";
$username_db = "root";
$password_db = "";

$query = "INSERT INTO clientes (nombre, email, telefono, direccion, tiempo_interaccion) 
          VALUES ('Prueba', 'prueba@example.com', '123456789', 'Direccion de prueba', 12.34)";
$stmt = $pdo->prepare($query);
if ($stmt->execute()) {
    echo "Inserción exitosa.";
} else {
    print_r($stmt->errorInfo());
}