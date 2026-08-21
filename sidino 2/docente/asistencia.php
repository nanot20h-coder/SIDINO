<?php
require_once __DIR__ . '/auth.php';
require_auth([4]);

$pdo = getDB();
$id_docente = $_SESSION['user_id'];
$msg = '';
$tipo = '';

$pdo->exec("CREATE TABLE IF NOT EXISTS asistencia (id_asistencia INT NOT NULL AUTO_INCREMENT PRIMARY KEY, id_matricula INT NOT NULL, fecha DATE NOT NULL, estado ENUM('presente','ausente') NOT NULL, UNIQUE KEY asistencia_dia (id_matricula, fecha)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$asignaciones_stmt = $pdo->prepare('SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso FROM asignacion_academica aa JOIN materia m ON aa.id_materia = m.id_materia JOIN curso c ON aa.id_curso = c.id_curso WHERE aa.id_docente = ? ORDER BY c.nombre, m.nombre');
$asignaciones_stmt->execute([$id_docente]);
$asignaciones = $asignaciones_stmt->fetchAll();
$id_asignacion = (int)($_POST['id_asignacion'] ?? $_GET['id_asignacion'] ?? 0);
$fecha = $_POST['fecha'] ?? $_GET['fecha'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_asistencia') {
    $estados = $_POST['estado'] ?? [];
    $asignacion_check = $pdo->prepare('SELECT COUNT(*) FROM asignacion_academica WHERE id_asignacion = ? AND id_docente = ?');
    $asignacion_check->execute([$id_asignacion, $id_docente]);

    if (!$asignacion_check->fetchColumn() || !$fecha || empty($estados)) {
        $msg = 'Selecciona una clase y registra el estado de los estudiantes.';
        $tipo = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            $matricula_check = $pdo->prepare('SELECT COUNT(*) FROM matricula WHERE id_matricula = ? AND id_asignacion = ?');
            $guardar = $pdo->prepare("INSERT INTO asistencia (id_matricula, fecha, estado) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE estado = VALUES(estado)");
            foreach ($estados as $id_matricula => $estado) {
                if (!in_array($estado, ['presente', 'ausente'], true)) { continue; }
                $matricula_check->execute([(int)$id_matricula, $id_asignacion]);
                if ($matricula_check->fetchColumn()) {
                    $guardar->execute([(int)$id_matricula, $fecha, $estado]);
                }
            }
            $pdo->commit();
            $msg = 'Asistencia guardada correctamente.';
            $tipo = 'success';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $msg = 'No fue posible guardar la asistencia.';
            $tipo = 'error';
        }
    }
}

$estudiantes = [];
if ($id_asignacion) {
    $estudiantes_stmt = $pdo->prepare('SELECT mat.id_matricula, u.nombre, COALESCE(a.estado, "presente") AS estado FROM matricula mat JOIN usuario u ON mat.id_estudiante = u.id_usuario LEFT JOIN asistencia a ON a.id_matricula = mat.id_matricula AND a.fecha = ? WHERE mat.id_asignacion = ? ORDER BY u.nombre');
    $estudiantes_stmt->execute([$fecha, $id_asignacion]);
    $estudiantes = $estudiantes_stmt->fetchAll();
}

layout_head('Docente — Asistencia');
echo "<div class='app'>";
echo sidebar_html('asistencia', [
    ['id'=>'dashboard', 'href'=>'docente.php', 'icon'=>'fa-gauge', 'label'=>'Dashboard'],
    ['id'=>'horario', 'href'=>'horario.php', 'icon'=>'fa-calendar-days', 'label'=>'Mis clases'],
    ['id'=>'asistencia', 'href'=>'asistencia.php', 'icon'=>'fa-user-check', 'label'=>'Asistencia'],
    ['id'=>'notas', 'href'=>'generar_nota.php', 'icon'=>'fa-star', 'label'=>'Registrar notas'],
    ['id'=>'observador', 'href'=>'observador.php', 'icon'=>'fa-book-open', 'label'=>'Observador'],
    ['id'=>'contenido', 'href'=>'contenido.php', 'icon'=>'fa-folder-open', 'label'=>'Contenido'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Docente — Asistencia', '#20a7e0');
echo "<div class='module-container'>";
?>
<div class="section-title"><i class="fa-solid fa-user-check"></i> Registrar asistencia</div>
<?php if ($msg): ?><div class="alert <?= $tipo === 'success' ? 'success' : 'error' ?>"><i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:850px;"><form method="GET" action="asistencia.php" class="form-inline"><div class="field-group"><label class="field-label">Clase</label><select name="id_asignacion" class="field-input" required><option value="">Seleccionar clase...</option><?php foreach ($asignaciones as $asignacion): ?><option value="<?= $asignacion['id_asignacion'] ?>" <?= $id_asignacion == $asignacion['id_asignacion'] ? 'selected' : '' ?>><?= htmlspecialchars($asignacion['materia']) ?> - <?= htmlspecialchars($asignacion['curso']) ?></option><?php endforeach; ?></select></div><div class="field-group" style="max-width:170px"><label class="field-label">Fecha</label><input type="date" name="fecha" class="field-input" value="<?= htmlspecialchars($fecha) ?>" required></div><button type="submit" class="btn btn-ghost"><i class="fa-solid fa-users"></i> Cargar estudiantes</button></form></div>
<?php if ($id_asignacion): ?><div class="card" style="max-width:850px;margin-top:1.25rem;"><div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-list-check"></i> Estado del <?= htmlspecialchars($fecha) ?></div><?php if (!$estudiantes): ?><div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>No hay estudiantes matriculados en esta clase.</p></div><?php else: ?><form method="POST" action="asistencia.php"><input type="hidden" name="accion" value="guardar_asistencia"><input type="hidden" name="id_asignacion" value="<?= $id_asignacion ?>"><input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>"><div class="table-wrap"><table><thead><tr><th>Estudiante</th><th>Estado</th></tr></thead><tbody><?php foreach ($estudiantes as $estudiante): ?><tr><td><?= htmlspecialchars($estudiante['nombre']) ?></td><td><select name="estado[<?= $estudiante['id_matricula'] ?>]" class="field-input"><option value="presente" <?= $estudiante['estado'] === 'presente' ? 'selected' : '' ?>>Presente</option><option value="ausente" <?= $estudiante['estado'] === 'ausente' ? 'selected' : '' ?>>Ausente</option></select></td></tr><?php endforeach; ?></tbody></table></div><button type="submit" class="btn btn-primary" style="margin-top:1rem"><i class="fa-solid fa-floppy-disk"></i> Guardar asistencia</button></form><?php endif; ?></div><?php endif; ?>
<?php echo "</div></div></div>"; layout_close();