<?php  
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header('Location: login.php'); // Redirige al login si no ha iniciado sesión
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Ventas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            padding-top: 70px; /* Espacio para la cabecera fija */
        }
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: #343a40;
            color: white;
            z-index: 1030;
            padding: 10px 0;
        }
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        .header .welcome {
            font-size: 1rem;
        }
        .btn i {
            margin-right: 8px;
        }
        .content {
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="header text-center">
    <h1>Sistema de Gestión de Ventas</h1>
    <p class="welcome">Bienvenido, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!</p>
</div>


<div class="header bg-dark text-white d-flex justify-content-between align-items-center px-3 py-2">
    <!-- "Bienvenido" alineado a la izquierda -->
    <p class="welcome mb-0">Bienvenido, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!</p>
    
    <!-- Módulos centrados -->
    <div class="d-flex justify-content-center flex-wrap">
        <button class="btn btn-primary m-1" id="btn-clientes">
            <i class="fas fa-user-friends"></i> Clientes
        </button>
        <button class="btn btn-success m-1" id="btn-proformas">
            <i class="fas fa-file-invoice"></i> Proformas
        </button>
        <button class="btn btn-warning m-1" id="btn-compras">
            <i class="fas fa-shopping-cart"></i> Compras
        </button>
        <button class="btn btn-info m-1" id="btn-productos">
            <i class="fas fa-box"></i> Productos
        </button>
        <button class="btn btn-dark m-1" id="btn-dashboard">
            <i class="fas fa-chart-line"></i> Reportes
        </button>
    </div>
    
    <!-- Botón de "Cerrar Sesión" alineado a la derecha -->
    <button class="btn btn-danger" id="btn-logout">
        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
    </button>
</div>


    <div id="module-content" class="content">
        <!-- El contenido dinámico se cargará aquí -->
    </div>
</div>

<script>
    $(document).ready(function () {
        function showLoading() {
            $("#module-content").html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>');
        }

        function loadModuleContent(module) {
            const urlParams = new URLSearchParams(window.location.search);
            const filtro = urlParams.get('filtro') || '';
            const fechaInicio = urlParams.get('fechaInicio') || '';
            const fechaFin = urlParams.get('fechaFin') || '';

            showLoading();

            $.ajax({
                url: module + ".php",
                method: "GET",
                data: { filtro, fechaInicio, fechaFin },
                success: function (response) {
                    $("#module-content").html(response);

                    const scripts = $("#module-content").find("script");
                    scripts.each(function () {
                        eval($(this).text());
                    });
                },
                error: function () {
                    $("#module-content").html('<div class="alert alert-danger">No se pudo cargar el módulo seleccionado. Inténtelo nuevamente.</div>');
                }
            });
        }

        $("#btn-clientes").click(() => loadModuleContent("consulta_clientes"));
        $("#btn-proformas").click(() => loadModuleContent("consulta_proformas"));
        $("#btn-compras").click(() => loadModuleContent("consulta_facturas"));
        $("#btn-productos").click(() => loadModuleContent("productos"));
        $("#btn-dashboard").click(() => loadModuleContent("dashboard"));
        $("#btn-logout").click(() => window.location.href = "logout.php");
    });
</script>
</body>
</html>