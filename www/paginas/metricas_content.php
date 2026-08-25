<?php
// Incluido desde metricas.php (carga normal y modo AJAX).
// Usa las variables ya calculadas por el controlador.
?>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h3 class="h5 mb-3 fw-bold text-secondary">Rendimiento General Histórico</h3>
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm metric-card border-start border-4">
            <div class="card-body">
                <div class="metric-label text-primary">Garrafones Vendidos</div>
                <div class="metric-value text-dark mt-2"><?= number_format($total_garrafones_global) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm metric-card border-start border-4">
            <div class="card-body">
                <div class="metric-label text-info">Total Bruto</div>
                <div class="metric-value text-dark mt-2">$<?= number_format($total_bruto_global, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm metric-card border-start border-4">
            <div class="card-body">
                <div class="metric-label text-danger">Total en Descuentos</div>
                <div class="metric-value text-danger mt-2">$<?= number_format($total_descuentos_global, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm metric-card border-start border-4">
            <div class="card-body">
                <div class="metric-label text-success">Ganancia Neta Total</div>
                <div class="metric-value text-success mt-2">$<?= number_format($total_ganado_global, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Rendimiento por Periodo: reubicado justo debajo de Rendimiento General Histórico -->

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Filtrar por Rango de Fechas</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="ajax-filter-form row g-2 align-items-end">
            <div class="col-6 col-md-4">
                <label class="form-label small fw-bold">Desde</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small fw-bold">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Aplicar</button>
            </div>
            <?php if ($modoRango): ?>
                <div class="col-6 col-md-2">
                    <a href="metricas.php" class="btn btn-sm btn-outline-secondary w-100 ajax-filter-link">Quitar</a>
                </div>
            <?php endif; ?>
        </form>
        <?php if ($modoRango && $resumenRango): ?>
            <div class="row g-2 mt-3 pt-3 border-top text-center">
                <div class="col-6 col-md-2">
                    <div class="small text-muted">Ventas</div>
                    <div class="fw-bold"><?= (int)$resumenRango['ventas'] ?></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="small text-muted">Garrafones</div>
                    <div class="fw-bold text-primary"><?= number_format($resumenRango['garrafones']) ?></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="small text-muted">Bruto</div>
                    <div class="fw-bold text-info">$<?= number_format($resumenRango['bruto'], 2) ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="small text-muted">Descuentos</div>
                    <div class="fw-bold text-danger">$<?= number_format($resumenRango['descuentos'], 2) ?></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="small text-muted">Ganancia Neta</div>
                    <div class="fw-bold text-success">$<?= number_format($resumenRango['ingresos'], 2) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h2 class="h5 mb-0">Rendimiento Detallado por Mes</h2>
                <small class="text-muted">
                    <?= $modoRango ? 'Filtrado por rango de fechas' : (!empty($periodos_comparar) ? 'Modo Comparación Activo' : 'Mostrando últimos 12 meses') ?>
                </small>
            </div>
            
            <form method="GET" class="mb-0 ajax-filter-form">
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownCompare" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            Seleccionar para comparar
                        </button>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-compare shadow-sm" aria-labelledby="dropdownCompare">
                            <div class="px-1 pb-2">
                                <input type="text" class="form-control form-control-sm compare-search" placeholder="Buscar mes o año...">
                            </div>
                            <?php foreach ($periodosPorAnio as $anio => $mesesDelAnio): ?>
                                <h6 class="dropdown-header year-group-header px-1 pt-2 pb-1 border-bottom"><?= htmlspecialchars($anio) ?></h6>
                                <?php foreach ($mesesDelAnio as $p):
                                    $valStr = $p['mes'] . '-' . $p['anio'];
                                    $checked = in_array($valStr, $periodos_comparar) ? 'checked' : '';
                                    $textoBusqueda = strtolower($meses[$p['mes']] . ' ' . $p['anio']);
                                ?>
                                    <div class="form-check my-2 compare-option" data-search="<?= htmlspecialchars($textoBusqueda) ?>">
                                        <input class="form-check-input" type="checkbox" name="periodos[]" value="<?= $valStr ?>" id="check_<?= $valStr ?>" <?= $checked ?>>
                                        <label class="form-check-label fw-semibold small text-dark" for="check_<?= $valStr ?>">
                                            <?= htmlspecialchars($meses[$p['mes']]) ?> <?= htmlspecialchars($p['anio']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <div class="d-grid mt-3 border-top pt-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Comparar seleccionados</button>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($periodos_comparar)): ?>
                        <a href="metricas.php" class="btn btn-sm btn-secondary ajax-filter-link" title="Limpiar comparación">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($comprasPorMes)): ?>
            <div class="alert alert-secondary m-4">No has seleccionado meses válidos o no existen datos registrados para la consulta.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 table-smaller">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Mes / Año</th>
                            <th class="text-center">Ventas</th>
                            <th class="text-center">Garrafones</th>
                            <th class="text-center text-info">Total Bruto</th>
                            <th class="text-center text-danger">Descuentos</th>
                            <th class="text-end text-success">Ganancia Neta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comprasPorMes as $fila): ?>
                            <tr class="<?= (!empty($periodos_comparar) || $modoRango) ? 'table-warning border-start border-warning border-3' : '' ?>">
                                <td class="fw-bold"><?= htmlspecialchars($meses[$fila['month']]) ?> <?= htmlspecialchars($fila['year']) ?></td>
                                <td class="text-center"><?= (int)$fila['ventas'] ?></td>
                                <td class="text-center fw-bold text-primary"><?= number_format($fila['garrafones']) ?></td>
                                <td class="text-center text-info fw-bold">$<?= number_format($fila['bruto'], 2) ?></td>
                                <td class="text-center text-danger">$<?= number_format($fila['descuentos'], 2) ?></td>
                                <td class="text-end text-success fw-bold">$<?= number_format($fila['ingresos'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Rendimiento Detallado por Año</h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($comprasPorAnio)): ?>
            <div class="alert alert-secondary m-4">No hay registros de compras anuales históricos en el sistema.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 table-smaller">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Año</th>
                            <th class="text-center">Ventas</th>
                            <th class="text-center">Garrafones</th>
                            <th class="text-center text-info">Total Bruto</th>
                            <th class="text-center text-danger">Descuentos</th>
                            <th class="text-end text-success">Ganancia Neta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comprasPorAnio as $fAnio): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($fAnio['year']) ?></td>
                                <td class="text-center"><?= (int)$fAnio['ventas'] ?></td>
                                <td class="text-center fw-bold text-primary"><?= number_format($fAnio['garrafones']) ?></td>
                                <td class="text-center text-info fw-bold">$<?= number_format($fAnio['bruto'], 2) ?></td>
                                <td class="text-center text-danger">$<?= number_format($fAnio['descuentos'], 2) ?></td>
                                <td class="text-end text-success fw-bold">$<?= number_format($fAnio['ingresos'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4"> 
    <div class="col-12 col-md-6">
        <div class="card shadow-sm metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="metric-label">Predicción de Demanda</div>
                        <div class="metric-value text-primary"><?= $prediccionGarrafones !== null ? number_format($prediccionGarrafones) : '---' ?></div>
                    </div>
                    <div class="text-end"><span class="badge bg-info">Próx. mes</span></div>
                </div>
                <p class="mb-2 text-muted">Proyección de garrafones esperados para <strong><?= $mesSiguiente ?: 'N/A' ?></strong>.</p>
                <?php if ($prediccionGarrafones !== null): ?>
                    <div class="mb-2">
                        <small class="text-muted">Cambio promedio mensual</small>
                        <div class="fw-bold <?= $crecimientoPromedio >= 0 ? 'text-success' : 'text-danger' ?>"><?= $crecimientoPromedio >= 0 ? '+' : '' ?><?= $crecimientoPromedio ?? 0 ?>%</div>
                    </div>
                    <div class="progress progress-small mb-1">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= max(5, min(100, 50 + ($crecimientoPromedio ?? 0) / 2)) ?>%"></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0 p-2 small">Necesitas al menos dos meses de datos para generar una proyección.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card shadow-sm metric-card">
            <div class="card-body">
                <div class="metric-label">Meses activos evaluados</div>
                <div class="metric-value text-warning"><?= count($comprasPorMes) ?></div>
                <p class="text-muted mb-0">Rango de meses válidos con registros comerciales detectados en el sistema.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Mantenimientos Próximos</h2></div>
            <div class="card-body">
                <?php if (empty($mantenimientosProximos)): ?>
                    <div class="alert alert-success mb-0">No hay mantenimientos programados en los próximos 30 días.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Mantenimiento</th>
                                    <th class="text-center">Próxima Fecha</th>
                                    <th class="text-center">Días Restantes</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mantenimientosProximos as $mantenimiento): ?>
                                    <tr>
                                        <td><div class="fw-bold"><?= htmlspecialchars($mantenimiento['tipo_mantenimiento']) ?></div></td>
                                        <td class="text-center"><span class="badge bg-primary"><?= date('d/m/Y', strtotime($mantenimiento['proxima_fecha'])) ?></span></td>
                                        <td class="text-center">
                                            <span class="fw-bold <?= $mantenimiento['dias_restantes'] <= 3 ? 'text-danger' : ($mantenimiento['dias_restantes'] <= 7 ? 'text-warning' : 'text-success') ?>">
                                                <?= $mantenimiento['dias_restantes'] ?> días
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($mantenimiento['dias_restantes'] <= 3): ?>
                                                <span class="badge bg-danger">Urgente</span>
                                            <?php elseif ($mantenimiento['dias_restantes'] <= 7): ?>
                                                <span class="badge bg-warning">Próximo</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Programado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0">Ventas promedio por día de la semana</h2>
        <span class="badge bg-secondary">
            <?= $modoRango ? 'Rango de fechas activo' : (!empty($periodos_comparar) ? 'Filtrado por selección' : 'Últimos 12 meses activos') ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (empty($ventasSemana)): ?>
            <div class="alert alert-secondary mb-0">No hay datos suficientes en el periodo seleccionado para generar la gráfica semanal.</div>
        <?php else: ?>
            <canvas id="weekdayChart" height="90"></canvas>
            <script type="application/json" id="weekdayChartData"><?= json_encode($weekdayChartData) ?></script>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Top 10 Clientes por garrafones</h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topClientes)): ?>
                    <div class="alert alert-secondary m-4">No hay información de compras para mostrar clientes.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Cliente</th>
                                    <th class="text-center">Compras</th>
                                    <th class="text-center">Garrafones</th>
                                    <th class="text-end">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topClientes as $cliente): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($cliente['nombre_cliente']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($cliente['codigo_cliente']) ?></div>
                                        </td>
                                        <td class="text-center"><?= (int)$cliente['total_compras'] ?></td>
                                        <td class="text-center"><?= (int)$cliente['garrafones_totales'] ?></td>
                                        <td class="text-end">$<?= number_format($cliente['ingresos_totales'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card shadow-sm mb-4 border-start border-warning border-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Retención de Clientes (Fidelidad)</h2>
                <span class="badge bg-secondary">
                    <?= $modoRango ? 'Filtrado por rango' : (!empty($periodos_comparar) ? 'Filtrado por periodo' : 'Últimos 12 meses activos') ?>
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center">
                    <div class="me-sm-4 mb-3 mb-sm-0 text-center text-sm-start">
                        <div class="metric-value text-dark"><?= round($tasa_retencion, 1) ?>%</div>
                        <div class="metric-label text-warning mt-1">Tasa de retorno</div>
                    </div>
                    <div class="flex-grow-1 border-start ps-sm-4 pt-2 pt-sm-0">
                        <p class="mb-2 text-muted">
                            <strong><?= $clientes_activos ?></strong> de tus <strong><?= $total_clientes_registrados ?></strong> clientes registrados compraron agua en el periodo evaluado.
                        </p>
                        <div class="progress progress-small w-100">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= round($tasa_retencion, 1) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>