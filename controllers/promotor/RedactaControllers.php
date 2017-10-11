<?php

class RedactaControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta promotor/templates_c
  }

  /**
   *
   */
  public function countFiles($route)
  {
    $cont = 0;
    foreach (glob($route . "*") as $file) {
      ++$cont;
    }
    return $cont;
  }

  /**
   *
   */
  public function getNameAndExtension($file)
  {
    $data     = explode(".", $file);
    $sizeData = count($data);
    if ($sizeData > 2) {
      // el archivo tiene más de un punto en su nombre
      $extension   = "." . $data[($sizeData - 1)];
      $fileName    = explode($extension, $file);
      $fileName[1] = $extension;
      return $fileName;

    } else if ($sizeData == 2) {
      $data[1] = "." . $data[1]; // el archivo tiene solo un punto en su nombre
      return $data;
    }
    die('CHECAR ARCHIVO'); // que onda??
  }

  /**
   *
   */
  public function getReading($cveperiodo, $cveletra, $cvepromotor)
  {
    $sql = "SELECT * FROM lectura WHERE cveperiodo=? AND cveletra=? AND cveletra in
      (SELECT cveletra FROM laboral WHERE cvepromotor=? and cveperiodo=?)";
    return $this->DB->GetAll($sql, array($cveperiodo, $cveletra, $cvepromotor, $cveperiodo));
  }

  /**
   *
   */
  public function insertMsj($introduccion, $descripcion, $emisor, $expira, $cveletra, $cveperiodo, $archivo)
  {
    $sql = "INSERT INTO msj(introduccion, descripcion, tipo, emisor, expira, cveletra, cveperiodo, archivo)
    VALUES (?, ?, 'G', ?, ?, ?, ?, ?)";
    return $this->query($sql, array($introduccion, $descripcion, $emisor, $expira, $cveletra, $cveperiodo, $archivo));
  }

  /**
   *
   */
  public function getIndividualReading($letra, $cveperiodo)
  {
    $sql = "SELECT * FROM lectura
      INNER JOIN abecedario ON abecedario.cve = lectura.cveletra
      WHERE abecedario.letra=? AND lectura.cveperiodo=?";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo));
  }

}
