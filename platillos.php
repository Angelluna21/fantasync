<?php
// platillos.php - Versión PDO para SQLite (Compatible con Render)
require_once 'db.php';

// Tu función helper (ya existe en db.php, pero la mantengo por compatibilidad)
if (!function_exists('h')) {
  function h($s)
  {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

/* ========= CRUD con PDO ========= */

// Agregar platillo
if (isset($_POST['add_platillo'])) {
  try {
    $stmt = $pdo->prepare("INSERT INTO platillo (nombre, descripcion, porciones_base, id_categoria)
                                VALUES (:nombre, :descripcion, :porciones, :categoria)");
    $stmt->execute([
      ':nombre' => $_POST['nombre'],
      ':descripcion' => $_POST['descripcion'],
      ':porciones' => max(1, (int)$_POST['porciones_base']),
      ':categoria' => (int)$_POST['categoria']
    ]);
  } catch (PDOException $e) {
    // Error silencioso o podrías mostrar un mensaje
  }
}

// Actualizar platillo
if (isset($_POST['update_platillo'])) {
  try {
    $stmt = $pdo->prepare("UPDATE platillo 
                                SET nombre = :nombre, 
                                    descripcion = :descripcion, 
                                    porciones_base = :porciones, 
                                    id_categoria = :categoria
                                WHERE id_platillo = :id");
    $stmt->execute([
      ':id' => (int)$_POST['id'],
      ':nombre' => $_POST['nombre'],
      ':descripcion' => $_POST['descripcion'],
      ':porciones' => max(1, (int)$_POST['porciones_base']),
      ':categoria' => (int)$_POST['categoria']
    ]);
  } catch (PDOException $e) {
    // Error silencioso
  }
}

// Eliminar platillo
if (isset($_GET['delete'])) {
  try {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM platillo WHERE id_platillo = :id");
    $stmt->execute([':id' => $id]);
    // Redirigir para evitar reenvío del GET
    header("Location: platillos.php");
    exit;
  } catch (PDOException $e) {
    // Error silencioso
  }
}

/* ========= Listas ========= */

// Obtener todas las categorías
$cats = [];
$result = $pdo->query("SELECT id_categoria, nombre FROM categoria_platillo ORDER BY orden, nombre");
$cats = $result->fetchAll();

// 🔍 BÚSQUEDA DE PLATILLOS
$search = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sqlPlat = "SELECT * FROM platillo";
$params = [];

if ($search !== '') {
  $sqlPlat .= " WHERE nombre LIKE :search OR descripcion LIKE :search";
  $params[':search'] = "%$search%";
}
$sqlPlat .= " ORDER BY id_platillo ASC";

$stmt = $pdo->prepare($sqlPlat);
$stmt->execute($params);
$platillos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gestión de Platillos</title>
  <style>
    /* Estilos modernos para platillos */
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
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
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
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
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

    .btn.danger {
      background: linear-gradient(135deg, #dc3545, #c82333);
      color: white;
      border: none;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin: 20px 0;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fff;
      box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }

    /* 🔍 Estilos barra de búsqueda */
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
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.12);
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
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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

    table input[type="text"],
    table input[type="number"],
    table select {
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
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
      <h1>🍲 Gestión de Platillos</h1>
    </div>
    <div class="page-content">
      <a class="btn secondary" href="index.php">← Regresar al inicio</a>

      <div class="card">
        <h2>➕ Agregar nuevo platillo</h2>
        <form method="post">
          <div class="form-group">
            <label>Nombre:</label>
            <input type="text" name="nombre" required>
          </div>

          <div class="form-group">
            <label>Descripción:</label>
            <textarea name="descripcion"></textarea>
          </div>

          <div class="form-group">
            <label>Porciones base:</label>
            <input type="number" name="porciones_base" value="100" min="1" required>
          </div>

          <div class="form-group">
            <label>Categoría:</label>
            <select name="categoria" required>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id_categoria'] ?>"><?= h($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" name="add_platillo" class="btn primary">➕ Agregar</button>
        </form>
      </div>

      <div class="card">
        <h2>📋 Listado de platillos</h2>

        <!-- 🔍 Barra de búsqueda -->
        <form method="get" class="search-bar">
          <input
            type="text"
            name="buscar"
            placeholder="Buscar por nombre o descripción..."
            value="<?= h($search) ?>">
          <button type="submit" class="btn primary">Buscar</button>
          <?php if ($search !== ''): ?>
            <a href="platillos.php" class="btn secondary">Limpiar</a>
          <?php endif; ?>
        </form>

        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Porciones base</th>
              <th>Categoría</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($platillos as $p): ?>
              <tr>
                <form method="post">
                  <td><?= (int)$p['id_platillo'] ?><input type="hidden" name="id" value="<?= (int)$p['id_platillo'] ?>"></td>
                  <td><input type="text" name="nombre" value="<?= h($p['nombre']) ?>"></td>
                  <td><input type="text" name="descripcion" value="<?= h($p['descripcion'] ?? '') ?>"></td>
                  <td><input type="number" name="porciones_base" min="1" value="<?= (int)$p['porciones_base'] ?>"></td>
                  <td>
                    <select name="categoria" required>
                      <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c['id_categoria'] ?>" <?= ((int)$c['id_categoria'] == (int)$p['id_categoria']) ? 'selected' : ''; ?>>
                          <?= h($c['nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="actions">
                    <button type="submit" name="update_platillo">💾 Guardar</button>
                    <a href="?delete=<?= (int)$p['id_platillo'] ?>" onclick="return confirm('¿Eliminar platillo?')">🗑 Eliminar</a>
                  </td>
                </form>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</body>

</html>