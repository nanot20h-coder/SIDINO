<?php
require_once 'auth.php';
require_auth([6]);

$pdo = getDB();
$id_acud = $_SESSION['user_id'];

// Estudiantes a cargo
$hijos = $pdo->query("
    SELECT u.id_usuario, u.nombre, u.correo
    FROM acudiente_estudiante ae
    JOIN usuario u ON ae.id_estudiante = u.id_usuario
    WHERE ae.id_acudiente = $id_acud
")->fetchAll();

$ids_hijos = array_column($hijos, 'id_usuario');
$notas_hijos = [];
$promedios_hijos = [];
$observaciones_hijos = [];
$citaciones_hijos = [];
$boletines_hijos = [];

if (!empty($ids_hijos)) {
    $ph = implode(',', $ids_hijos);

    $notas_hijos = $pdo->query("
        SELECT n.valor, n.tipo, n.porcentaje, n.fecha,
               m.nombre AS materia, c.nombre AS curso, u.nombre AS estudiante
        FROM nota n
        JOIN matricula mat ON n.id_matricula = mat.id_matricula
        JOIN usuario u ON mat.id_estudiante = u.id_usuario
        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c   ON aa.id_curso   = c.id_curso
        WHERE mat.id_estudiante IN ($ph)
        ORDER BY u.nombre, n.fecha DESC
    ")->fetchAll();

    $promedios_hijos = $pdo->query("
        SELECT u.nombre AS estudiante, m.nombre AS materia,
               ROUND(AVG(n.valor * n.porcentaje / 100) / AVG(n.porcentaje / 100), 2) AS promedio
        FROM nota n
        JOIN matricula mat ON n.id_matricula = mat.id_matricula
        JOIN usuario u ON mat.id_estudiante = u.id_usuario
        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        WHERE mat.id_estudiante IN ($ph)
        GROUP BY u.id_usuario, m.id_materia
        ORDER BY u.nombre, m.nombre
    ")->fetchAll();

    $observaciones_hijos = $pdo->query("
        SELECT o.descripcion, o.fecha, u.nombre AS estudiante,
               d.nombre AS docente, m.nombre AS materia
        FROM observador o
        JOIN usuario u ON o.id_estudiante = u.id_usuario
        JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
        JOIN usuario d ON aa.id_docente = d.id_usuario
        JOIN materia m ON aa.id_materia = m.id_materia
        WHERE o.id_estudiante IN ($ph)
        ORDER BY o.fecha DESC
    ")->fetchAll();

    $citaciones_hijos = $pdo->query("
        SELECT ci.motivo, ci.fecha, u.nombre AS estudiante
        FROM citacion ci JOIN usuario u ON ci.id_estudiante = u.id_usuario
        WHERE ci.id_estudiante IN ($ph)
        ORDER BY ci.fecha DESC
    ")->fetchAll();

    $boletines_hijos = $pdo->query("
        SELECT b.id_boletin, b.fecha, u.nombre AS estudiante, p.nombre AS periodo
        FROM boletin b
        JOIN usuario u ON b.id_estudiante = u.id_usuario
        JOIN periodo_academico p ON b.id_periodo = p.id_periodo
        WHERE b.id_estudiante IN ($ph)
        ORDER BY b.fecha DESC
    ")->fetchAll();
}

$color = '#a78bfa';
layout_head('Acudiente — Dashboard');
echo "<div class='app'>";
echo sidebar_html('dashboard', [
    ['id'=>'dashboard',    'href'=>'acudiente.php',                'icon'=>'fa-gauge',           'label'=>'Dashboard'],
    ['id'=>'notas',        'href'=>'acudiente.php?m=notas',        'icon'=>'fa-star',             'label'=>'Notas del hijo/a'],
    ['id'=>'observador',   'href'=>'acudiente.php?m=observador',   'icon'=>'fa-book-open',        'label'=>'Observador'],
    ['id'=>'citaciones',   'href'=>'acudiente.php?m=citaciones',   'icon'=>'fa-calendar-check',   'label'=>'Citaciones'],
    ['id'=>'boletines',    'href'=>'acudiente.php?m=boletines',    'icon'=>'fa-file-invoice',     'label'=>'Boletines'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Acudiente — Seguimiento', $color);
echo "<div class='module-container'>";

$modulo = $_GET['m'] ?? 'dashboard';

if ($modulo === 'dashboard'):
?>
<div class="welcome-banner">
  <div class="welcome-octopus">🐙</div>
  <div>
    <div class="welcome-title">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class="welcome-sub">Sigue el progreso académico de tu hijo/a</div>
  </div>
</div>

<?php if(empty($hijos)): ?>
  <div class="alert info"><i class="fa-solid fa-circle-info"></i> No tienes estudiantes vinculados. Comunícate con administración.</div>
<?php else: ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-people-roof"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($hijos) ?></div><div class="stat-label">Estudiante(s) a cargo</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-star"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($notas_hijos) ?></div><div class="stat-label">Notas registradas</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa-solid fa-calendar-check"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($citaciones_hijos) ?></div><div class="stat-label">Citaciones</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-book-open"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($observaciones_hijos) ?></div><div class="stat-label">Observaciones</div></div>
  </div>
</div>

<!-- Tarjeta por cada hijo -->
<?php foreach($hijos as $hijo): ?>
<?php
  $notas_hijo = array_filter($notas_hijos, fn($n) => $n['estudiante'] === $hijo['nombre']);
  $prom_hijo  = array_filter($promedios_hijos, fn($p) => $p['estudiante'] === $hijo['nombre']);
  $prom_gen   = !empty($prom_hijo) ? array_sum(array_column(array_values($prom_hijo),'promedio'))/count($prom_hijo) : 0;
?>
<div class="card" style="margin-bottom:1.25rem">
  <div class="card-header">
    <div class="card-title">
      <i class="fa-solid fa-user-graduate"></i>
      <?= htmlspecialchars($hijo['nombre']) ?>
      <?php if($prom_gen > 0): ?>
        — Promedio: <span class="<?= $prom_gen >= 3.5 ? 'nota-alta' : ($prom_gen >= 3.0 ? 'nota-media' : 'nota-baja') ?>"><?= number_format($prom_gen,1) ?></span>
      <?php endif; ?>
    </div>
    <a href="acudiente.php?m=notas" class="card-action">Ver notas</a>
  </div>
  <?php if(empty($prom_hijo)): ?>
    <div class="empty-state" style="padding:1rem"><i class="fa-solid fa-inbox"></i><p style="font-size:.8rem">Sin notas aún</p></div>
  <?php else: ?>
  <?php foreach($prom_hijo as $p):
    $pct = min(100, ($p['promedio'] / 5) * 100);
    $cls = $p['promedio'] >= 3.5 ? 'nota-alta' : ($p['promedio'] >= 3.0 ? 'nota-media' : 'nota-baja');
  ?>
    <div style="margin-bottom:.75rem">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
        <span style="font-size:.82rem;color:var(--text2)"><?= htmlspecialchars($p['materia']) ?></span>
        <span class="<?= $cls ?>" style="font-size:.85rem"><?= number_format($p['promedio'],1) ?></span>
      </div>
      <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $p['promedio'] >= 3.5 ? 'linear-gradient(90deg,#34d399,#22d3ee)' : ($p['promedio'] >= 3.0 ? 'linear-gradient(90deg,#fbbf24,#fb923c)' : 'linear-gradient(90deg,#f87171,#fb923c)') ?>"></div></div>
    </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if(!empty($citaciones_hijos)): ?>
<div class="card">
  <div class="card-title" style="margin-bottom:.75rem"><i class="fa-solid fa-calendar-check" style="color:#f87171"></i> Citaciones pendientes</div>
  <?php foreach(array_slice($citaciones_hijos, 0, 3) as $ci): ?>
    <div class="list-item">
      <div class="stat-icon red"><i class="fa-solid fa-user-clock"></i></div>
      <div class="list-body">
        <div class="list-title"><?= htmlspecialchars($ci['estudiante']) ?></div>
        <div class="list-sub"><?= htmlspecialchars($ci['motivo']) ?></div>
      </div>
      <div class="list-meta"><?= date('d/m/Y', strtotime($ci['fecha'])) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php elseif($modulo === 'notas'): ?>
<div class="section-title"><i class="fa-solid fa-star"></i> Notas de mi hijo/a</div>
<?php if(empty($notas_hijos)): ?>
  <div class="card"><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay notas registradas aún</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Estudiante</th><th>Materia</th><th>Curso</th><th>Tipo</th><th class="nota-cell">Nota</th><th>%</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($notas_hijos as $n):
        $cls = $n['valor'] >= 3.5 ? 'nota-alta' : ($n['valor'] >= 3.0 ? 'nota-media' : 'nota-baja');
      ?>
        <tr>
          <td><?= htmlspecialchars($n['estudiante']) ?></td>
          <td><?= htmlspecialchars($n['materia']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($n['curso']) ?></span></td>
          <td><?= htmlspecialchars($n['tipo']) ?></td>
          <td class="nota-cell"><span class="<?= $cls ?>"><?= number_format($n['valor'],1) ?></span></td>
          <td><?= $n['porcentaje'] ?>%</td>
          <td><?= date('d/m/Y', strtotime($n['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php elseif($modulo === 'observador'): ?>
<div class="section-title"><i class="fa-solid fa-book-open"></i> Observador</div>
<?php if(empty($observaciones_hijos)): ?>
  <div class="card"><div class="empty-state"><i class="fa-solid fa-check-circle"></i><p>Sin observaciones registradas 🎉</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Estudiante</th><th>Materia</th><th>Docente</th><th>Descripción</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($observaciones_hijos as $o): ?>
        <tr>
          <td><?= htmlspecialchars($o['estudiante']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($o['materia']) ?></span></td>
          <td><?= htmlspecialchars($o['docente']) ?></td>
          <td><?= htmlspecialchars(substr($o['descripcion'],0,100)) ?></td>
          <td><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php elseif($modulo === 'citaciones'): ?>
<div class="section-title"><i class="fa-solid fa-calendar-check"></i> Citaciones</div>
<?php if(empty($citaciones_hijos)): ?>
  <div class="card"><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay citaciones registradas</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Estudiante</th><th>Motivo</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($citaciones_hijos as $ci): ?>
        <tr>
          <td><?= htmlspecialchars($ci['estudiante']) ?></td>
          <td><?= htmlspecialchars($ci['motivo']) ?></td>
          <td><?= date('d/m/Y', strtotime($ci['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php elseif($modulo === 'boletines'): ?>
<div class="section-title"><i class="fa-solid fa-file-invoice"></i> Boletines</div>
<?php if(empty($boletines_hijos)): ?>
  <div class="card"><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay boletines generados aún</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Estudiante</th><th>Periodo</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($boletines_hijos as $b): ?>
        <tr>
          <td><?= htmlspecialchars($b['estudiante']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($b['periodo']) ?></span></td>
          <td><?= date('d/m/Y', strtotime($b['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php
echo "</div></div></div>";
layout_close();
?>
