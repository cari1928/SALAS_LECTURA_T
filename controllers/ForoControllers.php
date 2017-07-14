<?php

class ForoControllers extends Sistema
{
  public function getAllLibros()
  {
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $sql = "SELECT portada, titulo, autor, libro.cvelibro FROM libro
    LEFT JOIN comentario ON libro.cvelibro = comentario.cvelibro";
    return $this->DB->GetAll($sql);
  }

  public function checkPortada($portada)
  {
    if (strlen($portada) == 0) {
      return 'no_disponible.jpg';
    }

    $route = "/home/slslctr/Images/portadas/";
    foreach (glob($route . $portada . ".*") as $nombre_fichero) {
      return $portada;
    }
    return 'no_disponible.jpg';
  }
}
