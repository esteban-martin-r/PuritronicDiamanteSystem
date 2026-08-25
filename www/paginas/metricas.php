<?php
require_once __DIR__ . '/auth.php';
requiereRol(['administrador']);

require_once "conexion.php";
require_once "../includes/alertas_mantenimiento.php";

$meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$diasSemanaNombres = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$topClientes = [];
$comprasPorMes = [];
$comprasPorAnio = [];
$ventasSemana = [];
$maxPromedioSemana = 0;
$diaMaxPromedio = null;
$prediccionGarrafones = null;
$crecimientoPromedio = null;
$mesSiguiente = null;
$mantenimientosProximos = [];
$error = null;
$resumenRango = null;

// Captura de múltiples periodos seleccionados para comparar (formato: "MES-ANIO")
$periodos_comparar = isset($_GET['periodos']) && is_array($_GET['periodos']) ? $_GET['periodos'] : [];

// NUEVO: Filtro de rango de fechas libre (tiene prioridad sobre la comparación de meses)
$fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fecha_fin    = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$formatoFechaValido = function ($f) {
    return $f !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) === 1;
};
$modoRango = $formatoFechaValido($fecha_inicio) && $formatoFechaValido($fecha_fin);

// Variables para los totales globales
$total_garrafones_global = 0;
$total_bruto_global = 0;
$total_descuentos_global = 0;
$total_ganado_global = 0;

