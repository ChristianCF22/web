<?php
require('fpdf/fpdf.php');

ob_start(); // Inicia el búfer de salida

try {
    // Configura y genera el contenido del PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Factura Generada');

    // Ejemplo de contenido
    $facturaId = $_POST['factura_id'];
    $numeroDocumento = $_POST['numero_documento'];
    //$pdf->Ln(10);
   // $pdf->Cell(40, 10, "Factura ID: $facturaId");
    $pdf->Ln(10);
    $pdf->Cell(40, 10, "Numero Documento: $numeroDocumento");

    ob_end_clean(); // Limpia cualquier salida previa
    $pdf->Output('D', "Factura_$facturaId.pdf");
} catch (Exception $e) {
    ob_end_clean(); // Asegura que no haya salida pendiente
    echo "Error generando el PDF: " . $e->getMessage();
}
?>
