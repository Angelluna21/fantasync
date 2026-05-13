<?php
// db.php – conexión PDO para SQLite (funciona en Render y local)
// Detectar automáticamente si estamos en Render o entorno local

// Configuración para SQLite
$isRender = getenv('RENDER') !== false;
$isLocal = !$isRender;

if ($isLocal) {
  // --- Modo LOCAL (XAMPP con MySQL) ---
  define('DB_HOST', '127.0.0.1');
  define('DB_NAME', 'cocina_fantasy');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  define('DB_DRIVER', 'mysql');

  $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];

  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
  } catch (Throwable $e) {
    http_response_code(500);
    die("No se pudo conectar a la BD MySQL local: " . htmlspecialchars($e->getMessage()));
  }
} else {
  // --- Modo RENDER (SQLite) ---
  define('DB_DRIVER', 'sqlite');

  // Crear carpeta para la base de datos si no existe
  $dbDir = __DIR__ . '/database';
  if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
  }

  // Ruta del archivo SQLite
  $dbPath = $dbDir . '/cocina_fantasy.sqlite';
  $dsn = "sqlite:" . $dbPath;

  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];

  try {
    $pdo = new PDO($dsn, null, null, $options);

    // Crear tablas automáticamente si no existen
    crearTablasSQLite($pdo);
  } catch (Throwable $e) {
    http_response_code(500);
    die("No se pudo conectar a la BD SQLite: " . htmlspecialchars($e->getMessage()));
  }
}

// Función para crear las tablas en SQLite (basado en tu archivo cocina_fantasy.sql)
function crearTablasSQLite($pdo)
{
  // Verificar si ya existen las tablas
  $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='categorias'");
  if ($stmt->fetch() === false) {
    // Crear tablas según tu estructura original
    $sql = "
            -- Tabla de categorías
            CREATE TABLE IF NOT EXISTS categorias (
                id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100) NOT NULL,
                descripcion TEXT,
                activo BOOLEAN DEFAULT 1
            );
            
            -- Tabla de salones
            CREATE TABLE IF NOT EXISTS salones (
                id_salon INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100) NOT NULL,
                capacidad INTEGER,
                precio DECIMAL(10,2),
                activo BOOLEAN DEFAULT 1
            );
            
            -- Tabla de platillos
            CREATE TABLE IF NOT EXISTS platillos (
                id_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100) NOT NULL,
                descripcion TEXT,
                precio DECIMAL(10,2),
                categoria_id INTEGER,
                activo BOOLEAN DEFAULT 1,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id_categoria)
            );
            
            -- Tabla de ingredientes
            CREATE TABLE IF NOT EXISTS ingredientes (
                id_ingrediente INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100) NOT NULL,
                unidad_medida VARCHAR(50),
                stock_actual DECIMAL(10,2),
                stock_minimo DECIMAL(10,2)
            );
            
            -- Tabla de eventos
            CREATE TABLE IF NOT EXISTS eventos (
                id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_evento VARCHAR(100) NOT NULL,
                fecha DATE,
                salon_id INTEGER,
                cliente VARCHAR(100),
                telefono VARCHAR(20),
                FOREIGN KEY (salon_id) REFERENCES salones(id_salon)
            );
            
            -- Tabla de pedidos
            CREATE TABLE IF NOT EXISTS pedidos (
                id_pedido INTEGER PRIMARY KEY AUTOINCREMENT,
                evento_id INTEGER,
                platillo_id INTEGER,
                cantidad INTEGER,
                fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (evento_id) REFERENCES eventos(id_evento),
                FOREIGN KEY (platillo_id) REFERENCES platillos(id_platillo)
            );
            
            -- Tabla de compras
            CREATE TABLE IF NOT EXISTS compras (
                id_compra INTEGER PRIMARY KEY AUTOINCREMENT,
                ingrediente_id INTEGER,
                cantidad DECIMAL(10,2),
                fecha_compra DATE,
                proveedor VARCHAR(100),
                costo DECIMAL(10,2),
                FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id_ingrediente)
            );
            
            -- Tabla de recetas (relación platillo-ingrediente)
            CREATE TABLE IF NOT EXISTS recetas (
                id_receta INTEGER PRIMARY KEY AUTOINCREMENT,
                platillo_id INTEGER,
                ingrediente_id INTEGER,
                cantidad DECIMAL(10,2),
                FOREIGN KEY (platillo_id) REFERENCES platillos(id_platillo),
                FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id_ingrediente)
            );
        ";

    // Ejecutar la creación de tablas
    $pdo->exec($sql);

    // Insertar datos de ejemplo
    $pdo->exec("
            INSERT INTO categorias (nombre, descripcion) VALUES 
            ('Entradas', 'Platillos para comenzar la comida'),
            ('Plato Principal', 'Platillos fuertes principales'),
            ('Postres', 'Postres y dulces'),
            ('Bebidas', 'Bebidas y refrescos');
            
            INSERT INTO salones (nombre, capacidad, precio) VALUES 
            ('Salón Principal', 100, 5000.00),
            ('Terraza', 50, 3000.00),
            ('Salón VIP', 30, 8000.00);
            
            INSERT INTO platillos (nombre, precio, categoria_id) VALUES 
            ('Tacos al pastor', 120.00, 2),
            ('Enchiladas verdes', 95.00, 2),
            ('Pastel de chocolate', 80.00, 3),
            ('Agua de jamaica', 25.00, 4);
            
            INSERT INTO ingredientes (nombre, unidad_medida, stock_actual, stock_minimo) VALUES
            ('Carne de cerdo', 'kg', 50.00, 10.00),
            ('Tortillas de maíz', 'piezas', 200.00, 50.00),
            ('Chocolate', 'kg', 15.00, 5.00),
            ('Jamaica', 'kg', 8.00, 2.00);
        ");
  }
}

// Función helper que ya tenías (no tocar)
function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Para mantener compatibilidad si algún archivo usa $conn
$conn = $pdo;
