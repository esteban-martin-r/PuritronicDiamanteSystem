<?php
// En Docker, el nombre del host es el nombre del servicio en el docker-compose.yml
$servername = "db"; 
$username = "root"; 
// Cambiamos a la contraseña que definimos en tu archivo .env (1234)
$password = "1234"; 
// Asegúrate de que este nombre coincida con el MYSQL_DATABASE de tu .env
$BasedeDatos = "purificadorav2"; 

try {
    // IMPORTANTE: Internamente entre contenedores se usa el puerto estándar 3306
    $conn = new PDO("mysql:host=$servername;port=3306;dbname=$BasedeDatos;charset=utf8mb4", $username, $password);

    // Configurar el modo de error para desarrollo
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opcional: Forzar nombres de columnas en minúsculas para evitar problemas de compatibilidad
    $conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

} catch (PDOException $e) {
    // Usamos error_log para no exponer detalles técnicos en el navegador del cliente
    error_log("Error de conexión: " . $e->getMessage());
    die("Lo sentimos, hay un problema técnico con la base de datos.");
}
?>