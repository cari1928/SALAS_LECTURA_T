<?php

class InscripcionControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta alumno/templates_c
  }

  /**
   * Verifica que el grupo exista
   */
  public function checkGroup($letra, $cveperiodo)
  {
    $sql = "SELECT * FROM laboral
      WHERE cveletra IN (SELECT cve FROM abecedario WHERE letra=?) AND cveperiodo=?";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo));
  }

  /**
   * Verifica si el usuario ya está inscrito en el grupo seleccionado
   */
  public function isRolledOnGroup($nocontrol, $cveletra, $cveperiodo)
  {
    $sql = "SELECT * FROM lectura
    WHERE nocontrol=? AND cveperiodo=? AND cveletra IN
      (SELECT cve FROM abecedario WHERE cve IN
        (SELECT cveletra FROM laboral WHERE cveletra=? AND cveperiodo=?))";
    return $this->DB->GetAll($sql, array($nocontrol, $cveperiodo, $cveletra, $cveperiodo));
  }

  /**
   * Verifica si el usuario ya está inscrito en algún grupo
   */
  public function isRolledOn($nocontrol, $cveperiodo)
  {
    $sql = "SELECT * FROM lectura WHERE nocontrol=? AND cveperiodo=?";
    return $this->DB->GetAll($sql, array($nocontrol, $cveperiodo));
  }

  /**
   * Inserta en la tabla lectura
   */
  public function insertReading($nocontrol, $cveletra, $cveperiodo)
  {
    $sql = "INSERT INTO lectura(nocontrol, cveletra, cveperiodo) VALUES(?, ?, ?)";
    return $this->query($sql, array($nocontrol, $cveletra, $cveperiodo));
  }

  /**
   * Obtiene un registro de la tabla Lectura en base a nocontrol y cveperiodo
   */
  public function getReading($nocontrol, $cveperiodo)
  {
    $sql = "SELECT * FROM lectura WHERE nocontrol=? AND cveperiodo=?";
    return $this->DB->GetAll($sql, array($nocontrol, $cveperiodo));
  }

  /**
   * Obtiene un registro de la tabla abecedario en base a la cve
   */
  public function getLetter($cve)
  {
    $sql = "SELECT letra FROM abecedario WHERE cve=?";
    return $this->DB->GetAll($sql, $cve);
  }

  /**
   * Listado de grupos para un periodo específico
   */
  public function listGroups($cveperiodo)
  {
    $sql = "SELECT DISTINCT letra, laboral.nombre AS \"nombre_grupo\", ubicacion, usuarios.nombre AS \"nombre_promotor\"
      FROM laboral
      INNER JOIN abecedario ON abecedario.cve = laboral.cveletra
      INNER JOIN sala ON sala.cvesala = laboral.cvesala
      INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
      WHERE laboral.cveperiodo=?
      ORDER BY letra";
    return $this->DB->GetAll($sql, $cveperiodo);
  }

}
