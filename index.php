<!DOCTYPE html> 
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ventas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconos de Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        header {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
        }
        header h1 {
            text-align: center;
            font-size: 2.5rem;
        }
        nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        nav ul li {
            margin: 0 15px;
        }
        nav ul li a {
            text-decoration: none;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        nav ul li a:hover {
            background-color: #495057;
        }
        main {
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 15px 0;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<header style="display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; background-color: #343a40; color: white;">
    <h1 style="margin: 0; font-size: 24px; display: flex; align-items: center;">
        <i class="fas fa-shopping-cart" style="margin-right: 10px;"></i> Sistema de Ventas
    </h1>
    <nav style="flex-grow: 1; text-align: center;">
        <ul style="list-style: none; margin: 0; padding: 0; display: inline-flex; gap: 40px;">
            <li><a href="?action=clientes" style="color: white; text-decoration: none;">Clientes</a></li>
            <li><a href="?action=proformas" style="color: white; text-decoration: none;">Proformas</a></li>
            <li><a href="?action=facturacion" style="color: white; text-decoration: none;">Compras</a></li>
            <li><a href="?action=productos" style="color: white; text-decoration: none;">Productos</a></li>
            <li><a href="?action=dashboard" style="color: white; text-decoration: none;">Dashboard</a></li>
        </ul>
    </nav>
</header>

    <main>
        <?php
        // Incluir el código del sistema de ventas que ya tienes
        require 'ventas.php';
        ?>
    </main>
    <footer>
        <p>&copy; 2025 Sistema de Ventas - Empresa de Comercialización de Productos Tecnológicos</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>