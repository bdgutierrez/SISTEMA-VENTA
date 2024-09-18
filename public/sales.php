<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Insertar venta
    $stmt = $pdo->prepare("INSERT INTO sales (product_id, quantity) VALUES (?, ?)");
    $stmt->execute([$product_id, $quantity]);

    // Actualizar stock
    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    $stmt->execute([$quantity, $product_id]);

    header("Location: sales.php");
    exit();
}

$stmt = $pdo->prepare("SELECT p.*, s.name as store_name FROM products p JOIN stores s ON p.store_id = s.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();

include '../templates/header.php';
?>

<h2>Registrar Venta</h2>
<form method="post">
    <div class="mb-3">
        <label for="product_id" class="form-label">Producto</label>
        <select class="form-select" id="product_id" name="product_id" required>
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id'] ?>"><?= $product['name'] ?> (<?= $product['store_name'] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="quantity" class="form-label">Cantidad</label>
        <input type="number" class="form-control" id="quantity" name="quantity" required>
    </div>
    <button type="submit" class="btn btn-primary">Registrar Venta</button>
</form>

<h3>Historial de Ventas</h3>
<table class="table">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $pdo->prepare("SELECT s.*, p.name as product_name FROM sales s JOIN products p ON s.product_id = p.id WHERE p.store_id IN (SELECT id FROM stores WHERE user_id = ?)");
        $stmt->execute([$_SESSION['user_id']]);
        $sales = $stmt->fetchAll();
        ?>
        <?php foreach ($sales as $sale): ?>
            <tr>
                <td><?= $sale['product_name'] ?></td>
                <td><?= $sale['quantity'] ?></td>
                <td><?= $sale['sale_date'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../templates/footer.php'; ?>
