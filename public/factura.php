<?php
// Incluye TCPDF
require_once('../libs/tcpdf.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoge datos del formulario
    $nombre = $_POST['nombre'];
    $cedula = $_POST['cedula'];
    $fecha = $_POST['fecha'];
    $nombre_local = $_POST['nombre_local'];
    $nit = $_POST['nit'];
    $numero_factura = $_POST['numero_factura'];
    $garantia = $_POST['garantia'];
    $terminos = $_POST['terminos'];

    // Recoge los productos
    $productos = [];
    foreach ($_POST['productos'] as $producto) {
        if (!empty($producto['nombre']) && !empty($producto['descripcion'])) {
            $productos[] = $producto;
        }
    }

    // Crea el documento PDF
    $pdf = new TCPDF();
    $pdf->AddPage();

    // Establece el título
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'Factura', 0, 1, 'C');

    // Información de la factura
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Nombre: ' . $nombre, 0, 1);
    $pdf->Cell(0, 10, 'Cédula: ' . $cedula, 0, 1);
    $pdf->Cell(0, 10, 'Fecha: ' . $fecha, 0, 1);
    $pdf->Cell(0, 10, 'Nombre del Local: ' . $nombre_local, 0, 1);
    $pdf->Cell(0, 10, 'NIT: ' . $nit, 0, 1);
    $pdf->Cell(0, 10, 'Número de Factura: ' . $numero_factura, 0, 1);

    // Tabla de productos
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Productos', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 10, 'Nombre', 1, 0, 'C');
    $pdf->Cell(60, 10, 'Descripción', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Precio Unitario', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Precio Total', 1, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    foreach ($productos as $producto) {
        $pdf->Cell(50, 10, $producto['nombre'], 1);
        $pdf->Cell(60, 10, $producto['descripcion'], 1);
        $pdf->Cell(30, 10, $producto['cantidad'], 1);
        $pdf->Cell(30, 10, $producto['precio_unitario'], 1);
        $pdf->Cell(30, 10, $producto['precio_total'], 1);
        $pdf->Ln();
    }

    // Apartado de garantía y términos
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Garantía', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->MultiCell(0, 10, $garantia);
    $pdf->Ln();

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Términos y Condiciones', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->MultiCell(0, 10, $terminos);
    $pdf->Ln(60);
    $pdf->Cell(0, 10, 'firma: _________________________________________', 0, 1);

    // Cierra y envía el documento PDF
    $pdf->Output('factura.pdf', 'I');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Factura</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #f4f7f6;
            color: #6c8181;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .form-control {
            border: 1px solid #6c8181;
            border-radius: 4px;
        }
        .btn-primary {
            background-color: #6c8181;
            border-color: #6c8181;
        }
        .btn-primary:hover {
            background-color: #5a6e6e;
            border-color: #5a6e6e;
        }
        .btn-danger {
            background-color: #d9534f;
            border-color: #d9534f;
        }
        .btn-danger:hover {
            background-color: #c9302c;
            border-color: #c9302c;
        }
        .remove-producto {
            margin-top: 25px;
        }
    </style>
    <script>
        $(document).ready(function() {
            let productoIndex = 0;

            $('#add-producto').click(function() {
                productoIndex++;
                $('#productos-list').append(`
                    <div class="form-row producto-row mb-2" id="producto-${productoIndex}">
                        <div class="col">
                            <input type="text" class="form-control" name="productos[${productoIndex}][nombre]" placeholder="Nombre del Producto" required>
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" name="productos[${productoIndex}][descripcion]" placeholder="Descripción" required>
                        </div>
                        <div class="col">
                            <input type="number" class="form-control" name="productos[${productoIndex}][cantidad]" placeholder="Cantidad" required>
                        </div>
                        <div class="col">
                            <input type="number" step="0.01" class="form-control" name="productos[${productoIndex}][precio_unitario]" placeholder="Precio Unitario" required>
                        </div>
                        <div class="col">
                            <input type="number" step="0.01" class="form-control" name="productos[${productoIndex}][precio_total]" placeholder="Precio Total" required>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger btn-sm remove-producto" data-id="${productoIndex}">Eliminar</button>
                        </div>
                    </div>
                `);
            });

            $(document).on('click', '.remove-producto', function() {
                const id = $(this).data('id');
                $(`#producto-${id}`).remove();
            });
        });
    </script>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Generar Factura</h1>
    <form action="factura.php" method="post">
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="cedula">Cédula</label>
            <input type="text" class="form-control" id="cedula" name="cedula" required>
        </div>
        <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" class="form-control" id="fecha" name="fecha" required>
        </div>
        <div class="form-group">
            <label for="nombre_local">Nombre del Local</label>
            <input type="text" class="form-control" id="nombre_local" name="nombre_local" required>
        </div>
        <div class="form-group">
            <label for="nit">NIT</label>
            <input type="text" class="form-control" id="nit" name="nit" required>
        </div>
        <div class="form-group">
            <label for="numero_factura">Número de Factura</label>
            <input type="text" class="form-control" id="numero_factura" name="numero_factura" required>
        </div>

        <div id="productos-list">
            <div class="form-row producto-row mb-2">
                <div class="col">
                    <input type="text" class="form-control" name="productos[0][nombre]" placeholder="Nombre del Producto" required>
                </div>
                <div class="col">
                    <input type="text" class="form-control" name="productos[0][descripcion]" placeholder="Descripción" required>
                </div>
                <div class="col">
                    <input type="number" class="form-control" name="productos[0][cantidad]" placeholder="Cantidad" required>
                </div>
                <div class="col">
                    <input type="number" step="0.01" class="form-control" name="productos[0][precio_unitario]" placeholder="Precio Unitario" required>
                </div>
                <div class="col">
                    <input type="number" step="0.01" class="form-control" name="productos[0][precio_total]" placeholder="Precio Total" required>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger btn-sm remove-producto" data-id="0">Eliminar</button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-primary" id="add-producto">Agregar Producto</button>

        <div class="form-group mt-3">
            <label for="garantia">Garantía</label>
            <textarea class="form-control" id="garantia" name="garantia" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label for="terminos">Términos y Condiciones</label>
            <textarea class="form-control" id="terminos" name="terminos" rows="4"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Generar Factura</button>
    </form>
</div>
</body>
</html>
