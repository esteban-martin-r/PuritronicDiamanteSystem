<?php
// Verificación de sesión + rol (reemplaza el chequeo manual anterior)
require_once "paginas/auth.php";

// CORRECCIÓN: La ruta ahora es relativa a la raíz del servidor web
require_once "paginas/conexion.php";

// Incluir alertas de mantenimiento
require_once "includes/alertas_mantenimiento.php";

// Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Lógica de Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login_purificadora.php");
    exit;
}

// --- BLOQUE DE LÓGICA DE ALERTA DE LAVADO ---
try {
    $sqlTotal = "SELECT COALESCE(SUM(cantidad_garrafones), 0) AS total_historico FROM compras";
    $stmtTotal = $conn->query($sqlTotal);
    $total_historico = (int)$stmtTotal->fetchColumn();

    $sqlRef = "SELECT garrafones_referencia FROM cambio_agua ORDER BY id DESC LIMIT 1";
    $stmtRef = $conn->query($sqlRef);
    $garrafones_referencia = (int)($stmtRef->fetchColumn() ?: 0);

    $garrafones_desde_ultimo = $total_historico - $garrafones_referencia;

    if ($garrafones_desde_ultimo >= 40) {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (confirm('🚨 ATENCIÓN: Se han lavado {$garrafones_desde_ultimo} garrafones. ¿Ya se realizó el cambio de agua?')) {
                reiniciarContadorManual(" . $total_historico . ");
            }
        });
        </script>";
    }
} catch (PDOException $e) { 
    error_log("Error en contador de lavado: " . $e->getMessage()); 
}

$cliente = null;
$busquedaHecha = false;
$ultimoCliente = null;

try {
    $stmtUltimo = $conn->query("SELECT * FROM clientes ORDER BY CAST(codigo_cliente AS UNSIGNED) DESC LIMIT 1");
    $ultimoCliente = $stmtUltimo->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $ultimoCliente = null; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['busqueda'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF error");
    $busqueda = trim($_POST['busqueda']);
    header("Location: index.php?busqueda=" . urlencode($busqueda));
    exit;
}

if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
    $busquedaHecha = true;
    $busqueda = trim($_GET['busqueda']);
    $sql = "SELECT c.*, COALESCE(p.garrafones_acumulados, 0) AS garrafones_promocion 
            FROM clientes c 
            LEFT JOIN cliente_progreso_promo p ON c.codigo_cliente = p.codigo_cliente 
            WHERE c.codigo_cliente = :b OR c.nombre_cliente LIKE :l";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':b' => $busqueda, ':l' => "%$busqueda%"]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<?php
// Verificación de sesión + rol (reemplaza el chequeo manual anterior)
require_once "paginas/auth.php";

// CORRECCIÓN: La ruta ahora es relativa a la raíz del servidor web
require_once "paginas/conexion.php";

// Incluir alertas de mantenimiento
require_once "includes/alertas_mantenimiento.php";

// Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Lógica de Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login_purificadora.php");
    exit;
}

// --- BLOQUE DE LÓGICA DE ALERTA DE LAVADO ---
try {
    $sqlTotal = "SELECT COALESCE(SUM(cantidad_garrafones), 0) AS total_historico FROM compras";
    $stmtTotal = $conn->query($sqlTotal);
    $total_historico = (int)$stmtTotal->fetchColumn();

    $sqlRef = "SELECT garrafones_referencia FROM cambio_agua ORDER BY id DESC LIMIT 1";
    $stmtRef = $conn->query($sqlRef);
    $garrafones_referencia = (int)($stmtRef->fetchColumn() ?: 0);

    $garrafones_desde_ultimo = $total_historico - $garrafones_referencia;

    if ($garrafones_desde_ultimo >= 40) {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (confirm('🚨 ATENCIÓN: Se han lavado {$garrafones_desde_ultimo} garrafones. ¿Ya se realizó el cambio de agua?')) {
                reiniciarContadorManual(" . $total_historico . ");
            }
        });
        </script>";
    }
} catch (PDOException $e) { 
    error_log("Error en contador de lavado: " . $e->getMessage()); 
}

$cliente = null;
$busquedaHecha = false;
$ultimoCliente = null;

