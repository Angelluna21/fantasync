<?php 
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function norm($s){
    if ($s === null) return '';
    $s = trim((string)$s);
    $s = mb_strtolower($s, 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-z0-9\s]/i', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function diaESUpper($ymd){
  static $dias = ['DOMINGO','LUNES','MARTES','MIÉRCOLES','JUEVES','VIERNES','SÁBADO'];
  $ts = strtotime($ymd); if($ts===false) return '';
  return $dias[(int)date('w',$ts)];
}
function fmt_human($val, $ingrediente = null, $unidad = null){
  $val = round((float)$val, 3);
  if ($ingrediente && stripos($ingrediente, 'nopal') !== false && $unidad && strtolower($unidad) === 'pz') {
    if ($val > 0 && $val <= 62) $val = 60;
  }
  if ($val > 0 && $val < 1) {
    if ($val >= 0.07) $val = 1.0;
    elseif ($val <= 0.06) $val = 0.5;
  }
  $ent  = floor($val + 1e-9);
  $frac = round($val - $ent, 2);
  if ($frac >= 0.24 && $frac <= 0.26) return trim(($ent? $ent : '').' 1/4');
  if ($frac >= 0.49 && $frac <= 0.51) return trim(($ent? $ent : '').' 1/2');
  if ($frac >= 0.74 && $frac <= 0.76) return trim(($ent? $ent : '').' 3/4');
  
  $s = number_format($val, 2, '.', ''); 
  $s = rtrim(rtrim($s,'0'),'.');
  return $s==='' ? '0' : $s;
}

function calcular_receta_por_porciones($pdo, $id_platillo, $porciones_plan){
  $out = [];
  $pstmt = $pdo->prepare("SELECT porciones_base, nombre FROM platillo WHERE id_platillo=?");
  $pstmt->execute([$id_platillo]);
  $pinfo = $pstmt->fetch(PDO::FETCH_ASSOC);
  if (!$pinfo) return $out;
  $porciones_base = (float)($pinfo['porciones_base'] ?: 100);
  $r = $pdo->prepare("
    SELECT r.id_ingrediente, i.nombre AS ingrediente, i.unidad, i.presentacion_descripcion,
           r.cantidad_por_base, r.nota
    FROM receta r
    JOIN ingrediente i ON i.id_ingrediente = r.id_ingrediente
    WHERE r.id_platillo = ?
  ");
  $r->execute([$id_platillo]);
  $rows = $r->fetchAll(PDO::FETCH_ASSOC);
  foreach($rows as $rw){
    $cantidad_calc = round( (float)$rw['cantidad_por_base'] * ($porciones_plan / max(1,$porciones_base)), 3);
    $out[] = [
      'ingrediente' => $rw['ingrediente'],
      'unidad' => $rw['unidad'],
      'cantidad' => $cantidad_calc,
      'cantidad_mostrada' => fmt_human($cantidad_calc, $rw['ingrediente'], $rw['unidad'])
    ];
  }
  return $out;
}

/* ================= Parámetro ================= */
$id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_evento <= 0) { 
  echo "<div class='card'>Falta el parámetro ?id=</div>"; 
  require_once __DIR__ . '/footer.php'; 
  exit; 
}

/* ================= Encabezado del evento ================= */
$ev = $pdo->prepare("SELECT id_evento, fecha, COALESCE(titulo,'Evento') AS titulo FROM evento WHERE id_evento=?");
$ev->execute([$id_evento]); 
$evento = $ev->fetch(PDO::FETCH_ASSOC);
if(!$evento){ 
  echo "<div class='card'>Evento no encontrado.</div>"; 
  require_once __DIR__ . '/footer.php'; 
  exit; 
}

