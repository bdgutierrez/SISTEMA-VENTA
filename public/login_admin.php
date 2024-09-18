<?php
// Inicia sesión para almacenar mensajes y el estado del usuario
session_start();

// Incluir conexión a la base de datos
include('../config/db.php'); // Asegúrate de tener este archivo con la conexión a la base de datos

// Manejar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Consulta para buscar al administrador con el correo proporcionado
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica si el administrador existe y la contraseña es correcta
    if ($admin && password_verify($password, $admin['password'])) {
        // Guarda la información del administrador en la sesión
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        
        // Redirecciona al dashboard del administrador
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Credenciales incorrectas
        $_SESSION['error'] = "Correo o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - Administrador</title>
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
                        <h3>Inicio de Sesión - Administrador</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_SESSION['error'])) {
                            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                            unset($_SESSION['error']);
                        }
                        ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <a href="register_admin.php" class="btn btn-link">Registrar nuevo administrador</a>
                        <br>
                        <a href="../index.html" class="btn btn-link">Volver al Inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
