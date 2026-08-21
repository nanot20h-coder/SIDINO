<?php
require_once __DIR__ . '/rector_common.php';

$msg = '';
$tipo = '';
$id_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
$periodo_actual = $pdo->query('SELECT id_periodo FROM periodo_academico ORDER BY anio DESC, id_periodo DESC LIMIT 1')->fetchColumn();
if (!$periodo_actual) {
    $periodo_insert = $pdo->prepare('INSERT INTO periodo_academico (nombre, nivel, tipo, anio) VALUES (?, ?, ?, ?)');
    $periodo_insert->execute(['Periodo actual', 'Todos', 'Anual', date('Y')]);
    $periodo_actual = $pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'asignar_profesor') {
    $docente = (int)($_POST['id_docente'] ?? 0);
    $materia = (int)($_POST['id_materia'] ?? 0);
    $curso = (int)($_POST['id_curso'] ?? 0);
    $salon = (int)($_POST['id_salon'] ?? 0);
    $horario = (int)($_POST['id_horario'] ?? 0);
    $periodo = (int)$periodo_actual;

    if (!$docente || !$materia || !$curso || !$salon || !$horario) {
        $msg = 'Selecciona profesor, materia, curso, salón y horario.';
        $tipo = 'error';
    } else {
        $docente_check = $pdo->prepare('SELECT nombre FROM usuario WHERE id_usuario = ? AND id_rol = 4');
        $docente_check->execute([$docente]);
        $nombre_docente = $docente_check->fetchColumn();

        $duplicate = $pdo->prepare('SELECT COUNT(*) FROM asignacion_academica WHERE id_docente = ? AND id_materia = ? AND id_curso = ? AND id_periodo = ?');
        $duplicate->execute([$docente, $materia, $curso, $periodo]);

        if (!$nombre_docente) {
            $msg = 'El profesor seleccionado no es válido.';
            $tipo = 'error';
        } elseif ($duplicate->fetchColumn()) {
            $msg = 'Ese profesor ya está asignado a esa materia, curso y período.';
            $tipo = 'error';
        } else {
            try {
                $pdo->beginTransaction();
                $insert = $pdo->prepare('INSERT INTO asignacion_academica (id_docente, id_materia, id_periodo, id_salon, id_horario, id_curso) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->execute([$docente, $materia, $periodo, $salon, $horario, $curso]);
                $id_asignacion = $pdo->lastInsertId();

                if ($id_sesion) {
                    $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'asignacion_academica')");
                    $hist->execute([$id_sesion, "Asignó profesor: $nombre_docente (ID $id_asignacion)"]);
                }
                $pdo->commit();
                $msg = "Asignación creada correctamente para {$nombre_docente}.";
                $tipo = 'success';
                $_POST = [];
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $msg = 'No fue posible guardar la asignación. Verifica que el horario y salón estén disponibles.';
                $tipo = 'error';
            }
        }
    }
}

$docentes = $pdo->query("SELECT id_usuario, nombre FROM usuario WHERE id_rol = 4 ORDER BY nombre")->fetchAll();

