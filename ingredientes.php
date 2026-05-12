<?php
// Conexión BD
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db = "cocina_fantasy";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Error: " . $conn->connect_error);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Agregar ingrediente
if (isset($_POST['add_ingrediente'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $unidad = $conn->real_escape_string($_POST['unidad']);
    $tamano = $conn->real_escape_string($_POST['tamano']);
    $presentacion = $conn->real_escape_string($_POST['presentacion']);
    $conn->query("INSERT INTO ingrediente (nombre, unidad, tamanio_presentacion, presentacion_descripcion) 
                  VALUES ('$nombre','$unidad','$tamano','$presentacion')");
}

// Actualizar ingrediente
if (isset($_POST['update_ingrediente'])) {
    $id = intval($_POST['id']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $unidad = $conn->real_escape_string($_POST['unidad']);
    $tamano = $conn->real_escape_string($_POST['tamano']);
    $presentacion = $conn->real_escape_string($_POST['presentacion']);
    $conn->query("UPDATE ingrediente 
                  SET nombre='$nombre', unidad='$unidad', tamanio_presentacion='$tamano', presentacion_descripcion='$presentacion' 
                  WHERE id_ingrediente=$id");
}

// Eliminar ingrediente
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM ingrediente WHERE id_ingrediente=$id");

    // Reajustar el AUTO_INCREMENT
    $conn->query("ALTER TABLE ingrediente AUTO_INCREMENT = 1");
}

/* ===== 🔍 BÚSQUEDA ===== */
$search = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sqlIng = "SELECT * FROM ingrediente";
if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $sqlIng .= " WHERE nombre LIKE '%$searchEsc%'
                 OR unidad LIKE '%$searchEsc%'
                 OR tamanio_presentacion LIKE '%$searchEsc%'
                 OR presentacion_descripcion LIKE '%$searchEsc%'";
}
$sqlIng .= " ORDER BY id_ingrediente ASC";

$ingredientes = $conn->query($sqlIng);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Ingredientes</title>
<style>
/* Estilos modernos para ingredientes */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 20px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  overflow: hidden;
}

.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 30px;
  text-align: center;
}

.page-header h1 {
  margin: 0;
  font-size: 2.2rem;
  font-weight: 700;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.page-content {
  padding: 30px;
}

.btn {
  display: inline-block;
  padding: 12px 24px;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  font-size: 1rem;
  cursor: pointer;
  text-align: center;
  margin: 5px;
}

.btn.primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
}

.btn.secondary {
  background: linear-gradient(135deg, #6c757d, #495057);
  color: white;
  border: none;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin: 20px 0;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border: 1px solid #f0f0f0;
}

.card h2 {
  color: #333;
  margin-top: 0;
  margin-bottom: 20px;
  font-weight: 600;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #555;
}

.form-group input {
  width: 100%;
  padding: 12px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #fff;
  box-sizing: border-box;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* 🔍 Barra de búsqueda */
.search-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  margin-bottom: 15px;
}

.search-bar input[type="text"] {
  flex: 1;
  min-width: 220px;
  padding: 10px 14px;
  border-radius: 25px;
  border: 2px solid #e9ecef;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-bar input[type="text"]:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102,126,234,0.12);
}

.search-bar .btn {
  margin: 0;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

table th {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 15px 12px;
  text-align: left;
  font-weight: 600;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

table td {
  padding: 12px;
  border-bottom: 1px solid #e9ecef;
  vertical-align: middle;
}

table tbody tr:hover {
  background-color: #f8f9fa;
}

table input[type="text"] {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9rem;
}

.actions {
  white-space: nowrap;
}

.actions button,
.actions a {
  margin: 2px;
  padding: 6px 12px;
  border-radius: 15px;
  font-size: 0.9rem;
  text-decoration: none;
  transition: all 0.3s ease;
}

.actions button {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: white;
  border: none;
  cursor: pointer;
}

.actions a {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
}

.actions button:hover,
.actions a:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
  .page-header h1 {
    font-size: 1.8rem;
  }
  
  .page-content {
    padding: 20px;
  }
  
  table {
    font-size: 0.9rem;
  }
  
  .btn {
    display: block;
    width: 100%;
    margin: 10px 0;
  }
  
  .actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
}
</style>
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1>🌿 Gestión de Ingredientes</h1>
  </div>
  <div class="page-content">
    <a class="btn secondary" href="index.php">← Regresar al inicio</a>
    <div class="card">
      <h2>➕ Agregar nuevo ingrediente</h2>
      <form method="post">
        <div class="form-group">
          <label>Nombre:</label>
          <input type="text" name="nombre" required>
        </div>

        <div class="form-group">
          <label>Unidad:</label>
          <input type="text" name="unidad" required>
        </div>

        <div class="form-group">
          <label>Tamaño:</label>
          <input type="text" name="tamano">
        </div>

        <div class="form-group">
          <label>Presentación:</label>
          <input type="text" name="presentacion">
        </div>

        <button type="submit" name="add_ingrediente" class="btn primary">➕ Agregar</button>
      </form>
    </div>

    <div class="card">
      <h2>📋 Listado de ingredientes</h2>

      <!-- 🔍 Barra de búsqueda -->
      <form method="get" class="search-bar">
        <input
          type="text"
          name="buscar"
          placeholder="Buscar por nombre, unidad o presentación..."
          value="<?= h($search) ?>"
        >
        <button type="submit" class="btn primary">Buscar</button>
        <?php if ($search !== ''): ?>
          <a href="ingredientes.php" class="btn secondary">Limpiar</a>
        <?php endif; ?>
      </form>

      <table>
        <thead>
          <tr><th>ID</th><th>Nombre</th><th>Unidad</th><th>Tamaño</th><th>Presentación</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php while($ing=$ingredientes->fetch_assoc()): ?>
          <tr>
            <form method="post">
              <td><?= (int)$ing['id_ingrediente'] ?><input type="hidden" name="id" value="<?= (int)$ing['id_ingrediente'] ?>"></td>
              <td><input type="text" name="nombre" value="<?= h($ing['nombre']) ?>"></td>
              <td><input type="text" name="unidad" value="<?= h($ing['unidad']) ?>"></td>
              <td><input type="text" name="tamano" value="<?= h($ing['tamanio_presentacion']) ?>"></td>
              <td><input type="text" name="presentacion" value="<?= h($ing['presentacion_descripcion']) ?>"></td>
              <td class="actions">
                <button type="submit" name="update_ingrediente">💾 Guardar</button>
                <a href="?delete=<?= (int)$ing['id_ingrediente'] ?>" onclick="return confirm('¿Eliminar ingrediente?')">🗑 Eliminar</a>
              </td>
            </form>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
