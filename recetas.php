<?php
// recetas.php - Versión PDO para SQLite
require_once 'db.php';

if (!function_exists('h')) {
  function h($s)
  {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

// Guardar receta
if (isset($_POST['guardar_receta'])) {
  $id_platillo = intval($_POST['id_platillo']);
  $cantidades = $_POST['cantidades'] ?? [];

  // Obtener todos los ingredientes que actualmente tiene la receta
  $stmt = $pdo->prepare("SELECT id_ingrediente FROM receta WHERE id_platillo = ?");
  $stmt->execute([$id_platillo]);
  $ingredientes_actuales = $stmt->fetchAll(PDO::FETCH_COLUMN);

  // Procesar cada ingrediente del formulario
  foreach ($cantidades as $id_ing => $cantidad_str) {
    $id_ing = intval($id_ing);
    $cantidad_str = trim($cantidad_str);

    if ($cantidad_str !== '') {
      $cantidad = floatval($cantidad_str);

      if ($cantidad > 0) {
        // Insertar o actualizar usando UPSERT
        $stmt = $pdo->prepare("
                    INSERT INTO receta (id_platillo, id_ingrediente, cantidad_por_base)
                    VALUES (?, ?, ?)
                    ON CONFLICT(id_platillo, id_ingrediente) 
                    DO UPDATE SET cantidad_por_base = excluded.cantidad_por_base
                ");
        $stmt->execute([$id_platillo, $id_ing, $cantidad]);
      } else if ($cantidad == 0) {
        // Eliminar si la cantidad es 0
        $stmt = $pdo->prepare("DELETE FROM receta WHERE id_platillo = ? AND id_ingrediente = ?");
        $stmt->execute([$id_platillo, $id_ing]);
      }
    }
  }

  header("Location: recetas.php?msg=guardado");
  exit;
}

// Eliminar receta completa
if (isset($_GET['delete'])) {
  $plat = intval($_GET['delete']);
  $stmt = $pdo->prepare("DELETE FROM receta WHERE id_platillo = ?");
  $stmt->execute([$plat]);
  header("Location: recetas.php?msg=eliminado");
  exit;
}

// Mostrar mensajes
$mensaje = '';
if (isset($_GET['msg'])) {
  if ($_GET['msg'] === 'guardado') {
    $mensaje = "<p style='color:green; background: #d4edda; padding: 12px; border-radius: 8px; border: 2px solid #c3e6cb; margin-bottom: 20px;'>✅ Receta guardada correctamente.</p>";
  } elseif ($_GET['msg'] === 'eliminado') {
    $mensaje = "<p style='color:green; background: #d4edda; padding: 12px; border-radius: 8px; border: 2px solid #c3e6cb; margin-bottom: 20px;'>✅ Receta eliminada correctamente.</p>";
  }
}

// Cargar datos de receta para editar
$editar_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$receta_actual = [];
$platillo_editando = null;

if ($editar_id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM platillo WHERE id_platillo = ?");
  $stmt->execute([$editar_id]);
  $platillo_editando = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($platillo_editando) {
    $stmt = $pdo->prepare("SELECT id_ingrediente, cantidad_por_base FROM receta WHERE id_platillo = ? ORDER BY id_ingrediente ASC");
    $stmt->execute([$editar_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $receta_actual[$row['id_ingrediente']] = $row['cantidad_por_base'];
    }
  }
}

// Listados
$platillos = $pdo->query("SELECT * FROM platillo ORDER BY id_platillo ASC")->fetchAll();
$ingredientes = $pdo->query("SELECT * FROM ingrediente ORDER BY id_ingrediente ASC")->fetchAll();

/* ===== 🔍 BÚSQUEDA DE RECETAS ===== */
$search = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sqlRec = "
    SELECT p.id_platillo, p.nombre AS platillo, p.porciones_base,
           GROUP_CONCAT(i.nombre || ' (' || r.cantidad_por_base || ' ' || i.unidad || ')') AS ingredientes
    FROM receta r
    JOIN platillo p ON r.id_platillo = p.id_platillo
    JOIN ingrediente i ON r.id_ingrediente = i.id_ingrediente
";

if ($search !== '') {
  $sqlRec .= " WHERE p.nombre LIKE :search OR i.nombre LIKE :search ";
}
$sqlRec .= " GROUP BY p.id_platillo ORDER BY p.id_platillo ASC";

$stmt = $pdo->prepare($sqlRec);
if ($search !== '') {
  $stmt->execute([':search' => "%$search%"]);
} else {
  $stmt->execute();
}
$recetas = $stmt->fetchAll();

/* ====== 🔢 DATOS PARA LA CALCULADORA ====== */
$calcData = [];
$resCalc = $pdo->query("
    SELECT r.id_platillo, i.nombre AS ingrediente, i.unidad, r.cantidad_por_base
    FROM receta r
    JOIN ingrediente i ON i.id_ingrediente = r.id_ingrediente
    ORDER BY r.id_platillo, i.nombre
");
while ($row = $resCalc->fetch(PDO::FETCH_ASSOC)) {
  $pid = (int)$row['id_platillo'];
  if (!isset($calcData[$pid])) {
    $calcData[$pid] = [];
  }
  $calcData[$pid][] = [
    'ingrediente' => $row['ingrediente'],
    'unidad' => $row['unidad'],
    'cantidad_por_base' => (float)$row['cantidad_por_base']
  ];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gestión de Recetas</title>
  <style>
    /* Estilos modernos para recetas */
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

    .card h2,
    .card h3 {
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
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fff;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

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
    }

    .table-ingredientes-search {
      display: flex;
      gap: 8px;
      align-items: center;
      margin-bottom: 8px;
    }

    .table-ingredientes-search input[type="text"] {
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #ddd;
      width: 320px;
    }

    .table-ingredientes-search .btn-small {
      padding: 8px 12px;
      border-radius: 8px;
      border: none;
      background: #e2e8f0;
      cursor: pointer;
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

    table input[type="number"] {
      width: 100px;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 6px;
      text-align: center;
    }

    .actions a {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 20px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      margin: 2px;
    }

    .actions a.danger {
      background: linear-gradient(135deg, #dc3545, #c82333);
      color: white;
    }

    .actions a.btn {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      border: none;
    }

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
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="page-header">
      <h1>📖 Gestión de Recetas</h1>
    </div>
    <div class="page-content">
      <?= $mensaje ?>
      <a class="btn secondary" href="index.php">← Regresar al inicio</a>

      <div class="card">
        <div class="card" style="border: 2px solid #667eea; background:#eef2ff;">
          <h2>📊 Calculadora de Ingredientes</h2>
          <div class="form-group">
            <label>Platillo a calcular:</label>
            <select id="calc_platillo">
              <option value="">-- Selecciona un platillo --</option>
              <?php foreach ($platillos as $p): ?>
                <option value="<?= $p['id_platillo'] ?>" data-base="<?= $p['porciones_base'] ?>">
                  <?= h($p['nombre']) ?> (Base: <?= (int)$p['porciones_base'] ?> porciones)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Cantidad de porciones a preparar:</label>
            <input type="number" id="calc_porciones" min="1" placeholder="Ej. 50, 120, 300">
            <button type="button" id="btn_calcular" class="btn primary" style="margin-top:10px;">🔢 Calcular Ingredientes</button>
          </div>
          <table id="tabla_calculo" style="display:none; margin-top:20px;">
            <thead>
              <tr>
                <th>Ingrediente</th>
                <th>Unidad</th>
                <th>Base</th>
                <th>Requerido</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <h2><?= $editar_id > 0 ? '✏️ Editar Receta' : '➕ Crear Nueva Receta' ?></h2>

        <?php if ($editar_id > 0 && $platillo_editando): ?>
          <p style="background: #fff3cd; padding: 12px; border-radius: 8px; border: 2px solid #ffc107; margin-bottom: 20px;">
            <strong>Editando:</strong> <?= h($platillo_editando['nombre']) ?> (Base: <?= (int)$platillo_editando['porciones_base'] ?> porciones)
          </p>
        <?php endif; ?>

        <form method="post">
          <div class="form-group">
            <label>Platillo:</label>
            <select name="id_platillo" required <?= $editar_id > 0 ? 'disabled' : '' ?>>
              <?php foreach ($platillos as $p): ?>
                <option value="<?= $p['id_platillo'] ?>" <?= ($editar_id > 0 && $p['id_platillo'] == $editar_id) ? 'selected' : '' ?>>
                  <?= h($p['nombre']) ?> (Base: <?= (int)$p['porciones_base'] ?> porciones)
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($editar_id > 0): ?>
              <input type="hidden" name="id_platillo" value="<?= $editar_id ?>">
            <?php endif; ?>
          </div>

          <h3>🥘 Ingredientes para la base del platillo</h3>

          <div class="table-ingredientes-search">
            <input type="text" id="buscar_ingrediente" placeholder="Buscar ingrediente dentro de esta tabla (ej. 'cebolla', 'azúcar')">
            <button type="button" id="limpiar_busqueda_ing" class="btn-small">Limpiar</button>
            <small style="color:#666;margin-left:8px;">El filtro oculta filas que no coinciden. No afecta lo guardado.</small>
          </div>

          <table id="tabla_ingredientes">
            <thead>
              <tr>
                <th>Ingrediente</th>
                <th>Unidad</th>
                <th>Cantidad base</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ingredientes as $i): ?>
                <?php $valor_actual = $receta_actual[$i['id_ingrediente']] ?? ''; ?>
                <tr>
                  <td class="ing-nombre"><?= h($i['nombre']) ?></td>
                  <td><?= h($i['unidad']) ?></td>
                  <td><input type="number" step="0.001" name="cantidades[<?= $i['id_ingrediente'] ?>]" value="<?= $valor_actual ?>"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <button type="submit" name="guardar_receta" class="btn primary"><?= $editar_id > 0 ? '💾 Guardar cambios' : '💾 Guardar receta' ?></button>
          <?php if ($editar_id > 0): ?>
            <a href="recetas.php" class="btn secondary">❌ Cancelar</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="card" id="todas-las-recetas">
        <h2>📋 Todas las recetas</h2>

        <form method="get" class="search-bar" id="form-buscar-recetas">
          <input type="text" name="buscar" id="input-buscar-recetas" placeholder="Buscar por platillo o ingrediente..." value="<?= h($search) ?>">
          <button type="submit" class="btn primary">Buscar</button>
          <?php if ($search !== ''): ?>
            <a href="recetas.php<?= $editar_id > 0 ? '?edit=' . $editar_id : '' ?>" class="btn secondary">Limpiar</a>
          <?php endif; ?>
          <?php if ($editar_id > 0): ?>
            <input type="hidden" name="edit" value="<?= $editar_id ?>">
          <?php endif; ?>
        </form>

        <table id="tabla-recetas">
          <thead>
            <tr>
              <th>Platillo</th>
              <th>Porciones base</th>
              <th>Ingredientes</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recetas as $r): ?>
              <tr>
                <td><?= h($r['platillo']) ?></td>
                <td><?= (int)$r['porciones_base'] ?></td>
                <td><?= h($r['ingredientes'] ?? 'Sin ingredientes') ?></td>
                <td class="actions">
                  <a href="?edit=<?= $r['id_platillo'] ?>" class="btn" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 6px 12px; font-size: 0.9rem;">✏️ Editar</a>
                  <a href="?delete=<?= $r['id_platillo'] ?>" onclick="return confirm('¿Eliminar receta completa de este platillo?')" class="danger">❌ Eliminar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    (function() {
      const input = document.getElementById('buscar_ingrediente');
      const btnLimpiar = document.getElementById('limpiar_busqueda_ing');
      const tabla = document.getElementById('tabla_ingredientes');
      if (!input || !tabla) return;

      const filas = Array.from(tabla.querySelectorAll('tbody tr'));

      function filtrar() {
        const q = (input.value || '').trim().toLowerCase();
        if (q === '') {
          filas.forEach(tr => tr.style.display = '');
          return;
        }
        filas.forEach(tr => {
          const nombreTd = tr.querySelector('.ing-nombre');
          const txt = nombreTd ? nombreTd.textContent.trim().toLowerCase() : '';
          tr.style.display = txt.indexOf(q) !== -1 ? '' : 'none';
        });
      }

      input.addEventListener('input', filtrar);
      btnLimpiar.addEventListener('click', function() {
        input.value = '';
        filtrar();
        input.focus();
      });
    })();

    (function() {
      const urlParams = new URLSearchParams(window.location.search);
      const buscar = urlParams.get('buscar');
      if (buscar && buscar.trim() !== '') {
        window.addEventListener('load', function() {
          const tablaRecetas = document.getElementById('todas-las-recetas');
          if (tablaRecetas) {
            setTimeout(function() {
              tablaRecetas.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
              tablaRecetas.style.transition = 'box-shadow 0.3s ease';
              tablaRecetas.style.boxShadow = '0 8px 40px rgba(102, 126, 234, 0.3)';
              setTimeout(function() {
                tablaRecetas.style.boxShadow = '';
              }, 1000);
            }, 100);
          }
        });
      }
    })();

    const RECETAS_CALC = <?php echo json_encode($calcData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    document.addEventListener("DOMContentLoaded", () => {
      const sel = document.getElementById("calc_platillo");
      const inp = document.getElementById("calc_porciones");
      const tabla = document.getElementById("tabla_calculo");
      const tbody = tabla.querySelector("tbody");
      const btn = document.getElementById("btn_calcular");

      function recalcular() {
        const id = sel.value;
        const porciones = parseFloat(inp.value || 0);

        if (!id || porciones <= 0) {
          tabla.style.display = "none";
          tbody.innerHTML = "";
          return;
        }

        const optSel = sel.options[sel.selectedIndex];
        let base = parseFloat(optSel.dataset.base || "0");
        if (!base || base <= 0) {
          alert("El platillo no tiene porciones base configuradas correctamente.");
          return;
        }

        const receta = RECETAS_CALC[id] || [];
        tbody.innerHTML = "";
        receta.forEach(r => {
          let baseCant = r.cantidad_por_base ? parseFloat(r.cantidad_por_base) : 0;
          let requeridoNum = baseCant * (porciones / base);
          let unidad = r.unidad;

          if (unidad && unidad.toLowerCase() === 'kg') {
            if (requeridoNum > 0 && requeridoNum < 1) {
              requeridoNum = requeridoNum * 1000;
              unidad = 'g';
            }
          }

          const nombrePlatillo = sel.options[sel.selectedIndex].text.toLowerCase();
          if (nombrePlatillo.includes('pizza') || nombrePlatillo.includes('gelatina')) {
            requeridoNum = Math.ceil(porciones);
            baseCant = 1;
            unidad = 'pieza';
          }

          const requerido = requeridoNum.toFixed(0);
          const tr = document.createElement("tr");
          tr.innerHTML = `<td>${r.ingrediente}</td><td>${unidad}</td><td>${baseCant}</td><td><strong>${requerido}</strong></td>`;
          tbody.appendChild(tr);
        });
        tabla.style.display = receta.length ? "" : "none";
      }

      if (btn) {
        btn.addEventListener("click", recalcular);
      }
    });
  </script>

</body>

</html>