/* Salones del evento y sus alias (para formar <DÍA> <ALIAS>) */
try {
  $hs = $pdo->prepare("SELECT * FROM vw_evento_salon_header WHERE id_evento=? ORDER BY salon");
  $hs->execute([$id_evento]);
  $headers = $hs->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $hs = $pdo->prepare("
    SELECT es.id_evento_salon, e.fecha, COALESCE(s.alias, s.nombre) AS salon, es.adultos, es.ninos,
           CASE WHEN COALESCE(es.cafe,e.cafe)=1 THEN 'SI' ELSE 'NO' END AS cafe,
           CASE WHEN COALESCE(es.descorche,e.descorche)=1 THEN 'SI' ELSE 'NO' END AS descorche
    FROM evento_salon es
    JOIN evento e ON e.id_evento = es.id_evento
    JOIN salon  s ON s.id_salon  = es.id_salon
    WHERE es.id_evento=?
    ORDER BY salon
  ");
  $hs->execute([$id_evento]);
  $headers = $hs->fetchAll(PDO::FETCH_ASSOC);
}
if(!$headers){ 
  echo "<div class='card'>No hay salones para este evento.</div>"; 
  require_once __DIR__ . '/footer.php'; 
  exit; 
}

$dia = diaESUpper($evento['fecha']);
$colSalon = [];
$headers_by_id = [];
foreach ($headers as $h) {
  $id_es = (int)$h['id_evento_salon'];

  $al = $pdo->prepare("
    SELECT COALESCE(s.alias, s.nombre) 
    FROM evento_salon es 
    JOIN salon s ON s.id_salon=es.id_salon 
    WHERE es.id_evento_salon=?
  ");
  $al->execute([$id_es]);
  $alias = $al->fetchColumn();

  $colSalon[$id_es] = trim($dia.' '.$alias);
  $headers_by_id[$id_es] = $h;
}

/* =======================================================
   DETALLE DE INGREDIENTES COMO EN reporte_evento.php
   ======================================================= */

// Traer detalle por platillo/ingrediente
$detStmt = $pdo->prepare("
  SELECT
    id_evento_salon,
    salon,
    seccion,
    orden_seccion,
    orden_platillo,
    platillo,
    ingrediente,
    unidad,
    presentacion_descripcion,
    nota_receta,
    cantidad_calc,
    cantidad_mostrada
  FROM vw_evento_salon_platillo_ingrediente
  WHERE id_evento = :id
");
$detStmt->execute([':id' => $id_evento]);
$det = $detStmt->fetchAll(PDO::FETCH_ASSOC);

// Ajustes para pizza, gelatina y fruta (mismo que en reporte_evento)
foreach ($det as &$r) {
    $platilloLower = strtolower($r['platillo'] ?? '');

    // Pizza y gelatina: se calculan por niño
    if (strpos($platilloLower, 'pizza') !== false || strpos($platilloLower, 'gelatina') !== false) {
        $id_es = (int)$r['id_evento_salon'];
        $h     = $headers_by_id[$id_es] ?? null;

        $totalPersonas = 0;
        if ($h) {
            $totalPersonas = max(0, (int)($h['ninos'] ?? 0));
        }

        $r['cantidad_calc']     = $totalPersonas;
        $r['cantidad_mostrada'] = fmt_human($totalPersonas, $r['ingrediente'], 'pieza');
        $r['unidad']            = 'pieza';
    }

    // Fruta: 1 pieza por niño
    if (strpos($platilloLower, 'fruta') !== false) {
        $id_es = (int)$r['id_evento_salon'];
        $h     = $headers_by_id[$id_es] ?? null;

        $totalPersonas = 0;
        if ($h) {
            $totalPersonas = max(0, (int)($h['ninos'] ?? 0));
        }

        $r['cantidad_calc']     = $totalPersonas;
        $r['cantidad_mostrada'] = fmt_human($totalPersonas, $r['ingrediente'], 'pieza');
        $r['unidad']            = 'pieza';
    }
}
unset($r);

// Quitar cualquier DESCORCHE que venga de la vista
$det = array_values(array_filter($det, function($r){
    return !(isset($r['seccion']) && strtoupper(trim($r['seccion'])) === 'DESCORCHE');
}));

// Añadir DESCORCHE (Limón) con la misma fórmula que reporte_evento: 2.5 kg / 100 adultos
foreach ($headers_by_id as $id_es => $h) {
    $total_adultos = max(0, (int)($h['adultos'] ?? 0));

    if (isset($h['descorche']) && strtoupper($h['descorche']) === 'SI' && $total_adultos > 0) {
        $cantidad_calc = round($total_adultos * 2.5 / 100, 3); // 0.025 kg por adulto

        if ($cantidad_calc > 0) {
            $det[] = [
                'id_evento_salon' => $id_es,
                'salon' => $h['salon'] ?? '',
                'seccion' => 'DESCORCHE',
                'orden_seccion' => 850,
                'orden_platillo' => null,
                'platillo' => 'Descorche',
                'ingrediente' => 'Limón',
                'unidad' => 'kg',
                'presentacion_descripcion' => null,
                'nota_receta' => null,
                'cantidad_calc' => $cantidad_calc,
                'cantidad_mostrada' => fmt_human($cantidad_calc, 'Limón', 'kg')
            ];
        }
    }
}

/* =======================================================
   CONSTRUIR porSalon A PARTIR DE $det
   ======================================================= */

$porSalon_map = [];
foreach ($det as $r) {
    $id_es = (int)$r['id_evento_salon'];
    $ing   = trim($r['ingrediente'] ?? '');
    if ($ing === '') continue;

    $unidad = $r['unidad'] ?? '';
    $cant   = isset($r['cantidad_calc']) ? (float)$r['cantidad_calc'] : 0.0;
    if ($cant <= 0) continue;

    $key = $id_es . '|' . $ing . '|' . $unidad;
    if (!isset($porSalon_map[$key])) {
        $porSalon_map[$key] = [
            'id_evento_salon' => $id_es,
            'salon'           => $colSalon[$id_es] ?? '',
            'ingrediente'     => $ing,
            'unidad'          => $unidad,
            'cantidad'        => 0.0
        ];
    }
    $porSalon_map[$key]['cantidad'] += $cant;
}

// Normalizar a arreglo indexado y calcular cantidad_mostrada
$porSalon = [];
foreach ($porSalon_map as $row) {
    $row['cantidad_mostrada'] = fmt_human($row['cantidad'], $row['ingrediente'], $row['unidad']);
    $porSalon[] = $row;
}

/* Construir pivot: ingrediente -> datos por salón */
$pivot = [];
foreach ($porSalon as $r) {
  $ing = $r['ingrediente'];
  if (!isset($pivot[$ing])) {
    $pivot[$ing] = [
      'unidad'  => $r['unidad'],
      'salon'   => []
    ];
  }
  $id_es = (int)$r['id_evento_salon'];
  $num   = isset($r['cantidad']) ? (float)$r['cantidad'] : 0.0;
  $show  = $r['cantidad_mostrada'] ?? fmt_human($num, $ing, $r['unidad'] ?? null);
  $pivot[$ing]['salon'][$id_es] = ['num'=>$num,'show'=>$show];
}

uksort($pivot, function($a,$b){
  return strnatcasecmp($a,$b);
});

/* === CALCULAR TOTALES SUMANDO LOS SALONES === */
$totEvento = [];
foreach ($pivot as $ing => $info) {
  $sum = 0.0;
  foreach ($info['salon'] as $id_es => $vals) {
    $sum += (float)($vals['num'] ?? 0.0);
  }
  $totEvento[$ing] = [
    'ingrediente' => $ing,
    'unidad' => $info['unidad'] ?? '',
    'cantidad' => $sum,
    'cantidad_mostrada' => fmt_human($sum, $ing, $info['unidad'] ?? null)
  ];
}

/* número total de columnas */
$colCount   = 3 + count($colSalon) + 4;
$numSalones = count($colSalon);
$salonHeadersJs = json_encode(array_values($colSalon), JSON_UNESCAPED_UNICODE);
?>
<style>
  body{
    font-family: Calibri, Arial, sans-serif;
    font-size:13px;
  }
  .pedido-head{display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:6px}
  .pedido-head h1{font-size:18px;margin:0}
  .pedido-head .muted{display:block;font-size:11px;opacity:.7}

  table.pedido{
    width:100%;
    border-collapse:collapse;
    background:#ffffff;
    table-layout:fixed;
  }
  table.pedido th,table.pedido td{
    border:0.5px solid #b4b4b4;
    padding:2px 4px;
    vertical-align:middle;
    overflow:hidden;
    white-space:nowrap;
    text-overflow:ellipsis;
  }
  table.pedido thead th{
    background:#d9e1f2;
    font-weight:700;
    text-align:center;
  }
  .excel-title{
    background:#c6e0b4 !important;
    font-size:14px;
    font-weight:bold;
    text-align:center;
  }
  .right{text-align:right}

  .cats-panel{
    margin:10px 0 10px;
    padding:8px 10px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:8px;
  }
  .cat-badges{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
    margin-top:4px;
  }
  .cat-badge{
    padding:2px 6px;
    border-radius:999px;
    background:#e0f2fe;
    color:#0369a1;
    font-size:0.75rem;
    border:1px solid #bae6fd;
  }
  .btn-small{
    padding:3px 8px;
    border-radius:16px;
    border:1px solid #64748b;
    background:#e2e8f0;
    font-size:0.75rem;
    cursor:pointer;
  }

  /* 🔹 BLOQUE RESUMEN POR CATEGORÍA (TABLAS VERDES) */
  .resumen-categorias{
    margin-top:18px;
  }
  table.pedido-cat{
    width:100%;
    border-collapse:collapse;
    background:#ffffff;
    margin-top:10px;
    margin-bottom:18px;
    table-layout:fixed;
  }
  table.pedido-cat th,
  table.pedido-cat td{
    border:0.5px solid #b4b4b4;
    padding:2px 4px;
    vertical-align:middle;
    overflow:hidden;
    white-space:nowrap;
    text-overflow:ellipsis;
  }
  table.pedido-cat thead th{
    background:#e2f0d9;           /* VERDE CLARO */
    font-weight:700;
    text-align:center;
  }
  .cat-block-title{
    background:#c6e0b4 !important; /* VERDE MÁS INTENSO */
    text-align:left;
    font-weight:bold;
  }

  /* 🔵 Forzar colores al imprimir */
  * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
  }

  /* 🔵 Ajustes de impresión */
  @media print {

      body {
          background: #ffffff !important;
          font-size: 10px;
      }

      .no-print {
          display: none !important;
      }

      /* OCULTAR LA TABLA DONDE SE ELIGEN LAS CATEGORÍAS */
      .pedido-original {
          display: none !important;
      }

      .pedido, table.pedido {
          font-size: 9px !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
      }

      table.pedido thead th {
          background: #d9e1f2 !important;
          color: #000 !important;
      }

      .excel-title {
          background: #c6e0b4 !important;
          color: #000 !important;
      }

      .cat-badge {
          background: #e0f2fe !important;
          color: #0369a1 !important;
          border-color: #bae6fd !important;
      }

      table.pedido-cat thead th{
          background:#e2f0d9 !important;
          color:#000 !important;
      }
      .cat-block-title{
          background:#c6e0b4 !important;
          color:#000 !important;
      }

      input {
          background: #fff !important;
          color: #000 !important;
      }

      @page {
          size: landscape;
          margin: 8mm;
      }
  }
</style>
<div class="pedido-head">
  <h1>Pedido de compra
    <small class="muted">
      Evento #<?php echo (int)$evento['id_evento']; ?> · Fecha: <?php echo h($evento['fecha']); ?> · <?php echo h($evento['titulo']); ?>
    </small>
  </h1>
  <div class="no-print">
    <a class="btn" href="reporte_evento.php?id=<?php echo (int)$evento['id_evento']; ?>">← Orden de Producción</a>
    <button class="btn" onclick="window.print()">Imprimir</button>
  </div>
</div>

<div class="small" style="margin-bottom:6px">
  Columnas por salón: <strong><?php echo h(implode(' | ', array_values($colSalon))); ?></strong>.
  Total = suma de salones.
</div>

<!-- Panel para definir categorías (pantalla solamente) -->
<div class="cats-panel no-print">
  <h3>Categorías para clasificar los productos</h3>
  <div class="cat-input-row">
    <input type="text" id="nueva_categoria" placeholder="Ej: ABARROTES, CARNES, VERDURAS, LACTEOS...">
    <button type="button" class="btn-small" onclick="agregarCategoria()">Agregar categoría</button>
  </div>
  <div style="margin-top:4px;font-size:0.75rem;color:#555;">
    Primero agrega aquí las categorías que quieras usar. Luego, en la tabla, elige una categoría para cada producto.
  </div>
  <div id="lista_categorias" class="cat-badges"></div>
</div>

<!-- Tabla de captura original -->
<div class="pedido-original">
<table class="pedido" id="tabla_pedido_original">
  <thead>
    <tr>
      <th class="excel-title" colspan="<?php echo (int)$colCount; ?>">
        PEDIDO DE COMPRA · EVENTO #<?php echo (int)$evento['id_evento']; ?> · <?php echo h($evento['fecha']); ?> · <?php echo h($evento['titulo']); ?>
      </th>
    </tr>
    <tr>
      <th>INGREDIENTE</th>
      <th>CATEGORÍA</th>
      <th>UNI</th>
      <?php foreach($colSalon as $id_es=>$etq): ?>
        <th><?php echo h($etq); ?></th>
      <?php endforeach; ?>
      <th>TOTAL</th>
      <th>PRESENTACIÓN / PROVEEDOR</th>
      <th>CANT. A PEDIR</th>
      <th>NOTAS</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($pivot as $ing => $info):
      $unidad = $info['unidad'];
      $totShow = isset($totEvento[$ing]['cantidad_mostrada'])
        ? $totEvento[$ing]['cantidad_mostrada']
        : fmt_human($totEvento[$ing]['cantidad'] ?? 0, $ing, $unidad);
    ?>
      <tr>
        <td><?php echo h($ing); ?></td>
        <td>
          <select class="cat-select">
            <option value="">--</option>
          </select>
        </td>
        <td><?php echo h($unidad); ?></td>
        <?php foreach($colSalon as $id_es=>$etq): ?>
          <td class="right"><?php echo h($info['salon'][$id_es]['show'] ?? ''); ?></td>
        <?php endforeach; ?>
        <td class="right"><strong><?php echo h($totShow); ?></strong></td>
        <td><input type="text" placeholder=""></td>
        <td><input type="text" placeholder=""></td>
        <td><input type="text" placeholder=""></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- RESUMEN AGRUPADO POR CATEGORÍA (para imprimir / ver por secciones) -->
<h2 style="margin-top:20px;">Resumen por categoría</h2>
<div id="resumen_categorias_wrapper"></div>

<script>
let categorias = ['ABARROTES','CARNES','VERDURAS','LACTEOS','EMBUTIDOS','OTROS'];
const NUM_SALONES   = <?php echo (int)$numSalones; ?>;
const SALON_HEADERS = <?php echo $salonHeadersJs; ?>;
const EVENT_ID      = <?php echo (int)$evento['id_evento']; ?>;
const STORAGE_KEY   = 'pedido_compra_' + EVENT_ID;

/* Cargar estado inicial desde localStorage (categorías + filas) */
let estadoInicial = null;
try {
  estadoInicial = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
} catch(e){
  estadoInicial = null;
}
if (estadoInicial && Array.isArray(estadoInicial.categorias)) {
  categorias = estadoInicial.categorias.slice();
}

function renderCategoriasBadges(){
  const cont = document.getElementById('lista_categorias');
  if (!cont) return;
  cont.innerHTML = '';
  categorias.forEach(cat=>{
    const span = document.createElement('span');
    span.className = 'cat-badge';
    span.textContent = cat;
    cont.appendChild(span);
  });
}

function llenarSelectsCategorias(){
  const selects = document.querySelectorAll('.cat-select');
  selects.forEach(sel=>{
    const valorActual = sel.value;
    sel.innerHTML = '<option value="">--</option>';
    categorias.forEach(cat=>{
      const opt = document.createElement('option');
      opt.value = cat;
      opt.textContent = cat;
      sel.appendChild(opt);
    });
    if (valorActual && categorias.includes(valorActual)) {
      sel.value = valorActual;
    }
  });
}

function agregarCategoria(){
  const input = document.getElementById('nueva_categoria');
  let val = (input.value || '').trim();
  if (!val) return;
  val = val.toUpperCase();
  if (!categorias.includes(val)){
    categorias.push(val);
    renderCategoriasBadges();
    llenarSelectsCategorias();
    actualizarResumenCategorias();
    guardarEstado();
  }
  input.value = '';
}

function escapeHtml(str){
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}

/* Guardar estado actual en localStorage */
function guardarEstado(){
  const rows = document.querySelectorAll('#tabla_pedido_original tbody tr');
  const data = {
    categorias: categorias.slice(),
    filas: {}
  };

  rows.forEach(tr=>{
    const tds = tr.querySelectorAll('td');
    if (!tds.length) return;

    const ing = tds[0].textContent.trim();
    const uni = tds[2].textContent.trim();
    const key = ing + '||' + uni;

    const sel = tds[1].querySelector('select');
    const cat = sel ? sel.value.trim() : '';

    const totalIndex = 3 + NUM_SALONES;
    const presInput  = tds[totalIndex+1]?.querySelector('input');
    const pedirInput = tds[totalIndex+2]?.querySelector('input');
    const notasInput = tds[totalIndex+3]?.querySelector('input');

    data.filas[key] = {
      cat:   cat,
      pres:  presInput  ? presInput.value  : '',
      pedir: pedirInput ? pedirInput.value : '',
      notas: notasInput ? notasInput.value : ''
    };
  });

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  } catch(e){
    // Si falla, simplemente no guardamos, pero no rompemos la página
  }
}

/* Aplicar estado de filas desde localStorage */
function aplicarEstadoFilas(data){
  if (!data || !data.filas) return;
  const rows = document.querySelectorAll('#tabla_pedido_original tbody tr');

  rows.forEach(tr=>{
    const tds = tr.querySelectorAll('td');
    if (!tds.length) return;

    const ing = tds[0].textContent.trim();
    const uni = tds[2].textContent.trim();
    const key = ing + '||' + uni;

    const info = data.filas[key];
    if (!info) return;

    const sel = tds[1].querySelector('select');
    if (sel && info.cat && categorias.includes(info.cat)) {
      sel.value = info.cat;
    }

    const totalIndex = 3 + NUM_SALONES;
    const presInput  = tds[totalIndex+1]?.querySelector('input');
    const pedirInput = tds[totalIndex+2]?.querySelector('input');
    const notasInput = tds[totalIndex+3]?.querySelector('input');

    if (presInput)  presInput.value  = info.pres  || '';
    if (pedirInput) pedirInput.value = info.pedir || '';
    if (notasInput) notasInput.value = info.notas || '';
  });
}

/* Construir tablas por categoría a partir de lo capturado */
function actualizarResumenCategorias(){
  const wrapper = document.getElementById('resumen_categorias_wrapper');
  if (!wrapper) return;

  const rows = document.querySelectorAll('#tabla_pedido_original tbody tr');
  const resumen = {};  // {categoria: [rowData,...]}

  rows.forEach(tr=>{
    const tds = tr.querySelectorAll('td');
    if (tds.length === 0) return;

    const ing   = tds[0].textContent.trim();
    const sel   = tds[1].querySelector('select');
    let   cat   = sel && sel.value ? sel.value.trim().toUpperCase() : 'SIN CATEGORÍA';
    const uni   = tds[2].textContent.trim();

    const salonVals = [];
    for (let i=0;i<NUM_SALONES;i++){
      const idx = 3 + i;
      const val = (tds[idx] && tds[idx].textContent) ? tds[idx].textContent.trim() : '';
      salonVals.push(val);
    }

    const totalIndex = 3 + NUM_SALONES;
    const totalTxt   = (tds[totalIndex] && tds[totalIndex].textContent) ? tds[totalIndex].textContent.trim() : '';

    const presInput  = tds[totalIndex+1].querySelector('input');
    const pedirInput = tds[totalIndex+2].querySelector('input');
    const notasInput = tds[totalIndex+3].querySelector('input');

    const present = presInput  ? presInput.value  : '';
    const pedir   = pedirInput ? pedirInput.value : '';
    const notas   = notasInput ? notasInput.value : '';

    if (!resumen[cat]) resumen[cat] = [];
    resumen[cat].push({
      ing: ing,
      uni: uni,
      salonVals: salonVals,
      total: totalTxt,
      present: present,
      pedir: pedir,
      notas: notas
    });
  });

  // Generar HTML
  let html = '';
  const catsOrdenadas = Object.keys(resumen).sort();
  catsOrdenadas.forEach(cat=>{
    const lista = resumen[cat];
    if (!lista || !lista.length) return;

    html += '<div class="card-cat-block">';
    html += '<div class="cat-title-print">'+escapeHtml(cat)+'</div>';
    html += '<table class="pedido pedido-cat"><thead><tr>';
    html += '<th>INGREDIENTE</th><th>UNI</th>';
    SALON_HEADERS.forEach(h=>{
      html += '<th>'+escapeHtml(h)+'</th>';
    });
    html += '<th>TOTAL</th><th>PRESENTACIÓN / PROVEEDOR</th><th>CANT. A PEDIR</th><th>NOTAS</th>';
    html += '</tr></thead><tbody>';

    lista.forEach(row=>{
      html += '<tr>';
      html += '<td>'+escapeHtml(row.ing)+'</td>';
      html += '<td>'+escapeHtml(row.uni)+'</td>';
      row.salonVals.forEach(v=>{
        html += '<td class="right">'+escapeHtml(v)+'</td>';
      });
      html += '<td class="right"><strong>'+escapeHtml(row.total)+'</strong></td>';
      html += '<td>'+escapeHtml(row.present)+'</td>';
      html += '<td>'+escapeHtml(row.pedir)+'</td>';
      html += '<td>'+escapeHtml(row.notas)+'</td>';
      html += '</tr>';
    });

    html += '</tbody></table></div>';
  });

  wrapper.innerHTML = html;
}

/* Eventos: cambios en categorías e inputs guardan estado */
document.addEventListener('change', function(e){
  if (e.target && e.target.classList.contains('cat-select')) {
    actualizarResumenCategorias();
    guardarEstado();
  }
});

document.addEventListener('input', function(e){
  if (e.target && e.target.closest('#tabla_pedido_original') && e.target.tagName === 'INPUT') {
    actualizarResumenCategorias();
    guardarEstado();
  }
});

/* Inicialización */
renderCategoriasBadges();
llenarSelectsCategorias();
if (estadoInicial) {
  aplicarEstadoFilas(estadoInicial);
}
actualizarResumenCategorias();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
