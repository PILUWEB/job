<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Conexión directa (sin db.php ni config externo)
$conn = new mysqli(
    'localhost',
    'wwwmobservi_root',
    'samantha80$',
    'wwwmobservi_erp_job'
);
if ($conn->connect_error) {
    die('Conexión fallida: ' . $conn->connect_error);
}
$conn->set_charset('utf8');

// === Proyectos ===
$proyectos = [];
$stmtProy = $conn->prepare("SELECT PSPID, POST1 FROM PROJ ORDER BY PSPID");
$stmtProy->execute();
$resProy = $stmtProy->get_result();
while ($row = $resProy->fetch_assoc()) {
    $proyectos[$row['PSPID']] = $row['POST1'];
}
$stmtProy->close();

// === Insertar WBS ===
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pspnr = $_POST['pspnr'];
    $pspid = $_POST['pspid'];
    $posid = $_POST['posid'];
    $post1 = $_POST['post1'];
    $parent = $_POST['parent'] ?: null;

    $stmt = $conn->prepare("INSERT INTO PRPS (PSPNR, PSPID, POSID, POST1, PARENT) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $pspnr, $pspid, $posid, $post1, $parent);
    if ($stmt->execute()) {
        if ($parent) {
            $stmt2 = $conn->prepare("INSERT INTO PRHI (PSPNR, PSPNR_PARENT) VALUES (?, ?)");
            $stmt2->bind_param('ss', $pspnr, $parent);
            $stmt2->execute();
            $stmt2->close();
        }
        $mensaje = "✅ WBS '$posid' creado correctamente.";
    } else {
        $mensaje = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// === Cargar WBS ===
$wbs = [];
$res = $conn->query("SELECT * FROM PRPS ORDER BY PSPNR");
while ($row = $res->fetch_assoc()) {
    $wbs[$row['PSPNR']] = $row + ['children' => []];
}

// === Armar jerarquía con PRHI
$relaciones = $conn->query("SELECT PSPNR, PSPNR_PARENT FROM PRHI");
if ($relaciones && $relaciones->num_rows > 0) {
    while ($rel = $relaciones->fetch_assoc()) {
        $padre = $rel['PSPNR_PARENT'];
        $hijo  = $rel['PSPNR'];
        if (isset($wbs[$padre]) && isset($wbs[$hijo])) {
            $wbs[$padre]['children'][] = &$wbs[$hijo];
            unset($wbs[$hijo]);
        }
    }
} else {
    foreach ($wbs as $id => &$item) {
        if ($item['PARENT'] && isset($wbs[$item['PARENT']])) {
            $wbs[$item['PARENT']]['children'][] = &$item;
            unset($wbs[$id]);
        }
    }
}
unset($item);

function renderTree($node, $nivel = 0)
{
    $indent = $nivel * 20;
    echo "<div style='margin-left: {$indent}px;' class='mb-1'>";
    echo "<span class='text-blue-800 font-semibold'>📁 " . htmlspecialchars($node['POSID']) . "</span>";
    echo " <span class='text-gray-600'> - " . htmlspecialchars($node['POST1']) . "</span>";
    echo " <a href='registrar_actividad.php?wbs=" . urlencode($node['PSPNR']) . "' class='text-sm ml-2 text-purple-700 hover:underline'>➕ Actividad</a>";
    echo "</div>";
    foreach ($node['children'] as $child) {
        renderTree($child, $nivel + 1);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>CJ20N - JOB</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

  <header class="bg-blue-700 text-white p-4">
    <h1 class="text-2xl font-bold">🌳 CJ20N - Árbol de Proyectos (PRPS + PRHI)</h1>
    <p class="text-sm">Estructura interactiva tipo SAP | Sistema: <strong>JOB</strong></p>
  </header>

  <main class="max-w-4xl mx-auto mt-6 p-6 bg-white rounded shadow">
    <h2 class="text-xl font-bold text-gray-800 mb-4">➕ Crear nuevo WBS</h2>

    <?php if ($mensaje): ?>
      <div class="mb-4 p-2 bg-green-100 text-green-700 font-medium rounded"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold">PSPNR (ID único)</label>
        <input name="pspnr" required class="border p-2 w-full rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold">Proyecto (PSPID)</label>
        <select name="pspid" required class="border p-2 w-full rounded">
          <option value="">-- Selecciona un proyecto --</option>
          <?php foreach ($proyectos as $id => $desc): ?>
            <option value="<?= htmlspecialchars($id) ?>"><?= $id ?> - <?= $desc ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold">Código POSID</label>
        <input name="posid" required class="border p-2 w-full rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold">Texto (POST1)</label>
        <input name="post1" class="border p-2 w-full rounded">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-semibold">WBS Padre (opcional)</label>
        <select name="parent" class="border p-2 w-full rounded">
          <option value="">-- Ninguno (nivel raíz) --</option>
          <?php
          $resPadres = $conn->query("SELECT PSPNR, POSID, POST1 FROM PRPS ORDER BY POSID");
          while ($w = $resPadres->fetch_assoc()) {
            echo "<option value='{$w['PSPNR']}'>{$w['POSID']} - {$w['POST1']}</option>";
          }
          ?>
        </select>
      </div>
      <div class="md:col-span-2 text-right">
        <button class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">💾 Crear WBS</button>
      </div>
    </form>
  </main>

  <section class="max-w-4xl mx-auto mt-4 p-6 bg-white rounded shadow">
    <h2 class="text-lg font-semibold mb-3 text-gray-800">📘 Árbol del Proyecto</h2>
    <?php foreach ($wbs as $nodoRaiz) renderTree($nodoRaiz); ?>
  </section>

</body>
</html>
