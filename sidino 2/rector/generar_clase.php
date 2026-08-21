<?php
require_once __DIR__ . '/rector_common.php';

$msg = '';
$tipo = '';
$id_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'generar_clase') {
    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '') {
        $msg = 'Escribe el nombre de la clase.';
        $tipo = 'error';
    } elseif (mb_strlen($nombre) > 80) {
        $msg = 'El nombre no puede superar los 80 caracteres.';
        $tipo = 'error';
    } else {
        $duplicate = $pdo->prepare('SELECT COUNT(*) FROM curso WHERE LOWER(nombre) = LOWER(?)');
        $duplicate->execute([$nombre]);

        if ($duplicate->fetchColumn()) {
            $msg = 'Ya existe una clase con ese nombre.';
            $tipo = 'error';
        } else {
            try {
                $pdo->beginTransaction();
                $insert = $pdo->prepare('INSERT INTO curso (nombre) VALUES (?)');
                $insert->execute([$nombre]);

                if ($id_sesion) {
                    $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'curso')");
                    $hist->execute([$id_sesion, "Generó clase: $nombre"]);
                }
                $pdo->commit();
                $msg = "La clase {$nombre} fue creada correctamente.";
                $tipo = 'success';
                $_POST = [];
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $msg = 'No fue posible guardar la clase.';
                $tipo = 'error';
            }
        }
    }
}

$cursos = $pdo->query('SELECT id_curso, nombre FROM curso ORDER BY nombre')->fetchAll();
rector_layout_start('Generar Clase', 'generar_clase');
?>
<div class="section-title"><i class="fa-solid fa-chalkboard"></i> Generar clase</div>
<?php if ($msg): ?><div class="alert <?= $tipo === 'success' ? 'success' : 'error' ?>"><i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:620px;">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-plus"></i> Nueva clase</div></div>
  <form method="POST" action="generar_clase.php" style="padding:1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
    <input type="hidden" name="accion" value="generar_clase">
    <div class="form-group"><label class="form-label" for="nombre"><i class="fa-solid fa-chalkboard"></i> Nombre de la clase</label><input id="nombre" type="text" name="nombre" class="field-input" maxlength="80" placeholder="Ej: 6°A - Matemáticas" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required></div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Crear clase</button>
  </form>
</div>
<div class="card" style="max-width:620px;margin-top:1.25rem;">
  <div class="card-title"><i class="fa-solid fa-list"></i> Clases existentes</div>
  <?php if (!$cursos): ?><div class="empty-state"><i class="fa-solid fa-chalkboard"></i><p>No hay clases creadas todavía.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>#</th><th>Clase</th></tr></thead><tbody><?php foreach ($cursos as $curso): ?><tr><td><?= $curso['id_curso'] ?></td><td><?= htmlspecialchars($curso['nombre']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<?php rector_layout_end(); ?>