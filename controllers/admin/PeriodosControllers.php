<?php

class PeriodosControllers extends Sistema
{

  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c
  }

  /**
   * Muestra mensajes de error
   */
  public function showMessages()
  {
    if (isset($_GET['e'])) {
      switch ($_GET['e']) {
        case 1:
          $this->simple_message('danger', 'No fue posible generar el reporte, hacen falta datos');
          break;
        case 2:
          $this->simple_message('warninig', 'No fue posible generar el reporte, hay error con los datos seleccionados');
          break;
        case 3:
          $this->simple_message('warning', 'No modifique la estructura de la interfaz');
          break;
        case 4:
          $this->simple_message('danger', 'No se pudieron obtener los datos del promotor');
          break;
      }
    }
    if (isset($_GET['aviso'])) {
      switch ($_GET['aviso']) {
        case 1:
          $this->simple_message('info', 'Datos guardados correctamente');
          break;
        case 2:
          $this->simple_message('info', 'Datos actualizados correctamente');
          break;
        case 3:
          $this->simple_message('info', 'Periodo eliminado correctamente');
          break;
      }
    }
  }

  /**
   * Obtiene todos los periodos ordenados por cveperiodo
   */
  public function getPeriodos()
  {
    $sql = 'SELECT cveperiodo, fechainicio, fechafinal FROM periodo ORDER BY cveperiodo';
    $this->DB->SetFetchMode(ADODB_FETCH_NUM);
    return $this->DB->GetAll($sql);
  }

  /**
   * Obtiene un periodo específico mediante cveperiodo
   */
  public function getPeriodo($cveperiodo)
  {
    $sql = "SELECT * FROM periodo WHERE cveperiodo=?";
    return $this->DB->GetAll($sql, $cveperiodo);
  }

  /**
   * Obtiene la última cveperiodo
   */
  public function getLastPeriodo()
  {
    $sql = "SELECT MAX(cveperiodo) FROM periodo";
    return $this->DB->GetAll($sql)[0][0];
  }

  /**
   * Inserta un periodo
   */
  public function insertPeriod($parameters)
  {
    $sql = "INSERT INTO periodo (fechainicio, fechafinal) VALUES(?, ?)";
    return $this->query($sql, $parameters);
  }

  /**
   * Actualiza un periodo
   */
  public function updatePeriodo($parameters)
  {
    $sql = "UPDATE periodo SET fechainicio=?, fechafinal=? WHERE cveperiodo=?";
    return $this->query($sql, $parameters);
  }

  /**
   * Obtiene los grupos y cvelectura de un periodo específico
   */
  public function getGrupos($cveperiodo)
  {
    $sql = "SELECT distinct cveletra FROM laboral WHERE cveperiodo=? ORDER BY cveletra";
    return $this->DB->GetAll($sql, $cveperiodo);
  }

  /**
   * Obtiene lecturas mediane una cveletra
   */
  public function getLecturas($cveletra)
  {
    $sql = "SELECT cvelectura FROM lectura WHERE cveletra=?";
    return $this->DB->GetAll($sql, $cveletra);
  }

  /**
   * Elimina un periodo
   * **Se modificó la BD para que, cuando se elimine un elemento de la tabla periodo, automáticamente se eliminan datos de:
   * lista_libros
   * evaluacion -> lectura
   * laboral -> sala
   * msj
   * observacion
   * periodos
   * **No es un trigger, se modificaron los foreign keys de dichas tablas con la propiedad ON DELETE CASCADE
   * El código sql utilizado está en Evernote, buscar por el título: Eliminar en cascada postgres
   */
  public function deletePeriodo($cveperiodo)
  {
    $sql = "DELETE FROM periodo WHERE cveperiodo=?";
    return $this->query($sql, $cveperiodo);
  }

  /**
   * Elimina directorios correspondientes a la cveperiodo dentro de archivos/mensajes, pdf y periodos
   */
  public function deleteFiles($cveperiodo)
  {
    $route = "/home/ubuntu/workspace/archivos/";

    $tmp = $route . "mensajes/" . $cveperiodo; //elimina la carpeta de mensajes
    $this->delTree($tmp);
    $tmp = $route . "pdf/" . $cveperiodo; //elimina la carpeta de reportes pdf
    $this->delTree($tmp);
    $tmp = $route . "periodos/" . $cveperiodo; //elimina la carpeta de reportes pdf
    $this->delTree($tmp);
  }

  /**
   * Elimina un directorio junto con su contenido
   * Usa recursividad
   */
  public function delTree($dir)
  {
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
  }
}
