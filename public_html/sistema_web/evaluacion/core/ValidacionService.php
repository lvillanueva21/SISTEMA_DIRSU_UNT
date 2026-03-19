<?php
namespace EvalV4;

class ValidacionService {
  public static function normalizar(string $accion, array $in): array {
    $accion = strtolower(trim($accion));

    switch ($accion) {

      case 'cotejo':
        // admite 'aprobado','observado','en_espera' (mapeamos 'espera' -> 'en_espera')
        $v = isset($in['estado']) ? strtolower(trim((string)$in['estado'])) : '';
        if ($v === 'espera') $v = 'en_espera';
        if (!in_array($v, ['aprobado','observado','en_espera'], true)) $v = 'en_espera';

        // días permitidos 0..30 (0 = sin plazo)
        $dias = isset($in['dias_subsanacion']) ? (int)$in['dias_subsanacion'] : 0;
        if ($dias < 0 || $dias > 30) $dias = 0;

        $obs  = isset($in['obs']) ? substr(trim((string)$in['obs']), 0, 3000) : null;

        return ['estado'=>$v, 'dias'=>$dias, 'obs'=>$obs];

      case 'vb':
        // 'aprobado' o 'en_espera' (mapeamos 'espera' -> 'en_espera')
        $v = isset($in['estado']) ? strtolower(trim((string)$in['estado'])) : '';
        if ($v === 'espera') $v = 'en_espera';
        if (!in_array($v, ['aprobado','en_espera'], true)) $v = 'en_espera';
        return ['estado'=>$v];

      case 'rubrica':
        // solo normalizamos inputs; el estado se calcula en el handler
        $asp = [];
        for ($i=1; $i<=5; $i++) {
          $score = isset($in["a$i"]) ? (int)$in["a$i"] : 0; // 0..4
          if ($score<0) $score=0; if ($score>4) $score=4;
          $obs   = isset($in["o$i"]) ? substr(trim((string)$in["o$i"]), 0, 3000) : null;
          $asp[$i] = ['score'=>$score, 'obs'=>$obs];
        }
        $dias = isset($in['dias_subsanacion']) ? (int)$in['dias_subsanacion'] : 0;
        if ($dias < 0 || $dias > 30) $dias = 0;
        return ['aspectos'=>$asp, 'dias'=>$dias];

      default:
        return [];
    }
  }
}
