<?php

class PromotorControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c
  }

  public function getPromotores()
  {
    $sql = "SELECT u.cveusuario, u.nombre, u.correo, otro AS \"Otro\", e.nombre AS \"Especialidad\",
      eu.cveespecialidad FROM usuarios u
      INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
      INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
      WHERE u.cveusuario in (SELECT cveusuario FROM usuario_rol WHERE cverol=2)
      ORDER BY u.cveusuario";
    return $this->DB->GetAll($sql);
  }

  public function getPromotoresByPeriod($cveperiodo)
  {
    $sql = "SELECT DISTINCT u.cveusuario ,u.nombre, u.correo, otro AS \"Otro\", e.nombre AS \"Especialidad\",
      eu.cveespecialidad FROM usuarios u
      INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
      INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
      INNER JOIN laboral ON laboral.cvepromotor = u.cveusuario
      WHERE u.cveusuario IN (SELECT cveusuario FROM usuario_rol WHERE cverol=2)
      AND cveperiodo=?
      ORDER BY u.cveusuario";
    return $this->DB->GetAll($sql, array());
  }

  public function updatePromoPass($arrData)
  {
    $sql = "UPDATE usuarios SET nombre=?, correo=?, pass=? WHERE cveusuario=?";
    return $this->query($sql, $arrData);
  }

  public function updatePromotor($arrData)
  {
    $sql = "UPDATE usuarios SET nombre=?, correo=? WHERE cveusuario=?";

    // $this->debug($sql, false);
    // $this->debug($arrData);

    return $this->query($sql, $arrData);
  }

  public function updateEspUsuario($cveespecialidad, $otro, $cveusuario)
  {
    $sql = "UPDATE especialidad_usuario SET cveespecialidad=?, otro=? WHERE cveusuario=? ";
    return $this->query($sql, array($cveespecialidad, $otro, $cveusuario));
  }

  public function getPromotor($cveusuario)
  {
    $sql = 'SELECT u.cveusuario, u.nombre AS "nombreUsuario", e.nombre, eu.cveespecialidad,
      eu.otro, u.correo
      FROM usuarios u
      INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
      INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
      WHERE u.cveusuario=?';
    return $this->DB->GetAll($sql, $cveusuario);
  }

  public function getTableGroups($cvepromotor, $cveperiodo)
  {
    $sql = "SELECT DISTINCT letra, nombre, ubicacion, titulo, laboral.cveperiodo
      FROM laboral
      INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
      INNER JOIN sala ON laboral.cvesala = sala.cvesala
      LEFT JOIN libro ON laboral.cvelibro_grupal = libro.cvelibro
      WHERE cvepromotor=? AND laboral.cveperiodo=?
      ORDER BY letra";
    return $this->DB->GetAll($sql, array($cvepromotor, $cveperiodo));
  }

  public function getHours($cveperiodo)
  {
    $sql = "SELECT distinct dia.cvedia, abc.letra, dia.nombre, hrs.hora_inicial, hrs.hora_final FROM laboral la
      INNER JOIN horario h1 ON h1.cvehorario = la.cvehorario1 OR h1.cvehorario = la.cvehorario2
      INNER JOIN dia ON dia.cvedia = h1.cvedia
      INNER JOIN abecedario abc ON abc.cve = la.cveletra
      INNER JOIN horas hrs ON hrs.cvehoras = h1.cvehora
      WHERE la.cveperiodo=?
      ORDER BY letra, dia.cvedia, hrs.hora_inicial";
    return $this->DB->GetAll($sql, $cveperiodo);
  }
}
