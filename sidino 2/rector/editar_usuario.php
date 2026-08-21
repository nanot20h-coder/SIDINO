<?php
require_once __DIR__ . '/rector_common.php';
$id = (int)($_GET['id'] ?? $_POST['id_usuario'] ?? 0);
$usuario = null;
if ($id) { $q = $pdo->prepare('SELECT * FROM usuario WHERE id_usuario = ?'); $q->execute([$id]); $usuario = $q->fetch(); }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $usuario) {
    $nombre = trim($_POST['nombre'] ?? ''); $correo = trim($_POST['correo'] ?? ''); $rol = (int)($_POST['id_rol'] ?? 0); $pass = trim($_POST['contrasena'] ?? '');
    if (!$nombre || !$correo || !$rol) $msg = 'Nombre, correo y rol son obligatorios.';
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $msg = 'El correo ingresado no es válido.';
    else {
        $check = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE correo = ? AND id_usuario != ?'); $check->execute([$correo, $id]);
        if ($check->fetchColumn()) $msg = 'Ese correo ya está en uso por otro usuario.';
        else {
            if ($pass !== '') { $update = $pdo->prepare('UPDATE usuario SET nombre=?, correo=?, id_rol=?, contrasena=? WHERE id_usuario=?'); $update->execute([$nombre, $correo, $rol, password_hash($pass, PASSWORD_DEFAULT), $id]); }
            else { $update = $pdo->prepare('UPDATE usuario SET nombre=?, correo=?, id_rol=? WHERE id_usuario=?'); $update->execute([$nombre, $correo, $rol, $id]); }
            $sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
            if ($sesion) { $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'usuario')"); $hist->execute([$sesion, "Editó usuario: $nombre (ID $id)"]); }
            header('Location: usuarios.php?editado=1'); exit;
        }
    }
}
$roles = $pdo->query('SELECT * FROM rol ORDER BY id_rol')->fetchAll();
rector_layout_start('Editar Usuario', 'usuarios');
if (!$usuario): ?><div class="alert error"><i class="fa-solid fa-circle-xmark"></i> Usuario no encontrado.</div><a href="usuarios.php" class="btn btn-secondary">Volver</a>
<?php else: ?>
<div class="section-title"><i class="fa-solid fa-pen-to-square"></i> Editar Usuario</div>
<?php if ($msg): ?><div class="alert error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:540px;"><div class="card-header"><div class="card-title"><i class="fa-solid fa-id-card"></i> Editando: <?= htmlspecialchars($usuario['nombre']) ?></div></div>
<form method="POST" action="editar_usuario.php?id=<?= $id ?>" style="padding:1.5rem;display:flex;flex-direction:column;gap:1.1rem;"><input type="hidden" name="id_usuario" value="<?= $id ?>">
<div class="form-group"><label class="form-label">Nombre completo</label><input type="text" name="nombre" class="form-input" value="<?= htmlspecialchars($_POST['nombre'] ?? $usuario['nombre']) ?>" required></div>
<div class="form-group"><label class="form-label">Correo electrónico</label><input type="email" name="correo" class="form-input" value="<?= htmlspecialchars($_POST['correo'] ?? $usuario['correo']) ?>" required></div>
<div class="form-group"><label class="form-label">Rol</label><select name="id_rol" class="form-input" required><?php foreach ($roles as $rol): ?><option value="<?= $rol['id_rol'] ?>" <?= ($_POST['id_rol'] ?? $usuario['id_rol']) == $rol['id_rol'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre_rol']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label">Nueva contraseña</label><input type="text" name="contrasena" class="form-input" placeholder="Solo si deseas cambiarla"></div>
<div style="display:flex;gap:.8rem;"><button type="submit" class="btn btn-primary" style="flex:1;"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button><a href="usuarios.php" class="btn btn-secondary" style="flex:1;text-align:center;">Cancelar</a></div></form></div>
<?php endif; rector_layout_end(); ?>
