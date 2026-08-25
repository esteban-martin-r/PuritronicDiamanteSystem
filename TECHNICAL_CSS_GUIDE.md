# 🔧 Guía Técnica de Estilos CSS - Puritronic POS

## Estructura de Archivos CSS

```
www/css/
├── variables.css       # ⭐ Design tokens centralizados
├── login.css          # Estilos específicos de login
├── assets.css         # Componentes generales (navbar, botones, tablas)
├── components.css     # Animaciones e interactividad
└── logo.css          # Estilos del logo
```

---

## 1. variables.css - El Corazón del Diseño

### Propósito
Define todas las variables CSS (Design Tokens) que se usan en el proyecto. **DEBE cargarse primero** en todos los archivos HTML/PHP.

### Estructura de Variables

```css
/* Colores Primarios */
--color-primary: rgb(42, 81, 255);
--color-primary-dark: rgb(28, 54, 170);
--color-primary-light: rgb(72, 111, 255);

/* Grises */
--color-gray-50: #f9f9f9;  /* Más claro */
...
--color-gray-900: #2c3e50; /* Más oscuro */

/* Estados */
--color-success, --color-warning, --color-danger, --color-info

/* Espaciado (multiples de 8px) */
--space-xs: 4px;
--space-sm: 8px;
--space-md: 16px;
--space-lg: 24px;
--space-xl: 32px;
--space-2xl: 48px;

/* Tipografía */
--font-size-xs a --font-size-3xl
--font-weight-normal a --font-weight-bold
--line-height-tight, --line-height-normal, --line-height-relaxed

/* Bordes */
--border-radius-sm a --border-radius-full

/* Sombras */
--shadow-sm a --shadow-2xl

/* Transiciones */
--transition-fast: 150ms
--transition-base: 300ms
--transition-slow: 500ms
```

### Cómo Usar Variables

```css
/* ✅ CORRECTO */
.button {
    background-color: var(--color-primary);
    padding: var(--space-md);
    border-radius: var(--border-radius-md);
}

/* ❌ INCORRECTO */
.button {
    background-color: #2a51ff;  /* Hardcoded */
    padding: 16px;              /* Número fijo */
    border-radius: 8px;         /* Valor literal */
}
```

---

## 2. login.css - Página de Autenticación

### Componentes
- `.layout` - Grid principal (header, body, footer)
- `.header` - Barra superior con logo
- `.body` - Área central con fondo borroso
- `.footer` - Pie de página
- `.login-card` - Tarjeta de formulario
- `.form-group` - Grupos de entrada
- `.login-button` - Botón de envío

### Características
- Usa variables CSS de `variables.css`
- Gradientes azul consistentes
- Animaciones de hover suave
- Focus states con shadow azul
- Responsive para móviles

### Ejemplo de Uso
```html
<div class="login-card">
    <h1 class="login-title">Iniciar Sesión</h1>
    <form>
        <div class="form-group">
            <label>Usuario</label>
            <input type="text" placeholder="Ingrese usuario">
        </div>
        <button type="submit" class="login-button">Ingresar</button>
    </form>
</div>
```

---

## 3. assets.css - Componentes Generales

### Navbars
```html
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <a class="navbar-brand" href="#">Puritronic</a>
    <div class="navbar-nav">
        <a class="nav-link" href="#">Link</a>
    </div>
</nav>
```

### Botones
```html
<button class="btn btn-primary">Primario</button>
<button class="btn btn-secondary">Secundario</button>
<button class="btn btn-success">Éxito</button>
<button class="btn btn-danger">Peligro</button>
<button class="btn btn-warning">Advertencia</button>

<!-- Tamaños -->
<button class="btn btn-primary btn-sm">Pequeño</button>
<button class="btn btn-primary btn-lg">Grande</button>
```

### Tarjetas
```html
<div class="card">
    <div class="card-header">Título</div>
    <div class="card-body">
        Contenido de la tarjeta
    </div>
</div>
```

### Tablas
```html
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Encabezado</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Dato</td>
        </tr>
    </tbody>
</table>
```

### Alertas
```html
<div class="alert alert-success">✅ Operación exitosa</div>
<div class="alert alert-danger">❌ Error occurred</div>
<div class="alert alert-warning">⚠️ Advertencia</div>
<div class="alert alert-info">ℹ️ Información</div>
```

### Badges
```html
<span class="badge badge-primary">Nuevo</span>
<span class="badge badge-success">Activo</span>
```

---

## 4. components.css - Animaciones e Interactividad

### Animaciones Disponibles
```css
@keyframes slideInDown   /* Entra desde arriba */
@keyframes slideInUp     /* Entra desde abajo */
@keyframes fadeIn        /* Desvanecimiento */
@keyframes pulse         /* Pulsación */
@keyframes bounce        /* Rebote */
```

### Tarjetas de Estadísticas
```html
<div class="stat-card">
    <div class="stat-number">1,234</div>
    <div class="stat-label">Clientes Activos</div>
</div>
```

### Tarjetas de Cliente
```html
<div class="client-card">
    <div class="client-name">Juan Pérez</div>
    <div class="client-info">📞 +52 123 456 7890</div>
    <div class="client-actions">
        <button class="btn btn-primary btn-sm">Editar</button>
        <button class="btn btn-danger btn-sm">Eliminar</button>
    </div>
</div>
```

### Búsqueda
```html
<div class="search-box">
    <i class="bi bi-search"></i>
    <input type="text" placeholder="Buscar...">
</div>
```

