<?php
// Iniciar sesión
session_start();

// Incluir conexión a la base de datos
include('../config/db.php');
require_once('../libs/tcpdf.php');

// Manejar eliminación de venta
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Obtener la cantidad de productos vendidos y el ID del producto antes de eliminar la venta
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM sales WHERE id = ?");
    $stmt->execute([$delete_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sale) {
        $product_id = $sale['product_id'];
        $quantity_sold = $sale['quantity'];

        // Incrementar la cantidad del producto en la tabla products
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmt->execute([$quantity_sold, $product_id]);

        // Eliminar la venta
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$delete_id]);
    }

    header("Location: view_sales.php"); // Redirigir después de eliminar
    exit();
}

// Manejar actualización de venta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_sale'])) {
    $sale_id = $_POST['sale_id'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['total_price'];
    
    // Preparar y ejecutar la consulta para actualizar la venta
    $stmt = $pdo->prepare("UPDATE sales SET product_id = ?, quantity = ?, price = ? WHERE id = ?");
    $stmt->execute([$product_id, $quantity, $total_price, $sale_id]);
    
    header("Location: view_sales.php"); // Redirigir después de actualizar
    exit();
}

// Manejar generación de factura
if (isset($_GET['generate_invoice_id'])) {
    $invoice_id = $_GET['generate_invoice_id'];

    // Obtener los datos de la venta
    $stmt = $pdo->prepare("SELECT s.id, p.name AS product_name, s.quantity, s.price, s.date, st.name AS store_name, st.location AS store_location, w.id AS worker_id, s.client_name 
                           FROM sales s
                           JOIN products p ON s.product_id = p.id
                           JOIN stores st ON s.store_id = st.id
                           JOIN workers w ON s.worker_id = w.id
                           WHERE s.id = ?");
    $stmt->execute([$invoice_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sale) {
        // Crear un nuevo objeto PDF
        $pdf = new TCPDF('P', 'mm', array(80, 297));

        // Configuración del documento
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($sale['worker_id']);
        $pdf->SetTitle('Factura de Venta');
        $pdf->SetSubject('Factura');
        $pdf->SetKeywords('TCPDF, PDF, factura, venta');

        // Agregar una página
        $pdf->AddPage();

        // Contenido del PDF
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Factura de Venta', 0, 1, 'C');
        $pdf->Cell(0, 10, $sale['store_name'], 0, 1, 'C');
        $pdf->Cell(0, 10, $sale['store_location'], 0, 1, 'C');
        $pdf->Cell(0, 10, '__________________________', 0, 1);
        $pdf->Ln(10);

        $pdf->Cell(0, 10, 'Id Trabajador: ' . htmlspecialchars($sale['worker_id']), 0, 1);
        $pdf->Cell(0, 10, 'Tienda: ' . htmlspecialchars($sale['store_name']), 0, 1);
        $pdf->Cell(0, 10, 'Cliente: ' . htmlspecialchars($sale['client_name']), 0, 1); // Mostrar el nombre del cliente
        $pdf->Cell(0, 10, 'Producto: ' . htmlspecialchars($sale['product_name']), 0, 1);
        $pdf->Cell(0, 10, 'Cantidad: ' . htmlspecialchars($sale['quantity']), 0, 1);
        $pdf->Cell(0, 10, 'Precio Total: ' . htmlspecialchars($sale['price']), 0, 1);
        $pdf->Cell(0, 10, 'Fecha: ' . htmlspecialchars($sale['date']), 0, 1);

        $pdf->Ln(20);
        $pdf->Cell(0, 10, 'Firma: __________________________', 0, 1);
        $pdf->Cell(0, 10, '_________________________', 0, 1);

        // Establecer la fuente en tamaño pequeño
        $pdf->SetFont('helvetica', '', 8);  // 'Arial', estilo normal (''), tamaño 8

        // Agregar la celda con el texto
        $pdf->MultiCell(0, 5, 'Debe revisar primero el producto antes de llevarlo del establecimiento. Las garantías no cubren golpes, rayones o humedad. Las garantías deberán pasar primero por el proceso de revisión.', 0, 1);

        // Salida del PDF
        $pdf->Output('factura.pdf', 'I');
        exit();
    }
}

// Determinar el rango de fechas
$date_range = 'day';
$start_date = $end_date = date('Y-m-d');

// Si se ha enviado un rango de fechas, ajustar las variables
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['date_range'])) {
    $date_range = $_POST['date_range'];
    if ($date_range == 'day') {
        $start_date = $end_date = date('Y-m-d');
    } elseif ($date_range == 'month') {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
    }
}

// Obtener ventas para el rango de fechas actual
$stmt = $pdo->prepare("SELECT s.id, p.name AS name, s.quantity, s.price, s.date, s.total_sale 
                       FROM sales s
                       JOIN products p ON s.product_id = p.id
                       WHERE s.store_id = :store_id AND s.date BETWEEN :start_date AND :end_date");
$stmt->execute([
    'store_id' => $_SESSION['store_id'],
    'start_date' => $start_date,
    'end_date' => $end_date
]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            color: #6c8181;
        }
        .navbar {
            background-color: #6c8181;
        }
        .navbar-brand, .navbar-nav .nav-link, .navbar-text {
            color: #f4f7f6;
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
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        .table {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table thead {
            background-color: #6c8181;
            color: white;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .alert-warning {
            background-color: #f8e0e0;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>

 <!-- Header con menú de navegación -->
 <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Sistema de Ventas</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="worker_dashboard.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_inventory.php">Inventario</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_sales.php">Ventas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="factura.php">Factura</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">
                    <?php echo htmlspecialchars($_SESSION['worker_name']); ?>
                </span>
                <a class="btn btn-outline-light" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Ventas</h2>
            <a href="add_sales.php" class="btn btn-primary">Agregar Venta</a>
        </div>

        <!-- Formulario para seleccionar el rango de fechas -->
        <form method="POST" action="view_sales.php" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <select class="form-select" name="date_range" required>
                        <option value="day" <?= $date_range === 'day' ? 'selected' : '' ?>>Ventas del Día</option>
                        <option value="month" <?= $date_range === 'month' ? 'selected' : '' ?>>Ventas del Mes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </div>
        </form>

        <!-- Formulario para eliminar venta (confirmar antes de eliminar) -->
        <?php if (isset($_GET['delete_id'])): ?>
            <div class="alert alert-warning">
                ¿Estás seguro de que deseas eliminar esta venta? 
                <a href="?delete_id=<?= $_GET['delete_id']; ?>" class="btn btn-danger">Sí</a>
                <a href="view_sales.php" class="btn btn-secondary">Cancelar</a>
            </div>
        <?php endif; ?>

        <!-- Tabla de ventas -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Precio Total</th>
                    <th>Fecha de Venta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= htmlspecialchars($sale['name']); ?></td>
                        <td><?= htmlspecialchars($sale['quantity']); ?></td>
                        <td><?= htmlspecialchars($sale['price']); ?></td>
                        <td><?= htmlspecialchars($sale['total_sale']); ?></td>
                        <td><?= htmlspecialchars($sale['date']); ?></td>
                        <td>
                            <!-- Botón para eliminar -->
                            <a href="?delete_id=<?= $sale['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar esta venta?');">Eliminar</a>
                            <!-- Botón para generar factura -->
                            <a href="?generate_invoice_id=<?= $sale['id']; ?>" class="btn btn-info btn-sm" target="_blank">Generar Factura</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
</body>
</html>
