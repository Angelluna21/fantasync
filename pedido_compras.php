<?php  
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';

if (!function_exists('h')) { 
    function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } 
}

function fmt_human($val, $ingrediente = null, $unidad = null){
    $val = round((float)$val, 3);
    if ($unidad && strtolower($unidad)==='kg' && $val>0 && $val<1){
        return round($val*1000) . ' gr';
    }
    $s = number_format($val, 2, '.', '');
    return rtrim(rtrim($s,'0'),'.');
}
function tituloConDiaYSalon($titulo, $fecha, $salon) {
    $dias = ["DOMINGO","LUNES","MARTES","MIÉRCOLES","JUEVES","VIERNES","SÁBADO"];
    $num = date("w", strtotime($fecha));
    $dia = $dias[$num];

    $salon = strtoupper($salon ?: "");

    return "$titulo / $dia $salon";
}
/* ======================================================
   OBTENER SEMANA SELECCIONADA
====================================================== */

/* PREVENIR WARNING */
$semana_seleccionada = null;

/* 1. Obtener semanas desde BD */
$semanas_raw = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(fecha, '%Y-W%v') AS semana
    FROM evento
    ORDER BY semana ASC
")->fetchAll(PDO::FETCH_COLUMN);

/* 2. Construir arreglo numerado */
$semanas = [];
$contador = 1;
foreach ($semanas_raw as $sem) {
    $semanas[] = [
        'id' => $sem,
        'num' => $contador
    ];
    $contador++;
}

/* 3. Leer cookie si existe */
$ultima_semana_cookie = $_COOKIE['ultima_semana'] ?? null;

