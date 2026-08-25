<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$_SESSION['mantenimiento_alertas_cerradas'] = true;

echo json_encode(['success' => true]);
