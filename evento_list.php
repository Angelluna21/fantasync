<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/header.php';

if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

// ---------------------- ACCIONES ----------------------
$msgs = [];
$current_event_id = null;
$current_es = isset($_GET['id_es']) ? (int)$_GET['id_es'] : 0;

// ELIMINAR EVENTO COMPLETO
if (isset($_GET['del']) && is_numeric($_GET['del']) && !isset($_GET['id'])) {
    $id_eliminar = (int)$_GET['del'];
    $stmt = $pdo->prepare("DELETE FROM evento WHERE id_evento=?");
    $stmt->execute([$id_eliminar]);
    $msgs[] = "✅ Evento eliminado correctamente.";
}

// ELIMINAR SALÓN (de un evento)
if (isset($_GET['del']) && isset($_GET['id']) && is_numeric($_GET['del']) && is_numeric($_GET['id'])) {
    $id_salon = (int)$_GET['del'];
    $pdo->prepare("DELETE FROM evento_salon WHERE id_evento_salon=?")->execute([$id_salon]);
    if ($current_es === $id_salon) $current_es = 0;
    $msgs[] = "✅ Salón eliminado del evento.";
}

// CREAR EVENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_evento'])) {
    $stmt = $pdo->prepare("
        INSERT INTO evento (fecha,titulo,misa,recepcion,inicio,descorche,cafe,degustaciones,notas)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $_POST['fecha'] ?: date('Y-m-d'),
        trim($_POST['titulo'] ?? ''),
        $_POST['misa'] ?: null,
        $_POST['recepcion'] ?: null,
        $_POST['inicio'] ?: null,
        isset($_POST['descorche']) ? 1 : 0,
        isset($_POST['cafe']) ? 1 : 0,
        trim($_POST['degustaciones'] ?? '') ?: null,
        trim($_POST['notas'] ?? '') ?: null
    ]);
    $current_event_id = (int)$pdo->lastInsertId();
    $msgs[] = "✅ Evento creado.";
}

// AGREGAR SALÓN A EVENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_salon'])) {
    $stmt = $pdo->prepare("
        INSERT INTO evento_salon (id_evento,id_salon,adultos,ninos,misa,recepcion,inicio,descorche,cafe,degustaciones,factor_nino)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        (int)$_POST['id_evento'],
        (int)$_POST['id_salon'],
        (int)$_POST['adultos'],
        (int)$_POST['ninos'],
        $_POST['misa'] ?: null,
        $_POST['recepcion'] ?: null,
        $_POST['inicio'] ?: null,
        isset($_POST['descorche']) ? 1 : 0,
        isset($_POST['cafe']) ? 1 : 0,
        trim($_POST['degustaciones'] ?? '') ?: null,
        $_POST['factor_nino'] !== '' ? (float)$_POST['factor_nino'] : 0.70
    ]);
    $current_es = (int)$pdo->lastInsertId();
    $current_event_id = (int)$_POST['id_evento'];
    $msgs[] = "✅ Salón agregado al evento.";
}

