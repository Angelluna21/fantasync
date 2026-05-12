<?php
// db.php – conexión PDO para XAMPP (root sin contraseña por defecto)
define('DB_HOST','127.0.0.1');
define('DB_NAME','cocina_fantasy'); // Cambia si usaste otro nombre
define('DB_USER','root');
define('DB_PASS',''); // Si tu MySQL tiene contraseña, ponla aquí

$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (Throwable $e) {
  http_response_code(500);
  die("No se pudo conectar a la BD: ".htmlspecialchars($e->getMessage()));
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
