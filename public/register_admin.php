<?php
// Inicia sesión para almacenar mensajes y el estado del usuario
session_start();

// Incluir conexión a la base de datos
include('../config/db.php'); // Asegúrate de tener este archivo con la conexión a la base de datos

// Manejar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Encripta la contraseña
    $business_name = $_POST['business_name'];

    // Verifica si el correo ya está registrado
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "El correo ya está registrado.";
    } else {
        // Inserta el nuevo administrador y el negocio
        $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $password])) {
            $admin_id = $pdo->lastInsertId();

            // Insertar el negocio asociado
            $stmt = $pdo->prepare("INSERT INTO business (name, admin_id) VALUES (?, ?)");
            if ($stmt->execute([$business_name, $admin_id])) {
                $_SESSION['success'] = "Registro exitoso. Puedes iniciar sesión.";
            } else {
                $_SESSION['error'] = "Hubo un error al registrar el negocio.";
            }
        } else {
            $_SESSION['error'] = "Hubo un error al registrar el administrador.";
        }
    }
    
    header("Location: register_admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Administrador</title>
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
                        <h3>Registrar Nuevo Administrador</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_SESSION['success'])) {
                            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                            unset($_SESSION['success']);
                        } elseif (isset($_SESSION['error'])) {
                            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                            unset($_SESSION['error']);
                        }
                        ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre Completo</label>
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
                                <label for="business_name" class="form-label">Nombre del Negocio</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Registrar</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
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
