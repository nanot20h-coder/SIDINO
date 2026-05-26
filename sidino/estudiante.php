<?php
require_once 'auth.php';
require_auth([5]);

$pdo = getDB();
$id_est = $_SESSION['user_id'];

// Matrículas del estudiante
$matriculas = $pdo->query("
    SELECT mat.id_matricula, m.nombre AS materia, c.nombre AS curso,
           u.nombre AS docente, h.dia, h.hora_inicio, h.hora_fin,
           s.nombre AS salon, p.nombre AS periodo
    FROM matricula mat
    JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
    JOIN materia m  ON aa.id_materia = m.id_materia
    JOIN curso c    ON aa.id_curso   = c.id_curso
    JOIN usuario u  ON aa.id_docente = u.id_usuario
    JOIN horario h  ON aa.id_horario = h.id_horario
    JOIN salon s    ON aa.id_salon   = s.id_salon
    JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
    WHERE mat.id_estudiante = $id_est
    ORDER BY m.nombre
")->fetchAll();

// Notas del estudiante
$notas = $pdo->query("
    SELECT n.valor, n.tipo, n.porcentaje, n.fecha,
           m.nombre AS materia, c.nombre AS curso
    FROM nota n
    JOIN matricula mat ON n.id_matricula = mat.id_matricula
    JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
    JOIN materia m ON aa.id_materia = m.id_materia
    JOIN curso c   ON aa.id_curso   = c.id_curso
    WHERE mat.id_estudiante = $id_est
    ORDER BY n.fecha DESC
")->fetchAll();

// Promedios por materia
$promedios = $pdo->query("
    SELECT m.nombre AS materia,
           ROUND(AVG(n.valor * n.porcentaje / 100) / AVG(n.porcentaje / 100), 2) AS promedio,
           COUNT(n.id_nota) AS total_notas
    FROM nota n
    JOIN matricula mat ON n.id_matricula = mat.id_matricula
    JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
    JOIN materia m ON aa.id_materia = m.id_materia
    WHERE mat.id_estudiante = $id_est
    GROUP BY m.id_materia
    ORDER BY m.nombre
")->fetchAll();

// Boletines
$boletines = $pdo->query("
    SELECT b.id_boletin, b.fecha, p.nombre AS periodo
    FROM boletin b JOIN periodo_academico p ON b.id_periodo = p.id_periodo
    WHERE b.id_estudiante = $id_est
    ORDER BY b.fecha DESC
")->fetchAll();

// Observaciones propias
$observaciones = $pdo->query("
    SELECT o.descripcion, o.fecha, d.nombre AS docente, m.nombre AS materia
    FROM observador o
    JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
    JOIN usuario d ON aa.id_docente = d.id_usuario
    JOIN materia m ON aa.id_materia = m.id_materia
    WHERE o.id_estudiante = $id_est
    ORDER BY o.fecha DESC
")->fetchAll();

// Contenido de mis materias
$contenidos = $pdo->query("
    SELECT co.titulo, co.descripcion, co.archivo, m.nombre AS materia, u.nombre AS docente
    FROM contenido co
    JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
    JOIN materia m ON aa.id_materia = m.id_materia
    JOIN usuario u ON aa.id_docente = u.id_usuario
    WHERE aa.id_asignacion IN (
        SELECT id_asignacion FROM matricula WHERE id_estudiante = $id_est
    )
    ORDER BY co.id_contenido DESC
")->fetchAll();

$color = '#34d399';
layout_head('Estudiante — Dashboard');
echo "<div class='app'>";
echo sidebar_html('dashboard', [
    ['id'=>'dashboard', 'href'=>'estudiante.php',              'icon'=>'fa-gauge',       'label'=>'Dashboard'],
    ['id'=>'notas',     'href'=>'estudiante.php?m=notas',      'icon'=>'fa-star',        'label'=>'Mis notas'],
    ['id'=>'horario',   'href'=>'estudiante.php?m=horario',    'icon'=>'fa-calendar-days','label'=>'Horario'],
    ['id'=>'boletines', 'href'=>'estudiante.php?m=boletines',  'icon'=>'fa-file-invoice', 'label'=>'Boletines'],
    ['id'=>'contenido', 'href'=>'estudiante.php?m=contenido',  'icon'=>'fa-folder-open', 'label'=>'Materiales'],
    ['id'=>'observador','href'=>'estudiante.php?m=observador', 'icon'=>'fa-book-open',   'label'=>'Observador'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Estudiante — Mi Panel', $color);
echo "<div class='module-container'>";

$modulo = $_GET['m'] ?? 'dashboard';

if ($modulo === 'dashboard'):
  $promedio_general = !empty($promedios) ? array_sum(array_column($promedios, 'promedio')) / count($promedios) : 0;
?>
<div class="welcome-banner">
  <div class="welcome-octopus">🐙</div>
  <div>
    <div class="welcome-title">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class="welcome-sub">Bienvenido a tu panel estudiantil — Aquí puedes ver tus notas y materiales</div>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-book"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($matriculas) ?></div><div class="stat-label">Materias matriculadas</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon <?= $promedio_general >= 3.5 ? 'green' : ($promedio_general >= 3.0 ? 'yellow' : 'red') ?>">
      <i class="fa-solid fa-star"></i>
    </div>
    <div class="stat-body">
      <div class="stat-value <?= $promedio_general >= 3.5 ? 'nota-alta' : ($promedio_general >= 3.0 ? 'nota-media' : 'nota-baja') ?>">
        <?= $promedio_general > 0 ? number_format($promedio_general,1) : '—' ?>
      </div>
      <div class="stat-label">Promedio general</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-file-invoice"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($boletines) ?></div><div class="stat-label">Boletines</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-folder-open"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($contenidos) ?></div><div class="stat-label">Materiales disponibles</div></div>
  </div>
</div>

<div class="content-grid">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-star"></i> Promedios por materia</div>
      <a href="estudiante.php?m=notas" class="card-action">Ver notas</a>
    </div>
    <?php if(empty($promedios)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Aún no tienes notas registradas</p></div>
    <?php else: ?>
    <?php foreach($promedios as $p):
      $pct = min(100, ($p['promedio'] / 5) * 100);
      $cls = $p['promedio'] >= 3.5 ? 'nota-alta' : ($p['promedio'] >= 3.0 ? 'nota-media' : 'nota-baja');
    ?>
      <div style="margin-bottom:.85rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
          <span style="font-size:.83rem;color:var(--text2)"><?= htmlspecialchars($p['materia']) ?></span>
          <span class="<?= $cls ?>" style="font-size:.85rem"><?= number_format($p['promedio'],1) ?></span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $p['promedio'] >= 3.5 ? 'linear-gradient(90deg,#34d399,#22d3ee)' : ($p['promedio'] >= 3.0 ? 'linear-gradient(90deg,#fbbf24,#fb923c)' : 'linear-gradient(90deg,#f87171,#fb923c)') ?>"></div></div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-folder-open"></i> Materiales recientes</div>
      <a href="estudiante.php?m=contenido" class="card-action">Ver todos</a>
    </div>
    <?php if(empty($contenidos)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay materiales disponibles aún</p></div>
    <?php else: ?>
    <?php foreach(array_slice($contenidos, 0, 5) as $co): ?>
      <div class="list-item">
        <div class="stat-icon blue"><i class="fa-solid fa-file"></i></div>
        <div class="list-body">
          <div class="list-title"><?= htmlspecialchars($co['titulo']) ?></div>
          <div class="list-sub"><?= htmlspecialchars($co['materia']) ?> · <?= htmlspecialchars($co['docente']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'notas'): ?>
<div class="section-title"><i class="fa-solid fa-star"></i> Mis Notas</div>
<?php if(empty($notas)): ?>
  <div class="card"><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No tienes notas registradas aún</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Materia</th><th>Curso</th><th>Tipo</th><th class="nota-cell">Nota</th><th>%</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($notas as $n):
        $cls = $n['valor'] >= 3.5 ? 'nota-alta' : ($n['valor'] >= 3.0 ? 'nota-media' : 'nota-baja');
      ?>
        <tr>
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

<?php elseif($modulo === 'horario'): ?>
<div class="section-title"><i class="fa-solid fa-calendar-days"></i> Mi Horario</div>
<div class="card">
  <?php if(empty($matriculas)): ?>
    <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No tienes clases matriculadas</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Materia</th><th>Docente</th><th>Curso</th><th>Salón</th><th>Día</th><th>Hora</th></tr></thead>
      <tbody>
      <?php foreach($matriculas as $mat): ?>
        <tr>
          <td><?= htmlspecialchars($mat['materia']) ?></td>
          <td><?= htmlspecialchars($mat['docente']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($mat['curso']) ?></span></td>
          <td><?= htmlspecialchars($mat['salon']) ?></td>
          <td><?= htmlspecialchars($mat['dia']) ?></td>
          <td><?= substr($mat['hora_inicio'],0,5) ?> – <?= substr($mat['hora_fin'],0,5) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif($modulo === 'boletines'): ?>
<div class="section-title"><i class="fa-solid fa-file-invoice"></i> Mis Boletines</div>
<div class="card">
  <?php if(empty($boletines)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay boletines generados aún</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Periodo</th><th>Fecha de emisión</th></tr></thead>
      <tbody>
      <?php foreach($boletines as $b): ?>
        <tr>
          <td><?= $b['id_boletin'] ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($b['periodo']) ?></span></td>
          <td><?= date('d/m/Y', strtotime($b['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif($modulo === 'contenido'): ?>
<div class="section-title"><i class="fa-solid fa-folder-open"></i> Materiales y Contenido</div>
<div class="card">
  <?php if(empty($contenidos)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay materiales disponibles aún</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Título</th><th>Materia</th><th>Docente</th><th>Descripción</th></tr></thead>
      <tbody>
      <?php foreach($contenidos as $co): ?>
        <tr>
          <td><?= htmlspecialchars($co['titulo']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($co['materia']) ?></span></td>
          <td><?= htmlspecialchars($co['docente']) ?></td>
          <td><?= htmlspecialchars(substr($co['descripcion'] ?? '', 0, 80)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif($modulo === 'observador'): ?>
<div class="section-title"><i class="fa-solid fa-book-open"></i> Mi Observador</div>
<div class="card">
  <?php if(empty($observaciones)): ?>
    <div class="empty-state"><i class="fa-solid fa-check-circle"></i><p>No tienes observaciones registradas 🎉</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Materia</th><th>Docente</th><th>Descripción</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($observaciones as $o): ?>
        <tr>
          <td><span class="badge blue"><?= htmlspecialchars($o['materia']) ?></span></td>
          <td><?= htmlspecialchars($o['docente']) ?></td>
          <td><?= htmlspecialchars(substr($o['descripcion'], 0, 100)) ?></td>
          <td><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
echo "</div></div></div>";
layout_close();
?>
