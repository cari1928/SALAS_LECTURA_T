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

  public function getStudents($letra, $cveperiodo, $cvepromotor)
  {
    $sql = "SELECT DISTINCT usuarios.nombre, comprension, actividades, reporte, participacion, asistencia,
      terminado, nocontrol, laboral.cvepromotor, abecedario.letra FROM lectura
      INNER JOIN evaluacion ON evaluacion.cvelectura = lectura.cvelectura
      INNER JOIN usuarios ON usuarios.cveusuario = lectura.nocontrol
      INNER JOIN abecedario ON abecedario.cve = lectura.cveletra
      INNER JOIN laboral ON laboral.cveletra= lectura.cveletra
      WHERE letra=? AND lectura.cveperiodo=? AND laboral.cvepromotor=?
      ORDER BY usuarios.nombre";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo, $cvepromotor));
  }

  public function getInfoHeader($cveperiodo, $letra)
  {
    $sql = "SELECT distinct letra, laboral.nombre AS \"nombre_grupo\", sala.ubicacion,
      fechainicio, fechafinal, usuarios.nombre AS \"nombre_promotor\",
      usuarios.cveusuario AS \"cvepromotor\" FROM laboral
      INNER JOIN sala ON laboral.cvesala = sala.cvesala
      INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
      INNER JOIN periodo ON laboral.cveperiodo= periodo.cveperiodo
      INNER JOIN lectura ON abecedario.cve = abecedario.cve
      INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
      WHERE laboral.cveperiodo=? AND letra=?
      ORDER BY letra";
    return $this->DB->GetAll($sql, array($cveperiodo, $letra));
  }

}
