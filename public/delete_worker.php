<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../config/db.php';

// Obtener el trabajador y la tienda
$worker_id = $_GET['worker_id'];
$store_id = $_GET['store_id'];

// Eliminar trabajador
$stmt = $pdo->prepare("DELETE FROM workers WHERE id = ?");
$stmt->execute([$worker_id]);

header("Location: manage_workers.php?store_id=$store_id");
exit();
