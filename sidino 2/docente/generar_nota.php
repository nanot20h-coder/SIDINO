<?php
require_once __DIR__ . '/auth.php';
require_auth([4]);

$pdo = getDB();
$id_docente = $_SESSION['user_id'];
$matriculas = $pdo->prepare("SELECT mat.id_matricula, u.nombre AS estudiante, m.nombre AS materia, c.nombre AS curso FROM matricula mat JOIN usuario u ON mat.id_estudiante = u.id_usuario JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion JOIN materia m ON aa.id_materia = m.id_materia JOIN curso c ON aa.id_curso = c.id_curso WHERE aa.id_docente = ? ORDER BY c.nombre, u.nombre");
$matriculas->execute([$id_docente]);
$matriculas = $matriculas->fetchAll();

layout_head('Docente — Generar Nota');
echo "<div class='app'>";
echo sidebar_html('notas', [
        ['id'=>'dashboard', 'href'=>'docente.php', 'icon'=>'fa-gauge', 'label'=>'Dashboard'],
        ['id'=>'asistencia', 'href'=>'asistencia.php',    'icon'=>'fa-user-check',  'label'=>'Asistencia'],
        ['id'=>'horario', 'href'=>'horario.php', 'icon'=>'fa-calendar-days', 'label'=>'Mis clases'],
        ['id'=>'notas', 'href'=>'generar_nota.php', 'icon'=>'fa-star', 'label'=>'Registrar notas'],
        ['id'=>'observador', 'href'=>'observador.php', 'icon'=>'fa-book-open', 'label'=>'Observador'],
        ['id'=>'contenido', 'href'=>'contenido.php', 'icon'=>'fa-folder-open', 'label'=>'Contenido'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Docente — Generar Nota', '#20a7e0');
echo "<div class='module-container'>";
?>
<div class="section-title"><i class="fa-solid fa-star"></i> Generar nota</div>
<?php if (isset($_GET['msg_success'])): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_GET['msg_success']) ?></div><?php endif; ?>
<div class="card" style="max-width:850px;">
    <div class="card-title" style="margin-bottom:1rem"><i class="fa-solid fa-plus"></i> Nueva nota académica</div>
    <?php if (!$matriculas): ?>
        <div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>No tienes estudiantes matriculados en tus clases.</p></div>
    <?php else: ?>
    <form method="POST" action="docente.php">
        <input type="hidden" name="registrar_nota" value="1">
        <div class="form-inline">
            <div class="field-group">
                <label class="field-label">Estudiante / Materia</label>
                <select name="id_matricula" class="field-input" required>
                    <option value="">Selecciona un estudiante</option>
                    <?php foreach ($matriculas as $matricula): ?><option value="<?= $matricula['id_matricula'] ?>"><?= htmlspecialchars($matricula['estudiante']) ?> · <?= htmlspecialchars($matricula['materia']) ?> (<?= htmlspecialchars($matricula['curso']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="field-group" style="max-width:120px"><label class="field-label">Nota (0–5)</label><input type="number" name="valor" class="field-input" min="0" max="5" step="0.1" placeholder="4.5" required></div>
            <div class="field-group"><label class="field-label">Tipo</label><input type="text" name="tipo" class="field-input" placeholder="Examen parcial" required></div>
            <div class="field-group" style="max-width:120px"><label class="field-label">Porcentaje</label><input type="number" name="porcentaje" class="field-input" min="1" max="100" placeholder="30" required></div>
            <div class="field-group" style="max-width:160px"><label class="field-label">Fecha</label><input type="date" name="fecha" class="field-input" value="<?= date('Y-m-d') ?>" required></div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar nota</button>
    </form>
    <?php endif; ?>
</div>
<?php
echo "</div></div></div>";
layout_close();
