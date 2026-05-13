<?php
// recrear_tablas_evento.php - Recrea las tablas relacionadas con eventos desde cero
require_once 'db.php';

echo "<h1>🔄 Recreando tablas de eventos</h1>";

try {
    // =====================================================
    // 1. Eliminar tablas en orden correcto (respetando foreign keys)
    // =====================================================
    echo "<h2>🗑️ Eliminando tablas existentes...</h2>";

    $pdo->exec("DROP TABLE IF EXISTS evento_salon_platillo");
    echo "<p>✅ Tabla 'evento_salon_platillo' eliminada</p>";

    $pdo->exec("DROP TABLE IF EXISTS evento_salon");
    echo "<p>✅ Tabla 'evento_salon' eliminada</p>";

    $pdo->exec("DROP TABLE IF EXISTS evento");
    echo "<p>✅ Tabla 'evento' eliminada</p>";

    // =====================================================
    // 2. Crear tabla evento con TODAS las columnas
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento'...</h2>";

    $pdo->exec("
        CREATE TABLE evento (
            id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATE NOT NULL,
            titulo VARCHAR(150),
            misa TIME,
            recepcion TIME,
            inicio TIME,
            descorche BOOLEAN NOT NULL DEFAULT 0,
            cafe BOOLEAN NOT NULL DEFAULT 0,
            degustaciones VARCHAR(120),
            notas TEXT
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'evento' creada con todas las columnas</p>";

    // =====================================================
    // 3. Crear tabla evento_salon
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento_salon'...</h2>";

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
            UNIQUE(id_evento, id_salon),
            FOREIGN KEY (id_evento) REFERENCES evento(id_evento) ON DELETE CASCADE,
            FOREIGN KEY (id_salon) REFERENCES salon(id_salon) ON DELETE CASCADE
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'evento_salon' creada</p>";

    // =====================================================
    // 4. Crear tabla evento_salon_platillo
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento_salon_platillo'...</h2>";

    $pdo->exec("
        CREATE TABLE evento_salon_platillo (
            id_evento_salon_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento_salon INTEGER NOT NULL,
            id_platillo INTEGER NOT NULL,
            porciones_plan INTEGER NOT NULL,
            orden INTEGER,
            notas VARCHAR(120),
            UNIQUE(id_evento_salon, id_platillo),
            FOREIGN KEY (id_evento_salon) REFERENCES evento_salon(id_evento_salon) ON DELETE CASCADE,
            FOREIGN KEY (id_platillo) REFERENCES platillo(id_platillo) ON DELETE CASCADE
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'evento_salon_platillo' creada</p>";

    // =====================================================
    // 5. Insertar datos de ejemplo
    // =====================================================
    echo "<h2>📥 Insertando datos de ejemplo...</h2>";

    // Insertar eventos
    $pdo->exec("
        INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) VALUES 
        (1, date('now'), 'Evento de prueba 1', 0, 0),
        (2, date('now', '+1 day'), 'Evento de prueba 2', 1, 1),
        (3, date('now', '-1 day'), 'Evento pasado', 0, 1)
    ");
    echo "<p>✅ 3 eventos insertados</p>";

    // Verificar que hay salones
    $salonesCount = $pdo->query("SELECT COUNT(*) FROM salon")->fetchColumn();
    if ($salonesCount == 0) {
        $pdo->exec("
            INSERT INTO salon (id_salon, nombre) VALUES 
            (1, 'CARMELO'),
            (2, 'SAN RAFAEL')
        ");
        echo "<p>✅ Salones insertados</p>";
    }

    // Insertar relaciones evento_salon
    $pdo->exec("
        INSERT INTO evento_salon (id_evento, id_salon, adultos, ninos) VALUES 
        (1, 1, 100, 20),
        (2, 2, 75, 15),
        (3, 1, 50, 10)
    ");
    echo "<p>✅ Relaciones evento_salon insertadas</p>";

    // =====================================================
    // 6. Verificar todo
    // =====================================================
    echo "<h2>🔍 Verificación final:</h2>";

    // Verificar columnas de evento
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    echo "<p><strong>Columnas en 'evento':</strong> ";
    $colNames = array_column($columns, 'name');
    echo implode(', ', $colNames);
    echo "</p>";

    // Verificar columnas de evento_salon
    $columns = $pdo->query("PRAGMA table_info(evento_salon)")->fetchAll();
    echo "<p><strong>Columnas en 'evento_salon':</strong> ";
    $colNames = array_column($columns, 'name');
    echo implode(', ', $colNames);
    echo "</p>";

    // Mostrar eventos
    $eventos = $pdo->query("SELECT * FROM evento")->fetchAll();
    echo "<h3>📅 Eventos en la base de datos:</h3>";
    echo "<ul>";
    foreach ($eventos as $e) {
        echo "<li>ID: {$e['id_evento']} - {$e['titulo']} - Fecha: {$e['fecha']} - Descorche: " . ($e['descorche'] ? 'Sí' : 'No') . "</li>";
    }
    echo "</ul>";

    echo "<h2 style='color:green'>✅ ¡Todo está listo!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
