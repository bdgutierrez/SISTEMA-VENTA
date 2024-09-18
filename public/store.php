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

include '../templates/header.php';
?>

<h2>Panel de la Tienda: <?= $store['name'] ?></h2>

<div class="list-group">
    <a href="view_sales.php?store_id=<?= $store_id ?>" class="list-group-item list-group-item-action">Ver Ventas</a>
    <a href="view_inventory.php?store_id=<?= $store_id ?>" class="list-group-item list-group-item-action">Ver Inventario</a>
    <a href="manage_workers.php?store_id=<?= $store_id ?>" class="list-group-item list-group-item-action">Gestionar Trabajadores</a>
</div>

<a href="dashboard.php" class="btn btn-secondary mt-3">Volver al Dashboard</a>

<?php include '../templates/footer.php'; ?>
