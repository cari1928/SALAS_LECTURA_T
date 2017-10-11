<?php

class PromoGruposControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta promotor/templates_c
  }

  public function getReading($cvepromotor, $cveperiodo)
  {
    $sql = "SELECT DISTINCT letra, nombre, ubicacion, titulo FROM laboral
        INNER JOIN sala ON laboral.cvesala = sala.cvesala
        INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
        INNER JOIN libro li ON li.cvelibro = laboral.cvelibro_grupal
        WHERE cvepromotor=? AND laboral.cveperiodo=?
        ORDER BY letra";
    return $this->DB->GetAll($sql, array($cvepromotor, $cveperiodo));
  }

  public function getSchedule($cvepromotor, $cveperiodo)
  {
    $sql = "SELECT distinct dia.cvedia, abc.letra, dia.nombre, hrs.hora_inicial, hrs.hora_final FROM laboral la
      INNER JOIN horario h1 ON h1.cvehorario = la.cvehorario1 OR h1.cvehorario = la.cvehorario2
      INNER JOIN dia ON dia.cvedia = h1.cvedia
      INNER JOIN abecedario abc ON abc.cve = la.cveletra
      INNER JOIN horas hrs ON hrs.cvehoras = h1.cvehora
      WHERE cvepromotor=? AND la.cveperiodo=?
      ORDER BY letra, dia.cvedia, hrs.hora_inicial";
    return $this->DB->GetAll($sql, array($cvepromotor, $cveperiodo));
  }

  public function getLaboral($letra, $cveperiodo)
  {
    $sql = "SELECT * FROM laboral WHERE cveletra IN (SELECT cve FROM abecedario WHERE letra=?) AND cveperiodo=?";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo));
  }

  public function updateLaboral($nombre, $cveletra, $cveperiodo)
  {
    $sql = "UPDATE laboral SET nombre=? WHERE cveletra=? AND cveperiodo=?";
    return $this->query($sql, array($nombre, $cveletra, $cveperiodo));
  }

}