/* 4. Si usuario escogió semana -> GET */
if (isset($_GET['sem'])) {
    $semana_seleccionada = $_GET['sem'];
    setcookie("ultima_semana", $semana_seleccionada, time() + 31536000, "/");
}
/* 5. Si no GET, usar cookie */
elseif ($ultima_semana_cookie && in_array($ultima_semana_cookie, array_column($semanas, 'id'))) {
    $semana_seleccionada = $ultima_semana_cookie;
}
/* 6. Si no GET ni cookie, usar semana 1 */
else {
    $semana_seleccionada = $semanas[0]['id'];
}
/* Si no viene semana, usar la más reciente */
if (!$semana_seleccionada) {
    $semana_seleccionada = $semanas[count($semanas)-1]['id']; 
}
/* Eventos SOLO de la semana seleccionada */
$eventsStmt = $pdo->prepare("
    SELECT id_evento, fecha, titulo
    FROM evento
    WHERE DATE_FORMAT(fecha, '%Y-W%v') = ?
    ORDER BY fecha ASC
");
$eventsStmt->execute([$semana_seleccionada]);
$events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$events) {
    echo "<div class='card'>No hay eventos en esta semana.</div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

/* Key del localStorage por semana */
$semana_id = $semana_seleccionada;
$LOCAL_KEY = "pedido_compras_semana_" . $semana_id;

if (!$events) {
    echo "<div class='card'>No hay eventos disponibles.</div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

/* ============== LocalStorage por semana ============== */
$primera_fecha = $events[0]['fecha'];
$semana_id = date("Y-W", strtotime($primera_fecha));
$LOCAL_KEY = "pedido_compras_semana_" . $semana_id;

/* ======================================================  
   DETALLE DE INGREDIENTES  
====================================================== */
$ids = implode(",", array_map(fn($ev)=>$ev['id_evento'],$events));

$detStmt = $pdo->prepare("
    SELECT id_evento_salon, salon, ingrediente, unidad, cantidad_calc, id_evento
    FROM vw_evento_salon_platillo_ingrediente
    WHERE id_evento IN ($ids)
");
$detStmt->execute();
$det = $detStmt->fetchAll(PDO::FETCH_ASSOC);
// Obtener salón por evento
// =======================
// ADULTOS Y NIÑOS POR EVENTO
// =======================
$adultos_evento = [];
$ninos_evento   = [];

$qAN = $pdo->prepare("
    SELECT id_evento, adultos, ninos
    FROM evento_salon
    WHERE id_evento IN ($ids)
");
$qAN->execute();

foreach ($qAN->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $id = $r['id_evento'];
    if (!isset($adultos_evento[$id])) $adultos_evento[$id] = (int)$r['adultos'];
    if (!isset($ninos_evento[$id]))   $ninos_evento[$id]   = (int)$r['ninos'];
}
/* AGRUPAR POR INGREDIENTE */
$porIng = [];
foreach ($det as $r){
    $ing = trim($r['ingrediente']);
    if ($ing==='') continue;

    $unidad = $r['unidad'];
    $id_evento = (int)$r['id_evento'];
    $cant = (float)$r['cantidad_calc'];

    if (!isset($porIng[$ing])){
        $porIng[$ing] = ['unidad'=>$unidad,'eventos'=>[],'total'=>0];
    }
    if (!isset($porIng[$ing]['eventos'][$id_evento])) 
        $porIng[$ing]['eventos'][$id_evento] = 0;

    $porIng[$ing]['eventos'][$id_evento] += $cant;
    $porIng[$ing]['total'] += $cant;
}

uksort($porIng, fn($a,$b)=>strnatcasecmp($a,$b));

?>
<style>
body{ font-family:Calibri,Arial; font-size:13px; }

table.pedido{ width:100%; border-collapse:collapse; background:#fff; }
table.pedido th, table.pedido td{
    border:0.5px solid #b4b4b4; padding:2px 4px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
thead th{ background:#d9e1f2; font-weight:700; }
.excel-title{ background:#c6e0b4!important; font-size:14px; font-weight:bold; }

.right{text-align:right}

/* COLORES POR CATEGORÍA */
.cat-LA MODERNA{ background:#fff7cd!important; }
.cat-VERDURAS{ background:#d6ffda!important; }
.cat-CARNES{ background:#ffd6d6!important; }
.cat-CREMERIA{ background:#e6eaff!important; }
.cat-EMBUTIDOS{ background:#ffe5d1!important; }
.cat-OTROS{ background:#f1f1f1!important; }

/* ENCABEZADOS VERDES IGUAL A TU FOTO */
.resumen-title {
    font-weight:bold;
    margin:20px 0 6px;
}
.resumen-table thead th{
    background:#e2f0d9 !important;
    font-weight:700;
}
.resumen-cat-header{
    font-weight:bold;
    margin-top:25px;
}
/* =========================================
   SUPER COMPRESIÓN SOLO PARA EL RESUMEN
   (mínima altura + sin espacios blancos)
=========================================*/

/* Reduce padding, márgenes y espacios */
#resumen_categorias_wrapper table.resumen-table th,
#resumen_categorias_wrapper table.resumen-table td {
    padding: 1px 2px !important;
    font-size: 10px !important;
    line-height: 10px !important;
    height: 12px !important;
    white-space: nowrap !important;
}

/* Filas ultra compactas */
#resumen_categorias_wrapper table.resumen-table tr {
    height: 14px !important;
}

/* Encabezados de eventos en dos líneas */
#resumen_categorias_wrapper .evento-head {
    padding: 0 !important;
    margin: 0 !important;
    font-size: 10px !important;
    line-height: 11px !important;
    white-space: normal !important;
    text-align: center !important;
}

/* Mantener compacto también al imprimir */
@media print {
    #resumen_categorias_wrapper table.resumen-table th,
    #resumen_categorias_wrapper table.resumen-table td {
        padding: 1px 2px !important;
        font-size: 9px !important;
        line-height: 10px !important;
        height: 12px !important;
    }

    #resumen_categorias_wrapper table.resumen-table tr {
        height: 14px !important;
    }

    #resumen_categorias_wrapper .evento-head {
        font-size: 9px !important;
        line-height: 10px !important;
    }
}

@media print {

    /* OCULTA LA TABLA PRINCIPAL Y TODO LO QUE ES NO-PRINT */
    .pedido-original,
    .no-print {
        display:none !important;
    }

    /* MUESTRA ÚNICAMENTE EL RESUMEN */
    #resumen_categorias_wrapper {
        display:block !important;
    }

    /* ACTIVAR COLORES REALES EN EL RESUMEN */
    #resumen_categorias_wrapper * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* ESTILO VERDE DEL ENCABEZADO DEL RESUMEN */
    #resumen_categorias_wrapper .resumen-table thead th{
        background:#e2f0d9 !important;
        color:#000 !important;
        font-weight:700 !important;
    }

    /* TÍTULO DE CATEGORÍA CON FONDO VERDE */
    #resumen_categorias_wrapper .resumen-cat-header {
        background:#e2f0d9 !important;
        padding:6px 10px !important;
        border-radius:6px !important;
        margin-top:15px !important;
        font-weight:bold !important;
    }

    /* Mantener ancho de página horizontal */
    @page { size: landscape; }
}
</style>

<!-- ENCABEZADO ESTILO EVENTO / ORDEN DE PRODUCCIÓN -->
<div class="header-bar no-print" style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    padding-bottom:8px;
    border-bottom:2px solid #ccc;">
    
    <div>
        <div style="font-size:24px; font-weight:bold;">Pedido de compra</div>
        <div style="font-size:13px; opacity:0.7;">Semana <?= h($semana_id) ?></div>
    </div>

    <div>
        <a href="evento_list.php">
            <button style="
                padding:6px 14px;
                border-radius:6px;
                background:#e2e8f0;
                border:1px solid #cbd5e1;
                cursor:pointer;">
                ← Evento
            </button>
        </a>

        <button onclick="window.print()" style="
            padding:6px 14px;
            border-radius:6px;
            background:#e2e8f0;
            border:1px solid #cbd5e1;
            cursor:pointer;
            margin-left:6px;">
            Imprimir
        </button>
    </div>
</div>
<!-- SELECTOR DE SEMANAS -->
<div class="no-print" style="margin-bottom:15px;">
    <form method="GET">
        <label><strong>Semana:</strong></label>
        <select name="sem" onchange="this.form.submit()" 
            style="padding:6px 10px; border-radius:6px; border:1px solid #ccc;">
           <<?php foreach ($semanas as $sem): ?>
    <option value="<?= $sem['id'] ?>" <?= $sem['id'] == $semana_seleccionada ? 'selected' : '' ?>>
        Semana <?= $sem['num'] ?>
    </option>
<?php endforeach; ?>
        </select>
    </form>
</div>
<script>
document.querySelector("select[name='sem']").addEventListener("change", function() {
    document.cookie = "ultima_semana=" + this.value + "; path=/; max-age=31536000";
});
</script>
<!-- PANEL DE CATEGORÍAS (NO SE MUEVE NADA) -->
<!-- PANEL DE CATEGORÍAS MEJORADO -->
<div class="no-print cat-box">
    <h3 class="cat-title">Categorías para clasificar los productos</h3>
    <!-- FORMULARIO NUEVO INSUMO -->
<div class="no-print insumo-box" style="
    background:#f7f9fc;
    border:1px solid #d0d7e1;
    border-radius:8px;
    padding:15px;
    margin-bottom:15px;">
    
    <h3 style="margin:0 0 10px 0;">Agregar insumo manual</h3>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <input id="insumo_nombre" type="text" placeholder="Nombre del insumo"
            style="padding:6px; border:1px solid #ccc; border-radius:6px; width:200px;">

        <input id="insumo_unidad" type="text" placeholder="Unidad (pz, caja...)"
            style="padding:6px; border:1px solid #ccc; border-radius:6px; width:120px;">

        <select id="insumo_categoria" class="cat-select"
            style="padding:6px; border:1px solid #ccc; border-radius:6px;">
            <option value="">-- categoría --</option>
        </select>

        <input id="insumo_pedir" type="text" placeholder="Cant. a pedir"
            style="padding:6px; border:1px solid #ccc; border-radius:6px; width:120px;">

        <input id="insumo_notas" type="text" placeholder="Notas"
            style="padding:6px; border:1px solid #ccc; border-radius:6px; width:200px;">

        <button onclick="agregarInsumoManual()" style="
            padding:6px 14px; border-radius:6px; background:#e2e8f0;
            border:1px solid #cbd5e1; cursor:pointer;">
            Agregar insumo
        </button>
    </div>
</div>


    <div class="cat-input-row">
        <input id="nueva_categoria" class="cat-input" 
               type="text" placeholder="Ej: ABARROTES, CARNES, VERDURAS...">

        <button class="btn-cat" onclick="addCategoria()">Agregar categoría</button>
    </div>

    <div id="cat_badges" class="cat-badges"></div>
</div>

<style>
/* Caja general */
.cat-box{
    background:#f7f9fc;
    border:1px solid #d0d7e1;
    border-radius:8px;
    padding:18px;
    margin-bottom:18px;
}

/* Título */
.cat-title{
    margin:0 0 12px 0;
    font-size:18px;
    font-weight:bold;
}

/* Fila de input */
.cat-input-row{
    display:flex;
    gap:10px;
    align-items:center;
    margin-bottom:12px;
}

/* Input */
.cat-input{
    padding:8px 12px;
    width:280px;
    border:1px solid #cbd5e1;
    border-radius:6px;
    outline:none;
    font-size:14px;
}
.cat-input:focus{
    border-color:#4a90e2;
}

/* Botón agregar */
.btn-cat{
    padding:8px 14px;
    background:#e2e8f0;
    border:1px solid #cbd5e1;
    border-radius:6px;
    cursor:pointer;
    font-size:14px;
}
.btn-cat:hover{
    background:#cbd5e1;
}

/* Badges */
.cat-badges{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-top:4px;
}

.cat-badges span{
    background:#edf2f7;
    padding:5px 12px;
    border-radius:8px;
    font-weight:600;
    font-size:13px;
    border:1px solid #d4dce4;
    
}
</style>
<!-- TABLA PRINCIPAL -->
<div class="pedido-original">
<table class="pedido" id="tabla_pedido_original">
<thead>
<tr><th class="excel-title" colspan="<?= 4 + count($events) ?>">PEDIDO SEMANAL</th></tr>
<tr>
    <th>INGREDIENTE</th>
    <th>CATEGORÍA</th>
    <th>UNI</th>

<?php foreach ($events as $e): ?>
<?php $id = $e['id_evento']; ?>
htmlHead += `
    <th class="evento-head" style="text-align:center;">

        <div style="font-size:10px; font-weight:bold;">
            Adultos: <?= $adultos_evento[$id] ?? 0 ?><br>
            Niños: <?= $ninos_evento[$id] ?? 0 ?>
        </div>

        <div style="font-size:10px; font-weight:bold; margin-top:2px;">
            <?= h($e['titulo']) ?>
        </div>

        <small style="font-size:9px; opacity:0.8;">
            <?= h(tituloConDiaYSalon("", $e['fecha'], $salones_evento[$id] ?? "")) ?>
        </small>

    </th>
`;
<?php endforeach; ?>
    <th>TOTAL</th>
    <th>CANT. A PEDIR</th>
    <th>NOTAS</th>
</tr>
</thead>

<tbody>
<?php foreach ($porIng as $ing=>$info): ?>
<tr data-ing="<?= h($ing) ?>">
    <td><?= h($ing) ?></td>
    <td><select class="cat-select"></select></td>
    <td><?= h($info['unidad']) ?></td>

    <?php foreach ($events as $ev): 
        $id=$ev['id_evento'];
        $cant=$info['eventos'][$id]??0;
    ?>
        <td class="right"><?= h(fmt_human($cant,$ing,$info['unidad'])) ?></td>
    <?php endforeach; ?>

    <td class="right"><strong><?= h(fmt_human($info['total'],$ing,$info['unidad'])) ?></strong></td>

    <!-- YA NO EXISTE PRESENTACIÓN -->
    <td><input type="text"></td> <!-- CANT. A PEDIR -->
    <td><input type="text"></td> <!-- NOTAS -->
    
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<!-- RESUMEN SIEMPRE VISIBLE -->
<h2>Resumen por categoría</h2>
<div id="resumen_categorias_wrapper"></div>

<script>
function addCategoria(){
    let nueva = document.getElementById("nueva_categoria").value.trim().toUpperCase();
    if (!nueva) return;

    if (!categorias.includes(nueva)) {
        categorias.push(nueva);
        localStorage.setItem("categorias_globales", JSON.stringify(categorias));
    }

    saved.cats = categorias;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));

    document.getElementById("nueva_categoria").value = "";

    renderBadges();
    fillSelects();
    updateResumen();
}

let STORAGE_KEY = "<?= $LOCAL_KEY ?>";

/* ================================
   CATEGORÍAS GLOBALES
================================ */
let categorias_default = ["LA MODERNA","VERDURAS","CARNES","CREMERIA","EMBUTIDOS", "TALISMAN", "POLLO", "TRINIDAD","OTROS"];
let categorias_globales = [];

try {
    categorias_globales = JSON.parse(localStorage.getItem("categorias_globales"));
} catch (e) {}

if (!Array.isArray(categorias_globales) || categorias_globales.length === 0) {
    categorias_globales = [...categorias_default];
    localStorage.setItem("categorias_globales", JSON.stringify(categorias_globales));
}

// 🔥 MEZCLAR DEFAULT + PERSONALIZADAS (sin duplicados)
let categorias = Array.from(new Set([
    ...categorias_default,
    ...categorias_globales
]));

// Guardar mezcla definitiva
localStorage.setItem("categorias_globales", JSON.stringify(categorias));
/* ================================
   GUARDADO POR SEMANA
================================ */
let saved = {};
try { saved = JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; } catch(e){}

if (!saved.cats) saved.cats = categorias;

/* BADGES */
function renderBadges(){
    const div=document.getElementById("cat_badges");
    div.innerHTML="";
    categorias.forEach(cat=>{
        let s=document.createElement("span");
        s.textContent=cat;
        s.style="background:#eee;padding:3px 6px;margin:3px;border-radius:6px;";
        div.appendChild(s);
    });
}

/* LLENAR SELECTS */
function fillSelects(){
    document.querySelectorAll(".cat-select").forEach(sel=>{
        let prev=sel.value;
        sel.innerHTML="<option value=''>--</option>";
        categorias.forEach(cat=>{
            let o=document.createElement("option");
            o.value=cat; o.textContent=cat;
            sel.appendChild(o);
        });
        if (prev && categorias.includes(prev)) sel.value=prev;
    });
}

renderBadges();
fillSelects();

/* === NUEVO: LLENAR SELECT insumo_categoria === */
if (document.getElementById("insumo_categoria")) {
    let selIns = document.getElementById("insumo_categoria");
    selIns.innerHTML = "<option value=''>-- categoría --</option>";
    categorias.forEach(c => {
        selIns.innerHTML += `<option value="${c}">${c}</option>`;
    });
}
/* ============================================= */

document.querySelectorAll("#tabla_pedido_original tbody tr").forEach(tr=>{
    let ing=tr.dataset.ing;
    let sel=tr.querySelector(".cat-select");
    if (saved[ing]) sel.value=saved[ing].cat||"";
});

document.querySelectorAll("#tabla_pedido_original tbody tr").forEach(tr=>{
    let ing = tr.dataset.ing;
    if (saved[ing] && saved[ing].cat){
        tr.className = "cat-" + saved[ing].cat;
    }
});

/* GUARDAR CATEGORIA */
document.addEventListener("change",e=>{
    if (e.target.classList.contains("cat-select")){
        let tr=e.target.closest("tr");
        let ing=tr.dataset.ing;

        if (!saved[ing]) saved[ing]={};
        saved[ing].cat=e.target.value;
        tr.className="cat-"+(e.target.value||"");

        localStorage.setItem(STORAGE_KEY,JSON.stringify(saved));
        updateResumen();
    }
});

/* GUARDAR INPUTS */
document.addEventListener("input",e=>{
    if (e.target.closest("#tabla_pedido_original")){
        let tr=e.target.closest("tr");
        let ing=tr.dataset.ing;
        if (!saved[ing]) saved[ing]={};

        let tds = tr.querySelectorAll("td");

        saved[ing].pedir = tds[tds.length-2].querySelector("input").value;
        saved[ing].notas = tds[tds.length-1].querySelector("input").value;

        localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
        updateResumen();
    }
});

/* ========= CALCULAR PEDIR ========= */
document.querySelectorAll("#tabla_pedido_original tbody tr").forEach(tr => {
    let ing = tr.dataset.ing;
    let tds = tr.querySelectorAll("td");

    let total = tds[3 + <?= count($events) ?>].innerText.trim();
    let unidad = tds[2].innerText.trim();
    let inputPedir = tds[3 + <?= count($events) ?> + 1].querySelector("input");

    let cantidad = calcularCantidadAPedir(total, unidad, ing);
    inputPedir.value = cantidad;

    if (!saved[ing]) saved[ing] = {};
    saved[ing].pedir = cantidad;

    localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
});

document.querySelectorAll("#tabla_pedido_original tbody tr").forEach(tr=>{
    let ing=tr.dataset.ing;
    if (saved[ing]){
        let tds = tr.querySelectorAll("td");
        if (saved[ing].pedir) tds[tds.length-2].querySelector("input").value=saved[ing].pedir;
        if (saved[ing].notas) tds[tds.length-1].querySelector("input").value=saved[ing].notas;
    }
});

/* ========= CALCULO DE CANTIDADES ========= */
function calcularCantidadAPedir(totalFormateado, unidad, ingrediente="") {

    unidad = unidad.toLowerCase();
    ingrediente = ingrediente.toLowerCase();

    let total = parseFloat(totalFormateado);

    if (totalFormateado.includes("gr") && unidad === "kg") {
        total = total / 1000;
    }

    if (ingrediente.includes("cilantro") || ingrediente.includes("perejil")) {
        let manojos = Math.max(1, Math.round((total * 1000) / 50));
        return manojos + " manojo(s)";
    }

    if (ingrediente.includes("chile")) {
        return Math.round(total * 1000) + " gr";
    }

    if (unidad === "kg") {

        let kg = total;

        if (
            ingrediente.includes("res") ||
            ingrediente.includes("bistec") ||
            ingrediente.includes("cerdo") ||
            ingrediente.includes("pierna") ||
            ingrediente.includes("milanesa") ||
            ingrediente.includes("carne")
        ) {
            let entero = Math.floor(kg);
            let decimal = kg - entero;

            if (decimal < 0.5) {
                return entero + " kg";
            } else {
                return (entero + 1) + " kg";
            }
        }

        if (kg < 0.5) return kg.toFixed(2) + " kg";
        if (kg >= 0.5 && kg < 1) return "1 kg";
        if (kg >= 1 && kg < 1.5) return "1 kg";
        if (kg >= 1.5 && kg < 1.75) return "1.5 kg";
        if (kg >= 1.75 && kg < 2) return "2 kg";

        let redondeado = Math.round(kg * 4) / 4;
        return redondeado + " kg";
    }

    return total + " " + unidad;
}

/* === NUEVO: AGREGAR INSUMO MANUAL ====================== */
function agregarInsumoManual(){

    let nombre = document.getElementById("insumo_nombre")?.value.trim();
    let unidad = document.getElementById("insumo_unidad")?.value.trim();
    let cat    = document.getElementById("insumo_categoria")?.value.trim();
    let pedir  = document.getElementById("insumo_pedir")?.value.trim();
    let notas  = document.getElementById("insumo_notas")?.value.trim();

    if (!nombre){ alert("Escribe un nombre para el insumo."); return; }
    if (!unidad){ alert("Escribe una unidad."); return; }

    let tbody = document.querySelector("#tabla_pedido_original tbody");
    let tr = document.createElement("tr");
    tr.dataset.ing = nombre;

    let html = `
        <td>${nombre}</td>
        <td><select class="cat-select"></select></td>
        <td>${unidad}</td>
    `;

    <?php foreach ($events as $ev): ?>
        html += `<td class="right">0</td>`;
    <?php endforeach; ?>

    html += `
        <td class="right">0</td>
        <td><input type="text" value="${pedir}"></td>
        <td><input type="text" value="${notas}"></td>
    `;

    tr.innerHTML = html;
    tbody.appendChild(tr);

    saved[nombre] = {
        cat: cat || "",
        pedir: pedir,
        notas: notas
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));

    fillSelects();

    if (cat){
        tr.querySelector(".cat-select").value = cat;
        tr.className = "cat-" + cat;
    }

    document.getElementById("insumo_nombre").value="";
    document.getElementById("insumo_unidad").value="";
    document.getElementById("insumo_pedir").value="";
    document.getElementById("insumo_notas").value="";
    document.getElementById("insumo_categoria").value="";

    updateResumen();
}
/* ====================================================== */

/* ==============================
   GENERAR RESUMEN (sin cambios)
============================== */
function updateResumen() {

    let wrapper = document.getElementById("resumen_categorias_wrapper");
    wrapper.innerHTML = "";

    // Agrupar ingredientes por categoría
    let grupos = {};
    document.querySelectorAll("#tabla_pedido_original tbody tr").forEach(tr => {
        let ing = tr.dataset.ing;
        let cat = saved[ing]?.cat || "SIN CATEGORÍA";

        if (!grupos[cat]) grupos[cat] = [];
        grupos[cat].push(tr);
    });

    // Ordenar categorías por nombre
    let orden = Object.keys(grupos).sort((a,b)=> a.localeCompare(b));

    orden.forEach(cat => {

        // Título de categoría
        let titulo = document.createElement("div");
        titulo.className = "resumen-cat-header";
        titulo.textContent = cat;
        wrapper.appendChild(titulo);

        // Crear tabla del resumen
        let table = document.createElement("table");
        table.className = "pedido resumen-table";
        let thead = document.createElement("thead");

        // ===== ENCABEZADO =====
        let htmlHead = `
            <tr>
                <th>INGREDIENTE</th>
                <th>UNI</th>
        `;

<?php foreach ($events as $e): ?>
<?php $id = $e['id_evento']; ?>
htmlHead += `
    <th class="evento-head" style="text-align:center;">

        <div style="font-size:10px; font-weight:bold;">
            Adultos: <?= $adultos_evento[$id] ?? 0 ?><br>
            Niños: <?= $ninos_evento[$id] ?? 0 ?>
        </div>

        <div style="font-size:10px; font-weight:bold; margin-top:2px;">
            <?= h($e['titulo']) ?>
        </div>

        <small style="font-size:9px; opacity:0.8;">
            <?= h(tituloConDiaYSalon("", $e['fecha'], $salones_evento[$id] ?? "")) ?>
        </small>

    </th>
`;
<?php endforeach; ?>
        htmlHead += `
                <th>TOTAL</th>
                <th>CANT. A PEDIR</th>
                <th>NOTAS</th>
            </tr>
        `;

        thead.innerHTML = htmlHead;
        table.appendChild(thead);

        let tbody = document.createElement("tbody");

        grupos[cat].forEach(orig => {
            let arr = orig.querySelectorAll("td");
            let row = document.createElement("tr");

            let ing = orig.dataset.ing;

            // Ingrediente
            row.innerHTML += `<td>${arr[0].textContent.trim()}</td>`;

            // Unidad
            row.innerHTML += `<td>${arr[2].textContent.trim()}</td>`;

            // Cantidades por evento
            <?php foreach ($events as $index=>$ev): ?>
            row.innerHTML += `
                <td class="right">
                    ${arr[3 + <?= $index ?>].textContent.trim()}
                </td>
            `;
            <?php endforeach; ?>

            // Total
            row.innerHTML += `
                <td class="right">${arr[3 + <?= count($events) ?>].textContent.trim()}</td>
            `;

            // Cant. a pedir
            row.innerHTML += `
                <td>${saved[ing]?.pedir || ""}</td>
            `;

            // Notas
            row.innerHTML += `
                <td>${saved[ing]?.notas || ""}</td>
            `;

            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        wrapper.appendChild(table);
    });
}


updateResumen();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
