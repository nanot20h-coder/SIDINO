<?php
require_once __DIR__ . '/rector_common.php';
$historial = rector_historial($pdo);
rector_layout_start('Historial de Acciones', 'historial');
?><div class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Historial de Acciones</div><div class="card"><div class="table-wrap"><?php if (!$historial): ?><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin actividad registrada aún</p></div><?php else: ?><table><thead><tr><th>Usuario</th><th>Acción</th><th>Tabla afectada</th><th>Fecha y hora</th></tr></thead><tbody><?php foreach ($historial as $h): ?><tr><td><?= htmlspecialchars($h['nombre']) ?></td><td><?= htmlspecialchars($h['accion']) ?></td><td><span class="badge blue"><?= htmlspecialchars($h['tabla_afectada']) ?></span></td><td><?= date('d/m/Y H:i', strtotime($h['fecha'])) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div><?php rector_layout_end(); ?>