### Validación de Formularios
```html
<!-- Campo válido -->
<input type="text" class="form-control is-valid">
<div class="valid-feedback">✅ Válido</div>

<!-- Campo inválido -->
<input type="text" class="form-control is-invalid">
<div class="invalid-feedback">❌ Campo requerido</div>
```

---

## 5. Cómo Agregar CSS a Nuevas Páginas

### Template Estándar
```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Página</title>
    
    <!-- Bootstrap Base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Personalizados (ORDEN IMPORTANTE) -->
    <link href="css/variables.css" rel="stylesheet">      <!-- 1. Variables primero -->
    <link href="css/assets.css" rel="stylesheet">        <!-- 2. Componentes generales -->
    <link href="css/components.css" rel="stylesheet">    <!-- 3. Animaciones e interactividad -->
    <link href="css/login.css" rel="stylesheet">         <!-- 4. Específicos si aplica -->
    
    <?php require_once "purifIcon.php"; ?>
</head>
<body class="bg-light">
    <!-- Contenido -->
</body>
</html>
```

### Para Páginas en Carpeta /paginas/
```html
<!-- Usa rutas relativas con ../ -->
<link href="../css/variables.css" rel="stylesheet">
<link href="../css/assets.css" rel="stylesheet">
<link href="../css/components.css" rel="stylesheet">
```

---

## 6. Paleta de Colores Referencia Rápida

### Primarios (Azul)
```
--color-primary:       rgb(42, 81, 255)   /* Azul vibrante principal */
--color-primary-dark:  rgb(28, 54, 170)   /* Más oscuro, hovering */
--color-primary-light: rgb(72, 111, 255)  /* Más claro, accents */
```

### Grises (De más claro a más oscuro)
```
--color-gray-50:   #f9f9f9   /* Fondo muy claro */
--color-gray-100:  #f4f7fa   /* Fondo de página */
--color-gray-200:  #e8ecf1   /* Bordes, separadores */
--color-gray-300:  #e0e0e0   /* Bordes más oscuros */
--color-gray-500:  #999999   /* Texto deshabilitado */
--color-gray-600:  #666666   /* Texto secondary */
--color-gray-700:  #555555   /* Texto body */
--color-gray-800:  #4a4a4a   /* Texto más oscuro */
--color-gray-900:  #2c3e50   /* Headings */
```

### Estados
```
--color-success: #10b981     /* Verde */
--color-warning: #f59e0b     /* Amarillo */
--color-danger:  #ef4444     /* Rojo */
--color-info:    rgb(42, 81, 255)  /* Azul */
```

---

## 7. Mejores Prácticas

### ✅ HACER
```css
/* 1. Usar variables CSS */
.element {
    color: var(--color-gray-700);
    padding: var(--space-md);
}

/* 2. Usar transiciones definidas */
.button {
    transition: all var(--transition-base);
}

/* 3. Usar espaciado consistente */
.section {
    margin-bottom: var(--space-lg);
    padding: var(--space-lg);
}

/* 4. Especificar colores por semántica */
.success { color: var(--color-success); }
.error { color: var(--color-danger); }
```

### ❌ NO HACER
```css
/* 1. NO hardcodear colores */
.element { color: #ff0000; } /* ❌ */

/* 2. NO usar valores mágicos */
.button { padding: 14px; } /* ❌ */

/* 3. NO crear nuevas variables ad-hoc */
:root { --special-blue: #1234ff; } /* ❌ */

/* 4. NO ignorar transiciones */
.link { transition: none; } /* ❌ */
```

---

## 8. Personalización

### Cambiar Color Primario
En `variables.css`, modifica:
```css
:root {
    --color-primary: rgb(0, 100, 200);        /* Nuevo azul */
    --color-primary-dark: rgb(0, 60, 120);
    --color-primary-light: rgb(100, 150, 255);
}
```

### Cambiar Espaciado Global
En `variables.css`, modifica:
```css
:root {
    --space-md: 20px;  /* Aumenta el espaciado base */
}
```

### Cambiar Tipografía
En `variables.css`, modifica:
```css
:root {
    --font-family-base: 'Arial', sans-serif;
    --font-size-base: 18px;
}
```

---

## 9. Troubleshooting

### Los estilos no aplican
1. Verifica que `variables.css` esté cargado PRIMERO
2. Verifica las rutas (usa `../css/` en carpeta `/paginas/`)
3. Abre DevTools (F12) y busca si los archivos CSS se cargan

### Color no cambia
1. Asegúrate de usar `var(--color-*)` y no hardcodear colores
2. Verifica que la variable exista en `variables.css`
3. Revisa la especificidad CSS (Bootstrap puede sobrescribir)

### Espaciado inconsistente
1. Usa siempre `var(--space-*)` en lugar de valores fijos
2. Verifica que estés usando el tamaño correcto (sm, md, lg)
3. Revisa si hay CSS conflictivo inline

---

## 10. Referencia de Breakpoints

```css
/* Mobile first approach */
@media (max-width: 576px) {
    /* Pequeños dispositivos */
}

@media (min-width: 576px) {
    /* SM - Tablets pequeños */
}

@media (min-width: 768px) {
    /* MD - Tablets */
}

@media (min-width: 992px) {
    /* LG - Desktops */
}

@media (min-width: 1200px) {
    /* XL - Grandes desktops */
}
```

---

## Soporte y Mantenimiento

- **Cambios globales**: Modifica `variables.css`
- **Nuevos componentes**: Añade a `components.css`
- **Bootstrap overrides**: Usa `assets.css`
- **Específicos de login**: Usa `login.css`

Para preguntas o problemas, consulta `UI_IMPROVEMENTS.md`
