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

// Manejar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $admin_id = $_SESSION['admin_id'];

    // Obtener el business_id asociado al administrador
    $stmt = $pdo->prepare("SELECT id FROM business WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $business = $stmt->fetch(PDO::FETCH_ASSOC);
    $business_id = $business['id'];

    // Insertar la nueva tienda en la base de datos
    $stmt = $pdo->prepare("INSERT INTO stores (name, location, admin_id, business_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $location, $admin_id, $business_id]);

    // Redirigir al dashboard
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Crear Nueva Tienda</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la Tienda</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="location" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Crear Tienda</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <a href="admin_dashboard.php" class="btn btn-link">Volver al Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
