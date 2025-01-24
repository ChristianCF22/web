<?php
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
?>

<form action="consulta_proformas.php" method="POST">
    <!-- Otros campos del formulario -->
    <div class="mb-3">
        <label for="cliente_id" class="form-label">Cliente</label>
        <select name="cliente_id" id="cliente_id" class="form-control" required>
            <option value="" disabled selected>Seleccione un cliente</option>
            <?php
            // Obtener clientes de la base de datos
            $clientes = $pdo->query("SELECT id, nombre FROM clientes")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($clientes as $cliente) {
                echo "<option value='{$cliente['id']}'>{$cliente['nombre']}</option>";
            }
            ?>
        </select>
    </div>

    <!-- Contenedor para los productos -->
    <div id="productos-container">
        <div class="producto-row mb-3">
            <label for="producto_id" class="form-label">Producto</label>
            <select name="producto_id[]" class="form-control producto_id" required>
                <option value="" disabled selected>Seleccione un producto</option>
                <?php
                // Obtener productos de la base de datos
                $productos = $pdo->query("SELECT id, nombre, precio, stock FROM productos")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($productos as $producto) {
                    echo "<option value='{$producto['id']}'>{$producto['nombre']} - S/{$producto['precio']} (Stock: {$producto['stock']})</option>";
                }
                ?>
            </select>

            <label for="cantidad" class="form-label">Cantidad</label>
            <input type="number" name="cantidad[]" class="form-control cantidad" placeholder="Cantidad" min="1" required>
        </div>
    </div>

    <!-- Botón para agregar más productos -->
    <button type="button" id="agregarProducto" class="btn btn-secondary">Agregar Producto</button>

    <!-- Campo oculto para el tiempo de interacción -->
    <input type="hidden" id="tiempo_interaccion" name="tiempo_interaccion" value="0">

    <div class="d-flex justify-content-between align-items-center mt-3">
        <button type="submit" class="btn btn-primary" id="btnGenerarProforma">Generar Proforma</button>
    </div>
</form>

<script>
    let startTime;

    // Captura cuando el modal se muestra para iniciar el tiempo
    document.getElementById('modalAgregarProforma').addEventListener('shown.bs.modal', function () {
        startTime = Date.now(); // Inicia el cronómetro
    });

    // Función para calcular el tiempo de interacción antes de enviar el formulario
    document.getElementById('btnGenerarProforma').addEventListener('click', function(event) {
        const endTime = Date.now();
        const interactionTime = Math.round((endTime - startTime) / 1000); // Tiempo en segundos
        document.getElementById('tiempo_interaccion').value = interactionTime; // Asigna el valor al campo oculto
    });

    // Función para agregar un nuevo producto
    document.getElementById('agregarProducto').addEventListener('click', function() {
        const productosContainer = document.getElementById('productos-container');

        // Crear una nueva fila para el producto y la cantidad
        const nuevaFila = document.createElement('div');
        nuevaFila.classList.add('producto-row', 'mb-3');

        // Agregar el campo para seleccionar el producto
        nuevaFila.innerHTML = `
            <label for="producto_id" class="form-label">Producto</label>
            <select name="producto_id[]" class="form-control producto_id" required>
                <option value="" disabled selected>Seleccione un producto</option>
                <?php
                // Obtener productos de la base de datos
                $productos = $pdo->query("SELECT id, nombre, precio, stock FROM productos")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($productos as $producto) {
                    echo "<option value='{$producto['id']}'>{$producto['nombre']} - S/{$producto['precio']} (Stock: {$producto['stock']})</option>";
                }
                ?>
            </select>

            <label for="cantidad" class="form-label">Cantidad</label>
            <input type="number" name="cantidad[]" class="form-control cantidad" placeholder="Cantidad" min="1" required>
        `;
        
        // Añadir la nueva fila al contenedor de productos
        productosContainer.appendChild(nuevaFila);
    });
</script>
