cat > db.php << 'EOF'
  <?php
  // db.php - Con creación automática de tablas
  $dbPath = __DIR__ . '/database/cocina_fantasy.sqlite';

  try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Crear tablas automáticamente si no existen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS salon (
            id_salon INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(80) NOT NULL,
            alias VARCHAR(20)
        );
        
        CREATE TABLE IF NOT EXISTS categoria_platillo (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(60) NOT NULL,
            orden INTEGER NOT NULL DEFAULT 1
        );
        
        CREATE TABLE IF NOT EXISTS platillo (
            id_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT,
            id_categoria INTEGER,
            porciones_base INTEGER NOT NULL DEFAULT 100
        );
        
        CREATE TABLE IF NOT EXISTS ingrediente (
            id_ingrediente INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(120) NOT NULL,
            unidad VARCHAR(20) NOT NULL
        );
        
        CREATE TABLE IF NOT EXISTS evento (
            id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATE NOT NULL,
            titulo VARCHAR(150),
            hora TIME
        );
        
        -- Insertar datos de ejemplo si la tabla está vacía
        INSERT OR IGNORE INTO salon (id_salon, nombre) VALUES 
        (1, 'CARMELO'),
        (2, 'SAN RAFAEL');
        
        INSERT OR IGNORE INTO categoria_platillo (id_categoria, nombre, orden) VALUES 
        (1, 'GUISADOS', 1),
        (2, 'BUFFET INFANTIL', 2),
        (3, 'BEBIDAS', 3);
    ");
  } catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
  }

  function h($s)
  {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }

  $conn = $pdo;
  ?>