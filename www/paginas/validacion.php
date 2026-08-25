<?php
session_start();
require_once "conexion.php";


$Vusuario = trim($_POST["txtusuario"] ?? '');
$Vclave   = trim($_POST["txtpassword"] ?? '');

if (empty($Vusuario) || empty($Vclave)) {
    header("Location: /purificadora/login_purificadora.php?error=1");
    exit;
}

try {
    // Se agrega la columna "rol" a la consulta
    $sqlLOGIN = "SELECT usuario, clave, rol FROM usuarios WHERE usuario = :usuario LIMIT 1";
    $stmt = $conn->prepare($sqlLOGIN);
    $stmt->bindParam(':usuario', $Vusuario, PDO::PARAM_STR);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if (password_verify($Vclave, $row['clave'])) {

            $_SESSION["validado"] = true;
            $_SESSION["usuario"]  = $row['usuario'];
            $_SESSION["rol"]      = $row['rol']; // 'administrador' o 'empleado'

            $conn = null;
            header("Location: ../index.php");
            exit;
        } else {
            $conn = null;
            header("Location: ../login_purificadora.php?error=1");
            exit;
        }
    } else {
        header("Location: /purificadora/login_purificadora.php?error=1");
        exit;
    }
} catch (PDOException $e) {
    echo "Error en validación: " . $e->getMessage();
    exit;
}