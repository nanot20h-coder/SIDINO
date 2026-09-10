<?php
// docente.php
require_once 'auth.php';
require_auth([4]);

$pdo = getDB();
$id_docente = $_SESSION['user_id'];
$msg = '';

function ensureContenidoActivityColumns(PDO $pdo): void {
    $columns = $pdo->query("SHOW COLUMNS FROM contenido")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('tipo', $columns, true)) {
        $pdo->exec("ALTER TABLE contenido ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT 'material' AFTER archivo");
    }
    if (!in_array('fecha_entrega', $columns, true)) {
        $pdo->exec("ALTER TABLE contenido ADD COLUMN fecha_entrega DATE NULL AFTER tipo");
    }
}

ensureContenidoActivityColumns($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_actividad'])) {
    $id_asignacion = (int)($_POST['id_asignacion'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_entrega = $_POST['fecha_entrega'] ?? null;

    if ($id_asignacion && $titulo && $descripcion) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM asignacion_academica WHERE id_asignacion = ? AND id_docente = ?');
        $check->execute([$id_asignacion, $id_docente]);

        if ($check->fetchColumn()) {
            $stmt = $pdo->prepare('INSERT INTO contenido (titulo, descripcion, archivo, tipo, fecha_entrega, id_asignacion) VALUES (?, ?, NULL, ?, ?, ?)');
            $stmt->execute([$titulo, $descripcion, 'actividad', $fecha_entrega ?: null, $id_asignacion]);
            $msg = 'success:Actividad publicada correctamente. Los estudiantes ya pueden verla desde la clase.';
        } else {
            $msg = 'error:La clase seleccionada no está asignada a este docente.';
        }
    } else {
        $msg = 'error:Completa el título, la clase y la descripción de la actividad.';
    }
}


// 1. Ingreso de nota desde generar_nota.php O desde el formulario interno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['registrar_nota']) || (isset($_POST['origen']) && $_POST['origen'] === 'generar_nota'))) {
    $id_mat = (int)($_POST['id_matricula'] ?? 0);
    $valor  = (float)str_replace(',', '.', $_POST['valor'] ?? $_POST['nota'] ?? '');
    $tipo   = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'Actividad Externa';
    $porc   = isset($_POST['porcentaje']) ? (float)$_POST['porcentaje'] : 20.0;
    $fecha  = $_POST['fecha'] ?? date('Y-m-d');

    $matricula_check = $pdo->prepare("SELECT COUNT(*) FROM matricula mat JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion WHERE mat.id_matricula = ? AND aa.id_docente = ?");
    $matricula_check->execute([$id_mat, $id_docente]);

    if ($matricula_check->fetchColumn() && $valor >= 0 && $valor <= 5 && $tipo && $porc > 0 && $porc <= 100 && $fecha) {
        try {
            $pdo->prepare("INSERT INTO nota (id_matricula, valor, tipo, porcentaje, fecha) VALUES (?,?,?,?,?)")
                ->execute([$id_mat, $valor, $tipo, $porc, $fecha]);
            $msg = 'success:Nota registrada correctamente en el sistema.';
            // Forzar redirección limpia a la pestaña de notas para ver los cambios
            header("Location: generar_nota.php?msg_success=Nota registrada con éxito");
            exit;
        } catch (Exception $e) {
            $msg = 'error:Error en la base de datos al guardar la nota.';
        }
    } else {
        $msg = 'error:Verifica los datos ingresados. La nota debe estar entre 0 y 5.';
    }
}

// 2. Ingreso de observación tradicional
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_obs'])) {
    $id_asig = (int)$_POST['id_asignacion'];
    $id_est  = (int)$_POST['id_estudiante'];
    $desc    = trim($_POST['descripcion']);
    $fecha   = $_POST['fecha_obs'];
    if ($id_asig && $id_est && $desc && $fecha) {
        $pdo->prepare("INSERT INTO observador (id_estudiante, id_asignacion, descripcion, fecha) VALUES (?,?,?,?)")
            ->execute([$id_est, $id_asig, $desc, $fecha]);
        $msg = 'success:Observación registrada con éxito.';
    } else {
        $msg = 'error:Completa todos los campos obligatorios del observador.';
    }
}


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

// Notas registradas
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

