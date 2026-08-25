<?php
// auth.php
// Incluir SIEMPRE al inicio de cada página protegida:
//   require_once __DIR__ . '/auth.php';           (si el archivo está en paginas/)
//   require_once __DIR__ . '/paginas/auth.php';    (si el archivo está en la raíz, como index.php)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta base del sitio, para que los links funcionen igual
// sin importar si la página que incluye esto está en la raíz o en paginas/.
// Si tu sitio se abre como http://localhost/index.php  -> deja '/'
// Si se abre como http://localhost/purificadora/index.php -> cambia a '/purificadora/'
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

// 1. Verifica que haya sesión iniciada
if (!isset($_SESSION['usuario']) || !isset($_SESSION['validado'])) {
    header("Location: " . BASE_URL . "login_purificadora.php");
    exit;
}

/**
 * Restringe el acceso a la página según el rol del usuario.
 * Uso: requiereRol(['administrador']);
 *      requiereRol(['administrador', 'empleado']);
 */
function requiereRol(array $rolesPermitidos): void {
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos, true)) {
        http_response_code(403);
        die('<h2 style="text-align:center;margin-top:50px;">🚫 No tienes permiso para ver esta página.</h2>
             <p style="text-align:center;"><a href="' . BASE_URL . 'index.php">Volver al inicio</a></p>');
    }
}

/**
 * Helper para mostrar/ocultar cosas en el HTML según el rol.
 * Uso: <?php if (esAdministrador()): ?> ... <?php endif; ?>
 */
function esAdministrador(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador';
}