<?php
require_once 'auth.php';
require_auth([1]); // solo Rectoria

$pdo = getDB();

// ── Crear usuario (POST) ──
$msg_crear = '';
$msg_tipo  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_usuario') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $id_rol = (int)($_POST['id_rol'] ?? 0);
    $pass   = trim($_POST['contrasena'] ?? '');

    if (!$nombre || !$correo || !$id_rol || !$pass) {
        $msg_crear = 'Todos los campos son obligatorios.';
        $msg_tipo  = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $msg_crear = 'El correo ingresado no es válido.';
        $msg_tipo  = 'error';
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE correo = ?");
        $chk->execute([$correo]);
        if ($chk->fetchColumn() > 0) {
            $msg_crear = 'Ya existe un usuario con ese correo.';
            $msg_tipo  = 'error';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare("INSERT INTO usuario (nombre, correo, contrasena, id_rol) VALUES (?, ?, ?, ?)");
            $ins->execute([$nombre, $correo, $hash, $id_rol]);
            $nuevo_id = $pdo->lastInsertId();

            // Registrar en historial
            $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'usuario')");
            $hist->execute([$_SESSION['id_usuario'], "Creó usuario: $nombre (ID $nuevo_id)"]);

            $msg_crear = "Usuario «{$nombre}» creado exitosamente (ID: {$nuevo_id}).";
            $msg_tipo  = 'success';
        }
    }
}

// ── Eliminar usuario (POST) ──
$msg_eliminar = '';
$msg_eliminar_tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_usuario') {
    $id_eliminar = (int)($_POST['id_usuario'] ?? 0);
    $id_sesion   = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
    if (!$id_eliminar) {
        $msg_eliminar = 'Usuario no válido.'; $msg_eliminar_tipo = 'error';
    } elseif ($id_eliminar == $id_sesion) {
        $msg_eliminar = 'No puedes eliminar tu propio usuario.'; $msg_eliminar_tipo = 'error';
    } else {
        $chk = $pdo->prepare("SELECT nombre FROM usuario WHERE id_usuario = ?");
        $chk->execute([$id_eliminar]);
        $usr_nombre = $chk->fetchColumn();
        if (!$usr_nombre) {
            $msg_eliminar = 'El usuario no existe.'; $msg_eliminar_tipo = 'error';
        } else {
            $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$id_eliminar]);
            if ($id_sesion) {
                $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'usuario')");
                $hist->execute([$id_sesion, "Eliminó usuario: $usr_nombre (ID $id_eliminar)"]);
            }
            $msg_eliminar = "Usuario «{$usr_nombre}» eliminado correctamente."; $msg_eliminar_tipo = 'success';
        }
    }
}

// ── Editar usuario (POST) ──
$msg_editar = '';
$msg_editar_tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_usuario') {
    $id_edit   = (int)($_POST['id_usuario'] ?? 0);
    $nombre    = trim($_POST['nombre'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $id_rol    = (int)($_POST['id_rol'] ?? 0);
    $pass      = trim($_POST['contrasena'] ?? '');
    $id_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

    if (!$id_edit || !$nombre || !$correo || !$id_rol) {
        $msg_editar = 'Nombre, correo y rol son obligatorios.'; $msg_editar_tipo = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $msg_editar = 'El correo ingresado no es válido.'; $msg_editar_tipo = 'error';
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE correo = ? AND id_usuario != ?");
        $chk->execute([$correo, $id_edit]);
        if ($chk->fetchColumn() > 0) {
            $msg_editar = 'Ese correo ya está en uso por otro usuario.'; $msg_editar_tipo = 'error';
        } else {
            if ($pass !== '') {
                $upd = $pdo->prepare("UPDATE usuario SET nombre=?, correo=?, id_rol=?, contrasena=? WHERE id_usuario=?");
                $upd->execute([$nombre, $correo, $id_rol, $pass, $id_edit]);
            } else {
                $upd = $pdo->prepare("UPDATE usuario SET nombre=?, correo=?, id_rol=? WHERE id_usuario=?");
                $upd->execute([$nombre, $correo, $id_rol, $id_edit]);
            }
            if ($id_sesion) {
                $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'usuario')");
                $hist->execute([$id_sesion, "Editó usuario: $nombre (ID $id_edit)"]);
            }
            header("Location: rector.php?m=usuarios&editado=1");
            exit;
        }
    }
}

