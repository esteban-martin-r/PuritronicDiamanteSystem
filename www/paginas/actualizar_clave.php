<?php
require_once "conexion.php";

$usuario = "root";
$clave_ingresada = "cisco123";

$stmt = $conn->prepare("SELECT clave FROM usuarios WHERE usuario = :usuario");
$stmt->bindParam(':usuario', $usuario);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

var_dump($row['clave']); 

if (password_verify($clave_ingresada, $row['clave'])) {
    echo "Contraseña correcta";
} else {
    echo "Contraseña incorrecta";
}
