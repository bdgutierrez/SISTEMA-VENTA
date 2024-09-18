<?php 
session_start();
include('../config/db.php');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['store_id'])) {
    header("Location: login_worker.php");
    exit();
}

// Obtener el ID de la tienda desde la sesión
$store_id = $_SESSION['store_id'];

// Manejo de formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['open_register'])) {
        // Verificar si ya hay una caja abierta
        $stmt = $pdo->prepare("SELECT id FROM cash_register WHERE store_id = ? AND close_date IS NULL");
        $stmt->execute([$store_id]);
        $open_register = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($open_register) {
            $message = "Ya hay una caja abierta para esta tienda.";
        } else {
            // Obtener el saldo final de la última caja cerrada
            $stmt = $pdo->prepare("SELECT closing_balance FROM cash_register WHERE store_id = ? AND close_date IS NOT NULL ORDER BY close_date DESC LIMIT 1");
            $stmt->execute([$store_id]);
            $last_closing_balance = $stmt->fetchColumn();

            // Usar el saldo final de la última caja cerrada o 0 si no hay caja cerrada
            $opening_balance = $last_closing_balance !== false ? $last_closing_balance : 0;

            // Abrir caja
            $user_id = $_SESSION['worker_id']; // ID del trabajador que abre la caja
            
            $stmt = $pdo->prepare("INSERT INTO cash_register (user_id, store_id, open_date, opening_balance) 
                                   VALUES (?, ?, DATE_SUB(NOW(), INTERVAL 5 HOUR), ?)");
            $stmt->execute([$user_id, $store_id, $opening_balance]);

            $message = "Caja abierta exitosamente.";
        }

    } elseif (isset($_POST['close_register'])) {
        // Cerrar caja
        $register_id = $_POST['register_id'];
        $cash_out = $_POST['cash_out'];
        $description = $_POST['description'];

        // Obtener el saldo actual de la caja
        $stmt = $pdo->prepare("SELECT opening_balance FROM cash_register WHERE id = ?");
        $stmt->execute([$register_id]);
        $opening_balance = $stmt->fetchColumn();

        // Obtener las ventas asociadas a esta caja para calcular el dinero entrante
        $stmt = $pdo->prepare("SELECT SUM(price) FROM sales WHERE register_id = ?");
        $stmt->execute([$register_id]);
        $cash_in = $stmt->fetchColumn();

        // Calcular el saldo de cierre
        $closing_balance = $opening_balance + $cash_in - $cash_out;

        // Actualizar la caja con los valores finales
        $stmt = $pdo->prepare("UPDATE cash_register 
                               SET close_date = DATE_SUB(NOW(), INTERVAL 5 HOUR), cash_out = ?, closing_balance = ?, description = ? 
                               WHERE id = ?");
        $stmt->execute([$cash_out, $closing_balance, $description, $register_id]);

        $message = "Caja cerrada exitosamente.";

        // Redirigir para evitar resubida de formulario
        header("Location: add_sales.php");
        exit();
    } elseif (isset($_POST['add_sale'])) {
        // Verificar si hay una caja abierta
        $stmt = $pdo->prepare("SELECT id FROM cash_register WHERE store_id = ? AND close_date IS NULL");
        $stmt->execute([$store_id]);
        $open_register = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$open_register) {
            $message = "Debe haber una caja abierta para registrar una venta.";
        } else {
            // Agregar una venta
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'];
            $price = $_POST['price'];
            $total_sale = $price * $quantity;
            $client_name = $_POST['client_name'];  // Nuevo campo para el nombre del cliente
            $description = $_POST['description']; // Campo para la descripción de la venta
            $register_id = $open_register['id'];  // Aquí se obtiene el ID de la caja abierta
    
            // Insertar la venta con el ID de la caja
            $stmt = $pdo->prepare("INSERT INTO sales (product_id, store_id, quantity,total_sale, price, date, register_id, worker_id, client_name, description) 
                                   VALUES (?, ?, ?,?, ?, DATE_SUB(NOW(), INTERVAL 5 HOUR), ?, ?, ?, ?)");
            $stmt->execute([$product_id, $store_id, $quantity,$total_sale, $price, $register_id, $_SESSION['worker_id'], $client_name, $description]);
    
            // Reducir la cantidad del producto en el inventario
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$quantity, $product_id]);
    
            $message = "Venta agregada exitosamente.";
        }
    }
}