// ELIMINAR PLATILLO DE UN SALÓN  (PRG)
if (isset($_GET['del_plat']) && $current_es) {

    $pdo->prepare("
        DELETE FROM evento_salon_platillo 
        WHERE id_evento_salon_platillo=? AND id_evento_salon=?
    ")->execute([(int)$_GET['del_plat'], $current_es]);

    $_SESSION['flash_msg'] = "❌ Platillo eliminado.";

    // ⬇⬇⬇ Redirige evitando salto al inicio
    header("Location: ".$_SERVER['PHP_SELF']."?id_es=".$current_es."#menu_salon");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_platillo'])) {

    $id_es_post = (int)($_POST['id_evento_salon'] ?? 0);

    if (!$id_es_post) {
        $_SESSION['flash_msg'] = "⚠️ Selecciona un salón válido antes de agregar platillos.";
        header("Location: ".$_SERVER['PHP_SELF']."#menu_salon");
        exit;
    }

    // Normalizar: siempre array
    $platillos_enviados = $_POST['id_platillo'] ?? [];

    if (!is_array($platillos_enviados)) {
        $platillos_enviados = [$platillos_enviados];
    }

    // Filtrar solo numéricos válidos
    $platillos_enviados = array_filter($platillos_enviados, function($v){
        return ctype_digit((string)$v) && (int)$v > 0;
    });

    if (empty($platillos_enviados)) {
        $msgs[] = "⚠️ Selecciona al menos un platillo.";
        $current_es = $id_es_post;
        goto end_add_platillo;
    }

    // obtener adultos/niños y evento
    $es = $pdo->prepare("SELECT id_evento, adultos, ninos FROM evento_salon WHERE id_evento_salon=?");
    $es->execute([$id_es_post]);
    $es = $es->fetch(PDO::FETCH_ASSOC);
    if (!$es) {
        $msgs[] = "⚠️ Salón no encontrado.";
        goto end_add_platillo;
    }

    $adultos = (int)$es['adultos'];
    $ninos   = (int)$es['ninos'];
    $current_event_id = (int)$es['id_evento'];

    /*
     * Aquí preservamos tu lógica infantil EXACTA:
     * Si un platillo seleccionado pertenece a una categoría infantil,
     * agregamos TODOS los platillos de esa categoría.
     *
     * Además añadimos 3 categorías infantiles extras como solicitaste,
     * sin quitar las tuyas anteriores.
     */

    // Categorías "infantiles" definidas en tu BD (con 3 adicionales)
    $categorias_infantiles = [
        'MENU INFANTIL',
        'BUFFET INFANTIL',
        '1 INFANTIL',
        '2 INFANTIL'
    ];

    // Expandir: si algún platillo seleccionado es "infantil", añadir todos los platillos de su categoría
    $expanded = [];

    foreach ($platillos_enviados as $pid) {
        $pid = (int)$pid;
        // obtener categoría principal y nombre
        $stmtCat = $pdo->prepare("
            SELECT p.id_categoria, cp.nombre
            FROM platillo p
            LEFT JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria
            WHERE p.id_platillo = ?
        ");
        $stmtCat->execute([$pid]);
        $rowCat = $stmtCat->fetch(PDO::FETCH_ASSOC);
        $cat_nombre = strtoupper(trim($rowCat['nombre'] ?? ''));
        $id_cat = isset($rowCat['id_categoria']) ? (int)$rowCat['id_categoria'] : 0;

        if ($id_cat && in_array($cat_nombre, $categorias_infantiles, true)) {
            // traer todos los platillos de esa categoria (solo por categoria principal)
            $stmtMenu = $pdo->prepare("SELECT DISTINCT id_platillo FROM platillo WHERE id_categoria = ? ORDER BY nombre");
            $stmtMenu->execute([$id_cat]);
            $rows = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $expanded[] = (int)$r['id_platillo'];
            }
        } else {
            // no infantil → mantener el platillo seleccionado
            $expanded[] = $pid;
        }
    }

    // Unificar y limpiar
    $platillos_final = array_values(array_unique(array_filter($expanded, function($v){
        return ctype_digit((string)$v) && (int)$v > 0;
    })));

    if (empty($platillos_final)) {
        $msgs[] = "⚠️ No hay platillos válidos para agregar.";
        $current_es = $id_es_post;
        goto end_add_platillo;
    }

    // PROCESAR INSERCION DE CADA PLATILLO (manteniendo tu lógica de porciones)
    $insertados = 0;

    foreach ($platillos_final as $id_platillo) {
        $id_platillo = (int)$id_platillo;

        // Obtener categoría del platillo (para determinar si infantil — preservamos la tuya)
        $stmtCat = $pdo->prepare("
            SELECT p.id_categoria, cp.nombre 
            FROM platillo p 
            LEFT JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria 
            WHERE p.id_platillo = ?
        ");
        $stmtCat->execute([$id_platillo]);
        $catRow = $stmtCat->fetch(PDO::FETCH_ASSOC);
        $cat_nombre = strtoupper(trim($catRow['nombre'] ?? ''));

        $es_infantil = in_array($cat_nombre, $categorias_infantiles, true);

        // Determinar porciones según tu lógica (niños para infantiles, adultos para el resto)
        if ($es_infantil) {
            $porciones = max($ninos, 1);
        } else {
            $porciones = max($adultos, 1);
        }

        // Evitar duplicados
        $ch = $pdo->prepare("
            SELECT COUNT(*) 
            FROM evento_salon_platillo 
            WHERE id_evento_salon = ? AND id_platillo = ?
        ");
        $ch->execute([$id_es_post, $id_platillo]);
        if ($ch->fetchColumn() > 0) {
            continue; // ya existe
        }

        // Insertar
        $insert = $pdo->prepare("
            INSERT INTO evento_salon_platillo (id_evento_salon, id_platillo, porciones_plan, orden, notas)
            VALUES (?,?,?,?,?)
        ");
        $insert->execute([
            $id_es_post,
            $id_platillo,
            $porciones,
            $_POST['orden'] !== '' ? (int)$_POST['orden'] : null,
            trim($_POST['notas'] ?? '') ?: null
        ]);

        $insertados++;
    }

      if ($insertados > 0) {
        $_SESSION['flash_msg'] = "✅ Se agregaron {$insertados} platillos.";
    } else {
        $_SESSION['flash_msg'] = "⚠️ Todos los platillos seleccionados ya estaban agregados.";
    }

    // Mantener datos
    $current_es = $id_es_post;
    $current_event_id = (int)$es['id_evento'];

    // 🚀 PRG para evitar salto al inicio
    header("Location: ".$_SERVER['PHP_SELF']."?id_es=".$current_es."#menu_salon");
    exit;
}


end_add_platillo:

/* ====== CREAR EVENTO + SALÓN EN UN SOLO SUBMIT ====== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_evento_salon'])) {
    // 1) Crear evento
    $stmtE = $pdo->prepare("INSERT INTO evento (fecha,titulo,misa,recepcion,inicio,descorche,cafe,degustaciones,notas) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmtE->execute([
        $_POST['fecha'] ?: date('Y-m-d'),
        trim($_POST['titulo'] ?? ''),
        $_POST['misa'] ?: null,
        $_POST['recepcion'] ?: null,
        $_POST['inicio'] ?: null,
        isset($_POST['descorche']) ? 1 : 0,
        isset($_POST['cafe']) ? 1 : 0,
        trim($_POST['degustaciones'] ?? '') ?: null,
        trim($_POST['notas'] ?? '') ?: null
    ]);
    $current_event_id = (int)$pdo->lastInsertId();

    // 2) Ligar el salón al evento recién creado
    $stmtS = $pdo->prepare("INSERT INTO evento_salon (id_evento,id_salon,adultos,ninos,misa,recepcion,inicio,descorche,cafe,degustaciones,factor_nino) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmtS->execute([
        $current_event_id,
        (int)$_POST['id_salon'],
        (int)$_POST['adultos'],
        (int)$_POST['ninos'],
        $_POST['misa'] ?: null,
        $_POST['recepcion'] ?: null,
        $_POST['inicio'] ?: null,
        isset($_POST['descorche']) ? 1 : 0,
        isset($_POST['cafe']) ? 1 : 0,
        null,
        $_POST['factor_nino'] !== '' ? (float)$_POST['factor_nino'] : 0.70
    ]);
    $current_es = (int)$pdo->lastInsertId();
    $msgs[] = "✅ Evento y salón creados en un solo paso.";
}

// ---------------------- DATOS BÁSICOS ----------------------
$salones_master = $pdo->query("SELECT * FROM salon ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Eventos: traer banderas normalizadas (SI/NO) desde evento
$eventos = $pdo->query("SELECT id_evento, fecha, titulo, CASE WHEN COALESCE(descorche,0)=1 THEN 'SI' ELSE 'NO' END AS descorche, CASE WHEN COALESCE(cafe,0)=1 THEN 'SI' ELSE 'NO' END AS cafe FROM evento ORDER BY fecha DESC")->fetchAll(PDO::FETCH_ASSOC);

$es_list = $pdo->query("SELECT es.id_evento_salon, es.id_evento, COALESCE(s.alias,s.nombre) AS salon, e.fecha, e.titulo FROM evento_salon es JOIN salon s ON s.id_salon=es.id_salon JOIN evento e ON e.id_evento=es.id_evento ORDER BY es.id_evento_salon DESC")->fetchAll(PDO::FETCH_ASSOC);

// Obtener platillos: desde categoría principal (sin lógica de platillo_categoria)
$platsStmt = $pdo->query("
    SELECT DISTINCT p.id_platillo, p.nombre, cp.nombre AS categoria, cp.orden 
    FROM platillo p 
    LEFT JOIN receta r ON r.id_platillo = p.id_platillo 
    LEFT JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria 
    ORDER BY cp.orden, p.nombre
");


$opts = [];
$categorias_orden = []; // Guardar el orden de cada categoría
foreach($platsStmt->fetchAll(PDO::FETCH_ASSOC) as $p){
    $cat_norm = $p['categoria'];
    if (!isset($categorias_orden[$cat_norm])) {
        $categorias_orden[$cat_norm] = (int)$p['orden'];
    }
    $opts[$cat_norm][] = $p;
}

$opts_js = [];

foreach($opts as $cat => $list){
    // Normalizar categoría
    $key = strtoupper(trim($cat));

    foreach($list as $p){
        $opts_js[$key][] = [
            'id'     => (int)$p['id_platillo'],
            'nombre' => $p['nombre']
        ];
    }
}


// Ordenar categorías por orden, luego por nombre
uksort($opts, function($a, $b) use ($categorias_orden) {
    $orden_a = isset($categorias_orden[$a]) ? $categorias_orden[$a] : 999;
    $orden_b = isset($categorias_orden[$b]) ? $categorias_orden[$b] : 999;
    if ($orden_a == $orden_b) {
        return strcmp($a, $b);
    }
    return $orden_a - $orden_b;
});
$categorias = array_keys($opts);

// Si tenemos un salón seleccionado: info y listado de platillos
$info_es = null;
$adultos_sel=0; $ninos_sel=0; $porciones_sel=0; $rows_platillos = [];
if ($current_es) {
    $q = $pdo->prepare("SELECT es.*, COALESCE(s.alias,s.nombre) AS salon, e.id_evento, e.fecha, e.titulo FROM evento_salon es JOIN salon s ON s.id_salon=es.id_salon JOIN evento e ON e.id_evento=es.id_evento WHERE es.id_evento_salon=?");
    $q->execute([$current_es]);
    $info_es = $q->fetch(PDO::FETCH_ASSOC);
    if ($info_es) {
        $adultos_sel = (int)$info_es['adultos'];
        $ninos_sel   = (int)$info_es['ninos'];
        $porciones_sel = max($adultos_sel,1);
        $current_event_id = (int)$info_es['id_evento'];
$r = $pdo->prepare("
    SELECT 
        esp.*, 
        p.nombre AS platillo,
        cp.nombre AS categoria,
        cp.orden AS orden_categoria
    FROM evento_salon_platillo esp
    JOIN platillo p ON p.id_platillo = esp.id_platillo
    LEFT JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria
    WHERE esp.id_evento_salon = ?
    ORDER BY 
        cp.orden ASC,
        COALESCE(esp.orden, p.id_platillo) ASC
");

        $r->execute([$current_es]);
        $rows_platillos = $r->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $current_es = 0;
    }
}
?>

<?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-info">
        <?= $_SESSION['flash_msg']; ?>
    </div>
    <?php unset($_SESSION['flash_msg']); ?>
<?php endif; ?>

<div class="page-header">
  <h1>📅 Eventos</h1>
  <p class="page-subtitle">Crear evento → Agregar salón → Agregar platillos, sin salir de esta página</p>
  <div class="header-actions">
    <a class="btn secondary" href="index.php">← Volver al inicio</a>
    <?php if ($current_event_id): ?>
      <a class="btn primary" href="reporte_evento.php?id=<?= (int)$current_event_id; ?>">📊 Ver reporte</a>
    <?php endif; ?>
    <!-- Nuevo botón para Pedido de Compra, siempre visible -->
    <a class="btn primary" href="pedido_compras.php">🛒 Ver Pedido de Compra</a>
  </div>
</div>

<?php foreach($msgs as $m): ?>
  <div class="card" style="padding:14px 18px;"><?= h($m) ?></div>
<?php endforeach; ?>

<!-- 1) CREAR EVENTO + AGREGAR SALÓN (un solo formulario) -->
<div class="card create-card" id="crear_evento_salon">
  <div class="card-header"><div class="card-icon">✨</div><h2>Crear evento y agregar salón</h2></div>
  <form method="post">
    <div class="input-row">
      <label>Fecha<input name="fecha" type="date" required></label>
      <label>Título<input name="titulo" type="text"></label>
      <label>Misa<input name="misa" type="time"></label>
      <label>Recepción<input name="recepcion" type="time"></label>
      <label>Inicio<input name="inicio" type="time"></label>
      <label><span>Descorche</span><input name="descorche" type="checkbox" id="descorche_salon"></label>
      <label><span>Café</span><input name="cafe" type="checkbox"></label>
      <label style="grid-column:1/-1;">Degustaciones<input name="degustaciones" type="text"></label>
      <label style="grid-column:1/-1;">Notas<textarea name="notas" rows="2"></textarea></label>
    </div>

    <div class="input-row">
      <label>Salón
        <select name="id_salon" required>
          <option value="">-- Selecciona --</option>
          <?php foreach ($salones_master as $s): ?>
            <option value="<?= (int)$s['id_salon'] ?>"><?= h($s['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Adultos<input type="number" name="adultos" value="0" min="0" id="adultos_salon"></label>
      <label>Niños<input type="number" name="ninos" value="0" min="0" id="ninos_salon"></label>
      <label>Factor niño<input type="number" step="0.05" name="factor_nino" value="0.70"></label>
    </div>

    <div class="input-row" id="limon_info_salon" style="display:none;">
      <label style="grid-column:1/-1;background:#fff3cd;padding:12px;border-radius:8px;border:2px solid #ffc107;">
        <strong>🍋 Limón necesario para descorche:</strong>
        <span id="limon_cantidad_salon">0</span> kg
        <small style="display:block;margin-top:5px;color:#666;">(Cálculo proporcional: 2.5 kg por cada 100 adultos)</small>
      </label>
    </div>

    <div style="grid-column:1/-1;text-align:center;padding:0 25px 20px;">
      <button class="btn primary" name="create_evento_salon">Crear evento y salón</button>
    </div>
  </form>
</div>

<!-- 3) MENÚ POR SALÓN (AGREGAR PLATILLOS) -->
<div class="card dishes-block" id="menu_salon">
  <div class="card-header"><div class="card-icon">🍽️</div><h2>Menú del salón</h2></div>
  <form method="get" style="padding:20px 25px;">
    <label>Salón asignado
      <select name="id_es" onchange="this.form.submit()">
        <option value="">-- Selecciona un salón del evento --</option>
        <?php foreach($es_list as $es): ?>
          <option value="<?= (int)$es['id_evento_salon']; ?>" <?= $current_es==(int)$es['id_evento_salon']?'selected':''; ?>>
            #<?= (int)$es['id_evento']; ?> · <?= h($es['fecha']); ?> – <?= h($es['titulo']); ?> · <?= h($es['salon']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

  <?php if ($info_es): ?>
  <div class="info-content" style="padding:0 25px 20px;">
    <div class="stats-row" style="display:flex;gap:10px;flex-wrap:wrap;">
      <span class="badge adults"><?= $adultos_sel; ?> adultos</span>
      <span class="badge children"><?= $ninos_sel; ?> niños</span>
      <span class="badge total">Total porciones (auto): <?= $porciones_sel; ?></span>
      <a class="btn secondary" href="reporte_evento.php?id=<?= (int)$info_es['id_evento']; ?>">📊 Reporte del evento</a>
    </div>
  </div>

  <form method="post" class="input-row" style="padding:0 25px 25px;">
    <input type="hidden" name="id_evento_salon" value="<?= (int)$current_es; ?>">

    <label>Categoría
      <select id="select_categoria">
        <option value="">-- Selecciona --</option>
        <?php foreach($categorias as $cat): ?>
          <option value="<?= h($cat); ?>"><?= h($cat); ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Platillo
  <select name="id_platillo[]" id="select_platillo" multiple>
    <option value="">-- Selecciona una categoría primero --</option>
  </select>

  <!-- 👇 Mensaje que solo se mostrará para GUISADOS -->
  <small id="ayuda-multiple" style="display:none; color:#666; font-size:12px;">
    Para seleccionar varios platillos, mantén presionada la tecla <strong>CTRL</strong>.
  </small>
</label>


    <label>Total porciones (auto)
      <input type="number" id="porciones_auto" value="<?= $porciones_sel; ?>" readonly style="background:#f9f9f9;">
    </label>
    <label>Niños
      <input type="number" id="ninos_valor" value="<?= $ninos_sel; ?>" readonly style="background:#f9f9f9;">
    </label>
    <label>Orden (número)<input type="number" name="orden" min="1"></label>
    <label>Notas<input type="text" name="notas"></label>
    <button class="btn primary" name="add_platillo">Agregar platillo</button>
  </form>

  <div class="card dishes-list-card" style="box-shadow:none;border:none;margin:0;">
    <div class="card-header"><div class="card-icon">📋</div><h2>Platillos del salón</h2></div>
    <table>
      <thead><tr><th>Orden</th><th>Platillo</th><th>Porciones</th><th>Notas</th><th>Quitar</th></tr></thead>
      <tbody>
        <?php foreach($rows_platillos as $r): ?>
          <tr>
            <td><?= h($r['orden']); ?></td>
            <td><?= h($r['platillo']); ?></td>
            <td><?= (int)$r['porciones_plan']; ?></td>
            <td><?= h($r['notas']); ?></td>
            <td><a class="btn danger" href="?id_es=<?= $current_es; ?>&del_plat=<?= (int)$r['id_evento_salon_platillo']; ?>" onclick="return confirm('¿Eliminar de este salón?');">Eliminar</a></td>
          </tr>
          
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div style="padding:18px 25px;">Selecciona un salón asignado para gestionar su menú.</div>
  <?php endif; ?>
</div>

<!-- LISTADO: EVENTOS -->
<div class="card events-list-card">
  <div class="card-header"><div class="card-icon">📋</div><h2>Listado de Eventos</h2></div>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Título</th>
        <th>Descorche</th>
        <th>Café</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>

      <?php $contador = 1; ?>
<?php foreach ($eventos as $r): ?>
<tr>
    <td><?= $contador ?></td>
    <td><?= h($r['fecha']) ?></td>
    <td><?= h($r['titulo']) ?></td>
    <td><?= h($r['descorche']) ?></td>
    <td><?= h($r['cafe']) ?></td>
    <td class="actions">
        <a href="evento_edit.php?id=<?= (int)$r['id_evento'] ?>">Editar</a>
        <a href="reporte_evento.php?id=<?= (int)$r['id_evento'] ?>">Reporte</a>
        <a class="btn danger" href="?del=<?= (int)$r['id_evento'] ?>" onclick="return confirm('¿Eliminar evento completo?');">Eliminar</a>
    </td>
</tr>
<?php $contador++; ?>
<?php endforeach; ?>

    </tbody>
  </table>
</div>

<?php
$rows_asignados = $pdo->query("
  SELECT 
      es.id_evento_salon,
      es.id_evento,
      s.nombre AS salon,
      e.titulo,
      es.adultos,
      es.ninos,
      es.misa,
      es.recepcion,
      es.inicio,
      CASE WHEN COALESCE(e.descorche, 0) = 1 THEN 'SI' ELSE 'NO' END AS descorche_effect,
      CASE WHEN COALESCE(e.cafe, 0) = 1 THEN 'SI' ELSE 'NO' END AS cafe_effect
  FROM evento_salon es
  JOIN salon s ON s.id_salon = es.id_salon
  JOIN evento e ON e.id_evento = es.id_evento
  ORDER BY es.id_evento_salon DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card assigned-card">
  <div class="card-header"><div class="card-icon">🎯</div><h2>Salones Asignados</h2></div>
  <table>
    <thead>
      <tr>
        <th>Salón</th>
        <th>Título</th>
        <th>Adultos</th>
        <th>Niños</th>
        <th>Horarios</th>
        <th>Quitar</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows_asignados as $r): ?>
      <tr>
        <td><?= h($r['salon']); ?></td>
        <td><?= h($r['titulo']); ?></td>
        <td><?= (int)$r['adultos']; ?></td>
        <td><?= (int)$r['ninos']; ?></td>
        <td>
          <?= $r['misa'] ? 'Misa '.$r['misa'].' · ' : ''; ?>
          Recepción <?= h($r['recepcion']); ?> · Inicio <?= h($r['inicio']); ?>
          <?= " · Descorche ".h($r['descorche_effect']); ?>
          <?= $r['cafe_effect'] === 'SI' ? " · Café SI" : ""; ?>
          <?php if ($r['descorche_effect'] === 'SI'): 
            $total = (int)$r['adultos'];
            $limon = number_format(($total * 2.5) / 100, 2, '.', ''); ?>
            <br><small style="color:#856404;background:#fff3cd;padding:4px 8px;border-radius:4px;display:inline-block;margin-top:4px;">
              🍋 Limón: <?= $limon; ?> kg
            </small>
          <?php endif; ?>
        </td>
        <td>
          <a class="btn danger" href="?id=<?= (int)$r['id_evento']; ?>&del=<?= (int)$r['id_evento_salon']; ?>" onclick="return confirm('¿Quitar este salón del evento?');">Eliminar</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
// Datos de PHP a JS
const opts_js = <?= json_encode($opts_js, JSON_UNESCAPED_UNICODE); ?>;

// Subcategorías infantiles
const subInfantil = {
  "BUFFET INFANTIL": 1,
  "1 INFANTIL": 2,
  "2 INFANTIL": 3,
  "3 INFANTIL": 4
};

// Referencias a elementos
const selectCategoria = document.getElementById('select_categoria');
const selectPlatillo = document.getElementById('select_platillo');
const porcionesAuto = document.getElementById('porciones_auto');
const ninosValor = document.getElementById('ninos_valor');

// 🔥 Creamos un SELECT DE SUBCATEGORÍA (INFANTIL)
let subSelect = document.createElement("select");
subSelect.id = "sub_infantil";
subSelect.style.display = "none";
subSelect.innerHTML = `<option value="">-- Selecciona subcategoría --</option>`;
for (let s in subInfantil) {
  subSelect.innerHTML += `<option value="${s}">${s}</option>`;
}
selectCategoria.parentNode.appendChild(subSelect);

// Cuando se selecciona subcategoría
subSelect.addEventListener("change", function () {
  const subcat = subSelect.value;

  selectPlatillo.innerHTML = '<option value="">-- Selecciona un platillo --</option>';
  selectPlatillo.disabled = true;

  if (!subcat || !opts_js[subcat]) return;

  opts_js[subcat].forEach(p => {
    const opt = document.createElement("option");
    opt.value = p.id;
    opt.textContent = p.nombre;
    selectPlatillo.appendChild(opt);
  });

  selectPlatillo.disabled = false;
});

// Función para actualizar platillos según categoría
function actualizarPlatillos() {
  const cat = selectCategoria.value.toUpperCase();

  // Reiniciar selects
  selectPlatillo.innerHTML = '<option value="">-- Selecciona un platillo --</option>';
  selectPlatillo.disabled = true;

  // 🔥 SI ES INFANTIL → MOSTRAR SUBCATEGORÍAS
  if (cat === "MENU INFANTIL" || cat === "MENÚ INFANTIL") {
    subSelect.style.display = "block";
    subSelect.value = "";
    selectPlatillo.removeAttribute("multiple");
    selectPlatillo.size = 1;
    return;
  }

  // Si NO es infantil → ocultar subcategorías
  subSelect.style.display = "none";
  subSelect.value = "";

 // Múltiple para guisados, guarniciones, bebidas y salsas
const ayudaMultiple = document.getElementById('ayuda-multiple');

// Categorías que deben permitir selección múltiple
const categoriasMultiple = ["GUISADOS", "GUARNICIONES", "BEBIDAS", "SALSAS"];

if (categoriasMultiple.includes(cat)) {
  selectPlatillo.setAttribute("multiple", "multiple");
  selectPlatillo.size = 6;
  ayudaMultiple.style.display = "block";
} else {
  selectPlatillo.removeAttribute("multiple");
  selectPlatillo.size = 1;
  ayudaMultiple.style.display = "none";
}

  // Cargar platillos de categoría normal
  if (!opts_js[cat]) return;

  opts_js[cat].forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = p.nombre;
    selectPlatillo.appendChild(opt);
  });

  selectPlatillo.disabled = false;
}

// Actualizar porciones automáticas según salón seleccionado
function actualizarPorciones() {
  porcionesAuto.value = <?= $porciones_sel ?>;
  ninosValor.value = <?= $ninos_sel ?>;
}

function calcularLimon(adultos) {
  const total = parseInt(adultos,10)||0;
  return ((total*2.5)/100).toFixed(2);
}

function actualizarLimonSalon() {
  const descorche = document.getElementById('descorche_salon');
  const adultos = parseInt(document.getElementById('adultos_salon')?.value||'0',10);
  const box = document.getElementById('limon_info_salon');
  const span = document.getElementById('limon_cantidad_salon');
  if (!descorche || !box || !span) return;
  if (descorche.checked && adultos>0) { 
    span.textContent = calcularLimon(adultos); 
    box.style.display='block'; 
  } else { 
    box.style.display='none'; 
  }
}

// Event listeners
selectCategoria.addEventListener('change', actualizarPlatillos);
['descorche_salon','adultos_salon','ninos_salon'].forEach(id=>{
  const el = document.getElementById(id);
  if(el) el.addEventListener('input', actualizarLimonSalon);
});

// Inicializar
actualizarLimonSalon();
actualizarPorciones();
</script>



<style>
/* Estilos CSS tal como en tu código original */
.page-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:30px;border-radius:16px;margin-bottom:30px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.1)}
.page-header h1{font-size:2.4rem;margin:0 0 10px;font-weight:700;text-shadow:2px 2px 4px rgba(0,0,0,.3)}
.page-subtitle{font-size:1.1rem;margin:0 0 15px;opacity:.9}
.header-actions{margin-top:10px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.card{background:#fff;border-radius:16px;padding:0;margin:20px 0;box-shadow:0 4px 20px rgba(0,0,0,.08);border:1px solid #f0f0f0;overflow:hidden;transition:all .3s ease}
.card:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,0,0,.12)}
.card-header{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);padding:16px 22px;border-bottom:1px solid #e9ecef;display:flex;align-items:center;gap:12px}
.card-icon{font-size:1.6rem;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.card h2{margin:0;color:#333;font-size:1.2rem;font-weight:600}
.input-row {
    display: grid;
    grid-template-columns: 1.5fr 0.6fr 0.6fr; /* ← SOLUCIÓN */
    gap: 24px;
    margin-bottom: 16px;
    padding: 18px 22px;
}

.input-row label{display:flex;flex-direction:column;gap:6px;font-weight:500;color:#555}
.input-row input,.input-row select,.input-row textarea{padding:12px;border:2px solid #e9ecef;border-radius:8px;font-size:1rem;transition:all .3s ease;background:#fff}
.input-row input:focus,.input-row select:focus,.input-row textarea:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1)}
.btn{display:inline-block;padding:12px 24px;border-radius:25px;text-decoration:none;font-weight:600;transition:all .3s ease;border:2px solid transparent;font-size:1rem;cursor:pointer;text-align:center}
.btn.primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none}
.btn.secondary{background:linear-gradient(135deg,#6c757d,#495057);color:#fff;border:none}
.btn.danger{background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;border:none}
.btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.2)}
table{width:100%;border-collapse:collapse;margin:0}
table th{background:linear-gradient(135deg,#f8f9fa,#e9ecef);padding:14px 12px;text-align:left;font-weight:600;color:#495057;border-bottom:2px solid #dee2e6}
table td{padding:12px;border-bottom:1px solid #e9ecef;vertical-align:middle}
table tbody tr:hover{background:#f8f9fa}
.badge{padding:8px 16px;border-radius:20px;font-size:.9rem;font-weight:600;border:2px solid transparent;transition:all .3s ease}
.badge.adults{background:linear-gradient(135deg,#e3f2fd,#bbdefb);color:#1976d2;border-color:#bbdefb}
.badge.children{background:linear-gradient(135deg,#f3e5f5,#e1bee7);color:#7b1fa2;border-color:#e1bee7}
.badge.total{background:linear-gradient(135deg,#e8f5e8,#c8e6c9);color:#388e3c;border-color:#c8e6c9}
.report-button-container{margin-top:20px;text-align:center;padding:20px;background:linear-gradient(135deg,#f8f9fa,#e9ecef);border-top:1px solid #e9ecef}
.actions a{margin-right:8px;padding:6px 12px;border-radius:15px;font-size:.9rem;text-decoration:none;transition:all .3s ease}
.actions a:not(.btn){background:#e9ecef;color:#495057;border:1px solid #dee2e6}
.actions a:not(.btn):hover{background:#667eea;color:#fff;border-color:#667eea}
@media (max-width:768px){.page-header h1{font-size:2rem}.card-header{flex-direction:column;text-align:center;gap:10px}.input-row{grid-template-columns:1fr}.actions{display:flex;flex-direction:column;gap:6px}}
.dishes-block .card-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: white;
    padding: 15px 20px;
}
/* Aumentar tamaño y permitir scroll en ambos lados */
.input-row select[multiple] {
    width: 100%;
    min-width: 260px;
    height: 220px;
    overflow-y: auto;   /* Scroll vertical */
    overflow-x: auto;   /* Scroll horizontal */
    white-space: nowrap; /* Mantiene el texto en una sola línea para que haga scroll */
}

</style>

<?php require_once __DIR__ . '/footer.php'; ?>
