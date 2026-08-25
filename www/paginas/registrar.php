<?php
require_once "conexion.php";
session_start();

// Incluir alertas de mantenimiento
require_once "../includes/alertas_mantenimiento.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php require_once "purifIcon.php" ?>
    <link href="../css/variables.css" rel="stylesheet">
    <link href="../css/assets.css" rel="stylesheet">
    <link href="../css/components.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php render_alertas_mantenimiento(); ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Registro de Cliente</h3>

                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        // Validación de Token CSRF para seguridad
                        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                            echo '<div class="alert alert-danger text-center">Error de seguridad: Token inválido.</div>';
                        } else {
                            $codigo_cliente = trim($_POST['codigo_cliente']);
                            $nombre_cliente = trim($_POST['nombre_cliente']);

                            try {
                                // Iniciamos una transacción para asegurar que se creen ambos registros o ninguno
                                $conn->beginTransaction();

                                // 1. Insertar en la tabla principal de clientes
                                $query = "INSERT INTO clientes (codigo_cliente, nombre_cliente) VALUES (:codigo, :nombre)";
                                $stmt = $conn->prepare($query);
                                $stmt->execute([':codigo' => $codigo_cliente, ':nombre' => $nombre_cliente]);

                                // 2. Insertar en la tabla de progreso de promociones (Nueva lógica purificadorav2)
                                // Esto inicializa al cliente con 0 garrafones acumulados
                                $queryPromo = "INSERT INTO cliente_progreso_promo (codigo_cliente, garrafones_acumulados) VALUES (:codigo, 0)";
                                $stmtPromo = $conn->prepare($queryPromo);
                                $stmtPromo->execute([':codigo' => $codigo_cliente]);

                                $conn->commit();

                                echo '<div class="alert alert-success text-center" role="alert">
                                        ¡Cliente <b>' . htmlspecialchars($nombre_cliente) . '</b> registrado exitosamente con el código <b>' . htmlspecialchars($codigo_cliente) . '</b>!
                                      </div>';
                                
                            } catch (PDOException $e) {
                                $conn->rollBack();
                                if ($e->getCode() == 23000) { // Error de duplicado
                                    echo '<div class="alert alert-danger text-center" role="alert">
                                            Error: El código de cliente o el nombre ya existe.
                                          </div>';
                                } else {
                                    echo '<div class="alert alert-danger text-center" role="alert">
                                            Error en la base de datos: ' . $e->getMessage() . '
                                          </div>';
                                }
                            }
                        }
                    }
                    ?>

                    <div class="text-center mt-3">
                        <a href="../index.php" class="btn btn-primary">Volver al inicio</a>
                    </div>
                </div>
            </div>

            <?php require_once "footer.php"; ?>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Tu script de redirección original
    let tiempoRestante = 5;
    const contador = document.createElement("div");
    contador.className = "text-center mt-3 text-muted";
    document.querySelector(".card-body").appendChild(contador);

    function actualizarContador() {
        const minutos = Math.floor(tiempoRestante / 60);
        const segundos = tiempoRestante % 60;
        contador.innerHTML = `Redirigiendo en <b>${minutos}:${segundos.toString().padStart(2, '0')}</b>.`;

        if (tiempoRestante <= 0) {
            window.location.href = "../index.php";
        } else {
            tiempoRestante--;
            setTimeout(actualizarContador, 1000);
        }
    }
    actualizarContador();
</script>

</body>
</html>