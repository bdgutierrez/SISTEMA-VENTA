<?php
// Iniciar sesión
session_start();

// Verificar si el trabajador ha iniciado sesión
if (!isset($_SESSION['worker_id'])) {
    header("Location: login_worker.php");
    exit();
}

// Incluir la conexión a la base de datos
include('../config/db.php');

// Obtener la información del trabajador
$stmt = $pdo->prepare("SELECT id, name FROM workers WHERE id = ?");
$stmt->execute([$_SESSION['worker_id']]);
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    header("Location: login_worker.php");
    exit();
}

// Manejar el registro de asistencia
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['check_in'])) {
        // Registrar entrada
        $stmt = $pdo->prepare("INSERT INTO attendance (worker_id, check_in_time) VALUES (?, DATE_SUB(NOW(), INTERVAL 5 HOUR))");
        $stmt->execute([$_SESSION['worker_id']]);
    } elseif (isset($_POST['check_out'])) {
        // Registrar salida
        $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = DATE_SUB(NOW(), INTERVAL 5 HOUR) WHERE worker_id = ? AND DATE(check_in_time) = CURDATE() AND check_out_time IS NULL");
        $stmt->execute([$_SESSION['worker_id']]);
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
    <title>Control de Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .btn-custom {
            width: 100%;
            margin-top: 20px;
        }
        .table-custom {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Control de Asistencia</h1>

        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <h3>Bienvenido, <?= htmlspecialchars($worker['name']); ?></h3>
                
                <form method="POST">
                    <button type="submit" name="check_in" class="btn btn-success btn-custom">Registrar Entrada</button>
                    <button type="submit" name="check_out" class="btn btn-danger btn-custom">Registrar Salida</button>
                </form>
            </div>
        </div>

        <!-- Tabla de historial de asistencia -->
        <div class="row table-custom">
            <div class="col-md-8 offset-md-2">
                <h4 class="text-center">Historial de Asistencia</h4>
                <table class="table table-striped">
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
