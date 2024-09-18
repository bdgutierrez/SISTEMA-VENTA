<?php
ob_start(); // Iniciar buffering de salida

// Inicia sesión
session_start();

// Verificar si el administrador ha iniciado sesión
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Incluir conexión a la base de datos
include('../config/db.php');

// Verificar si se ha proporcionado un worker_id y validar que sea un entero
$worker_id = filter_var($_GET['worker_id'], FILTER_VALIDATE_INT);
if ($worker_id === false) {
    header("Location: admin_dashboard.php");
    exit();
}

// Obtener la información del trabajador
$stmt = $pdo->prepare("SELECT * FROM workers WHERE id = ? AND store_id IN (SELECT id FROM stores WHERE admin_id = ?)");
$stmt->execute([$worker_id, $_SESSION['admin_id']]);
$worker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$worker) {
    header("Location: admin_dashboard.php");
    exit();
}

// Manejar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $salary = filter_var($_POST['salary'], FILTER_VALIDATE_FLOAT);
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    // Procesar la imagen subida
    $photo = $worker['photo']; // Mantener la foto actual por defecto
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Definir los tipos de archivo permitidos
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = 'uploads/workers/';
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Eliminar la foto antigua si existe
                if ($photo && file_exists($photo)) {
                    unlink($photo);
                }

                // Actualizar el nombre de la foto en la base de datos
                $photo = $dest_path;
            } else {
                $message = "Error al mover el archivo subido.";
            }
        } else {
            $message = "Tipo de archivo no permitido. Solo se permiten imágenes JPG, JPEG, PNG y GIF.";
        }
    }

    // Actualizar la información del trabajador en la base de datos
    $updateStmt = $pdo->prepare("UPDATE workers SET name = ?, email = ?, photo = ?, salary = ? WHERE id = ?");
    $updateStmt->execute([$name, $email, $photo, $salary, $worker_id]);

    // Actualizar la contraseña si se ha proporcionado una nueva
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $passwordStmt = $pdo->prepare("UPDATE workers SET password = ? WHERE id = ?");
        $passwordStmt->execute([$hashed_password, $worker_id]);
    }

    // Redirigir de nuevo a la página de detalles del trabajador para reflejar los cambios
    header("Location: worker_details.php?worker_id=" . $worker_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Trabajador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link" href="admin_dashboard.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="store_dashboard.php?store_id=<?= htmlspecialchars($worker['store_id']); ?>">Volver a la Tienda</a>
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
        <h3>Detalles del Trabajador <?= htmlspecialchars($worker['name']); ?></h3>
        
        <!-- Mostrar los detalles del trabajador -->
        <div class="mb-4">
            <h4>Información del Trabajador</h4>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($worker['name']); ?></p>
            <p><strong>Correo Electrónico:</strong> <?= htmlspecialchars($worker['email']); ?></p>
            <p><strong>Foto:</strong> <img src="<?= htmlspecialchars($worker['photo']); ?>" alt="Foto del Trabajador" class="img-thumbnail" style="width: 100px;"></p>
        </div>

        <!-- Formulario para actualizar la información del trabajador -->
        <h4>Actualizar Información del Trabajador</h4>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($worker['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($worker['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="photo" class="form-label">Foto (subir nueva imagen)</label>
                <input type="file" class="form-control" id="photo" name="photo">
                <small class="form-text text-muted">Tipos permitidos: JPG, JPEG, PNG, GIF.</small>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Nueva Contraseña</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
                <small class="form-text text-muted">Deje este campo vacío si no desea cambiar la contraseña.</small>
            </div>
            <div class="mb-3">
                <label for="salary" class="form-label">Salario</label>
                <input type="number" class="form-control" id="salary" name="salary" value="<?= htmlspecialchars($worker['salary']); ?>" step="0.01">
                <small class="form-text text-muted">Ingrese el salario en formato numérico (por ejemplo, 1234.56).</small>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