// Actividades y contenidos subidos
$actividades = [];
$contenidos = [];
if (!empty($ids_asig)) {
    $ph = implode(',', $ids_asig);
    $actividades = $pdo->query("
        SELECT co.id_contenido, co.titulo, co.descripcion, co.archivo, co.tipo, co.fecha_entrega,
               m.nombre AS materia, c.nombre AS curso
        FROM contenido co
        JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c ON aa.id_curso = c.id_curso
        WHERE co.id_asignacion IN ($ph) AND co.tipo = 'actividad'
        ORDER BY co.fecha_entrega IS NULL, co.fecha_entrega DESC, co.id_contenido DESC
    ")->fetchAll();

    $contenidos = $pdo->query("
        SELECT co.id_contenido, co.titulo, co.descripcion, co.archivo, co.tipo, co.fecha_entrega,
               m.nombre AS materia, c.nombre AS curso
        FROM contenido co
        JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c ON aa.id_curso = c.id_curso
        WHERE co.id_asignacion IN ($ph)
        ORDER BY co.id_contenido DESC LIMIT 30
    ")->fetchAll();
}

// Listado de matrículas del docente para el formulario interno
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

// Estudiantes organizados por asignación
$estudiantes_por_asig = [];
if (!empty($ids_asig)) {
    $ph = implode(',', $ids_asig);
    $rows = $pdo->query("
        SELECT mat.id_asignacion, u.id_usuario, u.nombre
        FROM matricula mat 
        JOIN usuario u ON mat.id_estudiante = u.id_usuario
        WHERE mat.id_asignacion IN ($ph)
        ORDER BY u.nombre
    ")->fetchAll();
    foreach ($rows as $r) {
        $estudiantes_por_asig[$r['id_asignacion']][] = $r;
    }
}

// Preparar datos para el calendario
$eventos_calendario = [];
$dias_semana = [
    'Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 
    'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6, 'Domingo' => 7
];

foreach ($asignaciones as $a) {
    $dia_num = $dias_semana[$a['dia']] ?? 0;
    $hora_inicio = substr($a['hora_inicio'], 0, 5);
    $hora_fin = substr($a['hora_fin'], 0, 5);
    
    $eventos_calendario[] = [
        'dia' => $dia_num,
        'hora_inicio' => $hora_inicio,
        'hora_fin' => $hora_fin,
        'materia' => $a['materia'],
        'curso' => $a['curso'],
        'salon' => $a['salon'],
        'color' => 'blue'
    ];
}

// Obtener mes y año actual para el calendario
$mes_actual = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio_actual = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// Ajustar mes y año si están fuera de rango
if ($mes_actual < 1) {
    $mes_actual = 12;
    $anio_actual--;
} elseif ($mes_actual > 12) {
    $mes_actual = 1;
    $anio_actual++;
}

$primer_dia_mes = mktime(0, 0, 0, $mes_actual, 1, $anio_actual);
$dias_en_mes = date('t', $primer_dia_mes);
$dia_semana_inicio = date('N', $primer_dia_mes); // 1 (Lunes) a 7 (Domingo)
$mes_anterior = $mes_actual - 1;
$anio_mes_anterior = $anio_actual;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $anio_mes_anterior--;
}
$mes_siguiente = $mes_actual + 1;
$anio_mes_siguiente = $anio_actual;
if ($mes_siguiente > 12) {
    $mes_siguiente = 1;
    $anio_mes_siguiente++;
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$color = '#20a7e0';
$pagina_actual = basename($_SERVER['PHP_SELF']);
$menu_activo = [
  'horario.php' => 'horario',
  'asistencia.php' => 'asistencia',
  'generar_nota.php' => 'notas',
  'observador.php' => 'observador',
  'contenido.php' => 'contenido',
][$pagina_actual] ?? 'dashboard';
layout_head('Docente — Dashboard');

echo "<div class='app'>";
echo sidebar_html($menu_activo, [
  ['id'=>'dashboard',  'href'=>'docente.php',       'icon'=>'fa-gauge',        'label'=>'Dashboard'],
  ['id'=>'asistencia', 'href'=>'asistencia.php',    'icon'=>'fa-user-check',  'label'=>'Asistencia'],
  ['id'=>'horario',    'href'=>'horario.php',       'icon'=>'fa-calendar-days','label'=>'Mis clases'],
  ['id'=>'notas',      'href'=>'generar_nota.php',         'icon'=>'fa-star',         'label'=>'Registrar notas'],
  ['id'=>'observador', 'href'=>'observador.php',    'icon'=>'fa-book-open',    'label'=>'Observador'],
  ['id'=>'contenido',  'href'=>'contenido.php',     'icon'=>'fa-folder-open',  'label'=>'Contenido'],
]);

echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Docente — Panel Académico', $color);
echo "<div class='module-container'>";

$modulo = $_GET['m'] ?? 'dashboard';

// Capturar e imprimir alertas de mensajes redireccionados
if (isset($_GET['msg_success'])) {
    echo "<div class='alert success'><i class='fa-solid fa-circle-check'></i> ".htmlspecialchars($_GET['msg_success'])."</div>";
}

if ($msg) {
    [$tipo_msg, $texto_msg] = explode(':', $msg, 2);
    echo "<div class='alert {$tipo_msg}'><i class='fa-solid fa-circle-info'></i> {$texto_msg}</div>";
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
      <a href="horario.php" class="card-action">Ver horario</a>
    </div>
    <?php if(empty($asignaciones)): ?>
      <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No tienes clases asignadas aún</p></div>
    <?php else: ?>
        <?php foreach(array_slice($asignaciones, 0, 5) as $a): ?>
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
      <a href="generar_nota.php" class="card-action">Registrar nota</a>
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

<!-- CALENDARIO AGREGADO DEBAJO DEL CUADRO DE MIS CLASES -->
<div class="card" style="margin-top: 1.5rem;">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-calendar"></i> Calendario Académico</div>
    <div class="calendar-nav">
      <a href="docente.php?mes=<?= $mes_anterior ?>&anio=<?= $anio_mes_anterior ?>" class="btn btn-small btn-outline">
        <i class="fa-solid fa-chevron-left"></i>
      </a>
      <span class="calendar-month"><?= $meses[$mes_actual] ?> <?= $anio_actual ?></span>
      <a href="docente.php?mes=<?= $mes_siguiente ?>&anio=<?= $anio_mes_siguiente ?>" class="btn btn-small btn-outline">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
    </div>
  </div>
  
  <div class="calendar-container">
    <div class="calendar-grid">
      <div class="calendar-weekdays">
        <div class="weekday">Lun</div>
        <div class="weekday">Mar</div>
        <div class="weekday">Mié</div>
        <div class="weekday">Jue</div>
        <div class="weekday">Vie</div>
        <div class="weekday">Sáb</div>
        <div class="weekday">Dom</div>
      </div>
      
      <div class="calendar-days">
        <?php
        // Espacios vacíos antes del primer día del mes
        for ($i = 1; $i < $dia_semana_inicio; $i++): ?>
          <div class="calendar-day empty"></div>
        <?php endfor;
        
        // Días del mes
        for ($dia = 1; $dia <= $dias_en_mes; $dia++):
          $fecha_actual = sprintf('%04d-%02d-%02d', $anio_actual, $mes_actual, $dia);
          $dia_semana = date('N', strtotime($fecha_actual));
          $es_hoy = ($fecha_actual === date('Y-m-d'));
          $tiene_clases = false;
          $clases_del_dia = [];
          
          // Verificar si hay clases en este día
          foreach ($eventos_calendario as $evento) {
            if ($evento['dia'] == $dia_semana) {
              $tiene_clases = true;
              $clases_del_dia[] = $evento;
            }
          }
        ?>
          <div class="calendar-day <?= $es_hoy ? 'today' : '' ?> <?= $tiene_clases ? 'has-class' : '' ?>">
            <span class="day-number"><?= $dia ?></span>
            <?php if ($tiene_clases): ?>
              <div class="class-indicators">
                <?php foreach ($clases_del_dia as $clase): ?>
                  <div class="class-dot" title="<?= htmlspecialchars($clase['materia']) ?> - <?= htmlspecialchars($clase['curso']) ?> (<?= $clase['hora_inicio'] ?>-<?= $clase['hora_fin'] ?>)">
                    <span class="class-time"><?= $clase['hora_inicio'] ?></span>
                    <span class="class-name"><?= htmlspecialchars(substr($clase['materia'], 0, 12)) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endfor;
        
        // Completar la última semana si es necesario
        $total_celdas = $dia_semana_inicio + $dias_en_mes - 1;
        $celdas_restantes = 7 - ($total_celdas % 7);
        if ($celdas_restantes < 7):
          for ($i = 0; $i < $celdas_restantes; $i++): ?>
            <div class="calendar-day empty"></div>
        <?php endfor;
        endif; ?>
      </div>
    </div>
  </div>
  
  <div class="calendar-legend">
    <div class="legend-item">
      <div class="legend-color class-color"></div>
      <span>Días con clase</span>
    </div>
    <div class="legend-item">
      <div class="legend-color today-color"></div>
      <span>Hoy</span>
    </div>
  </div>
</div>

<style>
.calendar-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.calendar-month {
  font-weight: 600;
  color: var(--text-primary);
  min-width: 120px;
  text-align: center;
}

.calendar-container {
  padding: 1rem;
}

.calendar-grid {
  border: 1px solid var(--border-color);
  border-radius: 8px;
  overflow: hidden;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
}

.weekday {
  padding: 0.5rem;
  text-align: center;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
  min-height: 80px;
  padding: 0.5rem;
  border-right: 1px solid var(--border-color);
  border-bottom: 1px solid var(--border-color);
  background: white;
  transition: background 0.2s;
}

.calendar-day:nth-child(7n) {
  border-right: none;
}

.calendar-day.empty {
  background: var(--bg-secondary);
}

.calendar-day.today {
  background: #76aaee;
  border: 2px solid #3b82f6;
}

.calendar-day.has-class {
  background: #5cb2e4;
}

.calendar-day.today.has-class {
  background: linear-gradient(135deg, #3c90ff 0%, #40b5ca 100%);
}

.day-number {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
  display: block;
  margin-bottom: 0.25rem;
}

.calendar-day.today .day-number {
  color: #3b82f6;
  font-weight: 700;
}

.class-indicators {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.class-dot {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 2px 4px;
  background: #85b0e9;
  border-radius: 4px;
  font-size: 0.625rem;
  cursor: help;
  transition: background 0.2s;
}

.class-dot:hover {
  background: #6fa3e2;
}

.class-time {
  font-weight: 600;
  color: #1e40af;
}

.class-name {
  color: #1e40af;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.calendar-legend {
  display: flex;
  gap: 1.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--border-color);
  background: var(--bg-secondary);
  border-radius: 0 0 8px 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 3px;
}

.class-color {
  background: #67a0eb;
  border: 1px solid #93c5fd;
}

.today-color {
  background: #000102;
  border: 2px solid #3b82f6;
}

@media (max-width: 768px) {
  .calendar-day {
    min-height: 60px;
    padding: 0.25rem;
  }
  
  .class-dot {
    font-size: 0.55rem;
  }
  
  .class-name {
    display: none;
  }
}
</style>

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
  <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-plus"></i> Nueva nota académica</div>
  <form method="POST" action="generar_nota.php">
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
  <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-list"></i> Historial de notas calificadas</div>
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
  <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-plus"></i> Nueva observación convivencial</div>
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
<div class="section-title"><i class="fa-solid fa-folder-open"></i> Contenido y actividades académicas</div>

<div class="card" style="margin-bottom:1.25rem;">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-clipboard-list"></i> Publicar nueva actividad</div>
  </div>
  <form method="POST" action="contenido.php?m=contenido" style="padding:1.2rem;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;">
    <div class="field-group" style="grid-column:1 / -1;">
      <label class="field-label">Título de la actividad</label>
      <input type="text" name="titulo" class="field-input" placeholder="Ej: Taller de lectura y análisis" required>
    </div>
    <div class="field-group">
      <label class="field-label">Clase asignada</label>
      <select name="id_asignacion" class="field-input" required>
        <option value="">Seleccionar clase...</option>
        <?php foreach($asignaciones as $asignacion): ?>
          <option value="<?= $asignacion['id_asignacion'] ?>"><?= htmlspecialchars($asignacion['materia']) ?> · <?= htmlspecialchars($asignacion['curso']) ?> (<?= htmlspecialchars($asignacion['dia']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-group">
      <label class="field-label">Fecha de entrega</label>
      <input type="date" name="fecha_entrega" class="field-input">
    </div>
    <div class="field-group" style="grid-column:1 / -1;">
      <label class="field-label">Descripción de la actividad</label>
      <textarea name="descripcion" class="field-input" rows="5" placeholder="Describe la actividad, instrucciones, recursos o criterio de evaluación..." required></textarea>
    </div>
    <div style="grid-column:1 / -1;display:flex;gap:.8rem;align-items:center;">
      <button type="submit" name="guardar_actividad" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publicar actividad</button>
      <span style="color:var(--text-muted);font-size:0.8rem;">Visible para los estudiantes de la clase asignada.</span>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-list-check"></i> Actividades publicadas</div>
  </div>
  <?php if(empty($actividades)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Aún no has publicado actividades para tus estudiantes.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Clase</th><th>Título</th><th>Descripción</th><th>Entrega</th></tr></thead>
      <tbody>
      <?php foreach($actividades as $actividad): ?>
        <tr>
          <td><span class="badge blue"><?= htmlspecialchars($actividad['curso']) ?></span><br><?= htmlspecialchars($actividad['materia']) ?></td>
          <td><?= htmlspecialchars($actividad['titulo']) ?></td>
          <td><?= htmlspecialchars(substr($actividad['descripcion'] ?? '', 0, 100)) ?><?= strlen($actividad['descripcion'] ?? '') > 100 ? '...' : '' ?></td>
          <td><?= $actividad['fecha_entrega'] ? date('d/m/Y', strtotime($actividad['fecha_entrega'])) : 'Sin fecha' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:1.25rem;">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-book-open-reader"></i> Materiales y recursos</div>
  </div>
  <?php if(empty($contenidos)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No has subido recursos aún.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Clase</th><th>Título</th><th>Tipo</th><th>Descripción</th></tr></thead>
      <tbody>
      <?php foreach($contenidos as $co): ?>
        <tr>
          <td><span class="badge blue"><?= htmlspecialchars($co['curso']) ?></span></td>
          <td><?= htmlspecialchars($co['titulo']) ?></td>
          <td><?= htmlspecialchars($co['tipo'] ?? 'material') ?></td>
          <td><?= htmlspecialchars(substr($co['descripcion'] ?? '', 0, 90)) ?></td>
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