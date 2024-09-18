<?php
// Iniciar sesión
session_start();

// Incluir conexión a la base de datos
include('../config/db.php');

$id_worker = $_GET['worker_id'] ?? null;

if ($id_worker) {
    // Consulta de trabajadores usando PDO
    $stmt = $pdo->prepare("SELECT id, name,salary,email,  photo FROM workers WHERE id = ?");
    $stmt->execute([$id_worker]);
    $worker = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $worker = null;
}
// Consultar el historial de asistencia
$attendanceStmt = $pdo->prepare("SELECT * FROM attendance WHERE worker_id = ? ORDER BY check_in_time DESC");
$attendanceStmt->execute([$id_worker]);
$attendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el mes actual
$currentMonth = date('Y-m');

// Contar las asistencias del mes actual
$attendanceCountStmt = $pdo->prepare("SELECT COUNT(*) AS attendance_count FROM attendance WHERE worker_id = ? AND check_in_time LIKE ?");
$attendanceCountStmt->execute([$id_worker, "$currentMonth%"]);
$attendanceCount = $attendanceCountStmt->fetch(PDO::FETCH_ASSOC)['attendance_count'];

// Calcular el sueldo mensual
$monthlySalary = $worker['salary'] * $attendanceCount;

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Trabajador</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f7f6;
        }
        .worker-details {
            background-color: #6c8181;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .worker-photo {
            max-width: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        .table tbody tr:hover {
            background-color: #d1e0e0;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4" style="color: #6c8181;">Detalle del Trabajador</h2>

        <?php if ($worker): ?>
    <div class="worker-details text-center">
        <img src="<?php echo htmlspecialchars($worker['photo']); ?>" alt="Foto de <?php echo htmlspecialchars($worker['name']); ?>" class="worker-photo">
        <h3><?php echo htmlspecialchars($worker['name']); ?></h3>
        <p>Correo: <?php echo htmlspecialchars($worker['email']); ?></p>
        <p>Nómina por día: <?php echo htmlspecialchars('$' . number_format($worker['salary'], 2)); ?></p>
        <p>Asistencias este mes: <?php echo htmlspecialchars($attendanceCount); ?></p>
        <p>Sueldo estimado este mes: <?php echo htmlspecialchars('$' . number_format($monthlySalary, 2)); ?></p>
    </div>
<?php else: ?>
    <p class="text-center">No se encontró el trabajador con el ID proporcionado.</p>
<?php endif; ?>

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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
