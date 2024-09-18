<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

include '../config/db.php';

// Obtener la tienda seleccionada
$store_id = $_GET['store_id'];
$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ? AND user_id = ?");
$stmt->execute([$store_id, $_SESSION['user_id']]);
$store = $stmt->fetch();

if (!$store) {
    header("Location: dashboard.php");
    exit();
}

// Agregar trabajador
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_worker'])) {
    $worker_name = $_POST['worker_name'];
    $worker_role = $_POST['worker_role'];

    $stmt = $pdo->prepare("INSERT INTO workers (name, role, store_id) VALUES (?, ?, ?)");
    $stmt->execute([$worker_name, $worker_role, $store_id]);

    header("Location: manage_workers.php?store_id=$store_id");
    exit();
}

// Obtener los trabajadores de la tienda seleccionada
$stmt = $pdo->prepare("SELECT * FROM workers WHERE store_id = ?");
$stmt->execute([$store_id]);
$workers = $stmt->fetchAll();

include '../templates/header.php';
?>

<h2>Gestionar Trabajadores de <?= $store['name'] ?></h2>

<!-- Formulario para agregar nuevo trabajador -->
<form method="post">
    <div class="mb-3">
        <label for="worker_name" class="form-label">Nombre del Trabajador</label>
        <input type="text" class="form-control" id="worker_name" name="worker_name" required>
    </div>
    <div class="mb-3">
        <label for="worker_role" class="form-label">Rol del Trabajador</label>
        <input type="text" class="form-control" id="worker_role" name="worker_role" required>
    </div>
    <button type="submit" class="btn btn-primary" name="add_worker">Agregar Trabajador</button>
</form>

<hr>

<!-- Listado de trabajadores -->
<?php if ($workers): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workers as $worker): ?>
                <tr>
                    <td><?= $worker['name'] ?></td>
                    <td><?= $worker['role'] ?></td>
                    <td>
                        <a href="edit_worker.php?worker_id=<?= $worker['id'] ?>&store_id=<?= $store_id ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="delete_worker.php?worker_id=<?= $worker['id'] ?>&store_id=<?= $store_id ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este trabajador?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No hay trabajadores registrados en esta tienda.</p>
<?php endif; ?>

<a href="store.php?store_id=<?= $store_id ?>" class="btn btn-secondary">Volver a la Tienda</a>

<?php include '../templates/footer.php'; ?>
