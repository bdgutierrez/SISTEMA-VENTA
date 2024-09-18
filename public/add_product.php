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
        .product-image {
            max-width: 100px;
            height: auto;
        }
        .mb-4 {
            margin-top: 50px;
        }
        .container {
            max-width: 600px;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            font-size: 1.1em;
        }
        .card-header{
            background-color: #005f59;
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
                </ul>
                <span class="navbar-text me-3 text-light">
                    <?php echo htmlspecialchars($_SESSION['worker_name']); ?>
                </span>
                <a class="btn btn-outline-light" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header  text-white">
                <h3 class="mb-0">Agregar Nuevo Producto</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del Producto</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Precio</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoría</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['id']); ?>">
                                    <?= htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagen del Producto (opcional)</label>
                        <input type="file" class="form-control" id="image" name="image">
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_product" class="btn btn-success btn-block">Agregar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
