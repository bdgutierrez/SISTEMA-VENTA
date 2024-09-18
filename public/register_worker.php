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
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $store_id = $_POST['store_id'];

    // Manejo de la imagen subida
    $photo = $_FILES['photo'];
    $photo_name = time() . '_' . basename($photo['name']);
    $target_dir = "uploads/workers/";
    $target_file = $target_dir . $photo_name;

    // Intentar subir el archivo
    if (move_uploaded_file($photo['tmp_name'], $target_file)) {
        // Insertar el trabajador en la base de datos
        $stmt = $pdo->prepare("INSERT INTO workers (name, email, password, store_id, photo, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $store_id, $target_file, $_SESSION['admin_id']]);

        // Redirigir al dashboard del administrador
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Hubo un problema al subir la foto del trabajador.";
    }
}

// Obtener las tiendas del administrador
$stmt = $pdo->prepare("SELECT * FROM stores WHERE admin_id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Trabajador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header {
            background: #6c8181;
            color: white;
        }
        .card-footer{
            text-decoration: none;
            background: #6c8181;
            color: white;
        }
        .card-footer a{
            text-decoration: none;
            color: white;
        }
        .btn-primary {
            background-color: #f48a54;
            border: #f48a54;
        }
        .btn-primary:hover {
            background-color: #e87a48;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Registrar Trabajador</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del Trabajador</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="store_id" class="form-label">Asignar a Tienda</label>
                                <select class="form-control" id="store_id" name="store_id" required>
                                    <?php foreach ($stores as $store): ?>
                                        <option value="<?= $store['id']; ?>"><?= htmlspecialchars($store['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="photo" class="form-label">Foto del Trabajador</label>
                                <input type="file" class="form-control" id="photo" name="photo" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Registrar Trabajador</button>
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
