<?php
namespace Merp;

use mysqli;

class MerpEngine
{
    private mysqli $db;

    public function __construct(mysqli $conexion)
    {
        $this->db = $conexion;
    }

    /* ============ Punto de entrada ============ */
    public function render(string $reporte): string
    {
        $filas = $this->fetchFilas();          // lee la vista v_estado_proyecto
        ob_start();
        $datos = $filas;                      // visible en template
        include __DIR__ . '/templates/table.php';
        return ob_get_clean();
    }

    /* ============ Consulta principal ============ */

/**
 * Trae TODAS las filas que MERP debe mostrar.
 * - Lee directamente la vista v_estado_proyecto (DIRSU v2.2).
 * - Orden: proyecto más reciente y, dentro de él, periodo más reciente.
 * - Cada fila pasa por mapearFila() para formatear textos y badges.
 */
private function fetchFilas(): array
{
    $sql = "
        SELECT
            v.id_py                    AS id,
            p.p2                       AS titulo,
            v.periodo,
            v.nombres,
            v.apellidos,
            v.codigo_docente,
            p.estado                   AS estado_global,

            /* estados de aprobación */
            v.pcf_cot, v.pcf_rub,
            v.dd_vb,   v.df_vb,
            v.rsu_cot, v.rsu_rub,

            /* banderas de observación */
            v.obs_cfp, v.obs_rsu,

            /* texto y fecha de la última observación */
            v.texto_obs,
            v.fecha_obs,

            /* oficina donde se encuentra ahora */
            v.oficina_actual
        FROM v_estado_proyecto v
        JOIN proyectos p ON p.id = v.id_py
        ORDER BY v.id_py DESC, v.id_periodo DESC
        LIMIT 500
    ";

    $filas = [];

    if ($res = $this->db->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $filas[] = $this->mapearFila($row);
        }
        $res->free();
    } else {
        // Registra el error en el log del servidor para depuración futura
        error_log("MERP SQL error ({$this->db->errno}): {$this->db->error}");
    }

    return $filas;
}

    /* ============ Transformadores ============ */
private function mapearFila(array $r): array
{
    /* 1. Proyecto */
    $r['proyecto_fmt'] = sprintf(
        '%s <span class="badge bg-success">%s</span> <i>%d</i>',
        $r['titulo'],
        $r['periodo'],
        $r['id']
    );

    /* 2. Coordinador (ya no usamos splitCoordinador) */
    $r['coordinador_fmt'] = sprintf(
        '%s %s - <i>%s</i>',
        $r['nombres'],
        $r['apellidos'],
        $r['codigo_docente']
    );

    /* 3. Estado - Pendiente - Responsable */
    [$r['estado_fmt'], $r['pendiente_fmt'], $r['responsable_fmt']] =
        $this->calcularEstadoPendienteResp($r);

    /* 4. Observación detallada */
    $r['observacion_fmt'] = $this->extraerObservacion($r);

    return $r;
}


    /* ============ Lógica de negocio ============ */

    private function calcularEstadoPendienteResp(array $r): array
    {
        // Conteo de oficinas ya aprobadas (0-4)
        $aprob = (
            ($r['pcf_cot'] == 1 && $r['pcf_rub'] == 1) +
            ($r['dd_vb']   == 1) +
            ($r['df_vb']   == 1) +
            ($r['rsu_cot'] == 1 && $r['rsu_rub'] == 1)
        );
        $aprobStr = "[$aprob de 4 aprobaciones]";

        /* 0. Sin solicitud */
        if ($r['estado_global'] == 0 && $aprob == 0) {
            return [
                $this->badge('No ha solicitado Revisión<br>No se le puede aprobar', 'gris'),
                $this->badge('Solicitar revisión en<br>pestaña «Mi Progreso»', 'blanco', false),
                $this->badge('Coordinador del proyecto', 'gris', false)
            ];
        }

        /* 1. Aprobado total */
        if ($r['estado_global'] == 2 || $aprob == 4) {
            return [
                $this->badge("Aprobado totalmente <br>$aprobStr", 'amarillo'),
                $this->badge('Sin pendientes.<br>Proceso culminado<br>por este periodo', 'amarillo', false),
                $this->badge('No necesita', 'amarillo', false),
            ];
        }

        /* 2. Observado por CF / RSU  */
        if ($r['obs_cfp']) {
            return [
                $this->badge("Observado por Comité de Facultad<br>$aprobStr", 'rosa'),
                $this->pendienteObservado((int)$r['pcf_cot'], (int)$r['pcf_rub']),
                $this->badge('Coordinador del proyecto', 'gris', false)
            ];
        }
        if ($r['obs_rsu']) {
            return [
                $this->badge("Observado por Dirección de RSU<br>$aprobStr", 'rosa'),
                $this->pendienteObservado((int)$r['rsu_cot'], (int)$r['rsu_rub']),
                $this->badge('Coordinador del proyecto', 'gris', false)
            ];
        }

        /* 3. En evaluación según oficina_actual */
        switch ($r['oficina_actual']) {
            case 'pcf':
                return [
                    $this->badge("En Comité de Facultad<br>$aprobStr", 'azul'),
                    $this->badge('Revisión por<br>cotejo y rúbrica', 'azul'),
                    $this->badge('Presidente del comité de Facultad', 'azul', false)
                ];
            case 'dd':
                return [
                    $this->badge("En Dirección de Departamento<br>$aprobStr", 'naranja'),
                    $this->badge('Visto bueno', 'naranja', false),
                    $this->badge('Director de Departamento', 'naranja', false)
                ];
            case 'df':
                return [
                    $this->badge("En Decanato de Facultad<br>$aprobStr", 'cyan'),
                    $this->badge('Visto bueno', 'cyan', false),
                    $this->badge('Decanato de Facultad', 'cyan', false)
                ];
            case 'rsu':
            default:
                return [
                    $this->badge("En Dirección de RSU<br>$aprobStr", 'verde'),
                    $this->badge('Revisión por<br>cotejo y rúbrica', 'verde', false),
                    $this->badge('Dirección de RSU', 'verde', false)
                ];
        }
    }