// Obtener productos para el formulario de ventas
$stmt = $pdo->query("SELECT id, name, price FROM products WHERE quantity > 0 and store_id = $store_id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el registro de caja abierto para permitir cerrarlo
$stmt = $pdo->prepare("SELECT id FROM cash_register WHERE store_id = ? AND close_date IS NULL");
$stmt->execute([$store_id]);
$open_register = $stmt->fetch(PDO::FETCH_ASSOC);

// Variable para almacenar el saldo final más reciente
$last_closing_balance = 0;

// Ejecutar la consulta para obtener el saldo final de la caja más recientemente cerrada
try {
    // Preparar la consulta
    $stmt = $pdo->prepare("SELECT closing_balance FROM cash_register 
                            WHERE store_id = ? AND close_date IS NOT NULL 
                            ORDER BY close_date DESC 
                            LIMIT 1");
    // Ejecutar la consulta con el ID de la tienda
    $stmt->execute([$store_id]);

    // Recuperar el saldo final
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $last_closing_balance = $result['closing_balance'];
    }

} catch (PDOException $e) {
    // Manejo de errores
    echo "Error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agregar Venta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
    // Función para actualizar el precio automáticamente
    function updatePrice() {
        var productId = document.getElementById('product_id').value;
        var priceInput = document.getElementById('price');
        var products = <?php echo json_encode($products); ?>;
        var selectedProduct = products.find(product => product.id == productId);
        if (selectedProduct) {
            priceInput.value = selectedProduct.price || '';
        } else {
            priceInput.value = '';
        }
    }
</script>
</head>
<body>
<!-- Header con menú de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container-fluid">
<a class="navbar-brand" href="#">Sistema de Ventas</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
            <a class="nav-link" href="worker_dashboard.php">Inicio</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="view_inventory.php">Inventario</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="view_sales.php">Ventas</a>
        </li>
    </ul>
    <span class="navbar-text me-3">
        <?php echo htmlspecialchars($_SESSION['worker_name']); ?>
    </span>
    <a class="btn btn-outline-light" href="logout.php">Cerrar Sesión</a>
</div>
</div>
</nav>

<div class="container mt-5">
<h2>Agregar Venta</h2>

<!-- Mensajes de éxito o error -->
<?php if (isset($message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Formulario para agregar una venta -->
<?php if ($open_register): ?>
<form method="POST" action="">
    <div class="mb-3">
        <label for="product_id" class="form-label">Producto</label>
        <select class="form-select" id="product_id" name="product_id" onchange="updatePrice()" required>
            <option value="" disabled selected>Selecciona un producto</option>
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id']; ?>"><?= htmlspecialchars($product['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="quantity" class="form-label">Cantidad</label>
        <input type="number" class="form-control" id="quantity" name="quantity" required>
    </div>
    <div class="mb-3">
        <label for="price" class="form-label">Precio</label>
        <input type="number" step="0.01" class="form-control" id="price" name="price" required readonly>
    </div>
    <div class="mb-3">
        <label for="client_name" class="form-label">Nombre del Cliente</label>
        <input type="text" class="form-control" id="client_name" name="client_name" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Descripción de la venta</label>
        <input type="text" class="form-control" id="description" name="description" required>
    </div>
    <button type="submit" name="add_sale" class="btn btn-primary">Agregar Venta</button>
</form>
<?php else: ?>
<p class="alert alert-warning">Debe abrir una caja antes de registrar una venta.</p>
<form method="POST" action="">
    <button type="submit" name="open_register" class="btn btn-success">Abrir Caja</button>
</form>
<?php endif; ?>

<hr>

<!-- Formulario para cerrar caja -->
<?php if ($open_register): ?>
<h3>Cerrar Caja</h3>
<form method="POST" action="">
    <input type="hidden" name="register_id" value="<?= $open_register['id']; ?>">
    <div class="mb-3">
        <label for="cash_out" class="form-label">Dinero Salido</label>
        <input type="number" step="0.01" class="form-control" id="cash_out" name="cash_out" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Descripción</label>
        <input type="text" class="form-control" id="description" name="description" required>
    </div>
    <button type="submit" name="close_register" class="btn btn-danger">Cerrar Caja</button>
</form>
<?php else: ?>
<p>No hay caja abierta para cerrar.</p>
<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
