<?php

class LibrosControllers extends Sistema
{
  /**
   * 
   */
  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c
  }

  /**
   * 
   */
  public function getLastCveLibro()
  {
    $sql = "SELECT MAX(cvelibro) FROM libro where status = ?";
    return $this->DB->GetAll($sql, 'existente');
  }

  /**
   * 
   */
  public function getExtension($file)
  {
    $ext  = explode(".", $file);
    $size = count($ext);
    return $ext[($size - 1)];
  }

  /**
   * Recomendation: Use with INSERT Libro
   * Search and delete banner with a similar name to the last cvelibro
   */
  public function deleteOldBanner($cvelibro)
  {
    // delete all files in a directory matching a pattern in one line of code
    array_map('unlink', glob($this->route_images . "/portadas/" . $cvelibro . ".*"));
  }
  
  /**
   * @param $type 1 == INSERT | 2 == UPDATE
   */
  function uploadNewFile($cvelibro, $typeMessage)
  {
    $this->deleteOldBanner($cvelibro); //Search and delete banner with a similar name to the last cvelibro
    $extension      = $this->getExtension($_FILES['portada']['name']);
    $nombreTemporal = $_FILES['portada']['tmp_name'];
    $rutaArchivo    = $this->route_images . "/portadas/";
    $fileName = $cvelibro . "." . $extension;
    
    if (move_uploaded_file($nombreTemporal, $rutaArchivo.$fileName)) {
      $this->redimensionar($rutaArchivo, $fileName, 313, 496);
      $this->update(array('portada'=>($cvelibro . "." . $extension)), array('cvelibro'=>$cvelibro), 'libro');
      header('Location: libros.php?msg=' . $typeMessage);
      
    } else {
      header('Location: libros.php?msg=3');
    }
  }

}
