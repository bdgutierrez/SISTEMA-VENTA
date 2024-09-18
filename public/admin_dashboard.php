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

// Obtener la información del administrador y su negocio
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT business.name, id  FROM business WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$business = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener todas las tiendas del administrador
$stmt = $pdo->prepare("SELECT * FROM stores WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar la eliminación de una tienda
if (isset($_POST['delete_store'])) {
    $store_id = $_POST['store_id'];
    $stmt = $pdo->prepare("DELETE FROM stores WHERE id = ?");
    $stmt->execute([$store_id]);
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
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
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .list-group-item {
            background-color: white;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
        }
        .list-group-item h5 {
            color: #6c8181;
        }
        .btn-link {
            color: #6c8181;
            text-decoration: underline;
        }
        .btn-link:hover {
            color: #4b6060;
        }
        .container h3 {
            color: #6c8181;
        }
        .container p {
            color: #6c8181;
        }
        .btn-group a{
            
            left: 10px;
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
                        <a class="nav-link" href="#"><?= $business['name']; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register_worker.php">Registrar Trabajador</a>
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
        <div class="d-flex justify-content-between mb-4">
            <h3>Id de tu negocio:<?= $business['id']; ?> </h3>
            
            <a href="create_store.php" class="btn btn-primary">Crear Nueva Tienda</a>
        </div>
        <h3>Tiendas</h3>
        <?php if (count($stores) > 0): ?>
            <div class="list-group">
                <?php foreach ($stores as $store): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($store['name']); ?></h5> 
                            <p class="mb-1">Ubicación: <?= htmlspecialchars($store['location']); ?></p>
                             <a href="store_dashboard.php?store_id=<?= $store['id']; ?>" class="btn btn-link">Ir a la tienda</a> 
                            </div> 
                            <div class="btn-group">
                                 <a href="edit_store.php?store_id=<?= $store['id']; ?>" class="btn btn-warning">Editar</a> 
                                 <form method="POST" action="" class="d-inline-block" onsubmit="return confirm('¿Estás seguro de eliminar esta tienda?');"> 
                                    <input type="hidden" name="store_id" value="<?= $store['id']; ?>"> 
                                    <button type="submit" name="delete_store" class="btn btn-danger">Eliminar</button> 
                                </form>
                             </div>
                             </div>
                              <?php endforeach; ?> 
                            </div> <?php else: ?>
                                 <p>No se han creado tiendas aún.</p>
                                  <?php endif; ?>
                                 </div>
                                 <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
 </html>