try {
    $stmtUltimo = $conn->query("SELECT * FROM clientes ORDER BY CAST(codigo_cliente AS UNSIGNED) DESC LIMIT 1");
    $ultimoCliente = $stmtUltimo->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $ultimoCliente = null; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['busqueda'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("CSRF error");
    $busqueda = trim($_POST['busqueda']);
    header("Location: index.php?busqueda=" . urlencode($busqueda));
    exit;
}

if (isset($_GET['busqueda']) && $_GET['busqueda'] !== '') {
    $busquedaHecha = true;
    $busqueda = trim($_GET['busqueda']);
    $sql = "SELECT c.*, COALESCE(p.garrafones_acumulados, 0) AS garrafones_promocion 
            FROM clientes c 
            LEFT JOIN cliente_progreso_promo p ON c.codigo_cliente = p.codigo_cliente 
            WHERE c.codigo_cliente = :b OR c.nombre_cliente LIKE :l";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':b' => $busqueda, ':l' => "%$busqueda%"]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Puritronic Diamante - Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php require_once "paginas/purifIcon.php" ?>
    <link href="css/variables.css" rel="stylesheet">
    <link href="css/assets.css" rel="stylesheet">
    <link href="css/components.css" rel="stylesheet">
    <link href="css/logo.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <?php render_alertas_mantenimiento(); ?>

<?php require_once "paginas/navbar.php"; ?>

<main class="container py-3">
    <?php if (isset($garrafones_desde_ultimo)): ?>
        <div class="alert alert-info shadow-sm mb-4">
            <div class="row align-items-center">
                <div class="col-md-12 text-center text-md-start">
                    <h6 class="fw-bold mb-1">Lavado de Garrafones: <?= $garrafones_desde_ultimo ?> acumulados</h6>
                    <div class="progress mb-1" style="height: 15px;">
                        <?php $perc = min(100, ($garrafones_desde_ultimo / 40) * 100); ?>
                        <div class="progress-bar <?= ($perc >= 90) ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $perc ?>%">
                            <?= round($perc) ?>%
                        </div>
                    </div>
                    <small class="text-muted">El agua debe cambiarse cada 40 garrafones.</small>
                </div>
                <?php if (esAdministrador()): ?>
                <div class="col-md-4 text-start mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="reiniciarContadorManual(<?= $total_historico ?>)">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reiniciar Contador
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($ultimoCliente): ?>
        <div class="alert alert-info py-2 shadow-sm mb-4">
            <strong>Último cliente registrado:</strong> <?= htmlspecialchars($ultimoCliente['codigo_cliente']) ?> — <?= htmlspecialchars($ultimoCliente['nombre_cliente']) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><h5 class="m-0">Búsqueda Cliente</h5></div>
                <div class="card-body">
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="input-group">
                            <input type="text" name="busqueda" class="form-control" placeholder="Código o Nombre" value="<?= isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '' ?>" required autofocus>
                            <button class="btn btn-primary">Buscar</button>
                        </div>
                    </form>

                    <?php if ($busquedaHecha && $cliente): ?>
                        <div class="mt-4 p-3 border rounded bg-light">
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($cliente['nombre_cliente']) ?> (<?= htmlspecialchars($cliente['codigo_cliente']) ?>)</h6>
                            <?php if (!empty($cliente['nota'])): ?>
                                <div class="alert alert-danger py-2 mb-2 small"><strong>Nota:</strong> <?= htmlspecialchars($cliente['nota']) ?></div>
                            <?php endif; ?>

                            <div class="mb-2 small text-muted">Progreso Promoción: <?= (int)$cliente['garrafones_promocion'] ?> / 10</div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?= ($cliente['garrafones_promocion'] * 10) ?>%"></div>
                            </div>

                            <form method="POST" action="paginas/sumar.php" class="mb-4">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente']) ?>">
                                <div class="input-group">
                                    <input type="number" name="cantidad" class="form-control" placeholder="Cant. Garrafones" min="1" required>
                                    <button class="btn btn-success" type="submit">Registrar Venta</button>
                                </div>
                            </form>
                            <hr>
                            <form method="POST" action="paginas/nota.php" class="row g-2">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente']) ?>">
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Editar Nota:</label>
                                    <textarea name="nota" class="form-control form-control-sm mb-2" rows="2"><?= htmlspecialchars($cliente['nota'] ?? '') ?></textarea>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="accion" value="agregar" class="btn btn-info btn-sm text-white w-100">Guardar</button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="accion" value="eliminar" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('¿Borrar?')">Borrar</button>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($busquedaHecha): ?>
                        <div class="alert alert-warning mt-3">Cliente no encontrado.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><h5 class="m-0">Registrar Nuevo Cliente</h5></div>
                <div class="card-body">
                    <form method="POST" action="paginas/registrar.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" name="codigo_cliente" class="form-control" value="<?= $ultimoCliente && is_numeric($ultimoCliente['codigo_cliente']) ? ((int)$ultimoCliente['codigo_cliente'] + 1) : 1 ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="nombre_cliente" placeholder="Nombre De La Persona (Con Apellidos)" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">Registrar Cliente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (esAdministrador()): ?>
    <div class="my-5 text-center">
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#adminZone">
            Opciones Avanzadas
        </button>
        <div class="collapse mt-4" id="adminZone">
            <div class="row g-4 justify-content-center">
                <div class="col-md-5">
                    <div class="card border-danger shadow-sm">
                        <div class="card-body text-start">
                            <h6 class="text-danger fw-bold">Eliminar Cliente</h6>
                            <form action="paginas/eliminar.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="input-group input-group-sm">
                                    <input type="text" placeholder="Codigo del Cliente" name="busqueda" class="form-control" required>
                                    <button class="btn btn-danger">Eliminar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card border-secondary shadow-sm">
                        <div class="card-body text-start">
                            <h6 class="fw-bold text-secondary">Editar Información</h6>
                            <form action="paginas/editar.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="busqueda" placeholder="Codigo del Cliente" class="form-control" required>
                                    <button class="btn btn-secondary">Editar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php require_once "paginas/footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function reiniciarContadorManual(totalHistorico) {
        if (confirm('¿Deseas reiniciar el contador de lavado de agua?')) {
            fetch('paginas/reset_contador.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'nuevo_total_ref=' + totalHistorico
            })
            .then(r => r.json())
            .then(d => { 
                if(d.status === 'ok') {
                    alert('Contador reiniciado.');
                    location.reload(); 
                } else {
                    alert('Error al reiniciar.');
                }
            })
            .catch(e => alert('Error de conexión.'));
        }
    }
</script>
</body>
</html>