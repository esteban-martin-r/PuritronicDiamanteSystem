<?php
// Archivo para preparar alertas de mantenimientos próximos en todas las páginas
// Se puede incluir en cualquier página con sesión iniciada.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mantenimiento_alertas_html = '';

if (isset($_SESSION['usuario']) && empty($_SESSION['mantenimiento_alertas_cerradas'])) {
    try {
        require_once __DIR__ . "/../paginas/conexion.php";

        // Consulta mantenimientos próximos (próximos 7 días)
        $sqlAlertas = "SELECT tipo_mantenimiento, proxima_fecha,
                              DATEDIFF(proxima_fecha, CURDATE()) AS dias_restantes
                       FROM mantenimientos
                       WHERE proxima_fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                       ORDER BY proxima_fecha ASC";

        $stmtAlertas = $conn->query($sqlAlertas);
        $alertasMantenimiento = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($alertasMantenimiento)) {
            $scriptEndpoint = preg_replace('#/paginas$#', '', dirname($_SERVER['SCRIPT_NAME'])) . '/paginas/guardar_alerta_mantenimiento_cerrada.php';
            $scriptEndpoint = str_replace('//', '/', $scriptEndpoint);

            $html = '<div class="container mt-3" data-alert-endpoint="' . htmlspecialchars($scriptEndpoint) . '">';
            $html .= '<div class="alert alert-warning alert-dismissible fade show shadow-sm alert-mantenimiento-cerrable" role="alert">';
            $html .= '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
            $html .= '<strong>Alertas de Mantenimiento:</strong><br>';
            foreach ($alertasMantenimiento as $alerta) {
                $clase = $alerta['dias_restantes'] <= 1 ? 'text-danger fw-bold' : 'text-warning';
                $html .= '<span class="' . $clase . '">• ' . htmlspecialchars($alerta['tipo_mantenimiento']) . ' - ' . date('d/m/Y', strtotime($alerta['proxima_fecha'])) . ' (' . $alerta['dias_restantes'] . ' día(s))</span><br>';
            }
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var container = document.querySelector(".alert-mantenimiento-cerrable");
                    if (!container) return;
                    var endpoint = container.closest(".container").dataset.alertEndpoint;
                    function sendClosed() {
                        fetch(endpoint, {
                            method: "POST",
                            credentials: "same-origin",
                            headers: { "Content-Type": "application/json" }
                        }).catch(function() {});
                    }
                    container.addEventListener("closed.bs.alert", sendClosed);
                    var closeButton = container.querySelector(".btn-close");
                    if (closeButton) {
                        closeButton.addEventListener("click", sendClosed);
                    }
                });
            </script>';

            $mantenimiento_alertas_html = $html;
        }
    } catch (PDOException $e) {
        // Silenciar errores en alertas para no interrumpir la página
    }
}

function render_alertas_mantenimiento()
{
    global $mantenimiento_alertas_html;
    if (!empty($mantenimiento_alertas_html)) {
        echo $mantenimiento_alertas_html;
    }
}
?>