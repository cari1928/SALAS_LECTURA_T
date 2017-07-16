<?php

class LibrosControllers extends Sistema
{
  public $route = "/home/slslctr/Images/portadas/";

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

  /**
   * Recomendation: Use with INSERT Libro
   * Search and delete banner with a name similar to the last cvelibro
   */
  public function deleteOldBanner($cvelibro)
  {
    // delete all files in a directory matching a pattern in one line of code
    array_map('unlink', glob($this->route . $cvelibro . ".*"));
  }

}
