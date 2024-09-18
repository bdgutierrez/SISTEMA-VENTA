<?php
// Iniciar sesión
session_start();

// Verificar si el administrador ha iniciado sesión
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Incluir conexión a la base de datos
include('../config/db.php');

// Verificar si se ha proporcionado un store_id
if (!isset($_GET['store_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$store_id = $_GET['store_id'];

// Obtener la información de la tienda
$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ? AND admin_id = ?");
$stmt->execute([$store_id, $_SESSION['admin_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header("Location: admin_dashboard.php");
    exit();
}

// Obtener las categorías disponibles
$stmt = $pdo->prepare("SELECT id, name FROM category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar si se ha seleccionado una categoría para filtrar
$selected_category = isset($_GET['category_id']) ? $_GET['category_id'] : '';

// Modificar la consulta SQL para filtrar por categoría si se ha seleccionado una
$sql = "SELECT p.id, p.name, p.description, p.price, p.quantity, p.image, c.name as category 
        FROM products p 
        JOIN category c ON p.category_id = c.id 
        WHERE p.store_id = ?";
$params = [$store_id];

if ($selected_category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $selected_category;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de la Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Menú de navegación -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Panel de la Tienda</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="store_dashboard.php?store_id=<?= htmlspecialchars($store_id); ?>">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_view_sales.php?store_id=<?= htmlspecialchars($store_id); ?>">Ver Ventas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_view_inventory.php?store_id=<?= htmlspecialchars($store_id); ?>">Ver Inventario</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container mt-4">
        <h3>Inventario de la Tienda <?= htmlspecialchars($store['name']); ?></h3>

        <!-- Formulario de filtro por categoría -->
        <form method="GET" action="admin_view_inventory.php" class="mb-4">
            <input type="hidden" name="store_id" value="<?= htmlspecialchars($store_id); ?>">
            <div class="input-group">
                <select name="category_id" class="form-select">
                    <option value="">Todas las Categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['id']); ?>" <?= $selected_category == $category['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <h4 class="mt-4">Lista de Productos</h4>
        <?php if (count($products) > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= htmlspecialchars($product['image']); ?>" alt="Imagen de <?= htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php else: ?>
                                    <img src="path/to/default-image.jpg" alt="Imagen no disponible" class="product-image">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($product['name']); ?></td>
                            <td><?= htmlspecialchars($product['description']); ?></td>
                            <td><?= htmlspecialchars($product['price']); ?></td>
                            <td><?= htmlspecialchars($product['quantity']); ?></td>
                            <td><?= htmlspecialchars($product['category']); ?></td>
                            <td>
                                <a href="edit_product.php?product_id=<?= htmlspecialchars($product['id']); ?>" class="btn btn-primary">Editar</a>
                                <a href="delete_product.php?product_id=<?= htmlspecialchars($product['id']); ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay productos en el inventario de esta tienda.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
