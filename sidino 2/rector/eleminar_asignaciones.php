<?php
require_once __DIR__ . '/rector_common.php';

$msg = '';
$tipo = '';
$id_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_asignacion') {
	$id_asignacion = (int)($_POST['id_asignacion'] ?? 0);

	if (!$id_asignacion) {
		$msg = 'La asignación seleccionada no es válida.';
		$tipo = 'error';
	} else {
		$detalle = $pdo->prepare("SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso
			FROM asignacion_academica aa
			JOIN materia m ON aa.id_materia = m.id_materia
			JOIN curso c ON aa.id_curso = c.id_curso
			WHERE aa.id_asignacion = ?");
		$detalle->execute([$id_asignacion]);
		$asignacion = $detalle->fetch();

		if (!$asignacion) {
			$msg = 'La asignación ya no existe.';
			$tipo = 'error';
		} else {
			try {
				$pdo->beginTransaction();
				$matriculas = $pdo->prepare('SELECT id_matricula FROM matricula WHERE id_asignacion = ?');
				$matriculas->execute([$id_asignacion]);
				$ids_matricula = array_column($matriculas->fetchAll(), 'id_matricula');

				if ($ids_matricula) {
					$placeholders = implode(',', array_fill(0, count($ids_matricula), '?'));
					$pdo->prepare("DELETE FROM boletin_detalle WHERE id_nota IN (SELECT id_nota FROM nota WHERE id_matricula IN ($placeholders))")->execute($ids_matricula);
					$pdo->prepare("DELETE FROM nota WHERE id_matricula IN ($placeholders)")->execute($ids_matricula);
					if ($pdo->query("SHOW TABLES LIKE 'asistencia'")->fetchColumn()) {
						$pdo->prepare("DELETE FROM asistencia WHERE id_matricula IN ($placeholders)")->execute($ids_matricula);
					}
					$pdo->prepare("DELETE FROM matricula WHERE id_matricula IN ($placeholders)")->execute($ids_matricula);
				}

				$pdo->prepare('DELETE FROM citacion WHERE id_asignacion = ?')->execute([$id_asignacion]);
				$pdo->prepare('DELETE FROM contenido WHERE id_asignacion = ?')->execute([$id_asignacion]);
				$pdo->prepare('DELETE FROM observador WHERE id_asignacion = ?')->execute([$id_asignacion]);
				$pdo->prepare('DELETE FROM asignacion_academica WHERE id_asignacion = ?')->execute([$id_asignacion]);

				if ($id_sesion) {
					$hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'asignacion_academica')");
					$hist->execute([$id_sesion, "Quitó asignación: {$asignacion['materia']} - {$asignacion['curso']} (ID $id_asignacion)"]);
				}
				$pdo->commit();
				$msg = 'La asignación y sus datos relacionados fueron eliminados correctamente.';
				$tipo = 'success';
			} catch (PDOException $e) {
				if ($pdo->inTransaction()) { $pdo->rollBack(); }
				$msg = 'No fue posible quitar la asignación. No se realizaron cambios.';
				$tipo = 'error';
			}
		}
	}
}

$asignaciones = $pdo->query("SELECT aa.id_asignacion, u.nombre AS docente, m.nombre AS materia, c.nombre AS curso,
	s.nombre AS salon, h.dia, h.hora_inicio, h.hora_fin, p.nombre AS periodo
	FROM asignacion_academica aa
	JOIN usuario u ON aa.id_docente = u.id_usuario
	JOIN materia m ON aa.id_materia = m.id_materia
	JOIN curso c ON aa.id_curso = c.id_curso
	JOIN salon s ON aa.id_salon = s.id_salon
	JOIN horario h ON aa.id_horario = h.id_horario
	JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
	ORDER BY aa.id_asignacion DESC")->fetchAll();

rector_layout_start('Quitar Asignaciones', 'quitar asignaciones');
?>
<div class="section-title"><i class="fa-solid fa-trash"></i> Quitar asignaciones académicas</div>
<?php if ($msg): ?><div class="alert <?= $tipo === 'success' ? 'success' : 'error' ?>"><i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-calendar-xmark"></i> Asignaciones generadas</div></div>
  <div class="table-wrap">
	<?php if (!$asignaciones): ?><div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No hay asignaciones para quitar</p></div>
	<?php else: ?><table><thead><tr><th>Docente</th><th>Materia</th><th>Curso</th><th>Salón</th><th>Horario</th><th>Período</th><th>Acción</th></tr></thead><tbody>
	  <?php foreach ($asignaciones as $asignacion): ?><tr>
		<td><?= htmlspecialchars($asignacion['docente']) ?></td><td><?= htmlspecialchars($asignacion['materia']) ?></td>
		<td><span class="badge blue"><?= htmlspecialchars($asignacion['curso']) ?></span></td><td><?= htmlspecialchars($asignacion['salon']) ?></td>
		<td><?= htmlspecialchars($asignacion['dia']) ?>, <?= substr($asignacion['hora_inicio'], 0, 5) ?> - <?= substr($asignacion['hora_fin'], 0, 5) ?></td>
		<td><?= htmlspecialchars($asignacion['periodo']) ?></td><td><form method="POST" onsubmit="return confirm('¿Quitar esta asignación y todos sus datos relacionados?');">
		  <input type="hidden" name="accion" value="eliminar_asignacion"><input type="hidden" name="id_asignacion" value="<?= (int)$asignacion['id_asignacion'] ?>">
		  <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Quitar</button>
		</form></td>
	  </tr><?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
</div>
<?php rector_layout_end(); ?>