try {
    // 1. LÓGICA GLOBAL: Totales Históricos
    $sqlTotales = "SELECT 
                    COALESCE(SUM(cantidad_garrafones), 0) AS total_garrafones,
                    COALESCE(SUM(total_final + descuento_aplicado), 0) AS total_bruto,
                    COALESCE(SUM(descuento_aplicado), 0) AS total_descuentos,
                    COALESCE(SUM(total_final), 0) AS total_ganado
                   FROM compras";
    $stmtTotales = $conn->query($sqlTotales);
    $totalesGlobales = $stmtTotales->fetch(PDO::FETCH_ASSOC);
    
    $total_garrafones_global = $totalesGlobales['total_garrafones'];
    $total_bruto_global      = $totalesGlobales['total_bruto'];
    $total_descuentos_global = $totalesGlobales['total_descuentos'];
    $total_ganado_global     = $totalesGlobales['total_ganado'];

    // OBTENCIÓN DE TODOS LOS PERIODOS REALES DISPONIBLES (Para armar la lista de comparación, agrupada por año)
    $sqlPeriodosDisponibles = "SELECT DISTINCT YEAR(fecha_compra) AS anio, MONTH(fecha_compra) AS mes 
                               FROM compras 
                               ORDER BY anio DESC, mes DESC";
    $todosLosPeriodos = $conn->query($sqlPeriodosDisponibles)->fetchAll(PDO::FETCH_ASSOC);

    // NUEVO: agrupamos por año para el dropdown
    $periodosPorAnio = [];
    foreach ($todosLosPeriodos as $p) {
        $periodosPorAnio[$p['anio']][] = $p;
    }


    // 2. LÓGICA DE FILTROS DINÁMICOS: rango de fechas > comparación de meses > default (últimos 12 meses)
    $whereClause = "";
    $parametros = [];

    if ($modoRango) {
        // NUEVO: Filtro de rango de fechas libre (ej. del 1 al 15 de agosto)
        $whereClause = "WHERE DATE(fecha_compra) BETWEEN :f_ini AND :f_fin";
        $parametros[':f_ini'] = $fecha_inicio;
        $parametros[':f_fin'] = $fecha_fin;
    } elseif (!empty($periodos_comparar)) {
        // Construimos una consulta dinámica: WHERE (MONTH=x AND YEAR=y) OR (MONTH=w AND YEAR=z)...
        $orCondiciones = [];
        foreach ($periodos_comparar as $index => $pString) {
            $partes = explode('-', $pString);
            if(count($partes) === 2) {
                $mId = ':mes_' . $index;
                $aId = ':anio_' . $index;
                $orCondiciones[] = "(MONTH(fecha_compra) = $mId AND YEAR(fecha_compra) = $aId)";
                $parametros[$mId] = (int)$partes[0];
                $parametros[$aId] = (int)$partes[1];
            }
        }
        if (!empty($orCondiciones)) {
            $whereClause = "WHERE " . implode(" OR ", $orCondiciones);
        }
    }

    $limiteMensual = empty($whereClause) ? "LIMIT 12" : ""; // Si no hay filtros de comparación, muestra el último año

    $sqlMeses = "SELECT YEAR(fecha_compra) AS year, MONTH(fecha_compra) AS month,
                         COUNT(*) AS ventas,
                         SUM(cantidad_garrafones) AS garrafones,
                         SUM(total_final + descuento_aplicado) AS bruto,
                         SUM(descuento_aplicado) AS descuentos,
                         SUM(total_final) AS ingresos
                  FROM compras
                  $whereClause
                  GROUP BY year, month
                  ORDER BY year DESC, month DESC
                  $limiteMensual";

    $stmtMeses = $conn->prepare($sqlMeses);
    $stmtMeses->execute($parametros);
    $comprasPorMes = $stmtMeses->fetchAll(PDO::FETCH_ASSOC);

    // NUEVO: Resumen agregado del rango de fechas (solo si el filtro de rango está activo)
    if ($modoRango) {
        $sqlRango = "SELECT 
                        COUNT(*) AS ventas,
                        COALESCE(SUM(cantidad_garrafones), 0) AS garrafones,
                        COALESCE(SUM(total_final + descuento_aplicado), 0) AS bruto,
                        COALESCE(SUM(descuento_aplicado), 0) AS descuentos,
                        COALESCE(SUM(total_final), 0) AS ingresos
                     FROM compras
                     $whereClause";
        $stmtRango = $conn->prepare($sqlRango);
        $stmtRango->execute($parametros);
        $resumenRango = $stmtRango->fetch(PDO::FETCH_ASSOC);
    }


    // 2.5 LÓGICA POR AÑO: Agrupación anual histórica
    $sqlAnios = "SELECT YEAR(fecha_compra) AS year,
                        COUNT(*) AS ventas,
                        SUM(cantidad_garrafones) AS garrafones,
                        SUM(total_final + descuento_aplicado) AS bruto,
                        SUM(descuento_aplicado) AS descuentos,
                        SUM(total_final) AS ingresos
                 FROM compras
                 GROUP BY year
                 ORDER BY year DESC";
    $stmtAnios = $conn->query($sqlAnios);
    $comprasPorAnio = $stmtAnios->fetchAll(PDO::FETCH_ASSOC);


    // 3. Top Clientes
    $sqlTop = "SELECT c.codigo_cliente, c.nombre_cliente,
                      COUNT(co.id_compra) AS total_compras,
                      COALESCE(SUM(co.cantidad_garrafones), 0) AS garrafones_totales,
                      COALESCE(SUM(co.total_final), 0) AS ingresos_totales
               FROM clientes c
               LEFT JOIN compras co ON c.codigo_cliente = co.codigo_cliente
               GROUP BY c.codigo_cliente, c.nombre_cliente
               ORDER BY garrafones_totales DESC
               LIMIT 10";

    $stmtTop = $conn->query($sqlTop);
    $topClientes = $stmtTop->fetchAll(PDO::FETCH_ASSOC);


    // 4. Ventas por día de la semana (CON FILTRADO POR MES Y AÑO)
    $whereClauseSemana = $whereClause;
    $parametrosSemana = $parametros;

    if (empty($whereClauseSemana) && !empty($comprasPorMes)) {
        $condicionesPorDefecto = [];
        foreach ($comprasPorMes as $index => $mesActivo) {
            $mId = ':def_mes_' . $index;
            $aId = ':def_anio_' . $index;
            $condicionesPorDefecto[] = "(MONTH(fecha_compra) = $mId AND YEAR(fecha_compra) = $aId)";
            $parametrosSemana[$mId] = (int)$mesActivo['month'];
            $parametrosSemana[$aId] = (int)$mesActivo['year'];
        }
        if (!empty($condicionesPorDefecto)) {
            $whereClauseSemana = "WHERE " . implode(" OR ", $condicionesPorDefecto);
        }
    }

    $sqlSemana = "SELECT DAYOFWEEK(fecha_compra) AS dow,
                         COUNT(*) AS ventas,
                         AVG(cantidad_garrafones) AS promedio_garrafones
                  FROM compras
                  $whereClauseSemana
                  GROUP BY dow
                  ORDER BY dow";

    $stmtSemana = $conn->prepare($sqlSemana);
    $stmtSemana->execute($parametrosSemana);
    $ventasSemanaRaw = $stmtSemana->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ventasSemanaRaw as $fila) {
        $dia = $diasSemanaNombres[(int)$fila['dow'] - 1];
        $promedio = round((float)$fila['promedio_garrafones'], 1);
        $ventasSemana[$dia] = [
            'ventas' => (int)$fila['ventas'],
            'promedio' => $promedio,
        ];

        if ($promedio > $maxPromedioSemana) {
            $maxPromedioSemana = $promedio;
            $diaMaxPromedio = $dia;
        }
    }

    // NUEVO: normalizamos para Chart.js en el orden Lunes -> Domingo
    $ordenDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $weekdayChartData = [
        'labels' => $ordenDias,
        'promedios' => array_map(fn($d) => $ventasSemana[$d]['promedio'] ?? 0, $ordenDias),
        'ventas' => array_map(fn($d) => $ventasSemana[$d]['ventas'] ?? 0, $ordenDias),
        'diaMax' => $diaMaxPromedio,
    ];

    // 5. Algoritmo de Predicción
    if (count($comprasPorMes) >= 2) {
        $mesesOrdenados = array_reverse($comprasPorMes);
        $ultimos = array_slice($mesesOrdenados, max(0, count($mesesOrdenados) - 6));
        $diferencias = [];

        for ($i = 1; $i < count($ultimos); $i++) {
            $diferencias[] = $ultimos[$i]['garrafones'] - $ultimos[$i - 1]['garrafones'];
        }

        if (count($diferencias) > 0) {
            $promedioCambio = array_sum($diferencias) / count($diferencias);
            $ultimoMes = end($ultimos);
            $prediccionGarrafones = max(0, (int)round($ultimoMes['garrafones'] + $promedioCambio));
            $crecimientoPromedio = $ultimoMes['garrafones'] > 0 ? round(($promedioCambio / $ultimoMes['garrafones']) * 100, 1) : null;
            $mesProximo = $ultimoMes['month'] + 1;
            $anoProximo = $ultimoMes['year'];
            if ($mesProximo > 12) {
                $mesProximo = 1;
                $anoProximo++;
            }
            $mesSiguiente = $meses[$mesProximo] . ' ' . $anoProximo;
        }
    }

    // 6. Mantenimientos próximos
    $sqlMantenimientos = "SELECT tipo_mantenimiento, proxima_fecha, ultima_fecha,
                                 DATEDIFF(proxima_fecha, CURDATE()) AS dias_restantes
                          FROM mantenimientos
                          WHERE proxima_fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                          ORDER BY proxima_fecha ASC";

    $stmtMantenimientos = $conn->query($sqlMantenimientos);
    $mantenimientosProximos = $stmtMantenimientos->fetchAll(PDO::FETCH_ASSOC);


    // 7. Tasa de Retención de Clientes (AHORA TAMBIÉN RESPONDEN AL FILTRADO)
    $sqlActivosFiltrados = "SELECT COUNT(DISTINCT codigo_cliente) AS activos 
                            FROM compras 
                            $whereClauseSemana 
                            AND codigo_cliente IS NOT NULL 
                            AND codigo_cliente != ''";
    
    $stmtActivos = $conn->prepare($sqlActivosFiltrados);
    $stmtActivos->execute($parametrosSemana);
    $clientes_activos = $stmtActivos->fetch(PDO::FETCH_ASSOC)['activos'];

    $sqlTotalClientes = "SELECT COUNT(*) AS total FROM clientes";
    $total_clientes_registrados = $conn->query($sqlTotalClientes)->fetch(PDO::FETCH_ASSOC)['total'];

    $tasa_retencion = $total_clientes_registrados > 0 ? (($clientes_activos / $total_clientes_registrados) * 100) : 0;

} catch (PDOException $e) {
    error_log("Error en metricas.php: " . $e->getMessage());
    $error = "Ocurrió un error al cargar las métricas. Intenta de nuevo o contacta al administrador del sistema.";
}

