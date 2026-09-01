<?php
require_once "conexion.php";
session_start();

// Incluir alertas de mantenimiento
require_once "../includes/alertas_mantenimiento.php";

$datosVenta = null;
$mostrarTicketFinal = false; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificación de seguridad CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensaje'] = ['tipo' => 'danger', 'texto' => "Error de seguridad: Token inválido."];
        header("Location: ../index.php");
        exit;
    }

    // --- LÓGICA: CANCELAR COMPRA ---
    if (isset($_POST['cancelar_compra'])) {
        $id_compra = $_POST['id_compra'];
        try {
            $stmtDel = $conn->prepare("DELETE FROM compras WHERE id_compra = ?");
            $stmtDel->execute([$id_compra]);

            $_SESSION['mensaje'] = ['tipo' => 'warning', 'texto' => "La venta ha sido cancelada y eliminada del sistema."];
            header("Location: ../index.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['mensaje'] = ['tipo' => 'danger', 'texto' => "Error al cancelar: " . $e->getMessage()];
            header("Location: ../index.php");
            exit;
        }
    }

    // --- LÓGICA PASO 1: Registro inicial de la venta (CON CÁLCULO DE PROMOCIÓN) ---
    if (isset($_POST['cantidad']) && !isset($_POST['pago_cliente_final'])) {
        $codigo_cliente = $_POST['codigo_cliente'];
        $cantidad = (int)$_POST['cantidad']; 
        $precio_base = 15; 
        $total_bruto = $cantidad * $precio_base;

        try {
            $conn->beginTransaction();

            // A. Consultar el progreso acumulado del cliente
            $sqlProgreso = "SELECT garrafones_acumulados FROM cliente_progreso_promo WHERE codigo_cliente = ?";
            $stmtProgreso = $conn->prepare($sqlProgreso);
            $stmtProgreso->execute([$codigo_cliente]);
            $progreso = $stmtProgreso->fetch(PDO::FETCH_ASSOC);

            if (!$progreso) {
                // Si el cliente no existe en la tabla de promociones, lo registramos inicialmente en 0
                $sqlNuevoPromo = "INSERT INTO cliente_progreso_promo (codigo_cliente, garrafones_acumulados) VALUES (?, 0)";
                $conn->prepare($sqlNuevoPromo)->execute([$codigo_cliente]);
                $garrafones_previos = 0;
            } else {
                $garrafones_previos = (int)$progreso['garrafones_acumulados'];
            }

            // B. Evaluar la promoción de cada 10 garrafones
            $total_acumulado_temporal = $garrafones_previos + $cantidad;
            $descuentos_ganados = floor($total_acumulado_temporal / 10);
            
            // Calculamos el dinero a descontar (Cada descuento = 1 garrafón gratis de $15)
            $descuento_aplicado = $descuentos_ganados * $precio_base;
            $total_final = max(0, $total_bruto - $descuento_aplicado);

            // C. Insertar la compra incluyendo los descuentos calculados
            $query = "INSERT INTO compras (codigo_cliente, cantidad_garrafones, total_compra, descuento_aplicado, total_final, pago_cliente, cambio, fecha_compra) 
                      VALUES (:codigo, :cant, :total_compra, :descuento, :total_final, 0, 0, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':codigo'       => $codigo_cliente,
                ':cant'         => $cantidad,
                ':total_compra' => $total_bruto,
                ':descuento'    => $descuento_aplicado,
                ':total_final'  => $total_final
            ]);

            $id_reciente = $conn->lastInsertId();

            $stmtResumen = $conn->prepare("
                SELECT c.*, cl.nombre_cliente 
                FROM compras c 
                JOIN clientes cl ON c.codigo_cliente = cl.codigo_cliente 
                WHERE c.id_compra = ?
            ");
            $stmtResumen->execute([$id_reciente]);
            $datosVenta = $stmtResumen->fetch(PDO::FETCH_ASSOC);

            $conn->commit();
            $mostrarTicketFinal = false;

        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $_SESSION['mensaje'] = ['tipo' => 'danger', 'texto' => "Error: " . $e->getMessage()];
            header("Location: ../index.php");
            exit;
        }
    } 
    // --- LÓGICA PASO 2: Procesar el pago (Y ASENTAR PROGRESO EN LA BD) ---
    else if (isset($_POST['pago_cliente_final'])) {
        $id_compra = $_POST['id_compra'];
        $pago_recibido = (float)$_POST['pago_cliente_final'];

        try {
            // Buscamos los datos almacenados de la compra en el paso 1
            $stmtCheck = $conn->prepare("SELECT total_final, codigo_cliente, cantidad_garrafones FROM compras WHERE id_compra = ?");
            $stmtCheck->execute([$id_compra]);
            $compraActual = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            $total_a_pagar = (float)$compraActual['total_final'];
            $codigo_cliente = $compraActual['codigo_cliente'];
            $cantidad_comprada = (int)$compraActual['cantidad_garrafones'];

            if ($pago_recibido < $total_a_pagar) {
                $_SESSION['error_pago'] = "El pago es insuficiente. Faltan $" . number_format($total_a_pagar - $pago_recibido, 2);
                
                $stmtResumen = $conn->prepare("
                    SELECT c.*, cl.nombre_cliente 
                    FROM compras c 
                    JOIN clientes cl ON c.codigo_cliente = cl.codigo_cliente 
                    WHERE c.id_compra = ?
                ");
                $stmtResumen->execute([$id_compra]);
                $datosVenta = $stmtResumen->fetch(PDO::FETCH_ASSOC);
                $mostrarTicketFinal = false;
            } else {
                $conn->beginTransaction();

                // 1. Guardar el pago y el cambio del cliente en el historial
                $sqlActualizar = "UPDATE compras SET pago_cliente = ?, cambio = ? - total_final WHERE id_compra = ?";
                $stmtAct = $conn->prepare($sqlActualizar);
                $stmtAct->execute([$pago_recibido, $pago_recibido, $id_compra]);

                // 2. Calcular y actualizar el progreso real definitivo en 'cliente_progreso_promo'
                $sqlProgresoAct = "SELECT garrafones_acumulados FROM cliente_progreso_promo WHERE codigo_cliente = ?";
                $stmtProgresoAct = $conn->prepare($sqlProgresoAct);
                $stmtProgresoAct->execute([$codigo_cliente]);
                $garrafones_previos = (int)$stmtProgresoAct->fetchColumn();

                // El nuevo remanente es el residuo de la división entre 10
                $nuevo_progreso_remanente = ($garrafones_previos + $cantidad_comprada) % 10;

                $sqlUpdateProgreso = "UPDATE cliente_progreso_promo SET garrafones_acumulados = ? WHERE codigo_cliente = ?";
                $conn->prepare($sqlUpdateProgreso)->execute([$nuevo_progreso_remanente, $codigo_cliente]);

                // 3. Preparar los datos del ticket final exitoso
                $stmtResumen = $conn->prepare("
                    SELECT c.*, cl.nombre_cliente 
                    FROM compras c 
                    JOIN clientes cl ON c.codigo_cliente = cl.codigo_cliente 
                    WHERE c.id_compra = ?
                ");
                $stmtResumen->execute([$id_compra]);
                $datosVenta = $stmtResumen->fetch(PDO::FETCH_ASSOC);


                $conn->commit();
                $mostrarTicketFinal = true;
                unset($_SESSION['error_pago']);


            } 
        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $_SESSION['mensaje'] = ['tipo' => 'danger', 'texto' => "Error al procesar: " . $e->getMessage()];
            header("Location: ../index.php");
            exit;
        }
    }
}

// Calculamos el progreso de garrafones para mostrarlo en el ticket.
// La tabla cliente_progreso_promo solo se actualiza en el Paso 2 (al confirmar el pago),
// así que si todavía no se confirma, hay que sumar esta venta "a mano" antes de mostrarla.
$garrafonesActuales = 0;
if ($datosVenta) {
    $stmtProgresoTicket = $conn->prepare("SELECT garrafones_acumulados FROM cliente_progreso_promo WHERE codigo_cliente = ?");
    $stmtProgresoTicket->execute([$datosVenta['codigo_cliente']]);
    $progresoGuardado = (int)$stmtProgresoTicket->fetchColumn();

    if ($mostrarTicketFinal) {
        // El pago ya se confirmó: la tabla ya quedó actualizada con esta venta incluida.
        $garrafonesActuales = $progresoGuardado;
    } else {
        // Paso 1 (o reintento por pago insuficiente): la tabla aún NO incluye esta venta.
        $garrafonesActuales = ($progresoGuardado + (int)$datosVenta['cantidad_garrafones']) % 10;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Venta - Puritronic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .ticket { max-width: 450px; margin: 50px auto; background: #ffffff; border-radius: 15px; overflow: hidden; border: none; }
        .ticket-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px 20px; text-align: center; }
        .ticket-header.bg-error { background: linear-gradient(135deg, #dc3545, #a71d2a); }
        .ticket-body { padding: 30px; position: relative; }
        .item-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
        .item-label { color: #6c757d; }
        .item-value { font-weight: 600; color: #343a40; }
        .total-section { border-top: 2px dashed #dee2e6; margin-top: 20px; padding-top: 20px; }
        .pago-detalle { background-color: #f8f9fa; border-radius: 10px; padding: 15px; margin-top: 15px; border: 1px solid #eee; }
        .modal-content { border-radius: 15px; border: none; overflow: hidden; }
        .modal-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; border-bottom: none; }
        .confirm-cliente-box { background: #f8f9fa; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; border: 1px solid #eee; }
        .confirm-cliente-nombre { font-weight: 700; color: #343a40; text-transform: uppercase; font-size: 1rem; }
        .confirm-cliente-codigo { color: #6c757d; font-size: 0.85rem; }
        .confirm-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .confirm-row:last-of-type { border-bottom: none; }
        .confirm-label { color: #6c757d; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .confirm-value { font-weight: 700; font-size: 1rem; color: #343a40; }
        .confirm-total-box { border-top: 2px dashed #dee2e6; margin-top: 14px; padding-top: 14px; text-align: center; }
    </style>
</head>
<body>
    <?php render_alertas_mantenimiento(); ?>

<div class="container">
    <div class="ticket shadow-lg">
        <div class="ticket-header <?= (isset($_SESSION['error_pago'])) ? 'bg-error' : '' ?>">
            <i class="bi <?= $mostrarTicketFinal ? 'bi-check-circle-fill' : 'bi-cash-coin' ?> display-4"></i>
            <h4 class="mt-2 mb-0"><?= $mostrarTicketFinal ? '¡Venta Exitosa!' : 'Cálculo de Pago' ?></h4>
            <small class="opacity-75">ID Transacción: #<?= str_pad($datosVenta['id_compra'] ?? 0, 5, "0", STR_PAD_LEFT) ?></small>
        </div>

        <div class="ticket-body">
            <?php if ($datosVenta): ?>
                
                <?php if (isset($_SESSION['error_pago'])): ?>
                    <div class="alert alert-danger py-2 small text-center mb-3">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i><?= $_SESSION['error_pago'] ?>
                    </div>
                <?php endif; ?>

                <div class="item-row">
                    <span class="item-label">Cliente:</span>
                    <span class="item-value fw-bold text-uppercase"><?= htmlspecialchars($datosVenta['nombre_cliente']) ?></span>
                </div>
                <div class="item-row border-bottom pb-2">
                    <span class="item-label">Fecha:</span>
                    <span class="item-value small"><?= date("d/m/Y H:i", strtotime($datosVenta['fecha_compra'])) ?></span>
                </div>

                <div class="mt-3">
                    <div class="item-row">
                        <span><?= $datosVenta['cantidad_garrafones'] ?> Garrafones x $15.00</span>
                        <span class="item-value">$<?= number_format($datosVenta['total_compra'], 2) ?></span>
                    </div>

                    <?php if ($datosVenta['descuento_aplicado'] > 0): ?>
                        <div class="item-row text-success">
                            <span><i class="bi bi-tag-fill me-1"></i> Descuento Promoción:</span>
                            <span class="fw-bold">- $<?= number_format($datosVenta['descuento_aplicado'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                        <div class="item-row text">
                            <span>Garrafones Actuales:</span>
                            <span class="item-value"><?= $garrafonesActuales ?>/10</span>
                        </div>
                </div>

                <div class="total-section text-center">
                    <p class="text-muted mb-0">Total a pagar</p>
                    <h2 class="display-6 fw-bold text-primary mb-0">$<?= number_format($datosVenta['total_final'], 2) ?></h2>
                </div>

                <?php if (!$mostrarTicketFinal): ?>
                    <form action="sumar.php" method="POST" class="mt-4" id="formPago">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id_compra" value="<?= $datosVenta['id_compra'] ?>">
                        
                        <div class="pago-detalle">
                            <label class="small fw-bold text-secondary mb-2 text-center d-block">¿Cuánto entregó el cliente?</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">$</span>
                                <input type="number" step="0.01" name="pago_cliente_final" id="pago_cliente_final" class="form-control form-control-lg fw-bold" placeholder="0.00" required autofocus>
                            </div>
                            <div id="avisoPagoInsuficiente" class="alert alert-danger py-2 px-3 small text-center mt-3 mb-0 d-none">
                                <i class="bi bi-exclamation-octagon-fill me-1"></i>
                                <span id="avisoPagoInsuficienteTexto"></span>
                            </div>
                        </div>
                        
                        <button type="button" id="btnAbrirConfirmacion" class="btn btn-success btn-lg w-100 mt-3 shadow-sm rounded-pill">
                            Siguiente <i class="bi bi-arrow-right-short"></i>
                        </button>

                        <button type="submit" name="cancelar_compra" formnovalidate class="btn btn-link btn-sm w-100 mt-4 text-danger text-decoration-none" onclick="return confirmarCancelacion();">
                            <i class="bi bi-x-circle me-1"></i> Cancelar y volver
                        </button>
                    </form>

                    <!-- Modal de confirmación: solo pide confirmar cantidad a cobrar y garrafones -->
                    <div class="modal fade" id="modalConfirmarVenta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content shadow-lg">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-check2-square me-2"></i>Confirmar Venta</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body px-4 py-4">
                                    <p class="text-muted text-center mb-3 small">Verifica que los datos sean correctos antes de continuar.</p>

                                    <div class="confirm-cliente-box d-flex align-items-center gap-3">
                                        <i class="bi bi-person-circle fs-2 text-primary"></i>
                                        <div>
                                            <div class="confirm-cliente-nombre"><?= htmlspecialchars($datosVenta['nombre_cliente']) ?></div>
                                            <div class="confirm-cliente-codigo">Cliente #<?= htmlspecialchars($datosVenta['codigo_cliente']) ?></div>
                                        </div>
                                    </div>

                                    <div class="confirm-row">
                                        <span class="confirm-label"><i class="bi bi-droplet-fill text-info"></i> Garrafones comprados</span>
                                        <span class="confirm-value"><?= $datosVenta['cantidad_garrafones'] ?></span>
                                    </div>
                                    <?php if ($datosVenta['descuento_aplicado'] > 0): ?>
                                        <div class="confirm-row">
                                            <span class="confirm-label"><i class="text-success"></i> Descuento por promoción</span>
                                            <span class="confirm-value text-success">- $<?= number_format($datosVenta['descuento_aplicado'], 2) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="confirm-total-box">
                                        <p class="text-muted mb-0 small">Cantidad a cobrar</p>
                                        <h3 class="fw-bold text-primary mb-0">$<?= number_format($datosVenta['total_final'], 2) ?></h3>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 px-4 pb-4 justify-content-center">
                                    <button type="button" class="btn btn-outline-secondary w-50 rounded-pill" data-bs-dismiss="modal">
                                        Cancelar
                                    </button>
                                    <button type="button" id="btnConfirmarVenta" class="btn btn-success w-50 rounded-pill">
                                        Confirmar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="pago-detalle border-success" style="border-width: 2px;">
                        <div class="item-row">
                            <span class="item-label text-dark">Efectivo recibido:</span>
                            <span class="item-value">$<?= number_format($datosVenta['pago_cliente'], 2) ?></span>
                        </div>
                        <div class="item-row mb-0">
                            <span class="input-label fw-bold text-dark">Su cambio:</span>
                            <span class="item-value fw-bold text-danger" style="font-size: 1.3rem;">$<?= number_format($datosVenta['cambio'], 2) ?></span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <a href="../index.php?busqueda=<?= urlencode($datosVenta['codigo_cliente']) ?>" class="btn btn-primary btn-lg w-100 shadow-sm rounded-pill">
                            <i class="bi bi-check-circle me-2"></i>Finalizar
                        </a>
                        <p class="text-muted text-center small mt-3" id="contador">Redirigiendo en 60 segundos...</p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmarCancelacion() {
        return confirm("¿Estás seguro de que deseas cancelar esta compra? Se eliminará el registro actual.");
    }

    <?php if (!$mostrarTicketFinal && $datosVenta): ?>
    (function () {
        const form = document.getElementById('formPago');
        const inputPago = document.getElementById('pago_cliente_final');
        const btnAbrir = document.getElementById('btnAbrirConfirmacion');
        const btnConfirmar = document.getElementById('btnConfirmarVenta');
        const modalEl = document.getElementById('modalConfirmarVenta');
        const modal = new bootstrap.Modal(modalEl);
        const avisoInsuficiente = document.getElementById('avisoPagoInsuficiente');
        const avisoInsuficienteTexto = document.getElementById('avisoPagoInsuficienteTexto');
        const totalFinal = <?= json_encode((float)$datosVenta['total_final']) ?>;

        function ocultarAvisoInsuficiente() {
            avisoInsuficiente.classList.add('d-none');
        }

        function intentarContinuar() {
            // 1. Validamos que el campo tenga un valor válido
            if (!inputPago.checkValidity()) {
                inputPago.reportValidity();
                return;
            }

            const pagoIngresado = parseFloat(inputPago.value);

            // 2. Si el pago es menor al total, avisamos y NO abrimos el modal
            if (pagoIngresado < totalFinal) {
                const faltante = (totalFinal - pagoIngresado).toFixed(2);
                avisoInsuficienteTexto.textContent = `El pago es insuficiente. Faltan $${faltante}`;
                avisoInsuficiente.classList.remove('d-none');
                inputPago.focus();
                return;
            }

            // 3. Pago suficiente: recién aquí mostramos la confirmación
            ocultarAvisoInsuficiente();
            modal.show();
        }

        btnAbrir.addEventListener('click', intentarContinuar);

        // Al presionar Enter en el campo de pago, el navegador por defecto
        // enviaría el formulario usando el único botón type="submit" que queda
        // (el de "Cancelar y volver"). Lo evitamos y en vez de eso disparamos
        // el mismo flujo que el botón "Siguiente".
        inputPago.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                intentarContinuar();
            }
        });

        // Si el usuario vuelve a escribir, quitamos el aviso viejo para que no confunda
        inputPago.addEventListener('input', ocultarAvisoInsuficiente);

        btnConfirmar.addEventListener('click', function () {
            modal.hide();
            form.submit();
        });
        // Si el usuario le da "Cancelar" en el modal, no pasa nada más:
        // el modal se cierra solo (data-bs-dismiss="modal") y regresa a ingresar el monto.
    })();
    <?php endif; ?>

    <?php if ($mostrarTicketFinal): ?>
        let segundos = 60;
        const texto = document.getElementById('contador');
        const interval = setInterval(() => {
            segundos--;
            if(texto) texto.innerHTML = `Redirigiendo en <b>${segundos}</b> segundos...`;
            if (segundos <= 0) {
                window.location.href = "../index.php?busqueda=<?= urlencode($datosVenta['codigo_cliente']) ?>";
            }
        }, 1000);
    <?php endif; ?>
</script>

</body>
</html>