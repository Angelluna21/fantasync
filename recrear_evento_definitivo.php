<?php
// recrear_evento_definitivo.php - Recrea la tabla evento correctamente
require_once 'db.php';

echo "<h1>🔄 Recreando tabla 'evento' definitivamente</h1>";

try {
    // 1. Verificar columnas existentes
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    $columnNames = array_column($columns, 'name');

    echo "<h2>📋 Columnas actuales:</h2>";
    echo "<ul>";
    foreach ($columnNames as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";

    // 2. Guardar datos existentes si los hay
    $datosExistentes = [];
    try {
        $stmt = $pdo->query("SELECT * FROM evento");
        $datosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>📦 Se encontraron " . count($datosExistentes) . " eventos para respaldar</p>";
    } catch (PDOException $e) {
        echo "<p>⚠️ No se pudieron recuperar datos existentes</p>";
    }

    // 3. Eliminar tabla vieja
    $pdo->exec("DROP TABLE IF EXISTS evento");
    echo "<p>🗑️ Tabla vieja eliminada</p>";

    // 4. Crear tabla nueva con estructura correcta
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
    echo "<p style='color:green'>✅ Tabla 'evento' recreada con estructura correcta</p>";

    // 5. Restaurar datos si existían
    if (count($datosExistentes) > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) 
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($datosExistentes as $evento) {
            $stmt->execute([
                $evento['id_evento'],
                $evento['fecha'],
                $evento['titulo'] ?? 'Evento sin título',
                $evento['descorche'] ?? 0,
                $evento['cafe'] ?? 0
            ]);
        }
        echo "<p>✅ Datos restaurados: " . count($datosExistentes) . " eventos</p>";
    } else {
        // Insertar evento de ejemplo
        $pdo->exec("
            INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) VALUES 
            (1, date('now'), 'Evento de prueba', 0, 0)
        ");
        echo "<p>✅ Evento de ejemplo insertado</p>";
    }

    // 6. Verificar estructura final
    echo "<h2>📋 Estructura final:</h2>";
    $newColumns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    echo "<ul>";
    foreach ($newColumns as $col) {
        echo "<li><strong>{$col['name']}</strong> - {$col['type']}</li>";
    }
    echo "</ul>";

    // 7. Verificar que la tabla evento_salon existe
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento_salon'");
    if (!$result->fetch()) {
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
        echo "<p style='color:green'>✅ Tabla 'evento_salon' recreada</p>";
    }

    // 8. Verificar que la tabla evento_salon_platillo existe
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento_salon_platillo'");
    if (!$result->fetch()) {
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
        echo "<p style='color:green'>✅ Tabla 'evento_salon_platillo' recreada</p>";
    }

    echo "<h2 style='color:green'>✅ ¡TABLA EVENTO REPARADA!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
