<?php 
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$id_es = isset($_GET['id_es']) ? (int)$_GET['id_es'] : 0;

// ====== INFO DEL EVENTO Y SALÓN ======
$info = $pdo->prepare("
  SELECT es.*, COALESCE(s.alias,s.nombre) AS salon, e.fecha, e.titulo
  FROM evento_salon es
  JOIN salon s ON s.id_salon=es.id_salon
  JOIN evento e ON e.id_evento=es.id_evento
  WHERE es.id_evento_salon=?
");
$info->execute([$id_es]);
$info = $info->fetch(PDO::FETCH_ASSOC);
if(!$info){
  echo "<div class='card'>No existe el salón del evento.</div>";
  require_once __DIR__ . '/footer.php';
  exit;
}

// ====== Calcular las porciones (solo adultos) ======
$adultos = (int)$info['adultos'];
$porciones_total = max($adultos, 1);
$ninos = (int)$info['ninos']; // solo para mostrar

// ====== INSERTAR PLATILLO ======
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])){

    $id_platillo = (int)$_POST['id_platillo'];

    // Obtener categoría del platillo seleccionado
    $stmtCat = $pdo->prepare("
        SELECT cp.nombre 
        FROM platillo p
        JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria
        WHERE p.id_platillo = ?
    ");
    $stmtCat->execute([$id_platillo]);
    $categoria = $stmtCat->fetchColumn();

    // 🟣 CAMBIO: categorías infantiles (ambas)
    $esInfantil = in_array($categoria, ['MENÚ INFANTIL', 'BUFFET INFANTIL']);

    if ($esInfantil) {

        // 🟣 CAMBIO: obtener todos los platillos de la categoría seleccionada
        $stmtMenu = $pdo->prepare("
            SELECT p.id_platillo
            FROM platillo p
            JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria
            WHERE cp.nombre = ?
        ");
        $stmtMenu->execute([$categoria]);
        $platillosInf = $stmtMenu->fetchAll(PDO::FETCH_COLUMN);

        if ($platillosInf) {
            $insertados = 0;

            foreach ($platillosInf as $plat) {

                // Verificar si ya existe en el evento
                $check = $pdo->prepare("
                    SELECT COUNT(*) FROM evento_salon_platillo
                    WHERE id_evento_salon = ? AND id_platillo = ?
                ");
                $check->execute([$id_es, $plat]);
                if ($check->fetchColumn() > 0) continue;

                // Porciones para infantiles → se usan los niños
                $porciones_infantil = max($ninos, 1);

                $stmtInsert = $pdo->prepare("
                    INSERT INTO evento_salon_platillo 
                    (id_evento_salon,id_platillo,porciones_plan,orden,notas)
                    VALUES (?,?,?,?,?)
                ");
                $stmtInsert->execute([
                    $id_es,
                    $plat,
                    $porciones_infantil,
                    $_POST['orden'] !== '' ? (int)$_POST['orden'] : null,
                    trim($_POST['notas'] ?? '') ?: null
                ]);

                $insertados++;
            }

            echo "<div class='card'>✅ Se agregaron $insertados platillos de “$categoria”.</div>";

        } else {
            echo "<div class='card'>⚠️ No hay platillos registrados en “$categoria”.</div>";
        }

    } else {

        // 🎯 Platillo normal
        $porciones_usar = $porciones_total;

        $stmt = $pdo->prepare("
          INSERT INTO evento_salon_platillo 
          (id_evento_salon,id_platillo,porciones_plan,orden,notas)
          VALUES (?,?,?,?,?)
        ");
        $stmt->execute([
          $id_es,
          $id_platillo,
          $porciones_usar,
          $_POST['orden'] !== '' ? (int)$_POST['orden'] : null,
          trim($_POST['notas'] ?? '') ?: null
        ]);

        echo "<div class='card'>✅ Platillo agregado con $porciones_usar porciones (adultos).</div>";
    }
}


// ====== ELIMINAR PLATILLO ======
if(isset($_GET['del'])){
    $stmt = $pdo->prepare("DELETE FROM evento_salon_platillo WHERE id_evento_salon_platillo=? AND id_evento_salon=?");
    $stmt->execute([(int)$_GET['del'],$id_es]);
    echo '<div class="card">❌ Platillo eliminado.</div>';
}

// ====== CARGAR PLATILLOS DISPONIBLES (solo los que tienen recetas) ======
$platsStmt = $pdo->query("
  SELECT DISTINCT p.id_platillo, p.nombre, cp.nombre AS categoria, cp.orden
  FROM platillo p
  INNER JOIN receta r ON r.id_platillo = p.id_platillo
  LEFT JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria
  WHERE cp.nombre IS NOT NULL
  ORDER BY cp.orden, p.nombre
");
$opts = [];
foreach($platsStmt->fetchAll(PDO::FETCH_ASSOC) as $p){ 
    $opts[$p['categoria']][] = $p;
}

// ====== LISTADO ACTUAL ======
$rows = $pdo->prepare("
  SELECT esp.*, p.nombre AS platillo
  FROM evento_salon_platillo esp
  JOIN platillo p ON p.id_platillo=esp.id_platillo
  WHERE esp.id_evento_salon=?
  ORDER BY COALESCE(esp.orden, 99999), p.nombre
");
$rows->execute([$id_es]);
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1>🍽️ Menú por Salón</h1>
  <p class="page-subtitle">Gestiona los platillos y porciones para este evento</p>
  <div class="header-actions">
    <a class="btn secondary" href="evento_list.php?id=<?php echo (int)$info['id_evento']; ?>">← Volver a eventos</a>
    <a class="btn primary" href="reporte_evento.php?id=<?php echo (int)$info['id_evento']; ?>">📊 Ver reporte</a>
  </div>
</div>

<div class="card info-card">
  <div class="card-header">
    <div class="card-icon">ℹ️</div>
    <h2>Información del Evento</h2>
  </div>
  <div class="info-content">
    <div class="info-item">
      <span class="info-label">Evento:</span>
      <span class="info-value">#<?php echo (int)$info['id_evento']; ?> · <?php echo h($info['fecha']); ?> – <?php echo h($info['titulo']); ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Salón:</span>
      <span class="info-value"><?php echo h($info['salon']); ?></span>
    </div>
    <div class="stats-row">
      <span class="badge adults"><?php echo $adultos; ?> adultos</span>
      <span class="badge children"><?php echo $ninos; ?> niños</span>
      <span class="badge total">Total porciones: <?php echo $porciones_total; ?></span>
    </div>
  </div>
</div>

<div class="card add-dish-card">
  <div class="card-header">
    <div class="card-icon">➕</div>
    <h2>Agregar platillo</h2>
  </div>
  <form method="post" class="input-row" id="form-platillo">
    <label>Platillo
      <select name="id_platillo" id="select_platillo" required>
        <option value="">-- Selecciona --</option>
        <?php foreach($opts as $cat => $list): ?>
          <optgroup label="<?php echo h($cat); ?>">
            <?php foreach($list as $p): ?>
              <option value="<?php echo (int)$p['id_platillo']; ?>" 
                      data-categoria="<?php echo h($p['categoria']); ?>"><?php echo h($p['nombre']); ?></option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Total porciones (auto)
      <input type="number" id="porciones_auto" value="<?php echo $porciones_total; ?>" readonly style="background:#f9f9f9;">
    </label>
    <label>Niños
      <input type="number" id="ninos_valor" value="<?php echo $ninos; ?>" readonly style="background:#f9f9f9;">
    </label>
    <label>Orden (número)
      <input type="number" name="orden" min="1">
    </label>
    <label>Notas
      <input type="text" name="notas">
    </label>
    <button class="btn primary" name="add">Agregar</button>
  </form>
</div>

<div class="card dishes-list-card">
  <div class="card-header">
    <div class="card-icon">📋</div>
    <h2>Listado de platillos</h2>
  </div>
  <table>
    <thead><tr><th>Orden</th><th>Platillo</th><th>Porciones</th><th>Notas</th><th>Quitar</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?php echo h($r['orden']); ?></td>
        <td><?php echo h($r['platillo']); ?></td>
        <td><?php echo (int)$r['porciones_plan']; ?></td>
        <td><?php echo h($r['notas']); ?></td>
        <td><a class="btn danger" href="?id_es=<?php echo $id_es; ?>&del=<?php echo (int)$r['id_evento_salon_platillo']; ?>" onclick="return confirm('¿Eliminar de este salón?');">Eliminar</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="report-button-container">
    <a class="btn primary" href="reporte_evento.php?id=<?php echo (int)$info['id_evento']; ?>">📊 Ver reporte completo</a>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const adultos = <?php echo $adultos; ?>;
  const ninos = <?php echo $ninos; ?>;
  const totalInput = document.getElementById("porciones_auto");
  const selectPlatillo = document.getElementById("select_platillo");
  
  // Función para actualizar el campo de porciones según el platillo seleccionado
  function actualizarPorciones() {
    const selectedOption = selectPlatillo.options[selectPlatillo.selectedIndex];
    if (selectedOption.value === '') {
      // Si no hay selección, mostrar por defecto adultos
      totalInput.value = Math.max(adultos, 1);
    } else {
      const categoria = selectedOption.getAttribute('data-categoria');
      if (categoria === 'MENÚ INFANTIL') {
        // Si es MENÚ INFANTIL, mostrar número de niños
        totalInput.value = Math.max(ninos, 1);
      } else {
        // Para otros platillos, mostrar número de adultos
        totalInput.value = Math.max(adultos, 1);
      }
    }
  }
  
  // Inicializar con valor por defecto (adultos)
  totalInput.value = Math.max(adultos, 1);
  
  // Actualizar cuando cambie la selección
  selectPlatillo.addEventListener('change', actualizarPorciones);
});
</script>

<style>
<?php echo file_get_contents(__FILE__, false, null, __COMPILER_HALT_OFFSET__); ?>
</style>

<?php require_once __DIR__ . '/footer.php'; ?>

__halt_compiler();
/* Estilos para la página de menú */
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 30px;
  border-radius: 16px;
  margin-bottom: 30px;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.page-header h1 {
  font-size: 2.5rem;
  margin: 0 0 10px 0;
  font-weight: 700;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.page-subtitle {
  font-size: 1.2rem;
  margin: 0 0 20px 0;
  opacity: 0.9;
  font-weight: 300;
}

.header-actions {
  margin-top: 15px;
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

/* Tarjetas mejoradas */
.card {
  background: white;
  border-radius: 16px;
  padding: 0;
  margin: 20px 0;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border: 1px solid #f0f0f0;
  overflow: hidden;
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.card-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 20px 25px;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  align-items: center;
  gap: 15px;
}

.card-icon {
  font-size: 1.8rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.card h2 {
  margin: 0;
  color: #333;
  font-size: 1.4rem;
  font-weight: 600;
}

/* Estilos específicos para cada tarjeta */
.info-card .card-header {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
}

.add-dish-card .card-header {
  background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
}

.dishes-list-card .card-header {
  background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
}

/* Contenido de información */
.info-content {
  padding: 25px;
}

.info-item {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
  gap: 10px;
}

.info-label {
  font-weight: 600;
  color: #555;
  min-width: 80px;
}

.info-value {
  color: #333;
  font-weight: 500;
}

.stats-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 20px;
}

/* Badges mejorados */
.badge {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.badge.adults {
  background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  color: #1976d2;
  border-color: #bbdefb;
}

.badge.children {
  background: linear-gradient(135deg, #f3e5f5, #e1bee7);
  color: #7b1fa2;
  border-color: #e1bee7;
}

.badge.total {
  background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
  color: #388e3c;
  border-color: #c8e6c9;
}

.badge:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Formularios mejorados */
.input-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
  padding: 25px;
}

.input-row label {
  display: flex;
  flex-direction: column;
  gap: 5px;
  font-weight: 500;
  color: #555;
}

.input-row input, .input-row select {
  padding: 12px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #fff;
}

.input-row input:focus, .input-row select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-row input[readonly] {
  background: #f8f9fa;
  color: #6c757d;
  cursor: not-allowed;
}

/* Botones mejorados */
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
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

/* Tablas mejoradas */
table {
  width: 100%;
  border-collapse: collapse;
  margin: 0;
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

/* Botón de reporte al final */
.report-button-container {
  margin-top: 20px;
  text-align: center;
  padding: 20px;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-top: 1px solid #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header h1 {
    font-size: 2rem;
  }
  
  .header-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .input-row {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
  
  .info-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }
  
  .stats-row {
    justify-content: center;
  }
  
  table {
    font-size: 0.9rem;
  }
}
