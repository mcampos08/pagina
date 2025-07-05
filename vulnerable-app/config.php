<?php
// VULNERABLE: Credenciales hardcodeadas
$db_host = 'localhost';
$db_user = 'admin';
$db_pass = 'admin123';
$db_name = 'vulnerable_app';

// VULNERABLE: Configuración insegura
error_reporting(E_ALL);
ini_set('display_errors', 1);

// VULNERABLE: Conexión sin validación pero con mejor manejo
try {
    $connection = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Verificar conexión
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    
    // VULNERABLE: Configuración insegura de charset
    $connection->set_charset("utf8");
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// VULNERABLE: Configuración de sesión insegura
ini_set('session.cookie_httponly', 0);
ini_set('session.cookie_secure', 0);
session_start();

// VULNERABLE: Clave secreta hardcodeada
define('SECRET_KEY', 'super_secret_key_123');

// VULNERABLE: Función de logging insegura
function log_activity($message) {
    $log = date('Y-m-d H:i:s') . " - " . $message . "\n";
    file_put_contents('/tmp/app.log', $log, FILE_APPEND);
}
?>
