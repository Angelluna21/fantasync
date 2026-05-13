<?php
// crear_tablas_faltantes.php - Crea todas las tablas necesarias para evento_list.php
require_once 'db.php';

echo "<h1>🔧 Creando tablas faltantes</h1>";

try {
    // Verificar qué tablas existen
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $existingTables = [];
    foreach ($result->fetchAll() as $row) {
        $existingTables[] = $row['name'];
    }

    echo "<h2>📋 Tablas existentes:</h2>";
    echo "<ul>";
    foreach ($existingTables as $table) {
        echo "<li>✅ $table</li>";
    }
    echo "</ul>";

    // =====================================================
    // Crear tabla evento_salon si no existe
    // =====================================================
    if (!in_array('evento_salon', $existingTables)) {
        $pdo->exec("
            CREATE TABLE evento_salon (
                id_evento_salon INTEGER PRIMARY KEY AUTOINCREMENT,
                id_evento INTEGER NOT NULL,
                id_salon INTEGER NOT NULL,
                adultos INTEGER NOT NULL DEFAULT 0,
                ninos INTEGER NOT NULL DEFAULT 0,
                misa TIME,
                recepcion TIME,
                inicio TIME,
                descorche BOOLEAN NOT NULL DEFAULT 0,
                cafe BOOLEAN NOT NULL DEFAULT 0,
                degustaciones VARCHAR(120),
                factor_nino DECIMAL(5,2) NOT NULL DEFAULT 0.70,
                UNIQUE(id_evento, id_salon)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'evento_salon' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'evento_salon' ya existe</p>";
    }

    // =====================================================
    // Crear tabla evento_salon_platillo si no existe
    // =====================================================
    if (!in_array('evento_salon_platillo', $existingTables)) {
        $pdo->exec("
            CREATE TABLE evento_salon_platillo (
                id_evento_salon_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
                id_evento_salon INTEGER NOT NULL,
                id_platillo INTEGER NOT NULL,
                porciones_plan INTEGER NOT NULL,
                orden INTEGER,
                notas VARCHAR(120),
                UNIQUE(id_evento_salon, id_platillo)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'evento_salon_platillo' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'evento_salon_platillo' ya existe</p>";
    }

    // =====================================================
    // Crear tabla platillo_categoria si no existe
    // =====================================================
    if (!in_array('platillo_categoria', $existingTables)) {
        $pdo->exec("
            CREATE TABLE platillo_categoria (
                id_platillo INTEGER NOT NULL,
                id_categoria INTEGER NOT NULL,
                PRIMARY KEY (id_platillo, id_categoria)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'platillo_categoria' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'platillo_categoria' ya existe</p>";
    }

    // =====================================================
    // Crear tabla receta si no existe
    // =====================================================
    if (!in_array('receta', $existingTables)) {
        $pdo->exec("
            CREATE TABLE receta (
                id_platillo INTEGER NOT NULL,
                id_ingrediente INTEGER NOT NULL,
                cantidad_por_base DECIMAL(10,3) NOT NULL,
                nota VARCHAR(120),
                PRIMARY KEY (id_platillo, id_ingrediente)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'receta' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'receta' ya existe</p>";
    }

    // =====================================================
    // Verificar columnas de evento_salon
    // =====================================================
    echo "<h2>📋 Verificando columnas de 'evento_salon':</h2>";
    $result = $pdo->query("PRAGMA table_info(evento_salon)");
    $columns = $result->fetchAll();

    $neededColumns = [
        'id_evento_salon' => 'INTEGER',
        'id_evento' => 'INTEGER',
        'id_salon' => 'INTEGER',
        'adultos' => 'INTEGER',
        'ninos' => 'INTEGER',
        'misa' => 'TIME',
        'recepcion' => 'TIME',
        'inicio' => 'TIME',
        'descorche' => 'BOOLEAN',
        'cafe' => 'BOOLEAN',
        'degustaciones' => 'VARCHAR',
        'factor_nino' => 'DECIMAL'
    ];

    $existingCols = [];
    foreach ($columns as $col) {
        $existingCols[] = $col['name'];
    }

    foreach ($neededColumns as $colName => $colType) {
        if (!in_array($colName, $existingCols)) {
            try {
                if ($colName == 'factor_nino') {
                    $pdo->exec("ALTER TABLE evento_salon ADD COLUMN $colName DECIMAL(5,2) NOT NULL DEFAULT 0.70");
                } else {
                    $default = ($colName == 'adultos' || $colName == 'ninos') ? '0' : 'NULL';
                    if ($colName == 'descorche' || $colName == 'cafe') {
                        $pdo->exec("ALTER TABLE evento_salon ADD COLUMN $colName BOOLEAN NOT NULL DEFAULT 0");
                    } else {
                        $pdo->exec("ALTER TABLE evento_salon ADD COLUMN $colName $colType");
                    }
                }
                echo "<p style='color:green'>✅ Columna '$colName' agregada a evento_salon</p>";
            } catch (PDOException $e) {
                echo "<p>⚠️ No se pudo agregar '$colName': " . $e->getMessage() . "</p>";
            }
        }
    }

    // =====================================================
    // Insertar datos de ejemplo si es necesario
    // =====================================================
    $count = $pdo->query("SELECT COUNT(*) FROM evento")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) VALUES 
            (1, date('now'), 'Evento de ejemplo 1', 0, 0),
            (2, date('now', '+1 day'), 'Evento de ejemplo 2', 1, 1)
        ");
        echo "<p style='color:green'>✅ Eventos de ejemplo insertados</p>";
    }

    $countSalon = $pdo->query("SELECT COUNT(*) FROM evento_salon")->fetchColumn();
    if ($countSalon == 0 && $count > 0) {
        // Asegurar que hay salones
        $salonesCount = $pdo->query("SELECT COUNT(*) FROM salon")->fetchColumn();
        if ($salonesCount > 0) {
            $pdo->exec("
                INSERT INTO evento_salon (id_evento, id_salon, adultos, ninos) VALUES 
                (1, 1, 100, 20),
                (2, 2, 75, 15)
            ");
            echo "<p style='color:green'>✅ Relaciones evento_salon insertadas</p>";
        }
    }

    echo "<h2 style='color:green'>✅ ¡Todas las tablas están listas!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
