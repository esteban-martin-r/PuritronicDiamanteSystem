<?php
require_once "conexion.php";
session_start();

// Incluir alertas de mantenimiento
require_once "../includes/alertas_mantenimiento.php";

// Si no hay token CSRF, lo generamos (por si se entra directo a esta página)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$cliente = null;
$busquedaHecha = false;
$mensaje = "";

// 1. FASE DE BÚSQUEDA (Para confirmar a quién vamos a borrar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['busqueda'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad: Token CSRF inválido.");
    }

    $busquedaHecha = true;
    $busqueda = trim($_POST['busqueda']);

    // Buscamos con JOIN para mostrar también su progreso actual antes de borrar
    $sql = "SELECT c.*, COALESCE(p.garrafones_acumulados, 0) AS puntos 
            FROM clientes c 
            LEFT JOIN cliente_progreso_promo p ON c.codigo_cliente = p.codigo_cliente 
            WHERE c.codigo_cliente = :busqueda OR c.nombre_cliente LIKE :like";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':busqueda' => $busqueda,
        ':like' => "%$busqueda%"
    ]);

    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. FASE DE ELIMINACIÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar']) && isset($_POST['codigo_cliente'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad: Token CSRF inválido.");
    }

    $codigo = $_POST['codigo_cliente'];

    try {
        // Al borrar de 'clientes', el CASCADE se encarga de 'cliente_progreso_promo' y 'compras'
        $delete = "DELETE FROM clientes WHERE codigo_cliente = :codigo";
        $stmt = $conn->prepare($delete);
        $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $mensaje = "<div class='alert alert-success text-center shadow-sm'>✅ El cliente y todo su historial han sido eliminados correctamente.</div>";
            $cliente = null; 
        } else {
            $mensaje = "<div class='alert alert-danger text-center shadow-sm'>Error al intentar eliminar el registro.</div>";
        }
    } catch (PDOException $e) {
        $mensaje = "<div class='alert alert-danger text-center shadow-sm'>Error crítico en la base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Cliente - Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php require_once "purifIcon.php"; ?>
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/assets.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php render_alertas_mantenimiento(); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-danger text-white text-center py-3">
                    <h2 class="h5 m-0">Confirmar Eliminación</h2>
                </div>
                <div class="card-body p-4 text-center">

                    <?php if (!empty($mensaje)): ?>
                        <?= $mensaje ?>
                        <div class="d-grid mt-4">
                            <a href="../index.php" class="btn btn-primary">Volver al Inicio</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($busquedaHecha && !$cliente && empty($mensaje)): ?>
                        <div class="alert alert-warning">No se encontró ningún cliente con el criterio: <b><?= htmlspecialchars($busqueda) ?></b></div>
                        <a href="../index.php" class="btn btn-secondary w-100">Volver a intentar</a>
                    <?php endif; ?>

                    <?php if ($cliente && empty($mensaje)): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Atención:</strong> Se eliminará permanentemente al cliente y su historial de puntos.
                        </div>

                        <ul class="list-group list-group-flush border rounded mb-4 text-start">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Código:</span>
                                <strong><?= htmlspecialchars($cliente['codigo_cliente']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Nombre:</span>
                                <strong><?= htmlspecialchars($cliente['nombre_cliente']) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Progreso actual:</span>
                                <span class="badge bg-info text-dark"><?= (int)$cliente['puntos'] ?> / 10 garrafones</span>
                            </li>
                        </ul>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente']) ?>">
                            
                            <div class="d-flex gap-2">
                                <a href="../index.php" class="btn btn-light border w-100">Cancelar</a>
                                <button type="submit" name="confirmar" value="1" class="btn btn-danger w-100"
                                        onclick="return confirm('¿Estás totalmente seguro? Esta acción es irreversible.');">
                                    Confirmar Borrado
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>



<?php require_once "footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>