<?php
// diagnostico_platillos.php - Ver qué código tiene platillos.php
echo "<h1>Diagnóstico de platillos.php</h1>";
echo "<h2>Código actual:</h2>";
echo "<pre>";
$codigo = file_get_contents('platillos.php');
echo htmlspecialchars($codigo);
echo "</pre>";

echo "<h2>¿Qué funciones MySQLi usa?</h2>";
echo "<ul>";
if (strpos($codigo, 'mysqli') !== false) echo "<li>❌ Usa MySQLi</li>";
if (strpos($codigo, 'new mysqli') !== false) echo "<li>❌ Tiene 'new mysqli'</li>";
if (strpos($codigo, 'mysqli_query') !== false) echo "<li>❌ Usa mysqli_query</li>";
if (strpos($codigo, 'mysqli_fetch') !== false) echo "<li>❌ Usa mysqli_fetch</li>";
if (strpos($codigo, '$conn->query') !== false) echo "<li>✅ Podría ser PDO</li>";
echo "</ul>";
