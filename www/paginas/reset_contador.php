<?php
require_once "conexion.php";
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_total_ref'])) {
    $nuevo_valor = (int)$_POST['nuevo_total_ref'];

    try {
        // Insertamos o actualizamos la referencia para que el contador vuelva a 0
        // (La resta en el index: total_historico - nuevo_valor será 0)
        $sql = "INSERT INTO cambio_agua (garrafones_referencia, fecha_actualizacion) 
                VALUES (:valor, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':valor' => $nuevo_valor]);

        echo json_encode(['status' => 'ok']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}