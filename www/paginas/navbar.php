<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
            <img src="../imagenes/logov2.png" width="200" height="65" alt="Puritronic">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>paginas/filtrar_clientes.php">Buscar Clientes</a></li>
                <?php if (esAdministrador()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>paginas/compras.php">Historial</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>paginas/metricas.php">Métricas</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link text-warning" href="<?= BASE_URL ?>index.php?logout=1">Cerrar sesión</a></li>
                <li class="nav-item me-lg-2">
                    <span class="badge <?= esAdministrador() ? 'bg-warning text-dark' : 'bg-light text-primary' ?> px-3 py-2">
                        <i class="bi <?= esAdministrador() ? 'bi-shield-lock-fill' : 'bi-person-fill' ?> me-1"></i>
                        <?= htmlspecialchars($_SESSION['usuario']) ?>
                        · <?= esAdministrador() ? 'Administrador' : 'Empleado' ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</nav>