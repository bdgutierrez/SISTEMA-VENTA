<?php
// Iniciar sesión
session_start();

// Incluir conexión a la base de datos
include('../config/db.php');

// Manejar el cambio de negocio
if (isset($_GET['action']) && $_GET['action'] == 'change_business') {
    // Limpiar las variables de sesión relacionadas con el negocio y trabajador
    unset($_SESSION['business_id']);
    unset($_SESSION['stores']);
    unset($_SESSION['worker_id']);
    unset($_SESSION['worker_name']);
    unset($_SESSION['store_id']);
    unset($_SESSION['store_name']);
    header("Location: login_worker.php"); // Redirigir a la página de inicio de sesión
    exit();
}

// Manejar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Primer paso: verificar el ID del negocio
    if (isset($_POST['business_id']) && !isset($_SESSION['business_id'])) {
        $business_id = $_POST['business_id'];
        $_SESSION['business_id'] = $business_id;

        // Consultar el negocio en la base de datos
        $stmt = $pdo->prepare("SELECT id, name FROM business WHERE id = ?");
        $stmt->execute([$business_id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$business) {
            $error = "ID de negocio no válido.";
            unset($_SESSION['business_id']); // Limpiar la sesión si el negocio no es válido
        } else {
            // Obtener las tiendas del negocio y guardarlas en la sesión
            $stmt = $pdo->prepare("SELECT id, name FROM stores WHERE business_id = ?");
            $stmt->execute([$business_id]);
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($stores)) {
                $error = "No hay tiendas asociadas con este negocio.";
                unset($_SESSION['business_id']); // Limpiar la sesión si no hay tiendas
            } else {
                $_SESSION['stores'] = $stores; // Guardar las tiendas en la sesión
            }
        }
    }
    // Segundo paso: verificar las credenciales del trabajador
    elseif (isset($_POST['email']) && isset($_SESSION['business_id'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $store_id = $_POST['store_id'];

        // Consultar el trabajador en la base de datos
        $stmt = $pdo->prepare("SELECT w.id, w.name, w.password, s.id AS store_id, s.name AS store_name
                                FROM workers w
                                JOIN stores s ON w.store_id = s.id
                                WHERE w.email = ? AND s.business_id = ? AND s.id = ?");
        $stmt->execute([$email, $_SESSION['business_id'], $store_id]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($worker && password_verify($password, $worker['password'])) {
            // Iniciar sesión para el trabajador
            $_SESSION['worker_id'] = $worker['id'];
            $_SESSION['worker_name'] = $worker['name'];
            $_SESSION['store_id'] = $worker['store_id'];
            $_SESSION['store_name'] = $worker['store_name'];

            // Actualizar la fecha del último inicio de sesión y la ubicación en la base de datos
            $stmt = $pdo->prepare("UPDATE workers SET last_session = NOW(), location = ? WHERE id = ?");
            $stmt->execute([$_SERVER['REMOTE_ADDR'], $_SESSION['worker_id']]);

            // Redirigir al dashboard del trabajador
            header("Location: worker_dashboard.php");
            exit();
        } else {
            $error = "Credenciales incorrectas o trabajador no encontrado en el negocio y tienda seleccionados.";
        }
    }
}

// Si la sesión del negocio está establecida, verificar que las tiendas también lo estén
if (isset($_SESSION['business_id']) && !isset($_SESSION['stores'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM stores WHERE business_id = ?");
    $stmt->execute([$_SESSION['business_id']]);
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($stores)) {
        $error = "No hay tiendas asociadas con este negocio.";
        unset($_SESSION['business_id']);
        unset($_SESSION['stores']);
    } else {
        $_SESSION['stores'] = $stores;
    }
}

// Obtener todos los negocios para el formulario inicial
$stmt = $pdo->query("SELECT id, name FROM business");
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión como Trabajador</title>
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
                        <h3>Iniciar Sesión como Trabajador</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <!-- Primer paso: Verificación del ID del negocio -->
                        <?php if (!isset($_SESSION['business_id'])): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="business_id" class="form-label">ID del Negocio</label>
                                    <input type="text" class="form-control" id="business_id" name="business_id" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Verificar Negocio</button>
                            </form>
                        <!-- Segundo paso: Selección de la tienda y credenciales del trabajador -->
                        <?php elseif (isset($_SESSION['business_id']) && !isset($_SESSION['worker_id'])): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="store_id" class="form-label">Selecciona la Tienda</label>
                                    <select class="form-control" id="store_id" name="store_id" required>
                                        <option value="" disabled selected>Selecciona una tienda</option>
                                        <?php foreach ($_SESSION['stores'] as $store): ?>
                                            <option value="<?= $store['id']; ?>"><?= htmlspecialchars($store['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                                <a href="?action=change_business" class="btn btn-link">Cambiar Negocio</a>
                            </form>
                        <?php endif; ?>
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
