<?php
require_once __DIR__ . '/auth.php';
requiereRol(['administrador', 'empleado']);

require_once "conexion.php";

// Incluir alertas de mantenimiento
require_once "../includes/alertas_mantenimiento.php";

$clientes = [];
$busquedaHecha = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['busqueda'])) {
    $busquedaHecha = true;
    $busqueda = trim($_POST['busqueda']);

    // Nueva consulta adaptada al esquema v2
    // Traemos el progreso de la tabla de promociones y sumamos los garrafones de la tabla compras
    $sql = "SELECT 
                c.codigo_cliente, 
                c.nombre_cliente, 
                COALESCE(p.garrafones_acumulados, 0) AS garrafones_promocion,
                (SELECT COALESCE(SUM(cantidad_garrafones), 0) 
                 FROM compras 
                 WHERE codigo_cliente = c.codigo_cliente) AS total_acumulado
            FROM clientes c
            LEFT JOIN cliente_progreso_promo p ON c.codigo_cliente = p.codigo_cliente
            WHERE c.codigo_cliente = :codigo 
               OR c.nombre_cliente LIKE :like";
               
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':codigo' => $busqueda,
        ':like' => "%$busqueda%"
    ]);

    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Filtrar Clientes - Purificadora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <?php require_once "purifIcon.php"; ?>
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/assets.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php render_alertas_mantenimiento(); ?>

<?php require_once __DIR__ . "/navbar.php"; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 m-0 text-primary">Búsqueda de Clientes</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="row gy-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Código o Nombre del Cliente</label>
                            <input type="text" name="busqueda" class="form-control form-control-lg" 
                                   placeholder="Ej: 1020 o Juan Pérez" required autofocus
                                   value="<?= isset($busqueda) ? htmlspecialchars($busqueda) : '' ?>">
                            <div class="form-text">Útil en caso de extravío del código físico.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 py-2">Buscar Cliente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($busquedaHecha): ?>
        <div class="row justify-content-center mt-4">
            <div class="col-12 col-lg-10">
                <?php if (empty($clientes)): ?>
                    <div class="alert alert-warning text-center shadow-sm">
                        No se encontraron clientes que coincidan con "<strong><?= htmlspecialchars($busqueda) ?></strong>".
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h3 class="h6 m-0">Resultados encontrados: <?= count($clientes) ?></h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th class="text-center">Progreso Promoción</th>
                                            <th class="text-center">Total Histórico</th>
                                            <th class="text-end px-4">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientes as $c): ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($c['codigo_cliente']) ?></td>
                                                <td><?= htmlspecialchars($c['nombre_cliente']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?= $c['garrafones_promocion'] >= 9 ? 'bg-warning text-dark' : 'bg-info' ?>">
                                                        <?= (int)$c['garrafones_promocion'] ?> / 10
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-muted"><?= (int)$c['total_acumulado'] ?> garrafones</span>
                                                </td>
                                                <td class="text-end px-4">
                                                    <a href="../index.php?busqueda=<?= urlencode($c['codigo_cliente']) ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        Ver en Inicio
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?> 

    <div class="text-center mt-4">
        <a href="../index.php" class="btn btn-outline-secondary px-5">Volver al Panel</a>
    </div>
</main>

<?php require_once "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>