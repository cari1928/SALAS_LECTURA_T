<?php

class GruposControllers extends Sistema
{
  
  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta alumno/templates_c
  }

  /**
   *
   */
  public function getReading($cvelectura)
  {
    $sql = "SELECT * FROM lectura WHERE cvelectura=?";
    return $this->DB->GetAll($sql, $cvelectura);
  }

  /**
   * Obtiene un listado de libros en base a nocontrol y cvelectura
   */
  public function getBooks($nocontrol, $cvelectura)
  {
    $this->DB->SetFetchMode(ADODB_FETCH_BOTH);
    $sql = "SELECT libro.cvelibro, titulo, estado FROM lista_libros
      INNER JOIN estado ON estado.cveestado = lista_libros.cveestado
      INNER JOIN lectura ON lista_libros.cvelectura = lectura.cvelectura
      INNER JOIN libro ON libro.cvelibro = lista_libros.cvelibro
      WHERE nocontrol=? AND lectura.cvelectura=?
      ORDER BY titulo";
    return $this->DB->GetAll($sql, array($nocontrol, $cvelectura));
  }

  /**
   *
   */
  public function getLetter($cvelectura)
  {
    $sql = "SELECT letra FROM abecedario WHERE cve IN (SELECT cveletra FROM lectura WHERE cvelectura=?)";
    return $this->DB->GetAll($sql, $cvelectura);
  }

  /**
   *
   */
  public function getBook($cvelibro)
  {
    $sql = "SELECT * FROM libro WHERE cvelibro=?";
    return $this->DB->GetAll($sql, $cvelibro);
  }

  /**
   *
   */
  public function getReadingMesh($cvelectura, $cveperiodo)
  {
    $sql = "SELECT * FROM lectura
      INNER JOIN abecedario ON lectura.cveletra = abecedario.cve
      INNER JOIN laboral ON laboral.cveletra = abecedario.cve
      WHERE cvelectura=?
      AND lectura.cveperiodo=?";
    return $this->DB->GetAll($sql, array($cvelectura, $cveperiodo));
  }

  /**
   *
   */
  public function insertBookList($cvelibro, $cvelectura, $cveperiodo)
  {
    $sql = "INSERT INTO lista_libros(cvelibro, cvelectura, cveperiodo, cveestado, calif_reporte) VALUES (?, ?, ?, 1, 0)";
    return $this->query($sql, array($cvelibro, $cvelectura, $cveperiodo));
  }

  /**
   *
   */
  public function getGroups($letra, $cveperiodo, $nocontrol)
  {
    $sql = "SELECT distinct nocontrol FROM laboral
      INNER JOIN abecedario on abecedario.cve = laboral.cveletra
      INNER JOIN lectura on abecedario.cve = lectura.cveletra
      WHERE laboral.cveletra in (SELECT cve FROM abecedario WHERE letra=?)
      AND laboral.cveperiodo=? AND nocontrol=?";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo, $nocontrol));
  }

  /**
   *
   */
  public function getInfoHeader($nocontrol, $cveperiodo, $letra)
  {
    $sql = "SELECT distinct letra, laboral.nombre AS \"nombre_grupo\", sala.ubicacion, fechainicio, fechafinal,
      nocontrol, usuarios.nombre AS \"nombre_promotor\" FROM laboral
      INNER JOIN sala ON laboral.cvesala = sala.cvesala
      INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
      INNER JOIN periodo ON laboral.cveperiodo= periodo.cveperiodo
      INNER JOIN lectura ON abecedario.cve = abecedario.cve
      INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
      WHERE nocontrol=? AND laboral.cveperiodo=? AND letra=?
      ORDER BY letra";
    return $this->DB->GetAll($sql, array($nocontrol, $cveperiodo, $letra));
  }

  /**
   *
   */
  public function getDataUsers($letra, $cveperiodo, $nocontrol)
  {
    $sql = "SELECT distinct usuarios.nombre, asistencia, comprension, reporte, asistencia, actividades,
      participacion, terminado, nocontrol, cveeval, lectura.cveperiodo, lectura.cvelectura
      FROM lectura
      INNER JOIN evaluacion ON evaluacion.cvelectura = lectura.cvelectura
      INNER JOIN abecedario ON lectura.cveletra = abecedario.cve
      INNER JOIN usuarios ON lectura.nocontrol = usuarios.cveusuario
      INNER JOIN laboral ON abecedario.cve = laboral.cveletra
      WHERE letra=? AND lectura.cveperiodo=? AND nocontrol=?
      ORDER BY usuarios.nombre";
    return $this->DB->GetAll($sql, array($letra, $cveperiodo, $nocontrol));
  }

  /**
   *
   */
  public function getFile($dir, $cvelibro)
  {
    //obtiene una lista de archivos dentro de un directorio
    $files = array_diff(scandir($this->route_periodos . $dir), array('.', '..'));
    foreach ($files as $file) {
      //verifica si el nombre del archivo contiene la cvelibro y el nocontrol
      if (preg_match('/' . $cvelibro . '_' . $_SESSION['cveUser'] . '/', $file)) {
        return $file;
      }
    }
    return null;
  }
  
  /**
   * 
   */
  public function getExtension($fullExt) {
    return (preg_match('/pdf/', $fullExt)) ? ".pdf" : 
      (preg_match('/msword/', $fullExt) ? ".doc" : 
      (preg_match('/jpeg/', $fullExt) ? ".jpg" : 
      (preg_match('/png/', $fullExt) ? ".png" : ".docx")));
  }
  
  /**
   * Obtiene un listado de libros en base a nocontrol, cveperiodo y cvelectura.
   */
  public function getTableBooks($arrData) {
    $sql = "SELECT autor, titulo, editorial, cantidad, portada, cvelibro FROM libro
      WHERE status='existente' AND cvelibro NOT IN
        (SELECT cvelibro FROM lista_libros
          INNER JOIN lectura ON lectura.cvelectura = lista_libros.cvelectura
          INNER JOIN abecedario ON abecedario.cve = lectura.cveletra
          INNER JOIN laboral ON laboral.cveletra = abecedario.cve
          WHERE nocontrol=? AND laboral.cveperiodo=? AND lectura.cvelectura=?)
      ORDER BY titulo";
    return $this->DB->GetAll($sql, $arrData);
  }
}