// ── MODO AJAX ──────────────────────────────────────────────────────────────
// Si la petición viene del JS de filtros, solo respondemos el fragmento HTML
// del dashboard (sin <head>, navbar, etc.) para que el JS lo inyecte sin recargar.
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    include __DIR__ . '/metricas_content.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Métricas - Purificadora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <?php require_once "purifIcon.php"; ?>
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/assets.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
    <style>
        .metric-card { min-height: 140px; }
        .metric-label { font-size: 0.85rem; letter-spacing: 0.06em; text-transform: uppercase; color: #6c757d; font-weight: bold; }
        .metric-value { font-size: 2.2rem; font-weight: 700; }
        .progress-small { height: 10px; border-radius: 999px; }
        .table-smaller td, .table-smaller th { padding: 0.7rem 0.75rem; }
        .dropdown-menu-compare { max-height: 320px; overflow-y: auto; min-width: 260px; padding: 10px; }
        .dropdown-menu-compare .year-group-header { font-size: 0.72rem; letter-spacing: 0.05em; }

        /* NUEVO: overlay de carga para el AJAX (no afecta el diseño existente) */
        #dashboardContent { position: relative; transition: opacity 0.15s ease; }
        #dashboardContent.is-loading { opacity: 0.4; pointer-events: none; }
        #loadingOverlay {
            position: absolute; inset: 0; z-index: 10;
            display: none; align-items: center; justify-content: center;
        }
        #loadingOverlay.active { display: flex; }
    </style>
</head>
<body class="bg-light">
    <?php render_alertas_mantenimiento(); ?>

<?php require_once __DIR__ . "/navbar.php"; ?>

<main class="container py-4">
    <div class="text-left mb-4">
        <h1 class="fw-bold">Dashboard de Métricas</h1>
    </div>

    <div id="dashboardContentWrapper" style="position:relative;">
        <div id="loadingOverlay">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
        <div id="dashboardContent">
            <?php include __DIR__ . '/metricas_content.php'; ?>
        </div>
    </div>
</main>

<?php require_once "footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="../javascript/metricas.js"></script>
</body>
</html>