<?php
require_once "conexion.php";
session_start();

// Incluir alertas de mantenimiento
require_once "../includes/alertas_mantenimiento.php";

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$cliente = null;
$mensaje = "";

// 1. FASE DE BÚSQUEDA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['busqueda'])) {
    $busqueda = trim($_POST['busqueda']);
    
    // JOIN para traer datos del cliente y su progreso actual
    $query = "SELECT c.*, COALESCE(p.garrafones_acumulados, 0) AS garrafones_promocion 
              FROM clientes c 
              LEFT JOIN cliente_progreso_promo p ON c.codigo_cliente = p.codigo_cliente 
              WHERE c.codigo_cliente = :busqueda OR c.nombre_cliente LIKE :like";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':busqueda' => $busqueda,
        ':like' => "%$busqueda%"
    ]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $mensaje = '<div class="alert alert-danger text-center shadow-sm">Cliente no encontrado.</div>';
    }
}

// 2. FASE DE ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar'])) {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad: Token inválido.");
    }

    $id_cliente = $_POST['codigo_cliente']; 
    $nombre_cliente = trim($_POST['nombre_cliente']);
    $promociones = min((int)$_POST['promociones'], 9);

    try {
        $conn->beginTransaction();

        // Actualizar nombre en tabla clientes
        $stmt1 = $conn->prepare("UPDATE clientes SET nombre_cliente = :nombre WHERE codigo_cliente = :id");
        $stmt1->execute([':nombre' => $nombre_cliente, ':id' => $id_cliente]);

        // Actualizar puntos en tabla progreso
        $stmt2 = $conn->prepare("UPDATE cliente_progreso_promo SET garrafones_acumulados = :promos WHERE codigo_cliente = :id");
        $stmt2->execute([':promos' => $promociones, ':id' => $id_cliente]);

        $conn->commit();
        $mensaje = '<div class="alert alert-success text-center shadow-sm">✅ Datos actualizados correctamente.</div>';
        
    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = '<div class="alert alert-danger text-center">Error al actualizar: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente - Purificadora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php require_once "purifIcon.php"; ?>
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/assets.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php render_alertas_mantenimiento(); ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-primary text-white py-3 text-center">
                    <h3 class="h5 m-0">Editar Perfil de Cliente</h3>
                </div>
                <div class="card-body p-4">

                    <?= $mensaje ?>

                    <?php if ($cliente && !isset($_POST['actualizar'])): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente']) ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Código del Cliente (No editable)</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($cliente['codigo_cliente']) ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="nombre_cliente" class="form-label fw-bold">Nombre del Cliente</label>
                                <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente"
                                       value="<?= htmlspecialchars($cliente['nombre_cliente']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="promociones" class="form-label fw-bold">Garrafones Acumulados para Promoción</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="promociones" name="promociones"
                                           value="<?= (int)$cliente['garrafones_promocion'] ?>" min="0" max="9" required>
                                    <span class="input-group-text">/ 10</span>
                                </div>
                                <div class="form-text text-danger mt-1">
                                    * El máximo es 9. Al llegar a 10, el sistema aplica la promoción automáticamente.
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" name="actualizar" class="btn btn-success py-2">
                                    Guardar Cambios
                                </button>
                                <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <p class="text-muted small">Regresa al inicio para buscar otro cliente o ver los cambios.</p>
                            <a href="../index.php" class="btn btn-primary px-4">Ir al Inicio</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php require_once "footer.php"; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>