/** Texto pendiente cuando hay observación */
private function pendienteObservado(int $cot, int $rub): string
{
    $txt = [];
    if ($cot === 2) $txt[] = 'cotejo';
    if ($rub === 2) $txt[] = 'rúbrica';

    if (empty($txt)) {
        // nunca debería ocurrir, pero por si acaso
        return $this->badge('Subsanar observación (detalle no especificado)', 'rosa', false);
    }

    return $this->badge(
        'Subsanar observación<br>de ' . implode(' y ', $txt),
        'rosa',
        false
    );
}

    /* ============ Observaciones placeholder ============ */
private function extraerObservacion(array $r): string
{
    /* a) Hay observación registrada */
    if ($r['texto_obs']) {
        $cabecera = $r['obs_cfp']
            ? 'Observado por Comité de Facultad.'
            : ($r['obs_rsu'] ? 'Observado por Dirección de RSU.' : '');
        return "{$cabecera} {$r['fecha_obs']}\n{$r['texto_obs']}";
    }

    /* b) Nunca solicitó revisión */
    if ($r['estado_global'] == 0)
        return 'No solicitó revisión, no puede recibir observaciones.';

    /* c) Aún sin observaciones */
    return 'Sin observaciones hasta el momento.';
}


    /* ============ Helpers ============ */

    private function getActivePeriodo(): int
    {
        $id = 0;
        $res = $this->db->query("SELECT id FROM periodos WHERE activo = 1 LIMIT 1");
        if ($res) $id = (int) ($res->fetch_row()[0] ?? 0);
        return $id;
    }

    private function splitCoordinador(?string $c): array
    {
        // Formato esperado: "Nombres|Apellidos|Código"
        if (!$c) return ['--','--','--'];
        $p = explode('|', $c);
        return [$p[0] ?? '', $p[1] ?? '', $p[2] ?? ''];
    }

    private function badge(string $txt, string $c, bool $pill = true): string
    {
        $p = MerpConfig::PALETA[$c];
        $cls = $p['text'] . ' badge' . ($pill ? ' rounded-pill' : '');
        return "<span class=\"$cls\" style=\"background:{$p['bg']}\">$txt</span>";
    }
}
