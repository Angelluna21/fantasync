<?php 
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ===== CARGAR EVENTO =====
$stmt = $pdo->prepare("SELECT * FROM evento WHERE id_evento=?");
$stmt->execute([$id]);
$ev = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ev) {
    echo "<div class='card'>Evento no encontrado.</div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

// ===== CATÁLOGO DE SALONES =====
$salones_master = $pdo->query("SELECT * FROM salon ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// ===== CARGAR PRIMER SALÓN ASIGNADO =====
$salonStmt = $pdo->prepare("SELECT * FROM evento_salon WHERE id_evento=? ORDER BY id_evento_salon ASC LIMIT 1");
$salonStmt->execute([$id]);
$salon = $salonStmt->fetch(PDO::FETCH_ASSOC);
$id_evento_salon = $salon ? (int)$salon['id_evento_salon'] : 0;

$msg = '';

// ===== GUARDAR CAMBIOS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update']) || isset($_POST['update_and_back']))) {

    // ---------------------------
    // 1) ACTUALIZAR EVENTO
    // ---------------------------
    $upEv = $pdo->prepare("
        UPDATE evento 
        SET fecha=?, titulo=?, misa=?, recepcion=?, inicio=?, 
            descorche=?, cafe=?, degustaciones=?, notas=?
        WHERE id_evento=?
    ");

    $upEv->execute([
        $_POST['fecha'],
        $_POST['titulo'],
        $_POST['misa'] ?: null,
        $_POST['recepcion'] ?: null,
        $_POST['inicio'] ?: null,
        (isset($_POST['descorche']) ? 1 : 0),
        (isset($_POST['cafe']) ? 1 : 0),
        trim($_POST['degustaciones']),
        trim($_POST['notas']),
        $id
    ]);

    // ---------------------------
    // 2) ACTUALIZAR SALÓN
    // ---------------------------
    if (!empty($_POST['id_evento_salon'])) {

        $id_es = (int)$_POST['id_evento_salon'];
        $id_salon_form = (int)$_POST['id_salon'];
        $adultos = (int)$_POST['adultos'];
        $ninos = (int)$_POST['ninos'];
        $factor = ($_POST['factor_nino'] !== '' ? (float)$_POST['factor_nino'] : 0.70);

        $upSalon = $pdo->prepare("
            UPDATE evento_salon
            SET id_salon=?, adultos=?, ninos=?, factor_nino=?, 
                misa=?, recepcion=?, inicio=?,
                descorche=?, cafe=?, degustaciones=?
            WHERE id_evento_salon=? AND id_evento=?
        ");

        $upSalon->execute([
            $id_salon_form,
            $adultos,
            $ninos,
            $factor,
            $_POST['misa'] ?: null,
            $_POST['recepcion'] ?: null,
            $_POST['inicio'] ?: null,
            (isset($_POST['descorche']) ? 1 : 0),
            (isset($_POST['cafe']) ? 1 : 0),
            trim($_POST['degustaciones'] ?? ''),
            $id_es,
            $id
        ]);
    }

    // Si pidió regresar
    if (isset($_POST['update_and_back'])) {
        header('Location: evento_list.php');
        exit;
    }

    $msg = 'Evento actualizado.';

    // Recargar datos
    $stmt->execute([$id]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);
    $salonStmt->execute([$id]);
    $salon = $salonStmt->fetch(PDO::FETCH_ASSOC);
    $id_evento_salon = $salon ? (int)$salon['id_evento_salon'] : 0;
}
?>

<div class="page-header">
  <h1>📅 Editar evento #<?php echo (int)$id; ?></h1>
  <p class="page-subtitle">Modificar datos del evento y de su salón principal</p>
  <div class="header-actions">
    <a class="btn secondary" href="evento_list.php">← Volver a eventos</a>
    <a class="btn primary" href="reporte_evento.php?id=<?php echo (int)$id; ?>">📊 Ver reporte</a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="card" style="padding:14px 18px;"><?php echo h($msg); ?></div>
<?php endif; ?>

<div class="card create-card">
  <div class="card-header">
    <div class="card-icon">✨</div>
    <h2>Editar evento y salón</h2>
  </div>

  <form method="post">
    <!-- DATOS DEL EVENTO -->
    <div class="input-row">
      <label>Fecha <input name="fecha" type="date" required value="<?php echo h($ev['fecha']); ?>"></label>
      <label>Título <input name="titulo" type="text" value="<?php echo h($ev['titulo']); ?>"></label>
      <label>Misa <input name="misa" type="time" value="<?php echo h($ev['misa']); ?>"></label>
      <label>Recepción <input name="recepcion" type="time" value="<?php echo h($ev['recepcion']); ?>"></label>
      <label>Inicio <input name="inicio" type="time" value="<?php echo h($ev['inicio']); ?>"></label>
      <label><span>Descorche</span>
        <input name="descorche" type="checkbox" <?php echo $ev['descorche'] ? 'checked' : ''; ?>>
      </label>
      <label><span>Café</span>
        <input name="cafe" type="checkbox" <?php echo $ev['cafe'] ? 'checked' : ''; ?>>
      </label>
      <label style="grid-column:1/-1;">Degustaciones
        <input name="degustaciones" type="text" value="<?php echo h($ev['degustaciones']); ?>">
      </label>
      <label style="grid-column:1/-1;">Notas
        <textarea name="notas" rows="2"><?php echo h($ev['notas']); ?></textarea>
      </label>
    </div>

    <!-- DATOS DEL SALÓN -->
    <?php if ($salon): ?>
      <input type="hidden" name="id_evento_salon" value="<?php echo (int)$id_evento_salon; ?>">

      <div class="input-row">
        <label>Salón
          <select name="id_salon" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($salones_master as $s): ?>
              <option value="<?php echo (int)$s['id_salon']; ?>"
                <?php echo ((int)$s['id_salon'] === (int)$salon['id_salon']) ? 'selected' : ''; ?>>
                <?php echo h($s['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Adultos
          <input type="number" name="adultos" value="<?php echo (int)$salon['adultos']; ?>" min="0">
        </label>
        <label>Niños
          <input type="number" name="ninos" value="<?php echo (int)$salon['ninos']; ?>" min="0">
        </label>
        <label>Factor niño
          <input type="number" step="0.05" name="factor_nino" value="<?php echo h($salon['factor_nino']); ?>">
        </label>
      </div>

    <?php else: ?>
      <div class="input-row">
        <label style="grid-column:1/-1;">
          <em>Este evento aún no tiene salones asignados. Puedes agregarlos desde evento_list.php.</em>
        </label>
      </div>
    <?php endif; ?>

    <div style="grid-column:1/-1;text-align:center;padding:0 25px 20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
      <button class="btn primary" name="update">Guardar</button>
      <button class="btn secondary" name="update_and_back" value="1">Guardar y regresar</button>
      <a class="btn" href="evento_list.php">Regresar sin guardar</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
