<?php

class GrupoControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta promotor/templates_c
  }

  /**
   * Obtiene la lista de libros del alumno
   */
  public function listBooks($nocontrol, $cveperiodo)
  {
    $sql = "SELECT * FROM lista_libros
      INNER JOIN lectura ON lista_libros.cvelectura = lectura.cvelectura
      INNER JOIN libro ON libro.cvelibro = lista_libros.cvelibro
      INNER JOIN estado ON lista_libros.cveestado = estado.cveestado
      WHERE nocontrol=? AND lectura.cveperiodo=?
      ORDER BY libro.cvelibro";
    return $this->DB->GetAll($sql, array($nocontrol, $cveperiodo));
  }

  public function getBookList($cvelectura, $nocontrol)
  {
    $sql = "SELECT cveestado FROM lista_libros
    WHERE cvelectura=?
      AND cvelectura in (SELECT cvelectura FROM lectura WHERE nocontrol=?)";
    return $this->DB->GetAll($sql, array($cvelectura, $nocontrol));
  }

  public function getLaboral($cvelectura, $cveperiodo)
  {
    $sql = "SELECT * FROM laboral
    WHERE cveletra IN (SELECT cveletra FROM lectura WHERE cvelectura=? AND cveperiodo=?)";
    return $this->DB->GetAll($sql, array($cvelectura, $cveperiodo));
  }

  public function getPromoGroup($letra, $cveperiodo)
  {
    $sql = "SELECT cvepromotor FROM laboral
      WHERE cveletra IN (SELECT cve FROM abecedario WHERE letra=?) and cveperiodo=?";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo));
  }

  public function getEvaluations($cvepromotor, $cveeval)
  {
    $sql = "SELECT * FROM evaluacion
      INNER JOIN lectura on lectura.cvelectura = evaluacion.cvelectura
      INNER JOIN abecedario on abecedario.cve = lectura.cveletra
      INNER JOIN laboral on abecedario.cve = laboral.cveletra
      WHERE cvepromotor=? and cveeval=?";
    return $this->DB->GetAll($sql, array($cvepromotor, $cveeval));
  }

  public function getInfoHeader($cvepromotor, $cveperiodo, $letra)
  {
    $sql = "SELECT distinct letra, nombre, ubicacion, fechainicio, fechafinal FROM laboral
      INNER JOIN sala on laboral.cvesala = sala.cvesala
      INNER JOIN abecedario on laboral.cveletra = abecedario.cve
      INNER JOIN periodo on laboral.cveperiodo= periodo.cveperiodo
      WHERE cvepromotor=? and laboral.cveperiodo=? and letra=?
      ORDER BY letra";
    return $this->DB->GetAll($sql, array($cvepromotor, $cveperiodo, $letra));
  }

  public function getDataTable($letra, $cveperiodo)
  {
    $sql = "SELECT distinct usuarios.nombre, comprension, participacion,
      terminado, asistencia, reporte, actividades, nocontrol, cveeval, lectura.cveperiodo,
      lectura.cvelectura, asistencia FROM lectura
      INNER JOIN evaluacion on evaluacion.cvelectura = lectura.cvelectura
      INNER JOIN abecedario on lectura.cveletra = abecedario.cve
      INNER JOIN usuarios on lectura.nocontrol = usuarios.cveusuario
      INNER JOIN laboral on abecedario.cve = laboral.cveletra
      WHERE letra=? and lectura.cveperiodo=?
      ORDER BY usuarios.nombre";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo));
  }

  public function getCveLetter($letra)
  {
    $sql = "SELECT DISTINCT laboral.cveletra FROM laboral
      INNER JOIN abecedario a ON a.cve = laboral.cveletra
      WHERE letra=?";
    return $this->DB->GetAll($sql, $letra);
  }

}