$materias_basicas = ['Matemáticas', 'Lengua Castellana', 'Ciencias Naturales', 'Ciencias Sociales', 'Inglés', 'Educación Física', 'Tecnología e Informática', 'Educación Artística', 'Ética y Valores'];
$materia_check = $pdo->prepare('SELECT COUNT(*) FROM materia WHERE LOWER(nombre) = LOWER(?)');
$materia_insert = $pdo->prepare('INSERT INTO materia (nombre) VALUES (?)');
foreach ($materias_basicas as $materia_basica) {
    $materia_check->execute([$materia_basica]);
    if (!$materia_check->fetchColumn()) {
        $materia_insert->execute([$materia_basica]);
    }
}
$materias = $pdo->query('SELECT id_materia, nombre FROM materia ORDER BY nombre')->fetchAll();
$cursos = $pdo->query('SELECT id_curso, nombre FROM curso ORDER BY nombre')->fetchAll();
$salon_check = $pdo->prepare('SELECT COUNT(*) FROM salon WHERE nombre = ?');
$salon_insert = $pdo->prepare('INSERT INTO salon (nombre, capacidad, ubicacion) VALUES (?, 40, ?)');
for ($numero_salon = 101; $numero_salon <= 1001; $numero_salon += 100) {
    $nombre_salon = (string)$numero_salon;
    $salon_check->execute([$nombre_salon]);
    if (!$salon_check->fetchColumn()) {
        $salon_insert->execute([$nombre_salon, 'Bloque principal']);
    }
}
$salones = $pdo->query('SELECT id_salon, nombre FROM salon ORDER BY nombre')->fetchAll();
$dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$horario_insert = $pdo->prepare('INSERT INTO horario (dia, hora_inicio, hora_fin) SELECT ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM horario WHERE dia = ? AND hora_inicio = ? AND hora_fin = ?)');
foreach ($dias as $dia) {
    foreach ([['06:00:00', '12:00:00'], ['12:00:00', '18:00:00']] as [$inicio, $fin]) {
        $horario_insert->execute([$dia, $inicio, $fin, $dia, $inicio, $fin]);
    }
}
$horarios = $pdo->query('SELECT id_horario, dia, hora_inicio, hora_fin FROM horario WHERE (hora_inicio = "06:00:00" AND hora_fin = "12:00:00") OR (hora_inicio = "12:00:00" AND hora_fin = "18:00:00") ORDER BY FIELD(dia, "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"), hora_inicio')->fetchAll();
rector_layout_start('Asignar Profesor', 'asignar_profesor');
?>
<div class="section-title"><i class="fa-solid fa-chalkboard-user"></i> Asignar profesor a una clase</div>
<?php if ($msg): ?><div class="alert <?= $tipo === 'success' ? 'success' : 'error' ?>"><i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:760px;">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-calendar-plus"></i> Datos de la clase</div></div>
    <form method="POST" action="asignar_profesor.php" style="padding:1.5rem;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.1rem;">
    <input type="hidden" name="accion" value="asignar_profesor">
    <?php
    $campos = [
        ['id_docente', 'Profesor', $docentes, 'id_usuario', 'nombre'],
        ['id_materia', 'Materia', $materias, 'id_materia', 'nombre'],
        ['id_curso', 'Curso', $cursos, 'id_curso', 'nombre'],
        ['id_salon', 'Salón', $salones, 'id_salon', 'nombre'],
        ['id_horario', 'Horario', $horarios, 'id_horario', 'dia'],
    ];
    foreach ($campos as [$nombre, $etiqueta, $opciones, $id_key, $label_key]):
    ?>
    <div class="form-group"><label class="form-label" for="<?= $nombre ?>"><i class="fa-solid fa-circle-chevron-right"></i> <?= $etiqueta ?></label><select id="<?= $nombre ?>" name="<?= $nombre ?>" class="field-input" required><option value="">Seleccionar...</option><?php foreach ($opciones as $opcion): ?><option value="<?= $opcion[$id_key] ?>" <?= ($_POST[$nombre] ?? '') == $opcion[$id_key] ? 'selected' : '' ?>><?php if ($nombre === 'id_horario'): ?><?= htmlspecialchars($opcion['dia']) ?> (<?= substr($opcion['hora_inicio'], 0, 5) ?> - <?= substr($opcion['hora_fin'], 0, 5) ?>)<?php else: ?><?= htmlspecialchars($opcion[$label_key]) ?><?php endif; ?></option><?php endforeach; ?></select></div>
    <?php endforeach; ?>
    <div style="grid-column:1/-1;display:flex;gap:.8rem;margin-top:.4rem;"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar asignación</button><a href="asignaciones.php" class="btn btn-ghost"><i class="fa-solid fa-calendar-days"></i> Ver asignaciones</a></div>
  </form>
</div>
<?php rector_layout_end(); ?>