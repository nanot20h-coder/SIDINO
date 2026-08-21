<?php
require_once __DIR__ . '/rector_common.php';

$msg = '';
$tipo = '';
$id_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'asignar_profesor') {
    $docente = (int)($_POST['id_docente'] ?? 0);
    $materia = (int)($_POST['id_materia'] ?? 0);
    $curso = (int)($_POST['id_curso'] ?? 0);
    $salon = (int)($_POST['id_salon'] ?? 0);
    $horario = (int)($_POST['id_horario'] ?? 0);
    $periodo = (int)($_POST['id_periodo'] ?? 0);

    if (!$docente || !$materia || !$curso || !$salon || !$horario || !$periodo) {
        $msg = 'Selecciona todas las opciones para crear la asignación.';
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
$materias = $pdo->query('SELECT id_materia, nombre FROM materia ORDER BY nombre')->fetchAll();
$cursos = $pdo->query('SELECT id_curso, nombre FROM curso ORDER BY nombre')->fetchAll();
$salones = $pdo->query('SELECT id_salon, nombre FROM salon ORDER BY nombre')->fetchAll();
$horarios = $pdo->query('SELECT id_horario, dia, hora_inicio, hora_fin FROM horario ORDER BY FIELD(dia, "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"), hora_inicio')->fetchAll();
$periodos = $pdo->query('SELECT id_periodo, nombre, anio FROM periodo_academico ORDER BY anio DESC, id_periodo DESC')->fetchAll();

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
        ['id_periodo', 'Período académico', $periodos, 'id_periodo', 'nombre'],
    ];
    foreach ($campos as [$nombre, $etiqueta, $opciones, $id_key, $label_key]):
    ?>
    <div class="form-group"><label class="form-label" for="<?= $nombre ?>"><i class="fa-solid fa-circle-chevron-right"></i> <?= $etiqueta ?></label><select id="<?= $nombre ?>" name="<?= $nombre ?>" class="field-input" required><option value="">Seleccionar...</option><?php foreach ($opciones as $opcion): ?><option value="<?= $opcion[$id_key] ?>" <?= ($_POST[$nombre] ?? '') == $opcion[$id_key] ? 'selected' : '' ?>><?php if ($nombre === 'id_horario'): ?><?= htmlspecialchars($opcion['dia']) ?> (<?= substr($opcion['hora_inicio'], 0, 5) ?> - <?= substr($opcion['hora_fin'], 0, 5) ?>)<?php elseif ($nombre === 'id_periodo'): ?><?= htmlspecialchars($opcion['nombre']) ?> - <?= htmlspecialchars($opcion['anio']) ?><?php else: ?><?= htmlspecialchars($opcion[$label_key]) ?><?php endif; ?></option><?php endforeach; ?></select></div>
    <?php endforeach; ?>
    <div style="grid-column:1/-1;display:flex;gap:.8rem;margin-top:.4rem;"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar asignación</button><a href="asignaciones.php" class="btn btn-ghost"><i class="fa-solid fa-calendar-days"></i> Ver asignaciones</a></div>
  </form>
</div>
<?php rector_layout_end(); ?>