<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Purificadora</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <link href="css/variables.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <script src="javascript/validarLogin.js"></script>

    <?php
    require_once "paginas/purifIcon.php";
    ?>
</head>
<body>
    <div class="layout">
        <header class="header">
            <nav class="menu">
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                </ul>
            </nav>
        </header>

        <main class="body">
            <div class="login-card">
                <img src="../imagenes/logoV2_Login.png" width="250" height="170" style="align-items: center; align-content: center;">
                <br><br>
                <h1 class="login-title">Iniciar Sesión</h1>
                <h5>Ingresa Tus Claves De Acceso</h5>
                
                <form action="paginas/validacion.php" method="post" id="formulario1">
                    <div class="form-group">
                        <label for="txtusuario">Usuario</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="txtusuario" id="txtusuario" placeholder="Ingrese su usuario" required>
                        </div>
                        <div class="error-message" id="user-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="txtpassword">Contraseña</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="txtpassword" id="txtpassword" placeholder="Ingrese su contraseña" required>
                        </div>
                        <div class="error-message" id="pass-error"></div>
                    </div>
                    
                    <button type="submit" class="login-button" name="btn_aceptar" id="btn_aceptar">
                        Ingresar al Sistema
                    </button>
                </form>

                <?php
                    if (isset($_GET['error']) && $_GET['error'] == 1) {
                        echo '<p style="color: red; text-align: center; margin-top: 10px;">Usuario o contraseña incorrectos.</p>';
                    }
                ?>
            </div>
        </main>

        <footer class="footer">
            <p>Sistema Puritronic Diamante &copy; 2025 - Todos los derechos reservados</p>
        </footer>
    </div>
</body>
</html>