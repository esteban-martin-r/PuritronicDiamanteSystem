<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login_purificadora.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: token CSRF inválido.");
}

$codigo_cliente = isset($_POST['codigo_cliente']) ? trim($_POST['codigo_cliente']) : null;
$nota = isset($_POST['nota']) ? trim($_POST['nota']) : null;
$accion = isset($_POST['accion']) ? $_POST['accion'] : null;

if (!$codigo_cliente || !$accion) {
    die("Faltan datos obligatorios.");
}

try {
    if ($accion === 'agregar' && $nota !== '') {
        $sql = "UPDATE clientes SET nota = :nota WHERE codigo_cliente = :codigo_cliente";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':nota' => $nota, ':codigo_cliente' => $codigo_cliente]);

        $_SESSION['mensaje'] = "Nota agregada correctamente.";
    } elseif ($accion === 'eliminar') {
        $sql = "UPDATE clientes SET nota = NULL WHERE codigo_cliente = :codigo_cliente";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':codigo_cliente' => $codigo_cliente]);

        $_SESSION['mensaje'] = "Nota eliminada correctamente.";
    } else {
        $_SESSION['mensaje'] = "No se realizó ninguna acción.";
    }

    header("Location: ../index.php?busqueda=" . urlencode($codigo_cliente));
    exit;
} catch (PDOException $e) {
    die("Error al actualizar nota: " . $e->getMessage());
}
