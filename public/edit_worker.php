<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../config/db.php';

// Obtener el trabajador y la tienda
$worker_id = $_GET['worker_id'];
$store_id = $_GET['store_id'];

$stmt = $pdo->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->execute([$worker_id]);
$worker = $stmt->fetch();

if (!$worker) {
    header("Location: manage_workers.php?store_id=$store_id");
    exit();
}

// Actualizar trabajador
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $worker_name = $_POST['worker_name'];
    $worker_role = $_POST['worker_role'];

    $stmt = $pdo->prepare("UPDATE workers SET name = ?, role = ? WHERE id = ?");
    $stmt->execute([$worker_name, $worker_role, $worker_id]);

    header("Location: manage_workers.php?store_id=$store_id");
    exit();
}

include '../templates/header.php';
?>

<h2>Editar Trabajador: <?= $worker['name'] ?></h2>

<form method="post">
    <div class="mb-3">
        <label for="worker_name" class="form-label">Nombre del Trabajador</label>
        <input type="text" class="form-control" id="worker_name" name="worker_name" value="<?= $worker['name'] ?>" required>
    </div>
    <div class="mb-3">
        <label for="worker_role" class="form-label">Rol del Trabajador</label>
        <input type="text" class="form-control" id="worker_role" name="worker_role" value="<?= $worker['role'] ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar Trabajador</button>
</form>

<a href="manage_workers.php?store_id=<?= $store_id ?>" class="btn btn-secondary mt-3">Volver a Gestionar Trabajadores</a>

<?php include '../templates/footer.php'; ?>
