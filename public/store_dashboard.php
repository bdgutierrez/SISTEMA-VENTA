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
if (!isset($_GET['store_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Asegurarse de que $store_id es un entero válido
$store_id = isset($_GET['store_id']) ? (int) $_GET['store_id'] : 0;

// Obtener la información de la tienda
$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ? AND admin_id = ?");
$stmt->execute([$store_id, $_SESSION['admin_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header("Location: admin_dashboard.php");
    exit();
}

// Obtener el saldo final de la caja más reciente
$stmt = $pdo->prepare("SELECT closing_balance FROM cash_register 
                        WHERE store_id = ? AND close_date IS NOT NULL 
                        ORDER BY close_date DESC 
                        LIMIT 1");
$stmt->execute([$store_id]);
$last_closing_balance = $stmt->fetchColumn();

// Obtener los trabajadores de la tienda
$stmt = $pdo->prepare("SELECT * FROM workers WHERE store_id = ?");
$stmt->execute([$store_id]);
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grafico
try {
    // Preparar y ejecutar la consulta
    $sql = "SELECT SUM(cash_in) AS total_entradas, SUM(cash_out) AS total_salidas FROM cash_register WHERE store_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$store_id]);

    // Obtener el resultado
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Asignar valores si se obtuvo un resultado
    $total_entradas = $row['total_entradas'] ? $row['total_entradas'] : 0;
    $total_salidas = $row['total_salidas'] ? $row['total_salidas'] : 0;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Preparar y ejecutar la consulta para ventas del mes
try {
   // Preparar y ejecutar la consulta
   $sql_mes = "SELECT
   SUM(cash_in) AS total_entradas_mes, SUM(cash_out) As total_salidas_mes
FROM
   cash_register
WHERE
   store_id = ?
   AND YEAR(open_date) = YEAR(CURDATE())
   AND MONTH(open_date) = MONTH(CURDATE())";
   $stmt = $pdo->prepare($sql_mes);
   $stmt->execute([$store_id]);

   // Obtener el resultado
   $row_mes = $stmt->fetch(PDO::FETCH_ASSOC);

   // Asignar valores si se obtuvo un resultado
   $total_entradas_mes = $row_mes['total_entradas_mes'] ? $row_mes['total_entradas_mes'] : 0;
   $total_salidas_mes = $row_mes['total_salidas_mes'] ? $row_mes['total_salidas_mes'] : 0;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Preparar y ejecutar la consulta para ventas del dia
try {
    // Preparar y ejecutar la consulta
    $sql_dia = "SELECT
    SUM(cash_in) AS total_entradas_dia, SUM(cash_out) As total_salidas_dia
 FROM
    cash_register
 WHERE
    store_id = ?
    AND YEAR(open_date) = YEAR(CURDATE())
    AND MONTH(open_date) = MONTH(CURDATE())
    AND DAY(open_date) = Day(CURDATE())";
    $stmt = $pdo->prepare($sql_dia);
    $stmt->execute([$store_id]);
 
    // Obtener el resultado
    $row_dia = $stmt->fetch(PDO::FETCH_ASSOC);
 
    // Asignar valores si se obtuvo un resultado
    $total_entradas_dia = $row_dia['total_entradas_dia'] ? $row_dia['total_entradas_dia'] : 0;
    $total_salidas_dia = $row_dia['total_salidas_dia'] ? $row_dia['total_salidas_dia'] : 0;
 
 } catch (PDOException $e) {
     echo "Error: " . $e->getMessage();
 }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de la Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background-color: #f4f7f6;
            color: #333;
        }
        .navbar {
            background-color: #6c8181;
        }
        .navbar-brand, .nav-link {
            color: #ffffff !important;
        }
        .navbar-nav .nav-link:hover {
            color: #a2d5ab !important;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h3, h4 {
            color: #3b8b7b;
        }
        .title-graphic {
            color: #3b8b7b;
        }
        .graphics {
            margin-top: 40px;
            width: 70%;
            margin-left: 15%;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .container-graphic {
            max-width: 100%;
            text-align: center;
        }
        @media (max-width: 1600px) {
            .graphics {
                grid-template-columns: repeat(1, 1fr);
            }
        }
        .list-group-item {
            border: 1px solid #ddd;
        }
        .list-group-item:hover {
            background-color: #f1f1f1;
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
                        <a class="nav-link" href="admin_view_sales.php?store_id=<?= $store_id; ?>">Ver Ventas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_view_inventory.php?store_id=<?= $store_id; ?>">Ver Inventario</a>
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
        <h3>Dashboard de la Tienda <?= htmlspecialchars($store['name']); ?></h3>
        <h4>Información de la Tienda</h4>
        <p><strong>Nombre:</strong> <?= htmlspecialchars($store['name']); ?></p>
        <p><strong>Ubicación:</strong> <?= htmlspecialchars($store['location']); ?></p>

        <!-- Mostrar el saldo final de la caja más reciente -->
        <h4 class="mt-4">Saldo Final de la Última Caja</h4>
        <p><strong>Saldo Final:</strong> <?= $last_closing_balance ? htmlspecialchars($last_closing_balance) : 'No disponible'; ?></p>

        <h4 class="mt-4">Lista de Trabajadores</h4>
        <?php if (count($workers) > 0): ?>
            <div class="list-group">
                <?php foreach ($workers as $worker): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($worker['name']); ?></h5>
                            <p class="mb-1"><?= htmlspecialchars($worker['email']); ?></p>
                            <img src="<?= htmlspecialchars($worker['photo']); ?>" alt="Foto del Trabajador" class="img-thumbnail" style="width: 100px;">
                        </div>
                        <div class="buttons">
                        <a href="worker_details.php?worker_id=<?= $worker['id']; ?>" class="btn btn-info">Editar</a>
                        <a href="view_worker.php?worker_id=<?= $worker['id']; ?>" class="btn btn-warning">ver</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No hay trabajadores asignados a esta tienda.</p>
        <?php endif; ?>
    </div>

    <div class="graphics">
        <div class="container-graphic">
            <h2 class="title-graphic">Balance total</h2>
            <canvas id="myPieChart"></canvas>
        </div>
        <div class="container-graphic">
            <h2 class="title-graphic">Balance Mes</h2>
            <canvas id="myPieChart2"></canvas>
        </div>
        <div class="container-graphic">
            <h2 class="title-graphic">Balance Día</h2>
            <canvas id="myPieChart3"></canvas>
        </div>
    </div>

    <script>
        // Obtener los datos desde PHP
        var entradas = <?php echo $total_entradas; ?>;
        var salidas = <?php echo $total_salidas; ?>;

        var entradas_mes = <?php echo $total_entradas_mes; ?>;
        var salidas_mes = <?php echo $total_salidas_mes; ?>;

        var entradas_dia = <?php echo $total_entradas_dia; ?>;
        var salidas_dia = <?php echo $total_salidas_dia; ?>;

        // Configuración del gráfico circular
        var ctx = document.getElementById('myPieChart').getContext('2d');
        var myPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Entradas', 'Salidas'],
                datasets: [{
                    label: 'Distribución de Entradas y Salidas',
                    data: [entradas, salidas],
                    backgroundColor: ['#5b9bd5', '#ed7d31'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw.toFixed(2) + '$';
                            }
                        }
                    }
                }
            }
        });

        // Configuración del gráfico circular mes
        var ctx = document.getElementById('myPieChart2').getContext('2d');
        var myPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Entradas', 'Salidas'],
                datasets: [{
                    label: 'Distribución de Entradas y Salidas del Mes',
                    data: [entradas_mes, salidas_mes],
                    backgroundColor: ['#5b9bd5', '#ed7d31'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw.toFixed(2) + '$';
                            }
                        }
                    }
                }
            }
        });

        // Configuración del gráfico circular día
        var ctx = document.getElementById('myPieChart3').getContext('2d');
        var myPieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Entradas', 'Salidas'],
                datasets: [{
                    label: 'Distribución de Entradas y Salidas del Día',
                    data: [entradas_dia, salidas_dia],
                    backgroundColor: ['#5b9bd5', '#ed7d31'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw.toFixed(2) + '$';
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
