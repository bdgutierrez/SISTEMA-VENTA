<?php
// Iniciar sesión
session_start();

// Configurar la zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Incluir conexión a la base de datos
include('../config/db.php');

// Verificar si el trabajador está logueado
if (!isset($_SESSION['worker_id'])) {
    header("Location: login_worker.php");
    exit();
}

// Obtener la información del trabajador
$stmt = $pdo->prepare("SELECT w.id, w.name, w.photo, s.id AS store_id, s.name AS store_name
                        FROM workers w
                        JOIN stores s ON w.store_id = s.id
                        WHERE w.id = ?");
$stmt->execute([$_SESSION['worker_id']]);
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    header("Location: login_worker.php");
    exit();
}

// Verificar si el trabajador ya ha registrado su entrada
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE worker_id = ? AND DATE(check_in_time) = ?");
$stmt->execute([$_SESSION['worker_id'], $today]);
$existingCheckIn = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no ha registrado la entrada, no permitir ninguna acción
$hasCheckedIn = $existingCheckIn ? true : false;

// Obtener la lista de ventas e inventario (solo si ha registrado la entrada)
if ($hasCheckedIn) {
    $store_id = $worker['store_id'];
    $store_name = $worker['store_name'];

    // Consultar ventas
    $ventasStmt = $pdo->prepare("SELECT * FROM sales WHERE store_id = ?");
    $ventasStmt->execute([$store_id]);
    $ventas = $ventasStmt->fetchAll(PDO::FETCH_ASSOC);

    // Consultar inventario
    $inventarioStmt = $pdo->prepare("SELECT * FROM products WHERE store_id = ?");
    $inventarioStmt->execute([$store_id]);
    $inventario = $inventarioStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Manejar el CRUD de ventas e inventario (solo si ha registrado la entrada)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$hasCheckedIn && isset($_POST['check_in'])) {
        // Registrar entrada con la hora de Colombia, restando 7 horas
        $stmt = $pdo->prepare("INSERT INTO attendance (worker_id, check_in_time) VALUES (?, DATE_SUB(NOW(), INTERVAL 7 HOUR))");
        $stmt->execute([$_SESSION['worker_id']]);
        header("Location: worker_dashboard.php");
        exit();
    } elseif ($hasCheckedIn) {
        if (isset($_POST['add_sale'])) {
            // Agregar una venta
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'];
            $price = $_POST['price'];
            $date = $_POST['date'];

            $stmt = $pdo->prepare("INSERT INTO sales (store_id, product_id, quantity, price, date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$store_id, $product_id, $quantity, $price, $date]);

            header("Location: worker_dashboard.php");
            exit();
        } elseif (isset($_POST['update_inventory'])) {
            // Actualizar inventario
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'];

            $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE store_id = ? AND id = ?");
            $stmt->execute([$quantity, $store_id, $product_id]);

            header("Location: worker_dashboard.php");
            exit();
        } elseif (isset($_POST['check_out'])) {
            // Registrar salida con la hora de Colombia, restando 7 horas
            $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = DATE_SUB(NOW(), INTERVAL 7 HOUR) WHERE worker_id = ? AND DATE(check_in_time) = ? AND check_out_time IS NULL");
            $stmt->execute([$_SESSION['worker_id'], $today]);
        }
    }
}

// Consultar el historial de asistencia
$attendanceStmt = $pdo->prepare("SELECT * FROM attendance WHERE worker_id = ? ORDER BY check_in_time DESC");
$attendanceStmt->execute([$_SESSION['worker_id']]);
$attendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard del Trabajador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        body {
            background-color: #f2f2f2;
        }
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 4px solid #005f59;
            border-radius: 50%;
            transition: border-color 0.3s;
        }
        .profile-img:hover {
            border-color: #004d4d;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            transition: box-shadow 0.3s;
        }
        .card:hover {
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background-color: #005f59;
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 20px;
        }
        .card-footer {
            background-color: #e9ecef;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            padding: 15px;
        }
        .btn-custom {
            border-radius: 25px;
            padding: 12px;
            font-size: 16px;
            margin-top: 10px;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .btn-custom:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .btn-warning, .btn-primary, .btn-success, .btn-danger {
            border-radius: 25px;
            padding: 12px;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .btn-warning:hover, .btn-primary:hover, .btn-success:hover, .btn-danger:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .table-custom {
            margin-top: 30px;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
        .table-hover tbody tr:hover {
            background-color: #e2e6ea;
        }
        .footer-card {
            margin-top: 20px;
        }
        .footer-card .btn {
            border-radius: 25px;
            padding: 12px;
            font-size: 16px;
        }
        
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><?= htmlspecialchars($worker['name']); ?></h3>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($worker['photo']): ?>
                            <img src="<?= htmlspecialchars($worker['photo']); ?>" alt="Foto de Perfil" class="img-fluid profile-img">
                        <?php else: ?>
                            <img src="default_profile.jpg" alt="Foto de Perfil" class="img-fluid profile-img">
                        <?php endif; ?>
                        <p class="mt-3"><?= htmlspecialchars($worker['store_name']); ?></p>
                    </div>
                    <div class="card-footer text-center">
                        <a href="logout.php" class="btn btn-danger btn-custom">Cerrar Sesión</a>
                    </div>
                    <div class="footer-card text-center">
                        <div class="row">
                            <div class="col-">
                                <form method="POST" action="" class="d-inline">
                                    <?php if (!$hasCheckedIn): ?>
                                        <button type="submit" name="check_in" class="btn btn-success">Registrar Entrada</button>
                                    <?php else: ?>
                                        <a href="view_inventory.php" class="btn btn-warning">Inventario</a>
                                        <a href="view_sales.php" class="btn btn-primary">Ventas</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-">
                                <?php if ($hasCheckedIn): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <button type="submit" name="check_out" class="btn btn-danger">Registrar Salida</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de historial de asistencia -->
    <div class="container table-custom">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h4 class="text-center">Historial de Asistencia</h4>
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Hora de Entrada</th>
                            <th>Hora de Salida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $record): ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($record['check_in_time'])); ?></td>
                                <td><?= date('H:i:s', strtotime($record['check_in_time'])); ?></td>
                                <td>
                                    <?php
                                    if ($record['check_out_time']) {
                                        echo date('H:i:s', strtotime($record['check_out_time']));
                                    } else {
                                        echo 'No registrado';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
