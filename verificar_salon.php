<?php
// verificar_salon.php - Asegura que la tabla salon tiene datos
require_once 'db.php';

echo "<h1>🔧 Verificando tabla 'salon'</h1>";

try {
    // Verificar si hay salones
    $count = $pdo->query("SELECT COUNT(*) FROM salon")->fetchColumn();

    if ($count == 0) {
        echo "<p>⚠️ No hay salones registrados. Insertando...</p>";
        $pdo->exec("
            INSERT INTO salon (id_salon, nombre, alias) VALUES 
            (1, 'CARMELO', NULL),
            (2, 'SAN RAFAEL', NULL)
        ");
        echo "<p style='color:green'>✅ Salones insertados: CARMELO y SAN RAFAEL</p>";
    } else {
        echo "<p>✅ Ya existen $count salones</p>";
    }

    // Mostrar salones
    $salones = $pdo->query("SELECT * FROM salon")->fetchAll();
    echo "<h2>📋 Salones disponibles:</h2>";
    echo "<ul>";
    foreach ($salones as $s) {
        echo "<li>ID: {$s['id_salon']} - {$s['nombre']}</li>";
    }
    echo "</ul>";

    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
