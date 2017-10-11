<?php

class AdminGruposControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c
  }

  public function getGrupos($cveperiodo)
  {
    $sql = "SELECT COALESCE(MAX(cveletra),0) as cveletra FROM laboral WHERE cveperiodo=?";
    return $this->DB->GetAll($sql, $cveperiodo);
  }

  public function insertLaboral($cveperiodo, $cvesala, $cveletra, $nombre, $cvepromotor, $cvelibro_grupal, $cvehorario1, $cvehorario2)
  {
    $sql = "INSERT INTO laboral(cveperiodo, cvesala, cveletra, nombre, cvepromotor, cvelibro_grupal, cvehorario1, cvehorario2)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // $this->debug($sql, false);
    // $this->debug(array($cveperiodo, $cvesala, $cveletra, $nombre, $cvepromotor, $cvelibro_grupal, $cvehorario1, $cvehorario2), false);

    return $this->query($sql, array($cveperiodo, $cvesala, $cveletra, $nombre, $cvepromotor, $cvelibro_grupal, $cvehorario1, $cvehorario2));
  }

  public function insertHorario($cvehora, $cvedia)
  {
    $sql = "INSERT INTO horario(cvehora, cvedia) VALUES(?, ?)";

    // $this->debug($sql, false);
    // $this->debug(array($cvehora, $cvedia), false);

    return $this->query($sql, array($cvehora, $cvedia));
  }

  public function getMaxCveHorario()
  {
    $sql = "SELECT cvehorario FROM horario ORDER BY cvehorario DESC LIMIT 1";
    return $this->DB->GetAll($sql);
  }

  public function getPromotores($cveperiodo)
  {
    $sql = "SELECT cveusuario, usuarios.nombre
      FROM usuarios
      WHERE cveusuario NOT IN (SELECT cvepromotor
        FROM laboral
        INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
        WHERE cveperiodo=?
        GROUP BY 1
        HAVING COUNT(cvepromotor)=6)
      AND cveusuario IN (SELECT cveusuario FROM usuario_rol WHERE cverol=2);";
    return $this->DB->GetAll($sql, $cveperiodo);
  }

  public function getHoras($cvedia, $cvesala, $cveperiodo)
  {
    $sql = "SELECT cvehoras, hora_inicial, hora_final FROM horas
      WHERE cvehoras NOT IN
      (SELECT h1.cvehoras FROM laboral
      INNER JOIN horario hr1 ON hr1.cvehorario = laboral.cvehorario1
      INNER JOIN horas h1 ON hr1.cvehora = h1.cvehoras
      INNER JOIN dia ON hr1.cvedia = dia.cvedia
      INNER JOIN sala ON sala.cvesala = laboral.cvesala
      WHERE dia.cvedia=? AND sala.cvesala=? AND laboral.cveperiodo=? )
      AND cvehoras NOT IN
      (SELECT h2.cvehoras FROM laboral
      INNER JOIN horario hr2 ON hr2.cvehorario = laboral.cvehorario2
      INNER JOIN horas h2 ON hr2.cvehora = h2.cvehoras
      INNER JOIN dia ON hr2.cvedia = dia.cvedia
      INNER JOIN sala ON sala.cvesala = laboral.cvesala
      WHERE dia.cvedia=? AND sala.cvesala=? AND laboral.cveperiodo=? )
      ORDER BY hora_inicial, hora_final";
    return $this->DB->GetAll($sql, array($cvedia, $cvesala, $cveperiodo, $cvedia, $cvesala, $cveperiodo));
  }

  public function getAllGrupos($cveperiodo)
  {
    $sql = "SELECT DISTINCT abecedario.letra, usuarios.cveusuario AS \"nocontrol\", usuarios.nombre AS \"nombre_promotor\",
      laboral.nombre, sala.ubicacion, laboral.cvepromotor, titulo
      FROM laboral
      INNER JOIN sala ON sala.cvesala = laboral.cvesala
      INNER JOIN abecedario ON abecedario.cve = laboral.cveletra
      INNER JOIN usuarios ON usuarios.cveusuario = laboral.cvepromotor
      LEFT JOIN libro ON laboral.cvelibro_grupal = libro.cvelibro
      WHERE laboral.cveperiodo=?
      ORDER BY abecedario.letra";

    // $this->debug($sql, false);
    // $this->debug($cveperiodo, false);

    return $this->DB->GetAll($sql, $cveperiodo);
  }

  public function getSalas($cveperiodo, $cvehora, $cvedia, $cvesala)
  {
    $sql = "SELECT * FROM laboral
      INNER JOIN sala on laboral.cvesala = sala.cvesala
      INNER JOIN horario hr1 ON hr1.cvehorario = laboral.cvehorario1
      INNER JOIN horario hr2 ON hr2.cvehorario = laboral.cvehorario2
      WHERE laboral.cveperiodo=? AND ( (hr1.cvehora=? AND hr1.cvedia=?) OR (hr2.cvehora=? AND hr2.cvedia=?)) AND ubicacion IN
        (SELECT ubicacion FROM sala WHERE cvesala=?)";
    return $this->DB->GetAll($sql, array($cveperiodo, $cvehora, $cvedia, $cvehora, $cvedia, $cvesala));
  }

  public function getLectura($cveperiodo, $cveletra)
  {
    $sql = "SELECT * FROM lectura
      WHERE cveperiodo=? AND cveletra=?";

    // $this->debug($sql, false);
    // $this->debug(array($cveperiodo, $cveletra), false);

    return $this->DB->GetAll($sql, array($cveperiodo, $cveletra));
  }

  public function getSchedule($cveperiodo)
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
