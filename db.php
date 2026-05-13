<?php
// db.php - Versión simple y funcional
$dbPath = __DIR__ . '/database/cocina_fantasy.sqlite';

try {
  $pdo = new PDO("sqlite:" . $dbPath);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error de conexión: " . $e->getMessage());
}

function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Para compatibilidad con código existente
$conn = $pdo;
