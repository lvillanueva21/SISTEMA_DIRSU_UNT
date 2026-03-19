<?php
require_once("../componentes/db.php");

/* ------------------------------------------------------------------ */
/* 1. LISTAR PROYECTOS CON APROBACIÓN TOTAL + Datos del coordinador   */
/* ------------------------------------------------------------------ */
$aprobados = mysqli_query($conexion, "
    SELECT  p.id,
            p.p2                        AS titulo,
            pr.id                       AS id_periodo,
            pr.nombre                   AS periodo,
            CONCAT(u.nombres,' ',u.apellidos) AS nombre_usuario,
            u.usuario,
            f.nombre                    AS facultad,
            d.nombre                    AS departamento
    FROM    proyectos               p
    JOIN    proyectos_periodo       pp  ON pp.id_py       = p.id
    JOIN    periodos                pr  ON pr.id          = pp.id_periodo
    JOIN    revisiones_proyectos    rp  ON rp.id_py       = p.id
                                       AND rp.id_periodo  = pp.id_periodo
    /* ─── buscamos SOLO al coordinador del proyecto ─── */
    LEFT JOIN usuarios_proyectos    up  ON up.id_proyecto = p.id
    LEFT JOIN usuarios              u   ON u.id           = up.id_usuario
                                       AND u.id_rol       = 2          -- coordinador
                                       AND up.activo      = 1
    LEFT JOIN departamentos         d   ON d.id           = u.id_depa
    LEFT JOIN facultades            f   ON f.id           = d.id_facultad
    WHERE   p.estado          = 2              -- proyecto aprobado totalmente
      AND   rp.oficina_actual = 'rsu'          -- terminó el flujo
    ORDER BY f.nombre, d.nombre, p.id
");

/* ------------------------------------------------------------------ */
/* 2. REINICIAR UN PROYECTO (POST)                                    */
/* ------------------------------------------------------------------ */
if (isset($_POST['reiniciar'])) {
    $id_py      = (int)$_POST['id_py'];
    $id_periodo = (int)$_POST['id_periodo'];

    try {
        $conexion->begin_transaction();

        /* a) borrar evaluaciones → cascada a rubrica_aspectos y observaciones */
        $conexion->query("
            DELETE FROM evaluaciones
            WHERE id_py = $id_py AND id_periodo = $id_periodo
        ");

        /* b) resetear flujo en revisiones_proyectos */
        $stmt = $conexion->prepare("
            UPDATE revisiones_proyectos
               SET oficina_actual = 'pcf',
                   estado         = 'editable',
                   fecha_solicitud = NOW(),
                   fecha_cierre    = NULL
            WHERE id_py = ? AND id_periodo = ?
        ");
        $stmt->bind_param('ii', $id_py, $id_periodo);
        $stmt->execute();

        /* c) proyecto vuelve a “en revisión” (1) */
        $conexion->query("UPDATE proyectos SET estado = 1 WHERE id = $id_py");

        /* d) historial */
        $stmt = $conexion->prepare("
            INSERT INTO historial_estados
              (id_py,id_periodo,fecha,accion,descripcion,usuario_id)
            VALUES (?,?,NOW(),'Reinicio de proyecto',
                    'Devuelto al Comité de Facultad para nueva evaluación',NULL)
        ");
        $stmt->bind_param('ii', $id_py, $id_periodo);
        $stmt->execute();

        $conexion->commit();
        echo "<div class='alert alert-success'>✅ Proyecto #$id_py reiniciado con éxito.</div>";
    } catch (Exception $e) {
        $conexion->rollback();
        echo "<div class='alert alert-danger'>❌ Error: {$e->getMessage()}</div>";
    }
}
?>

<!-- ----------------------------------------------------------------- -->
<!-- 3. TABLA INTERACTIVA                                              -->
<!-- ----------------------------------------------------------------- -->
<?php if (mysqli_num_rows($aprobados) === 0): ?>
    <div class="alert alert-info">No hay proyectos con aprobación total.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-bordered table-sm">
    <thead class="thead-dark">
        <tr>
            <th>ID&nbsp;Proyecto</th>
            <th>Título</th>
            <th>Coordinador</th>
            <th>Usuario</th>
            <th>Facultad</th>
            <th>Departamento</th>
            <th>Período</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($p = mysqli_fetch_assoc($aprobados)): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['titulo']) ?></td>
            <td><?= htmlspecialchars($p['nombre_usuario'] ?? '—') ?></td>
            <td><?= htmlspecialchars($p['usuario']        ?? '—') ?></td>
            <td><?= htmlspecialchars($p['facultad']       ?? '—') ?></td>
            <td><?= htmlspecialchars($p['departamento']   ?? '—') ?></td>
            <td><?= htmlspecialchars($p['periodo']) ?></td>
            <td class="text-center">
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('¿Reiniciar el proyecto #<?= $p['id'] ?>?');">
                    <input type="hidden" name="id_py"      value="<?= $p['id'] ?>">
                    <input type="hidden" name="id_periodo" value="<?= $p['id_periodo'] ?>">
                    <button name="reiniciar" class="btn btn-sm btn-warning">
                        <i class="fas fa-undo-alt"></i> Reiniciar
                    </button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
