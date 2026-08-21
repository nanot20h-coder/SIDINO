<?php
require_once __DIR__ . '/rector_common.php';

$msg = '';
$tipo = '';
$id_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_usuario') {
    $id = (int)($_POST['id_usuario'] ?? 0);
    if (!$id) { $msg = 'Usuario no válido.'; $tipo = 'error'; }
    elseif ($id == $id_sesion) { $msg = 'No puedes eliminar tu propio usuario.'; $tipo = 'error'; }
    else {
        $check = $pdo->prepare('SELECT nombre FROM usuario WHERE id_usuario = ?');
        $check->execute([$id]);
        $nombre = $check->fetchColumn();
        if (!$nombre) { $msg = 'El usuario no existe.'; $tipo = 'error'; }
        else {
            $pdo->prepare('DELETE FROM usuario WHERE id_usuario = ?')->execute([$id]);
            if ($id_sesion) {
                $hist = $pdo->prepare("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'usuario')");
                $hist->execute([$id_sesion, "Eliminó usuario: $nombre (ID $id)"]);
            }
            $msg = "Usuario «{$nombre}» eliminado correctamente."; $tipo = 'success';
        }
    }
}

$usuarios = rector_usuarios($pdo);
rector_layout_start('Gestión de Usuarios', 'usuarios');
?>
<div class="section-title"><i class="fa-solid fa-users"></i> Gestión de Usuarios</div>
<?php if ($msg): ?><div class="alert <?= $tipo === 'success' ? 'success' : 'error' ?>"><i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if (isset($_GET['editado'])): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> Usuario actualizado correctamente.</div><?php endif; ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-list"></i> Todos los usuarios</div><a href="crear_usuario.php" class="card-action"><i class="fa-solid fa-user-plus"></i> Crear usuario</a></div>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr></thead>
    <tbody><?php foreach ($usuarios as $usuario): $es_yo = $usuario['id_usuario'] == $id_sesion; ?>
      <tr><td><?= $usuario['id_usuario'] ?></td><td><?= htmlspecialchars($usuario['nombre']) ?></td><td><?= htmlspecialchars($usuario['correo']) ?></td><td><span class="badge <?= rector_badge((int)$usuario['id_rol']) ?>"><?= htmlspecialchars($usuario['nombre_rol']) ?></span></td><td style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
        <a href="editar_usuario.php?id=<?= $usuario['id_usuario'] ?>" class="btn btn-primary" style="padding:.3rem .7rem;font-size:.8rem;"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
        <?php if ($es_yo): ?><span style="color:var(--text-muted,#aaa);font-size:.8rem;"><i class="fa-solid fa-user-check"></i> Tú</span><?php else: ?>
        <form method="POST" action="usuarios.php" style="display:inline;" onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($usuario['nombre'])) ?>? Esta acción no se puede deshacer.')"><input type="hidden" name="accion" value="eliminar_usuario"><input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>"><button type="submit" class="btn btn-danger" style="padding:.3rem .7rem;font-size:.8rem;"><i class="fa-solid fa-trash"></i> Eliminar</button></form>
        <?php endif; ?>
      </td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div>
<?php rector_layout_end(); ?>
