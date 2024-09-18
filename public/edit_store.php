<?php
// Inicia sesión
session_start();

// Verificar si el administrador ha iniciado sesión
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Incluir conexión a la base de datos
include('../config/db.php');

// Verificar si se ha proporcionado un ID de tienda
if (!isset($_GET['store_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$store_id = $_GET['store_id'];

// Obtener la información de la tienda actual
$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ? AND admin_id = ?");
$stmt->execute([$store_id, $_SESSION['admin_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    // Si no se encuentra la tienda o no pertenece al administrador, redirigir
    header("Location: admin_dashboard.php");
    exit();
}

// Manejar la actualización de la tienda
if (isset($_POST['update_store'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];

    // Actualizar los datos de la tienda
    $stmt = $pdo->prepare("UPDATE stores SET name = ?, location = ? WHERE id = ?");
    $stmt->execute([$name, $location, $store_id]);

    // Redirigir de vuelta al panel de administración
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f7f7;
        }
        .navbar {
            background-color: #6c8181;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .btn-primary {
            background-color: #f48a54;
            border-color: #f48a54;
        }
        .btn-primary:hover {
            background-color: #e47947;
            border-color: #e47947;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
        }
        h3 {
            color: #6c8181;
        }
    </style>
</head>
<body>
    <!-- Menú de navegación -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Panel de Administración</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container">
        <h3 class="mb-4">Editar Tienda</h3>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="name" class="form-label">Nombre de la Tienda</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($store['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">Ubicación</label>
                <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($store['location']); ?>" required>
            </div>
            <button type="submit" name="update_store" class="btn btn-primary">Actualizar Tienda</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
