<?php
require_once __DIR__ . '/auth.php';
requiereRol(['administrador']);

require_once "conexion.php";

// Incluir alertas de mantenimiento
require_once "../includes/alertas_mantenimiento.php";

$dia = isset($_GET['dia']) ? (int)$_GET['dia'] : '';
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : '';
// Si viene vacío el año, no lo vuelvas 0 para que no rompa tu lógica posterior
$anio = !empty($_GET['anio']) ? (int)$_GET['anio'] : '';
$filtroActivo = false;

try {
    $query = "SELECT 
                c.id_compra, 
                c.codigo_cliente, 
                COALESCE(cl.nombre_cliente, 'Cliente Eliminado') AS nombre_cliente, 
                c.cantidad_garrafones, 
                c.total_compra, 
                c.descuento_aplicado, 
                c.total_final, 
                c.pago_cliente,
                c.cambio,
                c.fecha_compra
              FROM compras c
              LEFT JOIN clientes cl ON c.codigo_cliente = cl.codigo_cliente
              WHERE 1=1";
    $params = [];

    if (!empty($dia)) {
        $query .= " AND DAY(c.fecha_compra) = :dia";
        $params[':dia'] = $dia;
        $filtroActivo = true;
    }
    if (!empty($mes)) {
        $query .= " AND MONTH(c.fecha_compra) = :mes";
        $params[':mes'] = $mes;
        $filtroActivo = true;
    }
    if (!empty($anio)) {
        $query .= " AND YEAR(c.fecha_compra) = :anio";
        $params[':anio'] = $anio;
        $filtroActivo = true;
    }

    if (!$filtroActivo) {
        $query .= " AND DATE(c.fecha_compra) = CURDATE()";
    }

    $query .= " ORDER BY c.fecha_compra DESC";
    $stmt = $conn->prepare($query);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    $stmt->execute();
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_garrafones = 0;
    $total_descuentos = 0;
    $total_ganado = 0;

    foreach ($compras as $c) {
        $total_garrafones += $c['cantidad_garrafones'];
        $total_descuentos += $c['descuento_aplicado'];
        $total_ganado += $c['total_final'];
    }

} catch (PDOException $e) {
    $error = "Error al obtener el historial: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ventas - Purificadora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/assets.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <style>
        .resumen-card { background: #ffffff; border-radius: 15px; padding: 25px; border: 1px solid #dee2e6; }
        .stat-label { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; font-weight: bold; }
        .stat-value { font-size: 1.5rem; font-weight: bold; }
        
        /* Estilos Estilo Ticket (Igual a sumar.php) */
        .ticket-modal .modal-content { border-radius: 15px; border: none; overflow: hidden; }
        .ticket-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px; text-align: center; }
        .ticket-body { padding: 25px; position: relative; background: #fff; }
        .item-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; }
        .item-label { color: #6c757d; }
        .item-value { font-weight: 600; color: #343a40; }
        .total-section { border-top: 2px dashed #dee2e6; margin-top: 15px; padding-top: 15px; }
        .pago-detalle { background-color: #f8f9fa; border-radius: 10px; padding: 12px; margin-top: 10px; border: 1px solid #eee; }
        .promo-badge { background-color: #d1e7dd; color: #0f5132; border-radius: 50px; padding: 3px 12px; font-size: 0.8rem; display: inline-block; }
    </style>
    <?php require_once "purifIcon.php"; ?>
</head>
<body class="bg-light">
    <?php render_alertas_mantenimiento(); ?>

<?php require_once __DIR__ . "/navbar.php"; ?>

<div class="container pb-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold">Historial de Ventas</h2>
        <p class="text-muted">
            <?= $filtroActivo ? "Resultados del filtro aplicado" : "Mostrando las ventas de hoy (" . date('d/m/Y') . ")" ?>
        </p>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 justify-content-center">
                <div class="col-6 col-md-2">
                    <label class="form-label small">Día</label>
                    <select name="dia" class="form-select">
                        <option value="">Todos</option>
                        <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?= $i ?>" <?= $dia == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Mes</label>
                    <select name="mes" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
                        foreach ($meses as $num=>$nombre): ?>
                            <option value="<?= $num ?>" <?= $mes == $num ? 'selected' : '' ?>><?= $nombre ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small">Año</label>
                    <input type="number" name="anio" class="form-control" value="<?= $anio ?: date('Y') ?>">
                </div>
                <div class="col-12 col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar Ventas</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($compras)): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Cliente</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Total Final</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $compra): ?>
                            <tr>
                                <td class="ps-3 text-muted small">#<?= $compra['id_compra'] ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($compra['nombre_cliente']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($compra['codigo_cliente']) ?></div>
                                </td>
                                <td class="text-center"><?= $compra['cantidad_garrafones'] ?></td>
                                <td class="text-end fw-bold">$<?= number_format($compra['total_final'], 2) ?></td>
                                <td class="text-center small"><?= date('d/m/y H:i', strtotime($compra['fecha_compra'])) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#ticketModal<?= $compra['id_compra'] ?>">
                                        <i class="bi bi-receipt me-1"></i> Ver Ticket
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade ticket-modal" id="ticketModal<?= $compra['id_compra'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content shadow-lg">
                                        <div class="ticket-header">
                                            <i class="bi bi-check-circle-fill fs-1"></i>
                                            <h5 class="mt-2 mb-0">Comprobante de Venta</h5>
                                            <small class="opacity-75">ID: #<?= str_pad($compra['id_compra'], 5, "0", STR_PAD_LEFT) ?></small>
                                        </div>
                                        <div class="ticket-body">
                                            <div class="item-row">
                                                <span class="item-label">Cliente:</span>
                                                <span class="item-value text-uppercase"><?= htmlspecialchars($compra['nombre_cliente']) ?></span>
                                            </div>
                                            <div class="item-row border-bottom pb-2">
                                                <span class="item-label">Fecha:</span>
                                                <span class="item-value small"><?= date("d/m/Y H:i", strtotime($compra['fecha_compra'])) ?></span>
                                            </div>

                                            <div class="mt-3">
                                                <div class="item-row">
                                                    <span><?= $compra['cantidad_garrafones'] ?> Garrafones x $15</span>
                                                    <span class="item-value">$<?= number_format($compra['total_compra'], 2) ?></span>
                                                </div>
                                                <?php if ($compra['descuento_aplicado'] > 0): ?>
                                                    <div class="item-row text-success">
                                                        <span>Descuento:</span>
                                                        <span class="fw-bold">- $<?= number_format($compra['descuento_aplicado'], 2) ?></span>
                                                    </div>
                                                    <div class="text-center my-1">
                                                        <span class="promo-badge">🎉 ¡Promoción Aplicada!</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="total-section text-center">
                                                <p class="text-muted mb-0 small">Total Pagado</p>
                                                <h3 class="fw-bold text-primary">$<?= number_format($compra['total_final'], 2) ?></h3>
                                            </div>

                                            <div class="pago-detalle">
                                                <div class="item-row mb-1">
                                                    <span class="item-label small">Efectivo:</span>
                                                    <span class="item-value small">$<?= number_format($compra['pago_cliente'], 2) ?></span>
                                                </div>
                                                <div class="item-row mb-0">
                                                    <span class="item-label small fw-bold">Cambio:</span>
                                                    <span class="item-value small fw-bold text-danger">$<?= number_format($compra['cambio'], 2) ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <button type="button" class="btn btn-secondary btn-sm w-100 rounded-pill" data-bs-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="resumen-card shadow-sm mt-4">
            <div class="row text-center">
                <div class="col-md-4 border-end">
                    <div class="stat-label">Garrafones Vendidos</div>
                    <div class="stat-value text-dark"><?= $total_garrafones ?></div>
                </div>
                <div class="col-md-4 border-end">
                    <div class="stat-label">Total en Descuentos</div>
                    <div class="stat-value text-danger">$<?= number_format($total_descuentos, 2) ?></div>
                </div>
                <div class="col-md-4">
                    <div class="stat-label">Ganancia Neta (Total)</div>
                    <div class="stat-value text-success">$<?= number_format($total_ganado, 2) ?></div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-warning text-center shadow-sm">
            No se encontraron registros para los criterios seleccionados.
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="../index.php" class="btn btn-outline-secondary px-5">Volver al Panel</a>
    </div>
</div>

<?php require_once "footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>