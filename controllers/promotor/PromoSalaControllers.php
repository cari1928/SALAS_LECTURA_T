<?php

class PromoSalaControllers extends Sistema
{
  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta promotor/templates_c
  }

  /**
   * Se obtiene el horario en base a la hora y día
   */
  public function getSchedule($cvehora, $cvedia)
  {
    $sql = "SELECT * FROM horario WHERE cvehora=? AND cvedia=?";
    return $this->DB->GetAll($sql, array($cvehora, $cvedia));
  }

  /**
   * Se obtiene el último registro de la tabla horario
   */
  public function getLastSchedule()
  {
    $sql = "SELECT * FROM horario ORDER BY cvehorario DESC LIMIT 1";
    return $this->DB->GetAll($sql);
  }

  /**
   * Inserta un registro en la tabla Horario
   */
  public function insertSchedule($cvehora, $cvedia)
  {
    $sql = "INSERT INTO horario(cvehora, cvedia) VALUES(?, ?)";
    return $this->query($sql, array($cvehora, $cvedia));
  }

  /**
   * Inserta un registro en la tabla Laboral
   */
  public function insertLaboral($cveperiodo, $cvesala, $cveletra, $nombre, $cvepromotor, $cvelibro_grupal, $cvehorario1)
  {
    $sql = "INSERT INTO laboral(cveperiodo, cvesala, cveletra, nombre, cvepromotor, cvelibro_grupal, cvehorario1)
      VALUES (?, ?, ?, ?, ?, ?, ?)";
    return $this->query($sql, array($cveperiodo, $cvesala, $cveletra, $nombre, $cvepromotor, $cvelibro_grupal, $cvehorario1));
  }

  /**
   * Actualiza un registro de la tabla Laboral
   */
  public function updateLaboral($cvehorario2, $cvelaboral)
  {
    $sql = "UPDATE laboral SET cvehorario2=? WHERE cvelaboral=?";
    return $this->query($sql, array($cvehorario2, $cvelaboral));
  }

  /**
   * Obtiene el último registro de la tabla Laboral
   */
  public function getLastLaboral()
  {
    $sql = "SELECT MAX(cvelaboral) AS cvelaboral FROM laboral";
    return $this->DB->GetAll($sql);
  }

  /**
   * Obtiene una letra de la tabla Abecedario en base a una cve
   */
  public function getLetter($cve)
  {
    $sql = "SELECT letra FROM abecedario WHERE cve=?";
    return $this->DB->GetAll($sql, $cve);
  }

  /**
   * Obtiene una sala en base a la cvesala
   */
  public function getClass($cvesala)
  {
    $sql = "SELECT * FROM sala WHERE cvesala=?";
    return $this->DB->GetAll($sql, $cvesala);
  }

  /**
   * Obtiene todos los días
   */
  public function getDays()
  {
    $sql = "SELECT * FROM dia";
    return $this->DB->GetAll($sql);
  }

  /**
   * Obtiene las horas disponibles para el promotor
   */
  public function getPromoHours($cvedia, $cvesala, $cveperiodo)
  {
    $sql = "SELECT cvehoras, hora_inicial, hora_final FROM horas
      EXCEPT
      (SELECT hs.cvehoras, hs.hora_inicial, hs.hora_final
      FROM laboral l
      INNER JOIN horario h1 ON h1.cvehorario = l.cvehorario1
      INNER JOIN horas hs ON hs.cvehoras = h1.cvehora
      WHERE cvedia=? AND cvesala=? AND cveperiodo=?
      UNION
      SELECT hs.cvehoras, hs.hora_inicial, hs.hora_final
      FROM laboral l
      INNER JOIN horario h1 ON h1.cvehorario = l.cvehorario2
      INNER JOIN horas hs ON hs.cvehoras = h1.cvehora
      WHERE cvedia=? AND cvesala=? AND cveperiodo=?)
      ORDER BY hora_inicial, hora_final";
    return $this->DB->GetAll($sql, array($cvedia, $cvesala, $cveperiodo, $cvedia, $cvesala, $cveperiodo));
  }

  /**
   * Obtiene la letra más alta de la tabla laboral, en base a cveperiodo
   */
  public function getLastLetterLaboral($cveperiodo)
  {
    $sql = "SELECT COALESCE(MAX(cveletra),0) as cveletra FROM laboral WHERE cveperiodo=?";
    return $this->DB->GetAll($sql, $cveperiodo);
  }

  public function getHours($cvehora)
  {
    $sql = "SELECT * FROM horas WHERE cvehoras=?";
    return $this->DB->GetAll($sql, $cvehora);
  }

  public function checkPromoGroups($cvepromotor, $cveperiodo)
  {
    $sql = "SELECT DISTINCT cveletra FROM laboral WHERE cvepromotor=? and cveperiodo=?";
    return $this->DB->GetAll($sql, array($cvepromotor, $cveperiodo));
  }

  public function getEnableClass()
  {
    $sql = "SELECT cvesala, ubicacion FROM sala WHERE disponible=true ORDER BY cvesala";
    return $this->DB->GetAll($sql);
  }

}
