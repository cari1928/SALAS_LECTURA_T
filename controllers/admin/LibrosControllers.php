<?php

class LibrosControllers extends Sistema
{
  public $route = "/home/ubuntu/workspace/Images/portadas/";

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c
  }

  public function getLastCveLibro()
  {
    $sql = "SELECT MAX(cvelibro) FROM libro";
    return $this->DB->GetAll($sql);
  }

  public function getExtension($file)
  {
    $ext  = explode(".", $file);
    $size = count($ext);
    return $ext[($size - 1)];
  }

  public function checkPortada()
  {
    $cvelibro = $this->getLastCveLibro()[0][0];
    foreach (glob($this->$route . $cvelibro . ".*") as $nombre_fichero) {
      return true;
    }
    return false;
  }

  // PENSAR BIEN ESTO!!!
  public function deleteOldPortada()
  {
    $cvelibro = $this->getLastCveLibro()[0][0];
    array_map('unlink', glob($this->route . $cvelibro . ".*"));
  }
}