// ── Estadísticas generales ──
$total_usuarios  = $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$total_docentes  = $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=4")->fetchColumn();
$total_estudiantes = $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=5")->fetchColumn();
$total_cursos    = $pdo->query("SELECT COUNT(*) FROM curso")->fetchColumn();
$total_materias  = $pdo->query("SELECT COUNT(*) FROM materia")->fetchColumn();
$total_periodos  = $pdo->query("SELECT COUNT(*) FROM periodo_academico")->fetchColumn();

// ── Listado de usuarios por rol ──
$usuarios = $pdo->query("
    SELECT u.id_usuario, u.nombre, u.correo, r.nombre_rol, u.id_rol
    FROM usuario u JOIN rol r ON u.id_rol = r.id_rol
    ORDER BY u.id_rol, u.nombre
")->fetchAll();

// ── Historial de acciones recientes ──
$historial = $pdo->query("
    SELECT h.accion, h.tabla_afectada, h.fecha, u.nombre
    FROM historial_accion h JOIN usuario u ON h.id_usuario = u.id_usuario
    ORDER BY h.fecha DESC LIMIT 10
")->fetchAll();

// ── Asignaciones académicas ──
$asignaciones = $pdo->query("
    SELECT aa.id_asignacion, u.nombre AS docente, m.nombre AS materia,
           c.nombre AS curso, s.nombre AS salon,
           h.dia, h.hora_inicio, h.hora_fin, p.nombre AS periodo
    FROM asignacion_academica aa
    JOIN usuario u ON aa.id_docente = u.id_usuario
    JOIN materia m ON aa.id_materia = m.id_materia
    JOIN curso c   ON aa.id_curso   = c.id_curso
    JOIN salon s   ON aa.id_salon   = s.id_salon
    JOIN horario h ON aa.id_horario = h.id_horario
    JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
    ORDER BY aa.id_asignacion DESC LIMIT 20
")->fetchAll();

// ── Roles ──
$roles = $pdo->query("SELECT * FROM rol ORDER BY id_rol")->fetchAll();

$color = '#f87171';
layout_head('Rector — Dashboard');
echo "<div class='app'>";
echo sidebar_html('dashboard', [
    ['id'=>'dashboard',   'href'=>'rector.php',                 'icon'=>'fa-gauge',         'label'=>'Dashboard'],
    ['id'=>'usuarios',    'href'=>'rector.php?m=usuarios',       'icon'=>'fa-users',         'label'=>'Usuarios'],
    ['id'=>'crear_usuario','href'=>'rector.php?m=crear_usuario', 'icon'=>'fa-user-plus',     'label'=>'Crear Usuario'],
    ['id'=>'asignaciones','href'=>'rector.php?m=asignaciones',   'icon'=>'fa-calendar-days', 'label'=>'Asignaciones'],
    ['id'=>'historial',   'href'=>'rector.php?m=historial',      'icon'=>'fa-clock-rotate-left','label'=>'Historial'],
    ['id'=>'reportes',    'href'=>'rector.php?m=reportes',       'icon'=>'fa-chart-bar',     'label'=>'Reportes'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Rector — Panel de Control', $color);
echo "<div class='module-container'>";

$modulo = $_GET['m'] ?? 'dashboard';

if ($modulo === 'dashboard'):
?>
<div class="welcome-banner">
  <div class="welcome-octopus">🐙</div>
  <div>
    <div class="welcome-title">¡Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class="welcome-sub">Panel de Rectoría — Control total del sistema SIDINO</div>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $total_usuarios ?></div>
      <div class="stat-label">Usuarios totales</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-user-graduate"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $total_estudiantes ?></div>
      <div class="stat-label">Estudiantes</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-chalkboard-user"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $total_docentes ?></div>
      <div class="stat-label">Docentes</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-door-open"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $total_cursos ?></div>
      <div class="stat-label">Cursos</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fa-solid fa-book"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $total_materias ?></div>
      <div class="stat-label">Materias</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa-solid fa-calendar"></i></div>
    <div class="stat-body">
      <div class="stat-value"><?= $total_periodos ?></div>
      <div class="stat-label">Periodos académicos</div>
    </div>
  </div>
</div>

<div class="content-grid">
  <!-- Usuarios recientes -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-users"></i> Usuarios del sistema</div>
      <a href="rector.php?m=usuarios" class="card-action">Ver todos</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th></tr></thead>
        <tbody>
        <?php foreach(array_slice($usuarios, 0, 8) as $u): 
          $badge_cls = ['1'=>'red','2'=>'orange','3'=>'yellow','4'=>'blue','5'=>'green','6'=>'purple'][$u['id_rol']] ?? 'blue';
        ?>
          <tr>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['correo']) ?></td>
            <td><span class="badge <?= $badge_cls ?>"><?= htmlspecialchars($u['nombre_rol']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Historial reciente -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Actividad reciente</div>
      <a href="rector.php?m=historial" class="card-action">Ver todo</a>
    </div>
    <?php if(empty($historial)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin actividad registrada aún</p></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Usuario</th><th>Acción</th><th>Tabla</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach($historial as $h): ?>
          <tr>
            <td><?= htmlspecialchars($h['nombre']) ?></td>
            <td><?= htmlspecialchars($h['accion']) ?></td>
            <td><span class="badge blue"><?= htmlspecialchars($h['tabla_afectada']) ?></span></td>
            <td><?= date('d/m H:i', strtotime($h['fecha'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'usuarios'): ?>
<div class="section-title"><i class="fa-solid fa-users"></i> Gestión de Usuarios</div>

<?php if($msg_eliminar): ?>
  <div class="alert <?= $msg_eliminar_tipo === 'success' ? 'success' : 'error' ?>">
    <i class="fa-solid <?= $msg_eliminar_tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
    <?= htmlspecialchars($msg_eliminar) ?>
  </div>
<?php endif; ?>
<?php if(isset($_GET['editado'])): ?>
  <div class="alert success"><i class="fa-solid fa-circle-check"></i> Usuario actualizado correctamente.</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-list"></i> Todos los usuarios</div>
    <a href="rector.php?m=crear_usuario" class="card-action"><i class="fa-solid fa-user-plus"></i> Crear usuario</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach($usuarios as $u):
        $badge_cls = ['1'=>'red','2'=>'orange','3'=>'yellow','4'=>'blue','5'=>'green','6'=>'purple'][$u['id_rol']] ?? 'blue';
        $es_yo = ($u['id_usuario'] == ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null));
      ?>
        <tr>
          <td><?= $u['id_usuario'] ?></td>
          <td><?= htmlspecialchars($u['nombre']) ?></td>
          <td><?= htmlspecialchars($u['correo']) ?></td>
          <td><span class="badge <?= $badge_cls ?>"><?= htmlspecialchars($u['nombre_rol']) ?></span></td>
          <td style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
            <a href="rector.php?m=editar_usuario&id=<?= $u['id_usuario'] ?>"
               class="btn btn-primary" style="padding:.3rem .7rem;font-size:.8rem;">
              <i class="fa-solid fa-pen-to-square"></i> Editar
            </a>
            <?php if($es_yo): ?>
              <span style="color:var(--text-muted,#aaa);font-size:.8rem;"><i class="fa-solid fa-user-check"></i> Tú</span>
            <?php else: ?>
              <form method="POST" action="rector.php?m=usuarios" style="display:inline;"
                    onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($u['nombre'])) ?>? Esta acción no se puede deshacer.')">
                <input type="hidden" name="accion" value="eliminar_usuario">
                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                <button type="submit" class="btn btn-danger" style="padding:.3rem .7rem;font-size:.8rem;">
                  <i class="fa-solid fa-trash"></i> Eliminar
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif($modulo === 'asignaciones'): ?>
<div class="section-title"><i class="fa-solid fa-calendar-days"></i> Asignaciones Académicas</div>
<div class="card">
  <div class="table-wrap">
    <?php if(empty($asignaciones)): ?>
      <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No hay asignaciones registradas aún</p></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Docente</th><th>Materia</th><th>Curso</th><th>Salón</th><th>Día</th><th>Hora</th><th>Periodo</th></tr></thead>
      <tbody>
      <?php foreach($asignaciones as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['docente']) ?></td>
          <td><?= htmlspecialchars($a['materia']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($a['curso']) ?></span></td>
          <td><?= htmlspecialchars($a['salon']) ?></td>
          <td><?= htmlspecialchars($a['dia']) ?></td>
          <td><?= substr($a['hora_inicio'],0,5) ?> – <?= substr($a['hora_fin'],0,5) ?></td>
          <td><?= htmlspecialchars($a['periodo']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'historial'): ?>
<div class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Historial de Acciones</div>
<div class="card">
  <div class="table-wrap">
    <?php if(empty($historial)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin actividad registrada aún</p></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Usuario</th><th>Acción</th><th>Tabla afectada</th><th>Fecha y hora</th></tr></thead>
      <tbody>
      <?php foreach($historial as $h): ?>
        <tr>
          <td><?= htmlspecialchars($h['nombre']) ?></td>
          <td><?= htmlspecialchars($h['accion']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($h['tabla_afectada']) ?></span></td>
          <td><?= date('d/m/Y H:i', strtotime($h['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'reportes'): ?>
<div class="section-title"><i class="fa-solid fa-chart-bar"></i> Reportes Generales</div>
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_usuarios ?></div><div class="stat-label">Usuarios totales</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-user-graduate"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_estudiantes ?></div><div class="stat-label">Estudiantes</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-chalkboard-user"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_docentes ?></div><div class="stat-label">Docentes</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-door-open"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_cursos ?></div><div class="stat-label">Cursos activos</div></div>
  </div>
</div>
<div class="alert info"><i class="fa-solid fa-circle-info"></i> Los reportes avanzados con gráficas estarán disponibles cuando se registren notas en el sistema.</div>

<?php elseif($modulo === 'crear_usuario'): ?>
<div class="section-title"><i class="fa-solid fa-user-plus"></i> Crear Nuevo Usuario</div>

<?php if($msg_crear): ?>
  <div class="alert <?= $msg_tipo === 'success' ? 'success' : 'error' ?>">
    <i class="fa-solid <?= $msg_tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
    <?= htmlspecialchars($msg_crear) ?>
  </div>
<?php endif; ?>

<div class="card" style="max-width:540px;">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-id-card"></i> Datos del nuevo usuario</div>
  </div>
  <form method="POST" action="rector.php?m=crear_usuario" style="padding:1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
    <input type="hidden" name="accion" value="crear_usuario">

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-user"></i> Nombre completo</label>
      <input type="text" name="nombre" class="form-input"
             placeholder="Ej: María López"
             value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
             required>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-envelope"></i> Correo electrónico</label>
      <input type="email" name="correo" class="form-input"
             placeholder="usuario@correo.com"
             value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
             required>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-shield-halved"></i> Rol</label>
      <select name="id_rol" class="form-input" required>
        <option value="">— Seleccionar rol —</option>
        <?php foreach($roles as $r): ?>
          <option value="<?= $r['id_rol'] ?>"
            <?= (isset($_POST['id_rol']) && $_POST['id_rol'] == $r['id_rol']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($r['nombre_rol']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-lock"></i> Contraseña temporal</label>
      <input type="text" name="contrasena" class="form-input"
             placeholder="Contraseña inicial para el usuario"
             required>
      <small style="color:var(--text-muted,#888);font-size:.78rem;">Se guardará cifrada con password_hash(). El usuario podrá cambiarla después.</small>
    </div>

    <div style="display:flex;gap:.8rem;margin-top:.4rem;">
      <button type="submit" class="btn btn-primary" style="flex:1;">
        <i class="fa-solid fa-floppy-disk"></i> Crear usuario
      </button>
      <a href="rector.php?m=usuarios" class="btn btn-secondary" style="flex:1;text-align:center;">
        <i class="fa-solid fa-arrow-left"></i> Ver todos
      </a>
    </div>
  </form>
</div>

<?php elseif($modulo === 'editar_usuario'):
  $id_edit = (int)($_GET['id'] ?? 0);
  $usr_edit = null;
  if ($id_edit) {
      $q = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
      $q->execute([$id_edit]);
      $usr_edit = $q->fetch();
  }
  if (!$usr_edit): ?>
    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i> Usuario no encontrado.</div>
    <a href="rector.php?m=usuarios" class="btn btn-secondary" style="margin-top:1rem;display:inline-block;"><i class="fa-solid fa-arrow-left"></i> Volver</a>
  <?php else: ?>
<div class="section-title"><i class="fa-solid fa-pen-to-square"></i> Editar Usuario</div>

<?php if($msg_editar): ?>
  <div class="alert <?= $msg_editar_tipo === 'success' ? 'success' : 'error' ?>">
    <i class="fa-solid <?= $msg_editar_tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
    <?= htmlspecialchars($msg_editar) ?>
  </div>
<?php endif; ?>

<div class="card" style="max-width:540px;">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-id-card"></i> Editando: <?= htmlspecialchars($usr_edit['nombre']) ?></div>
  </div>
  <form method="POST" action="rector.php?m=editar_usuario&id=<?= $id_edit ?>" style="padding:1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
    <input type="hidden" name="accion" value="editar_usuario">
    <input type="hidden" name="id_usuario" value="<?= $id_edit ?>">

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-user"></i> Nombre completo</label>
      <input type="text" name="nombre" class="form-input"
             value="<?= htmlspecialchars($_POST['nombre'] ?? $usr_edit['nombre']) ?>"
             required>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-envelope"></i> Correo electrónico</label>
      <input type="email" name="correo" class="form-input"
             value="<?= htmlspecialchars($_POST['correo'] ?? $usr_edit['correo']) ?>"
             required>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-shield-halved"></i> Rol</label>
      <select name="id_rol" class="form-input" required>
        <?php foreach($roles as $r): ?>
          <option value="<?= $r['id_rol'] ?>"
            <?= (($_POST['id_rol'] ?? $usr_edit['id_rol']) == $r['id_rol']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($r['nombre_rol']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label"><i class="fa-solid fa-lock"></i> Nueva contraseña
        <small style="font-weight:400;color:var(--text-muted,#888);">(dejar en blanco para no cambiarla)</small>
      </label>
      <input type="text" name="contrasena" class="form-input" placeholder="Solo si deseas cambiarla">
    </div>

    <div style="display:flex;gap:.8rem;margin-top:.4rem;">
      <button type="submit" class="btn btn-primary" style="flex:1;">
        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
      </button>
      <a href="rector.php?m=usuarios" class="btn btn-secondary" style="flex:1;text-align:center;">
        <i class="fa-solid fa-arrow-left"></i> Cancelar
      </a>
    </div>
  </form>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
echo "</div></div></div>";
layout_close();
?>