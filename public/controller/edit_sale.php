<?php
// Iniciar la sesión
session_start();

// Verificar si el administrador ha iniciado sesión
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login_admin.php");
    exit();
}

// Incluir conexión a la base de datos
include('../../config/db.php');

// Verificar si se ha proporcionado el sale_id y validarlo
$sale_id = filter_input(INPUT_GET, 'sale_id', FILTER_VALIDATE_INT);
if (!$sale_id) {
    echo "ID de venta no válido";
    exit();
}

// Obtener la venta desde la base de datos
$stmt = $pdo->prepare("SELECT s.id,s.quantity, s.store_id, s.date, p.name AS product_name, s.client_name, s.price
                       FROM sales s
                       JOIN products p ON s.product_id = p.id
                       WHERE s.id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no se encuentra la venta
if (!$sale) {
    echo "Venta no encontrada.";
    exit();
}

// Si el formulario se envía (vía POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y validar los campos enviados por el formulario
    $client_name = filter_input(INPUT_POST, 'client_name', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if ($client_name && $price !== false) {
        // Actualizar la venta en la base de datos
        $updateStmt = $pdo->prepare("UPDATE sales SET client_name = ?, price = ?, quantity = ? WHERE id = ?");
        $updated = $updateStmt->execute([$client_name, $price,$quantity, $sale_id]);

        if ($updated) {
            // Redirigir al panel de ventas de la tienda
            header("Location: ../store_dashboard.php?store_id=" . $store_id);
            exit();
        } else {
            echo "Error al actualizar la venta.";
        }
    } else {
        echo "Datos no válidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css"> <!-- Incluye tu archivo de estilos aquí -->
    <style>
        /* Estilos personalizados para que coincidan con admin_view_sales */
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h3 {
            color: #6c8181;
        }
        .form-label {
            color: #6c8181;
        }
        .btn-primary {
            background-color: #f48a54;
            border-color: #f48a54;
        }
        .btn-primary:hover {
            background-color: #e67c4f;
            border-color: #e67c4f;
        }
        .btn-secondary {
            background-color: #6c8181;
            border-color: #6c8181;
        }
        .btn-secondary:hover {
            background-color: #5a6b6b;
            border-color: #5a6b6b;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h3>Editar Venta</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="product" class="form-label">Producto</label>
                <input type="text" class="form-control" id="product" value="<?= htmlspecialchars($sale['product_name']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="client_name" class="form-label">Nombre del Cliente</label>
                <input type="text" class="form-control" id="client_name" name="client_name" value="<?= htmlspecialchars($sale['client_name']); ?>">
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Importe</label>
                <input type="number" class="form-control" id="price" name="price" value="<?= htmlspecialchars($sale['price']); ?>" step="0.01">
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Cantidad</label>
                <input type="number" class="form-control" id="price" name="quantity" value="<?= htmlspecialchars($sale['quantity']); ?>" step="0.01">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="../store_dashboard.php?store_id=<?= $sale['store_id']; ?>" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
