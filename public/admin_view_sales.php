<?php
// Inicia sesión
session_start();

// Verificar si el administrador ha iniciado sesión
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Incluir conexión a la base de datos
include('../config/db.php');

// Verificar si se ha proporcionado un store_id
$store_id = isset($_GET['store_id']) ? filter_var($_GET['store_id'], FILTER_VALIDATE_INT) : null;
if (!$store_id) {
    header("Location: admin_dashboard.php");
    exit();
}

// Obtener la información de la tienda
$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ? AND admin_id = ?");
$stmt->execute([$store_id, $_SESSION['admin_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header("Location: admin_dashboard.php");
    exit();
}

// Definir la fecha actual y el rango de fechas para el gráfico
$currentMonth = date('Y-m');
$startDate = $currentMonth . '-01';
$endDate = date('Y-m-t');

// Obtener las ventas para el mes actual
$salesStmt = $pdo->prepare("SELECT DATE(date) AS sale_date, SUM(price) AS total_amount 
                            FROM sales 
                            WHERE store_id = ? AND DATE(date) BETWEEN ? AND ?
                            GROUP BY DATE(date)");
$salesStmt->execute([$store_id, $startDate, $endDate]);
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
// Obtener las salidas para el mes actual
$cashOutStmt = $pdo->prepare("SELECT cash_out, description, close_date 
                              FROM cash_register 
                              WHERE store_id = ? AND cash_out > 0 AND  close_date BETWEEN ? AND ?");
$cashOutStmt->execute([$store_id, $startDate, $endDate]);
$cashOuts = $cashOutStmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar los datos para el gráfico
$salesData = [];
$salesLabels = [];
foreach ($sales as $sale) {
    $salesLabels[] = $sale['sale_date'];
    $salesData[] = $sale['total_amount'];
}

// Manejar la selección de filtro de ventas por día o por mes
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'day';
$filterDate = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : null;

// Validar el formato de la fecha para el filtro de día o mes
if ($filter === 'month' && !$filterDate) {
    $filterDate = date('Y-m');
} elseif ($filter === 'day' && !$filterDate) {
    $filterDate = date('Y-m-d');
}

// Obtener las ventas según el filtro
if ($filter === 'month') {
    $salesStmt = $pdo->prepare("SELECT s.id, s.date, p.name AS product_name, s.client_name, s.price, w.name AS worker_name
                                FROM sales s
                                JOIN products p ON s.product_id = p.id
                                JOIN workers w ON s.worker_id = w.id
                                WHERE s.store_id = ? AND DATE_FORMAT(s.date, '%Y-%m') = ?
                                ORDER BY s.date");
    $salesStmt->execute([$store_id, $filterDate]);
    $sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
}
if ($filter === 'day') {
    $salesStmt = $pdo->prepare("SELECT s.id, s.date, p.name AS product_name, s.client_name, s.price, w.name AS worker_name
                                FROM sales s
                                JOIN products p ON s.product_id = p.id
                                JOIN workers w ON s.worker_id = w.id
                                WHERE s.store_id = ? AND DATE(s.date) = ?
                                ORDER BY s.date");
    $salesStmt->execute([$store_id, $filterDate]);
    $sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> <!-- Incluye tu archivo de estilos aquí -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Estilos personalizados */
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        .navbar {
            background-color: #6c8181;
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
        }
        .navbar-nav .nav-link:hover {
            color: #f48a54 !important;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h3, h4 {
            color: #6c8181;
        }
        .form-label {
            color: #6c8181;
        }
        .btn-primary {
            background-color: #f48a54;
            border-color: #f48a54;
        }
        .btn-primary:hover {
            background-color: #e67c4f;
            border-color: #e67c4f;
        }
        .table thead th {
            background-color: #6c8181;
            color: #fff;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
        .table-striped tbody tr:hover {
            background-color: #f1f1f1;
        }
        .modal-content {
            border-radius: 8px;
        }
        .modal-header {
            background-color: #6c8181;
            color: #fff;
        }
        .modal-footer .btn-secondary {
            background-color: #6c8181;
            border-color: #6c8181;
        }
        .modal-footer .btn-secondary:hover {
            background-color: #5a6b6b;
            border-color: #5a6b6b;
        }
        .container h3{
            color: #6c8181;
        }
       
    </style>
</head>
<body>
    <!-- Menú de navegación -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Panel de la Tienda</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="store_dashboard.php?store_id=<?= $store_id; ?>">Volver a la Tienda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container mt-4">
        <h3 class="text-primar">Ventas de la Tienda <?= htmlspecialchars($store['name']); ?></h3>
        
        <!-- Filtros para ver ventas por día o mes -->
        <form method="GET" class="mb-4">
            <input type="hidden" name="store_id" value="<?= htmlspecialchars($store_id); ?>">
            <div class="form-group">
                <label for="filter">Filtrar por:</label>
                <select id="filter" name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="day" <?= $filter === 'day' ? 'selected' : ''; ?>>Día</option>
                    <option value="month" <?= $filter === 'month' ? 'selected' : ''; ?>>Mes</option>
                </select>
            </div>
            <?php if ($filter === 'month'): ?>
                <div class="form-group mt-2">
                    <label for="date">Seleccionar mes:</label>
                    <input type="month" id="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate); ?>" onchange="this.form.submit()">
                </div>
            <?php elseif ($filter === 'day'): ?>
                <div class="form-group mt-2">
                    <label for="date">Seleccionar día:</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate); ?>" onchange="this.form.submit()">
                </div>
            <?php endif; ?>
        </form>

        <!-- Mostrar ventas -->
<h4>Ventas <?= $filter === 'month' ? 'del Mes' : 'del Día'; ?> <?= htmlspecialchars($filterDate); ?></h4>
<?php if ($filter === 'day'): ?>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Cliente</th>
                <th>Trabajador</th> <!-- Nueva columna -->
                <th>Importe</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= htmlspecialchars($sale['id']); ?></td>
                    <td><?= htmlspecialchars($sale['date']); ?></td>
                    <td><?= htmlspecialchars($sale['product_name']); ?></td>
                    <td><?= htmlspecialchars($sale['client_name']); ?></td>
                    <td><?= htmlspecialchars($sale['worker_name']); ?></td> <!-- Nueva columna -->
                    <td><?= htmlspecialchars($sale['price']); ?></td>
                    <td>
                    <a href="controller/edit_sale.php?sale_id=<?= htmlspecialchars($sale['id']); ?>" class="btn btn-warning btn-sm">Editar</a>
                    <button class="btn btn-danger btn-sm" onclick="deleteSale(<?= $sale['id']; ?>)">Eliminar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <h5>Gráfico de Ventas Diarias</h5>
    <canvas id="salesChart"></canvas>
<?php endif; ?>
   

    <script>
        // Crear gráfico de ventas diarias
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($salesLabels); ?>,
                datasets: [{
                    label: 'Ventas Diarias',
                    data: <?= json_encode($salesData); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

       

        // Función para eliminar una venta
        function deleteSale(id) {
            if (confirm('¿Estás seguro de que deseas eliminar esta venta?')) {
                window.location.href = 'delete_sale.php?id=' + id;
            }
        }
    </script>

     <!-- Tabla de salidas del mes -->
     <div class="salidas">
            <h4>Salidas del Mes</h4>
            <?php if (count($cashOuts) > 0): ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Cantidad</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cashOuts as $cashOut): ?>
                            <tr>
                                <td><?= htmlspecialchars($cashOut['cash_out']); ?></td>
                                <td><?= htmlspecialchars($cashOut['description']); ?></td>
                                <td><?= htmlspecialchars($cashOut['close_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay salidas registradas para este mes.</p>
            <?php endif; ?>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
