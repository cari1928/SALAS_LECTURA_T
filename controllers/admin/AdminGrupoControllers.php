<?php

class AdminGrupoControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c
  }

  public function getGroup($letra, $cveperiodo)
  {
    $sql = "SELECT * FROM lectura
      INNER JOIN laboral ON laboral.cveletra = lectura.cveletra
      WHERE laboral.cveletra IN (SELECT cve FROM abecedario WHERE letra=?)
        AND lectura.cveperiodo=?";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo));
  }

}
