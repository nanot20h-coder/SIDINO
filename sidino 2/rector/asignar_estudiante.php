<?php
require_once __DIR__ . '/rector_common.php';

$msg = '';
$tipo = '';
$id_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'asignar_estudiante') {
    $estudiante = (int)($_POST['id_estudiante'] ?? 0);
    $asignacion = (int)($_POST['id_asignacion'] ?? 0);

    if (!$estudiante || !$asignacion) {
        $msg = 'Selecciona el estudiante y la clase.';
        $tipo = 'error';
    } else {
        $estudiante_check = $pdo->prepare('SELECT nombre FROM usuario WHERE id_usuario = ? AND id_rol = 5');
        $estudiante_check->execute([$estudiante]);
        $nombre_estudiante = $estudiante_check->fetchColumn();

        $asignacion_check = $pdo->prepare('SELECT CONCAT(m.nombre, " - ", c.nombre) FROM asignacion_academica aa JOIN materia m ON aa.id_materia = m.id_materia JOIN curso c ON aa.id_curso = c.id_curso WHERE aa.id_asignacion = ?');
        $asignacion_check->execute([$asignacion]);
        $nombre_clase = $asignacion_check->fetchColumn();

        $duplicate = $pdo->prepare('SELECT COUNT(*) FROM matricula WHERE id_estudiante = ? AND id_asignacion = ?');
        $duplicate->execute([$estudiante, $asignacion]);

        if (!$nombre_estudiante || !$nombre_clase) {
            $msg = 'El estudiante o la clase seleccionada no es válida.';
            $tipo = 'error';
        } elseif ($duplicate->fetchColumn()) {
            $msg = 'Ese estudiante ya está asignado a esa clase.';
            $tipo = 'error';
        } else {
            try {
                $pdo->beginTransaction();
                $insert = $pdo->prepare('INSERT INTO matricula (id_estudiante, id_asignacion) VALUES (?, ?)');
                $insert->execute([$estudiante, $asignacion]);

                if ($id_sesion) {
                    $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'matricula')");
                    $hist->execute([$id_sesion, "Asignó estudiante: $nombre_estudiante a $nombre_clase"]);
                }
                $pdo->commit();
                $msg = "{$nombre_estudiante} fue asignado correctamente a {$nombre_clase}.";
                $tipo = 'success';
                $_POST = [];
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $msg = 'No fue posible guardar la matrícula.';
                $tipo = 'error';
            }
        }
    }
}

$estudiantes = $pdo->query("SELECT id_usuario, nombre FROM usuario WHERE id_rol = 5 ORDER BY nombre")->fetchAll();
$asignaciones = $pdo->query("SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso, u.nombre AS docente FROM asignacion_academica aa JOIN materia m ON aa.id_materia = m.id_materia JOIN curso c ON aa.id_curso = c.id_curso JOIN usuario u ON aa.id_docente = u.id_usuario ORDER BY c.nombre, m.nombre, u.nombre")->fetchAll();

rector_layout_start('Asignar Estudiante', 'asignar_estudiante');
?>
<div class="section-title"><i class="fa-solid fa-user-graduate"></i> Asignar estudiante a una clase</div>
<?php if ($msg): ?><div class="alert <?= $tipo === 'success' ? 'success' : 'error' ?>"><i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:760px;">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-user-plus"></i> Datos de la matrícula</div></div>
  <form method="POST" action="asignar_estudiante.php" style="padding:1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
    <input type="hidden" name="accion" value="asignar_estudiante">
    <div class="form-group"><label class="form-label" for="id_estudiante"><i class="fa-solid fa-user-graduate"></i> Estudiante</label><select id="id_estudiante" name="id_estudiante" class="field-input" required><option value="">Seleccionar estudiante...</option><?php foreach ($estudiantes as $estudiante): ?><option value="<?= $estudiante['id_usuario'] ?>" <?= ($_POST['id_estudiante'] ?? '') == $estudiante['id_usuario'] ? 'selected' : '' ?>><?= htmlspecialchars($estudiante['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label class="form-label" for="id_asignacion"><i class="fa-solid fa-chalkboard"></i> Clase y curso</label><select id="id_asignacion" name="id_asignacion" class="field-input" required><option value="">Seleccionar clase...</option><?php foreach ($asignaciones as $asignacion): ?><option value="<?= $asignacion['id_asignacion'] ?>" <?= ($_POST['id_asignacion'] ?? '') == $asignacion['id_asignacion'] ? 'selected' : '' ?>><?= htmlspecialchars($asignacion['materia']) ?> · Curso: <?= htmlspecialchars($asignacion['curso']) ?> · Profesor: <?= htmlspecialchars($asignacion['docente']) ?></option><?php endforeach; ?></select></div>
    <div style="display:flex;gap:.8rem;margin-top:.4rem;"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar matrícula</button><a href="asignaciones.php" class="btn btn-ghost"><i class="fa-solid fa-calendar-days"></i> Ver clases</a></div>
  </form>
</div>
<?php rector_layout_end(); ?>