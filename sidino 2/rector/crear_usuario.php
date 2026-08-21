<?php
require_once __DIR__ . '/rector_common.php';
$msg = '';
$tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol = (int)($_POST['id_rol'] ?? 0);
    $pass = trim($_POST['contrasena'] ?? '');
    if (!$nombre || !$correo || !$rol || !$pass) { $msg = 'Todos los campos son obligatorios.'; $tipo = 'error'; }
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) { $msg = 'El correo ingresado no es válido.'; $tipo = 'error'; }
    else {
        $check = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE correo = ?'); $check->execute([$correo]);
        if ($check->fetchColumn()) { $msg = 'Ya existe un usuario con ese correo.'; $tipo = 'error'; }
        else {
            $insert = $pdo->prepare('INSERT INTO usuario (nombre, correo, contrasena, id_rol) VALUES (?, ?, ?, ?)');
            $insert->execute([$nombre, $correo, password_hash($pass, PASSWORD_DEFAULT), $rol]);
            $nuevo_id = $pdo->lastInsertId();
            $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'usuario')");
            $hist->execute([$_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'], "Creó usuario: $nombre (ID $nuevo_id)"]);
            $msg = "Usuario «{$nombre}» creado exitosamente (ID: {$nuevo_id})."; $tipo = 'success';
        }
    }
}
$roles = $pdo->query('SELECT * FROM rol ORDER BY id_rol')->fetchAll();
rector_layout_start('Crear Usuario', 'crear_usuario');
?>
<div class="section-title"><i class="fa-solid fa-user-plus"></i> Crear Nuevo Usuario</div>
<?php if ($msg): ?><div class="alert <?= $tipo === 'success' ? 'success' : 'error' ?>"><i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card" style="max-width:540px;"><div class="card-header"><div class="card-title"><i class="fa-solid fa-id-card"></i> Datos del nuevo usuario</div></div>
<form method="POST" action="crear_usuario.php" style="padding:1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
<input type="hidden" name="accion" value="crear_usuario">
<div class="form-group"><label class="form-label"><i class="fa-solid fa-user"></i> Nombre completo</label><input type="text" name="nombre" class="form-input" placeholder="Ej: María López" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required></div>
<div class="form-group"><label class="form-label"><i class="fa-solid fa-envelope"></i> Correo electrónico</label><input type="email" name="correo" class="form-input" placeholder="usuario@correo.com" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required></div>
<div class="form-group"><label class="form-label"><i class="fa-solid fa-shield-halved"></i> Rol</label><select name="id_rol" class="form-input" required><option value="">— Seleccionar rol —</option><?php foreach ($roles as $rol): ?><option value="<?= $rol['id_rol'] ?>" <?= ($_POST['id_rol'] ?? '') == $rol['id_rol'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre_rol']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label"><i class="fa-solid fa-lock"></i> Contraseña temporal</label><input type="text" name="contrasena" class="form-input" placeholder="Contraseña inicial para el usuario" required></div>
<div style="display:flex;gap:.8rem;margin-top:.4rem;"><button type="submit" class="btn btn-primary" style="flex:1;"><i class="fa-solid fa-floppy-disk"></i> Crear usuario</button><a href="usuarios.php" class="btn btn-secondary" style="flex:1;text-align:center;"><i class="fa-solid fa-arrow-left"></i> Ver todos</a></div>
</form></div>
<?php rector_layout_end(); ?>
