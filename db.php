<?php
// db.php – Adaptado para SQLite (funciona en Render)
class Database
{
  private static $pdo = null;

  public static function getConnection()
  {
    if (self::$pdo === null) {
      try {
        // Crear carpeta para la base de datos si no existe
        $dbDir = __DIR__ . '/database';
        if (!is_dir($dbDir)) {
          mkdir($dbDir, 0777, true);
        }

        $dbPath = $dbDir . '/cocina_fantasy.sqlite';
        self::$pdo = new PDO("sqlite:" . $dbPath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
      }
    }
    return self::$pdo;
  }
}

// Función global para obtener la conexión (compatible con tu código)
function getConnection()
{
  return Database::getConnection();
}

// Tu función helper original
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Inicializar la conexión
$pdo = Database::getConnection();
