<?php

class ForoControllers extends Sistema
{
  public function getAllLibros()
  {
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $sql = "SELECT DISTINCT sinopsis, titulo, autor, libro.cvelibro FROM libro
    LEFT JOIN comentario ON libro.cvelibro = comentario.cvelibro
    ORDER BY cvelibro";
    return $this->DB->GetAll($sql);
  }

  public function checkPortada($cvelibro)
  {
    if (strlen($cvelibro) == 0) {
      return 'no_disponible.jpg';
    }

    $route = "/home/slslctr/Images/portadas/";
    foreach (glob($route . $cvelibro . ".*") as $nombre_fichero) {
      return $nombre_fichero;
    }
    return 'no_disponible.jpg';
  }
}
