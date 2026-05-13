<?php
// recrear_evento_correctamente.php - Recrea la tabla evento con todas las columnas
require_once 'db.php';

echo "<h1>🔄 Recreando tabla 'evento' correctamente</h1>";

try {
    // =====================================================
    // 1. Eliminar tabla evento si existe
    // =====================================================
    echo "<h2>🗑️ Eliminando tabla 'evento'...</h2>";
    $pdo->exec("DROP TABLE IF EXISTS evento");
    echo "<p>✅ Tabla 'evento' eliminada</p>";

    // =====================================================
    // 2. Crear tabla evento con TODAS las columnas
    // =====================================================
    echo "<h2>📝 Creando nueva tabla 'evento'...</h2>";
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
    echo "<p style='color:green'>✅ Tabla 'evento' creada correctamente</p>";

    // =====================================================
    // 3. Insertar eventos de ejemplo
    // =====================================================
    echo "<h2>📥 Insertando eventos de ejemplo...</h2>";
    $pdo->exec("
        INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) VALUES 
        (1, date('now'), 'Evento de prueba 1 - CARMELO', 0, 0),
        (2, date('now', '+1 day'), 'Evento de prueba 2 - SAN RAFAEL', 1, 1),
        (3, date('now', '-1 day'), 'Evento pasado', 0, 1)
    ");
    echo "<p style='color:green'>✅ 3 eventos insertados</p>";

    // =====================================================
    // 4. Verificar que evento_salon existe y tiene datos
    // =====================================================
    echo "<h2>🔍 Verificando tabla 'evento_salon'...</h2>";

    // Limpiar evento_salon para evitar conflictos
    $pdo->exec("DELETE FROM evento_salon");

    // Insertar relaciones evento_salon
    $pdo->exec("
        INSERT INTO evento_salon (id_evento_salon, id_evento, id_salon, adultos, ninos) VALUES 
        (1, 1, 1, 100, 20),
        (2, 2, 2, 75, 15)
    ");
    echo "<p style='color:green'>✅ Relaciones evento_salon actualizadas</p>";

    // =====================================================
    // 5. Mostrar resultados
    // =====================================================
    echo "<h2>📋 Verificación final:</h2>";

    // Mostrar eventos
    $eventos = $pdo->query("
        SELECT id_evento, fecha, titulo, 
               CASE WHEN descorche = 1 THEN 'SI' ELSE 'NO' END as descorche,
               CASE WHEN cafe = 1 THEN 'SI' ELSE 'NO' END as cafe
        FROM evento
        ORDER BY id_evento
    ")->fetchAll();

    echo "<h3>📅 Eventos en la base de datos:</h3>";
    if (count($eventos) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Fecha</th><th>Título</th><th>Descorche</th><th>Café</th></tr>";
        foreach ($eventos as $e) {
            echo "<tr>";
            echo "<td>{$e['id_evento']}</td>";
            echo "<td>{$e['fecha']}</td>";
            echo "<td>{$e['titulo']}</td>";
            echo "<td>{$e['descorche']}</td>";
            echo "<td>{$e['cafe']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No hay eventos</p>";
    }

    // Mostrar relaciones evento_salon
    $relaciones = $pdo->query("
        SELECT es.id_evento_salon, es.id_evento, s.nombre as salon, es.adultos, es.ninos
        FROM evento_salon es
        JOIN salon s ON es.id_salon = s.id_salon
    ")->fetchAll();

    echo "<h3>🏢 Relaciones Evento-Salón:</h3>";
    if (count($relaciones) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
        echo "<tr><th>ID Relación</th><th>ID Evento</th><th>Salón</th><th>Adultos</th><th>Niños</th></tr>";
        foreach ($relaciones as $r) {
            echo "<tr>";
            echo "<td>{$r['id_evento_salon']}</td>";
            echo "<td>{$r['id_evento']}</td>";
            echo "<td>{$r['salon']}</td>";
            echo "<td>{$r['adultos']}</td>";
            echo "<td>{$r['ninos']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Verificar columnas de evento
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    echo "<h3>📋 Columnas de la tabla 'evento':</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['name']}</strong> - {$col['type']}</li>";
    }
    echo "</ul>";

    echo "<h2 style='color:green'>✅ ¡TODO LISTO!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
