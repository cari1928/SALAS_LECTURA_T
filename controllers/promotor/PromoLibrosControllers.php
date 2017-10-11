<?php

class PromoLibrosControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta promotor/templates_c
  }

  public function getLaboral($cvepromotor, $cveperiodo, $letra, $nocontrol)
  {
    $sql = "SELECT * FROM laboral
      INNER JOIN lectura ON lectura.cveletra = laboral.cveletra
      WHERE cvepromotor = ?
        AND lectura.cveperiodo=?
        AND lectura.cveletra IN (SELECT cve from abecedario where letra=?)
        AND lectura.nocontrol=?";
    return $this->DB->GetAll($sql, array($cvepromotor, $cveperiodo, $letra, $nocontrol));
  }

  public function getBooks($status, $cvelectura)
  {
    $sql = "SELECT cvelibro, titulo, autor, editorial FROM libro
      WHERE status=?
      AND cvelibro IN (SELECT cvelibro FROM lista_libros where cvelectura=?)
      ORDER BY titulo";
    return $this->DB->GetAll($sql, array($status, $cvelectura));
  }

}
