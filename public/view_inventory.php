<?php
// Iniciar sesión
session_start();

// Verificar si el trabajador ha iniciado sesión
if (!isset($_SESSION['worker_id'])) {
    header("Location: login_worker.php");
    exit();
}

// Incluir conexión a la base de datos
include('../config/db.php');

// Obtener todas las categorías disponibles en orden alfabético
$stmt = $pdo->prepare("SELECT id, name FROM category ORDER BY name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar la eliminación de un producto
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND store_id = ?");
    $stmt->execute([$delete_id, $_SESSION['store_id']]);
    header("Location: view_inventory.php");
    exit();
}

// Manejar la edición de un producto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];
    $target_file = null;

    // Verificar si se ha subido una nueva imagen
    if (!empty($_FILES['image']['name'])) {
        $image_name = $_FILES['image']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image_name);

        // Mover el archivo subido al directorio de destino
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Actualizar producto con la nueva imagen
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, image = ?, category_id = ? WHERE id = ? AND store_id = ?");
            $stmt->execute([$name, $description, $price, $quantity, $target_file, $category_id, $edit_id, $_SESSION['store_id']]);
        } else {
            echo "Error al cargar la imagen.";
            exit();
        }
    } else {
        // Si no se ha subido una nueva imagen, actualizar sin cambiar la imagen actual
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, category_id = ? WHERE id = ? AND store_id = ?");
        $stmt->execute([$name, $description, $price, $quantity, $category_id, $edit_id, $_SESSION['store_id']]);
    }

    header("Location: view_inventory.php");
    exit();
}

// Manejar la adición de un nuevo producto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];

    // Verificar si se ha subido una imagen
    $target_file = null; // Inicializar como null por defecto
    if (!empty($_FILES['image']['name'])) {
        $image_name = $_FILES['image']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image_name);

        // Mover el archivo subido al directorio de destino
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            echo "Error al cargar la imagen.";
            exit();
        }
    }

    // Insertar nuevo producto en la base de datos
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, quantity, store_id, image, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $quantity, $_SESSION['store_id'], $target_file, $category_id]);

    header("Location: view_inventory.php");
    exit();
}

// Manejar la búsqueda de productos y el filtrado por categoría
$search_query = '';
$category_filter = '';
$params = [$_SESSION['store_id']];

$sql = "SELECT p.*, c.name AS category_name FROM products p JOIN category c ON p.category_id = c.id WHERE p.store_id = ?";

if (isset($_GET['category_id']) && $_GET['category_id'] != '') {
    $category_filter = $_GET['category_id'];
    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    $sql .= " AND p.name LIKE ?";
    $params[] = '%' . $search_query . '%';
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
        body {
            background-color: #f2f2f2;
        }
        .navbar-dark.bg-dark {
            background-color: #122620 !important;
        }
        .btn-primary {
            background-color: #005f59;
            border-color: #005f59;
        }
        .btn-primary:hover {
            background-color: #004d47;
            border-color: #004d47;
        }
        .btn-outline-light {
            color: #ffffff;
            border-color: #ffffff;
        }
        .product-image {
            max-width: 100px;
            height: auto;
        }
        .card-header {
            background-color: #005f59;
            color: white;
        }
        .table {
            background-color: white;
        }
        .btn-warning {
            background-color: #ffc107;
        }
    </style>
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
                    <li class="nav-item">
                        <a class="nav-link" href="factura.php">Ventas</a>
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
        <h1>Inventario de la Tienda</h1>

        <!-- Formulario de filtro -->
        <form method="GET" action="view_inventory.php" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Filtrar por Categoría:</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Todas las Categorías</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category['id']); ?>" <?= $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar por Nombre:</label>
                    <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-4 mt-4">
                    <button type="submit" class="btn btn-primary mt-2">Filtrar</button>
                    <a href="add_product.php" class="btn btn-secondary mt-2">Agregar producto</a>
                </div>
            </div>
        </form>

        <!-- Listado de productos en el inventario -->
        <div class="card mt-5">
            <div class="card-header">Productos en Inventario</div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['image']): ?>
                                        <img src="<?= htmlspecialchars($product['image']); ?>" alt="Imagen del Producto" class="product-image">
                                    <?php else: ?>
                                        Sin imagen
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']); ?></td>
                                <td><?= htmlspecialchars($product['description']); ?></td>
                                <td><?= htmlspecialchars($product['price']); ?></td>
                                <td><?= htmlspecialchars($product['quantity']); ?></td>
                                <td><?= htmlspecialchars($product['category_name']); ?></td>
                                <td>
                                    <!-- Botón para abrir el modal de edición -->
                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editProductModal" 
                                    data-id="<?= htmlspecialchars($product['id']); ?>"
                                    data-name="<?= htmlspecialchars($product['name']); ?>"
                                    data-description="<?= htmlspecialchars($product['description']); ?>"
                                    data-price="<?= htmlspecialchars($product['price']); ?>"
                                    data-quantity="<?= htmlspecialchars($product['quantity']); ?>"
                                    data-category_id="<?= htmlspecialchars($product['category_id']); ?>"
                                    >Editar</button>

                                    <a href="view_inventory.php?delete_id=<?= htmlspecialchars($product['id']); ?>" class="btn btn-danger btn-sm">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="edit_id" id="edit-id">

                        <div class="mb-3">
                            <label for="edit-name" class="form-label">Nombre del Producto</label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-description" class="form-label">Descripción</label>
                            <textarea name="description" id="edit-description" class="form-control" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="edit-price" class="form-label">Precio</label>
                            <input type="number" name="price" id="edit-price" class="form-control" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-quantity" class="form-label">Cantidad</label>
                            <input type="number" name="quantity" id="edit-quantity" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-category_id" class="form-label">Categoría</label>
                            <select name="category_id" id="edit-category_id" class="form-select" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']); ?>">
                                        <?= htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-image" class="form-label">Imagen del Producto</label>
                            <input type="file" name="image" id="edit-image" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar los datos del producto en el modal
        var editProductModal = document.getElementById('editProductModal');
        editProductModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var description = button.getAttribute('data-description');
            var price = button.getAttribute('data-price');
            var quantity = button.getAttribute('data-quantity');
            var category_id = button.getAttribute('data-category_id');

            var modalTitle = editProductModal.querySelector('.modal-title');
            var editId = editProductModal.querySelector('#edit-id');
            var editName = editProductModal.querySelector('#edit-name');
            var editDescription = editProductModal.querySelector('#edit-description');
            var editPrice = editProductModal.querySelector('#edit-price');
            var editQuantity = editProductModal.querySelector('#edit-quantity');
            var editCategoryId = editProductModal.querySelector('#edit-category_id');

            modalTitle.textContent = 'Editar Producto: ' + name;
            editId.value = id;
            editName.value = name;
            editDescription.value = description;
            editPrice.value = price;
            editQuantity.value = quantity;
            editCategoryId.value = category_id;
        });
    </script>
</body>
</html>
