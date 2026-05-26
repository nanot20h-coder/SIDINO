<?php
require_once 'auth.php';
require_auth([4]);

$pdo = getDB();
$id_docente = $_SESSION['user_id'];

// Asignaciones del docente
$asignaciones = $pdo->query("
    SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso,
           s.nombre AS salon, h.dia, h.hora_inicio, h.hora_fin, p.nombre AS periodo
    FROM asignacion_academica aa
    JOIN materia m ON aa.id_materia = m.id_materia
    JOIN curso c   ON aa.id_curso   = c.id_curso
    JOIN salon s   ON aa.id_salon   = s.id_salon
    JOIN horario h ON aa.id_horario = h.id_horario
    JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
    WHERE aa.id_docente = $id_docente
    ORDER BY h.dia, h.hora_inicio
")->fetchAll();

$ids_asig = array_column($asignaciones, 'id_asignacion');

// Notas registradas por el docente
$notas = [];
if (!empty($ids_asig)) {
    $ph = implode(',', $ids_asig);
    $notas = $pdo->query("
        SELECT n.id_nota, n.valor, n.tipo, n.porcentaje, n.fecha,
               u.nombre AS estudiante, m.nombre AS materia, c.nombre AS curso
        FROM nota n
        JOIN matricula mat ON n.id_matricula = mat.id_matricula
        JOIN usuario u     ON mat.id_estudiante = u.id_usuario
        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c   ON aa.id_curso   = c.id_curso
        WHERE aa.id_docente = $id_docente
        ORDER BY n.fecha DESC LIMIT 30
    ")->fetchAll();
}

// Observaciones registradas
$observaciones = [];
if (!empty($ids_asig)) {
    $ph = implode(',', $ids_asig);
    $observaciones = $pdo->query("
        SELECT o.descripcion, o.fecha, u.nombre AS estudiante, c.nombre AS curso
        FROM observador o
        JOIN usuario u ON o.id_estudiante = u.id_usuario
        JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
        JOIN curso c ON aa.id_curso = c.id_curso
        WHERE o.id_asignacion IN ($ph)
        ORDER BY o.fecha DESC LIMIT 15
    ")->fetchAll();
}

// Contenidos subidos
$contenidos = [];
if (!empty($ids_asig)) {
    $ph = implode(',', $ids_asig);
    $contenidos = $pdo->query("
        SELECT co.titulo, co.descripcion, co.archivo, m.nombre AS materia
        FROM contenido co
        JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        WHERE co.id_asignacion IN ($ph)
        ORDER BY co.id_contenido DESC LIMIT 15
    ")->fetchAll();
}

// Ingreso de nota
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_nota'])) {
    $id_mat = (int)$_POST['id_matricula'];
    $valor  = (float)str_replace(',', '.', $_POST['valor']);
    $tipo   = trim($_POST['tipo']);
    $porc   = (float)$_POST['porcentaje'];
    $fecha  = $_POST['fecha'];
    if ($id_mat && $valor >= 0 && $valor <= 5 && $tipo && $porc > 0 && $fecha) {
        $pdo->prepare("INSERT INTO nota (id_matricula, valor, tipo, porcentaje, fecha) VALUES (?,?,?,?,?)")
            ->execute([$id_mat, $valor, $tipo, $porc, $fecha]);
        $msg = 'success:Nota registrada correctamente.';
    } else {
        $msg = 'error:Verifica los datos ingresados.';
    }
}

// Ingreso de observación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_obs'])) {
    $id_asig = (int)$_POST['id_asignacion'];
    $id_est  = (int)$_POST['id_estudiante'];
    $desc    = trim($_POST['descripcion']);
    $fecha   = $_POST['fecha_obs'];
    if ($id_asig && $id_est && $desc && $fecha) {
        $pdo->prepare("INSERT INTO observador (id_estudiante, id_asignacion, descripcion, fecha) VALUES (?,?,?,?)")
            ->execute([$id_est, $id_asig, $desc, $fecha]);
        $msg = 'success:Observación registrada.';
    } else {
        $msg = 'error:Completa todos los campos.';
    }
}

// Listado de matrículas del docente para formulario de notas
$matriculas_form = [];
if (!empty($ids_asig)) {
    $ph = implode(',', $ids_asig);
    $matriculas_form = $pdo->query("
        SELECT mat.id_matricula, u.nombre AS estudiante, m.nombre AS materia, c.nombre AS curso
        FROM matricula mat
        JOIN usuario u ON mat.id_estudiante = u.id_usuario
        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c ON aa.id_curso = c.id_curso
        WHERE mat.id_asignacion IN ($ph)
        ORDER BY c.nombre, u.nombre
    ")->fetchAll();
}

// Estudiantes por asignación para observaciones
$estudiantes_por_asig = [];
if (!empty($ids_asig)) {
    $ph = implode(',', $ids_asig);
    $rows = $pdo->query("
        SELECT mat.id_asignacion, u.id_usuario, u.nombre
        FROM matricula mat JOIN usuario u ON mat.id_estudiante = u.id_usuario
        WHERE mat.id_asignacion IN ($ph)
        ORDER BY u.nombre
    ")->fetchAll();
    foreach ($rows as $r) {
        $estudiantes_por_asig[$r['id_asignacion']][] = $r;
    }
}

$color = '#38bdf8';
layout_head('Docente — Dashboard');
echo "<div class='app'>";
echo sidebar_html('dashboard', [
    ['id'=>'dashboard',  'href'=>'docente.php',                'icon'=>'fa-gauge',       'label'=>'Dashboard'],
    ['id'=>'horario',    'href'=>'docente.php?m=horario',      'icon'=>'fa-calendar-days','label'=>'Mis clases'],
    ['id'=>'notas',      'href'=>'docente.php?m=notas',        'icon'=>'fa-star',        'label'=>'Registrar notas'],
    ['id'=>'observador', 'href'=>'docente.php?m=observador',   'icon'=>'fa-book-open',   'label'=>'Observador'],
    ['id'=>'contenido',  'href'=>'docente.php?m=contenido',    'icon'=>'fa-folder-open', 'label'=>'Contenido'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Docente — Panel Académico', $color);
echo "<div class='module-container'>";

$modulo = $_GET['m'] ?? 'dashboard';

// Mostrar mensaje flash
if ($msg) {
    [$tipo_msg, $texto_msg] = explode(':', $msg, 2);
    echo "<div class='alert {$tipo_msg}'><i class='fa-solid fa-circle-check'></i> {$texto_msg}</div>";
}

if ($modulo === 'dashboard'):
?>
<div class="welcome-banner">
  <div class="welcome-octopus">🐙</div>
  <div>
    <div class="welcome-title">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class="welcome-sub">Panel Docente — Tus clases y estudiantes</div>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-calendar-days"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($asignaciones) ?></div><div class="stat-label">Clases asignadas</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-users"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($matriculas_form) ?></div><div class="stat-label">Estudiantes a cargo</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-star"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($notas) ?></div><div class="stat-label">Notas registradas</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-book-open"></i></div>
    <div class="stat-body"><div class="stat-value"><?= count($observaciones) ?></div><div class="stat-label">Observaciones</div></div>
  </div>
</div>

<div class="content-grid">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-calendar-days"></i> Mis clases</div>
      <a href="docente.php?m=horario" class="card-action">Ver horario</a>
    </div>
    <?php if(empty($asignaciones)): ?>
      <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No tienes clases asignadas aún</p></div>
    <?php else: ?>
    <?php foreach($asignaciones as $a): ?>
      <div class="list-item">
        <div class="stat-icon blue"><i class="fa-solid fa-book"></i></div>
        <div class="list-body">
          <div class="list-title"><?= htmlspecialchars($a['materia']) ?> — <span class="badge blue"><?= htmlspecialchars($a['curso']) ?></span></div>
          <div class="list-sub"><?= htmlspecialchars($a['dia']) ?> · <?= substr($a['hora_inicio'],0,5) ?> – <?= substr($a['hora_fin'],0,5) ?></div>
        </div>
        <div class="list-meta"><?= htmlspecialchars($a['salon']) ?></div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-star"></i> Notas recientes</div>
      <a href="docente.php?m=notas" class="card-action">Registrar nota</a>
    </div>
    <?php if(empty($notas)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No has registrado notas aún</p></div>
    <?php else: ?>
    <?php foreach(array_slice($notas, 0, 5) as $n): 
      $cls = $n['valor'] >= 3.5 ? 'nota-alta' : ($n['valor'] >= 3.0 ? 'nota-media' : 'nota-baja');
    ?>
      <div class="list-item">
        <div class="list-body">
          <div class="list-title"><?= htmlspecialchars($n['estudiante']) ?></div>
          <div class="list-sub"><?= htmlspecialchars($n['materia']) ?> · <?= htmlspecialchars($n['tipo']) ?></div>
        </div>
        <div class="list-meta"><span class="<?= $cls ?>"><?= number_format($n['valor'],1) ?></span></div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'horario'): ?>
<div class="section-title"><i class="fa-solid fa-calendar-days"></i> Mi Horario de Clases</div>
<div class="card">
  <?php if(empty($asignaciones)): ?>
    <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No tienes clases asignadas</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Materia</th><th>Curso</th><th>Salón</th><th>Día</th><th>Hora inicio</th><th>Hora fin</th><th>Periodo</th></tr></thead>
      <tbody>
      <?php foreach($asignaciones as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['materia']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($a['curso']) ?></span></td>
          <td><?= htmlspecialchars($a['salon']) ?></td>
          <td><?= htmlspecialchars($a['dia']) ?></td>
          <td><?= substr($a['hora_inicio'],0,5) ?></td>
          <td><?= substr($a['hora_fin'],0,5) ?></td>
          <td><?= htmlspecialchars($a['periodo']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif($modulo === 'notas'): ?>
<div class="section-title"><i class="fa-solid fa-star"></i> Registrar Notas</div>
<?php if(!empty($matriculas_form)): ?>
<div class="card" style="margin-bottom:1.25rem">
  <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-plus"></i> Nueva nota</div>
  <form method="POST">
    <div class="form-inline">
      <div class="field-group">
        <label class="field-label">Estudiante / Materia</label>
        <select name="id_matricula" class="field-input" required>
          <option value="">— Selecciona —</option>
          <?php foreach($matriculas_form as $mat): ?>
            <option value="<?= $mat['id_matricula'] ?>"><?= htmlspecialchars($mat['estudiante']) ?> · <?= htmlspecialchars($mat['materia']) ?> (<?= htmlspecialchars($mat['curso']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field-group" style="max-width:110px">
        <label class="field-label">Nota (0–5)</label>
        <input type="number" name="valor" class="field-input" min="0" max="5" step="0.1" placeholder="4.5" required/>
      </div>
      <div class="field-group">
        <label class="field-label">Tipo</label>
        <input type="text" name="tipo" class="field-input" placeholder="Examen parcial" required/>
      </div>
      <div class="field-group" style="max-width:110px">
        <label class="field-label">Porcentaje</label>
        <input type="number" name="porcentaje" class="field-input" min="1" max="100" placeholder="30" required/>
      </div>
      <div class="field-group" style="max-width:150px">
        <label class="field-label">Fecha</label>
        <input type="date" name="fecha" class="field-input" value="<?= date('Y-m-d') ?>" required/>
      </div>
    </div>
    <button type="submit" name="registrar_nota" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar nota</button>
  </form>
</div>
<?php endif; ?>
<div class="card">
  <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-list"></i> Notas registradas</div>
  <?php if(empty($notas)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No has registrado notas aún</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Estudiante</th><th>Materia</th><th>Curso</th><th>Tipo</th><th class="nota-cell">Nota</th><th>%</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($notas as $n):
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
  <?php endif; ?>
</div>

<?php elseif($modulo === 'observador'): ?>
<div class="section-title"><i class="fa-solid fa-book-open"></i> Observador Estudiantil</div>
<?php if(!empty($asignaciones)): ?>
<div class="card" style="margin-bottom:1.25rem">
  <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-plus"></i> Nueva observación</div>
  <form method="POST">
    <div class="form-inline">
      <div class="field-group">
        <label class="field-label">Clase</label>
        <select name="id_asignacion" id="selAsig" class="field-input" required onchange="this.form.submit()">
          <option value="">— Selecciona clase —</option>
          <?php foreach($asignaciones as $a): $sel_asig = (int)($_POST['id_asignacion'] ?? 0); ?>
            <option value="<?= $a['id_asignacion'] ?>" <?= $sel_asig == $a['id_asignacion'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['materia']) ?> — <?= htmlspecialchars($a['curso']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php $sel_asig = (int)($_POST['id_asignacion'] ?? 0);
            $ests = $estudiantes_por_asig[$sel_asig] ?? [];
            if (!empty($ests)): ?>
      <div class="field-group">
        <label class="field-label">Estudiante</label>
        <select name="id_estudiante" class="field-input" required>
          <option value="">— Selecciona —</option>
          <?php foreach($ests as $e): ?>
            <option value="<?= $e['id_usuario'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field-group" style="max-width:160px">
        <label class="field-label">Fecha</label>
        <input type="date" name="fecha_obs" class="field-input" value="<?= date('Y-m-d') ?>" required/>
      </div>
    </div>
    <div class="field-group" style="margin-bottom:.75rem">
      <label class="field-label">Descripción</label>
      <textarea name="descripcion" class="field-input" rows="3" placeholder="Describe el comportamiento o situación..." required style="resize:vertical"></textarea>
    </div>
    <button type="submit" name="registrar_obs" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar observación</button>
      <?php endif; ?>
  </form>
</div>
<?php endif; ?>
<div class="card">
  <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-list"></i> Observaciones registradas</div>
  <?php if(empty($observaciones)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No has registrado observaciones aún</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Estudiante</th><th>Curso</th><th>Descripción</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($observaciones as $o): ?>
        <tr>
          <td><?= htmlspecialchars($o['estudiante']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($o['curso']) ?></span></td>
          <td><?= htmlspecialchars(substr($o['descripcion'],0,90)) ?>...</td>
          <td><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif($modulo === 'contenido'): ?>
<div class="section-title"><i class="fa-solid fa-folder-open"></i> Contenido Académico</div>
<div class="card">
  <?php if(empty($contenidos)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No has subido contenidos aún</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Título</th><th>Materia</th><th>Descripción</th></tr></thead>
      <tbody>
      <?php foreach($contenidos as $co): ?>
        <tr>
          <td><?= htmlspecialchars($co['titulo']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($co['materia']) ?></span></td>
          <td><?= htmlspecialchars(substr($co['descripcion'] ?? '', 0, 80)) ?></td>
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
