<?php
namespace EvalV4;

class RutaService {
  private \mysqli $db;
  public function __construct(\mysqli $db){ $this->db = $db; }

  public function oficinas(): array {
    $out=[]; $rs=$this->db->query("SELECT id,codigo,nombre FROM eva_oficinas WHERE activo=1 ORDER BY orden ASC");
    if($rs){ while($r=$rs->fetch_assoc()){ $out[]=['id'=>(int)$r['id'],'codigo'=>$r['codigo'],'nombre'=>$r['nombre']]; } $rs->free(); }
    return $out;
  }

  public function siguienteOficinaId(int $actual_id): ?int {
    $ofs = $this->oficinas();
    $n   = count($ofs);
    for($i=0;$i<$n;$i++){
      if ($ofs[$i]['id']===$actual_id) { return ($i+1<$n) ? (int)$ofs[$i+1]['id'] : null; }
    }
    return null;
  }
}
