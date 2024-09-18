<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../config/db.php';

$stmt = $pdo->prepare("SELECT p.*, s.name as store_name FROM products p JOIN stores s ON p.store_id = s.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();

include '../templates/header.php';
?>

<h2>Inventario</h2>

<?php if ($products): ?>
    <table class="table">
    <thead>
        <tr>
            <th>Imagen</th>
            <th>Producto</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Tienda</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>" style="width: 100px;"></td>
                <td><?= $product['name'] ?></td>
                <td><?= $product['description'] ?></td>
                <td><?= $product['price'] ?></td>
                <td><?= $product['stock'] ?></td>
                <td><?= $product['store_name'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
    <p>No tienes productos en tu inventario.</p>
<?php endif; ?>

<?php include '../templates/footer.php'; ?>
