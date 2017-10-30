<?php

class ForoControllers extends Sistema
{
  /**
   * 
   */
  public function getAllLibros()
  {
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $sql = "SELECT DISTINCT sinopsis, titulo, autor, libro.cvelibro FROM libro
      LEFT JOIN comentario ON libro.cvelibro = comentario.cvelibro
      ORDER BY cvelibro";
    return $this->DB->GetAll($sql);
  }

  /**
   * 
   */
  public function getLibro($cvelibro)
  {
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $sql = "SELECT * FROM libro WHERE cvelibro=?";
    return $this->DB->GetAll($sql, $cvelibro);
  }

  /**
   * 
   */
  public function checkPortada($cvelibro)
  {
    if (strlen($cvelibro) == 0) {
      return 'no_disponible.jpg';
    }
    foreach (glob($this->route_images . "portadas/" . $cvelibro . ".*") as $nombre_fichero) {
      return $nombre_fichero;
    }
    return 'no_disponible.jpg';
  }

  /**
   * 
   */
  public function insertLibro($params)
  {
    $sql = "INSERT INTO comentario(cvelibro, cveusuario, contenido) VALUES(?,?,?)";
    return $this->query($sql, $params);
  }
  
  /**
   * 
   */
  public function getComments($cvelibro) {
    $sql = "SELECT * FROM comentario
      INNER JOIN usuarios ON usuarios.cveusuario=comentario.cveusuario
      WHERE cvelibro=? AND cverespuesta IS NULL";
    return $this->DB->GetAll($sql, $cvelibro);
  }
  
  /**
   * 
   */
  public function getAnswers($cvelibro) {
    $sql = "SELECT * FROM comentario
      INNER JOIN usuarios ON usuarios.cveusuario=comentario.cveusuario
      WHERE cvelibro=? AND cverespuesta IS NOT NULL";
    return $this->DB->GetAll($sql, $cvelibro);
  }
}
