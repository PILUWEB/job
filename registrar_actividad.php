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

$pspnr = $_GET['wbs'] ?? '';
$wbsInfo = null;
$mensaje = '';

if ($pspnr !== '') {
    $stmt = $conn->prepare("SELECT PSPNR, POSID, POST1 FROM PRPS WHERE PSPNR = ? LIMIT 1");
    $stmt->bind_param('s', $pspnr);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $wbsInfo = $res->fetch_assoc();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aufnr = $_POST['aufnr'];
    $vornr = $_POST['vornr'];
    $ltxa1 = $_POST['ltxa1'];
    $istmng = $_POST['istmng'];
    $arbid = $_POST['arbid'];
    $bldat = $_POST['bldat'];

    $stmt = $conn->prepare("INSERT INTO AFRU (AUFNR, VORNR, LTXA1, ISTMNG, ARBID, BLDAT) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $aufnr, $vornr, $ltxa1, $istmng, $arbid, $bldat);
    if ($stmt->execute()) {
        $mensaje = "✅ Actividad registrada correctamente.";
    } else {
        $mensaje = "❌ Error al guardar: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Actividad - JOB</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

  <header class="bg-blue-700 text-white p-4">
    <h1 class="text-xl font-bold">📋 Registrar Actividad - JOB</h1>
    <p class="text-sm">Vinculado al módulo PRPS (CJ20N)</p>
  </header>

  <main class="max-w-2xl mx-auto mt-6 bg-white p-6 rounded shadow">

    <?php if ($pspnr === '' || !$wbsInfo): ?>
      <div class="text-red-600 font-semibold mb-4">
        ⚠️ WBS no válido o no encontrado. Por favor, accede desde el árbol CJ20N.
      </div>
    <?php else: ?>
      <h2 class="text-lg font-semibold mb-4">📁 <?= htmlspecialchars($wbsInfo['POSID']) ?> - <?= htmlspecialchars($wbsInfo['POST1']) ?></h2>

      <?php if ($mensaje): ?>
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4"><?= $mensaje ?></div>
      <?php endif; ?>

      <form method="POST" class="grid grid-cols-1 gap-4">
        <input type="hidden" name="aufnr" value="<?= htmlspecialchars($pspnr) ?>">

        <div>
          <label class="block font-semibold text-sm">Código Operación (VORNR)</label>
          <input type="text" name="vornr" required class="border p-2 w-full rounded" placeholder="Ej: 0010">
        </div>

        <div>
          <label class="block font-semibold text-sm">Descripción (LTXA1)</label>
          <input type="text" name="ltxa1" required class="border p-2 w-full rounded" placeholder="Descripción">
        </div>

        <div>
          <label class="block font-semibold text-sm">Cantidad (ISTMNG)</label>
          <input type="number" step="any" name="istmng" required class="border p-2 w-full rounded">
        </div>

        <div>
          <label class="block font-semibold text-sm">Centro (ARBID)</label>
          <input type="text" name="arbid" required class="border p-2 w-full rounded">
        </div>

        <div>
          <label class="block font-semibold text-sm">Fecha (BLDAT)</label>
          <input type="date" name="bldat" required class="border p-2 w-full rounded">
        </div>

        <div class="text-right">
          <button class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">💾 Registrar Actividad</button>
        </div>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
