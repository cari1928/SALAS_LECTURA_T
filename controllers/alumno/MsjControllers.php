<?php

class MsjControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta alumno/templates_c
  }

  public function getLaboral($letra, $cveperiodo)
  {
    $sql = "SELECT * FROM laboral
      WHERE cveletra in (SELECT cve FROM abecedario WHERE letra=?)
      AND cveletra in (SELECT cveletra FROM lectura WHERE cveletra in (SELECT cve FROM abecedario WHERE letra=?))
      AND laboral.cveperiodo=?";
    return $this->DB->GetAll($sql, array($letra, $letra, $cveperiodo));
  }

  public function getMsj($cveperiodo, $letra)
  {
    $sql = "SELECT cvemsj, introduccion, tipomsj.descripcion, fecha, expira FROM msj
      INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
      WHERE cveperiodo=?
      AND (tipo='G' OR tipo='I')
      AND cveletra in (SELECT cve FROM abecedario WHERE letra=?)
      AND expira > NOW()
      ORDER BY cvemsj, fecha";
    return $this->DB->GetAll($sql, array($cveperiodo, $letra));
  }

}
