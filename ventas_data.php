<?php
include 'ventas.php';

$query = "SELECT DATE(fecha) as fecha, SUM(cantidad) as total FROM ventas GROUP BY DATE(fecha)";
$result = $conexion->query($query);

$data = ['labels' => [], 'data' => []];
while ($row = $result->fetch_assoc()) {
    $data['labels'][] = $row['fecha'];
    $data['data'][] = $row['total'];
}

echo json_encode($data);
?>