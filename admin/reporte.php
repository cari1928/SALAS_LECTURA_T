<?php

require_once '../sistema.php';
require_once '../controllers/admin/pdf.class.php';

$web = new ReporteControllers;
$pdf = new PDF;
$web->smarty->setTemplateDir('../templates/admin/pdf/');

if (!isset($_GET['accion']) && !isset($_GET['info1']) && !isset($_GET['info2'])) {
  header('Location: index.php?e=1'); //no modifique la estructura de la interfaz
}

/**
 * Los e=1 e=2 estan en verificar_periodo y verificar_promotor
 */
switch ($_GET['accion']) {

  case 'promotor':
    // para mensajes de error
    switch (casePromotor($web, $pdf)) {
      case 'promotor':
        header('periodos.php?accion=historial&e=4'); //no se obtuvo info del promotor
        break;
    }
    break;

  default:
    header('periodos.php?accion=historial&e=3'); //no modifique la estructura de la interfaz
    break;
}

/***************************************************************************************
 * FUNCIONES
 ***************************************************************************************/
/**
 * Ahorra código, verifica que haya sido enviado y sea válido
 * El periodo es enviado desde $_GET['info2']
 * @param  Class  $web   Objeto para hacer uso de smarty
 * @return String cveperiodo
 */
function verifica_periodo($web)
{
  //verifica que mande cveperiodo
  if (!isset($_GET['info2'])) {
    header('Location: periodos.php?accion=historial&e=1');
    return false;
  }
  $cveperiodo = $_GET['info2'];

  //verifica que exista el periodo
  $sql     = "select * from periodo where cveperiodo=?";
  $periodo = $web->DB->GetAll($sql, $cveperiodo);
  if (!isset($periodo[0])) {
    header('Location: periodos.php?accion=historial&e=2');
    return false;
  }

  return $cveperiodo;
}

/**
 * Ahorra código, verifica que haya sido enviado y sea válido
 * El periodo es enviado desde $_GET['info2']
 * @param  Class  $web   Objeto para hacer uso de smarty
 * @return String cvepromotor
 */
function verifica_usuario($web)
{
  if (!isset($_GET['info3'])) {
    header('Location: periodos.php?accion=historial&e=1');
  }
  $cveusuario = $_GET['info3'];
  $sql        = "select * from usuarios where cveusuario=?";
  $usuario    = $web->DB->GetAll($sql, $cveusuario);
  if (!isset($usuario[0])) {
    header('Location: periodos.php?accion=historial&e=2');
  }
  return $cveusuario;
}

/**
 * Genera el código html necesario para mostrar el pdf correspondiente
 */
function casePromotor($web, $pdf)
{
  $cveperiodo  = verifica_periodo($web);
  $cvepromotor = verifica_usuario($web);
  $periodo     = $web->getPeriodo($cveperiodo); //esto es para mostrarlo

  // DATOS PROMOTOR
  $promotor = $web->getPromotor($cvepromotor, true);
  if ($promotor == null) {
    return 'promotor';
  }
  $promotorHeader = $web->getPromotor($cvepromotor);
  $promotorHeader = array_keys($promotorHeader[0]);
  //Assign correspondiente
  $arrPromo = creaArray($promotorHeader, $promotor[0]);
  $web->smarty->assign('usuario', $arrPromo);
  $web->smarty->assign('table', 'alumnos');

  /**
   * DATOS GRUPOS
   * $grupos incluye la cveletra, se necesita para obtener datos de lectura
   */
  $grupos = $web->getAllGrupos($cveperiodo, $cvepromotor, true);
  if ($grupos == null) {
    $grupos = '';
  }
  $gruposHeader = $web->getAllGrupos($cveperiodo, $cvepromotor);
  $gruposHeader = array_keys($gruposHeader[0]);

  // se comienzan a checar los grupos para obtener los alumnos
  $html = '';
  for ($j = 0; $j < count($grupos); $j++) {
    $lecturas = $web->getAllLecturas($cveperiodo, $cvepromotor, $grupos[$j]['cveletra']);

    if (!isset($lecturas[0])) {
      $alumnos = 'No hay alumnos en este grupo';

    } else {
      // Obtiene todos los alumnos de un grupo
      $tmpAlumno  = array();
      $tmpAluInfo = array();

      for ($i = 0; $i < count($lecturas); $i++) {
        $datos[$j] = $web->getAlumno(
          $lecturas[$i]['nocontrol'],
          $cveperiodo,
          $lecturas[$i]['cveletra'],
          $lecturas[$i]['cvelectura']
        );
        $datos[$j][0][4]              = $datos[$j][0]['TERMINADO'];
        $datos[$j][0][2]              = $web->getEspecialidad($datos[$j][0][2]);
        $datos[$j][0]['ESPECIALIDAD'] = $datos[$j][0][2];
        $tmpAlumno                    = array_merge($tmpAlumno, $datos[0]);

        // para la segunda tabla
        $evaluacion = $web->getEvaluation($lecturas[$i]['cvelectura']);
        $tmpAluInfo = array_merge($tmpAluInfo, $evaluacion);

      } //fin for
      $alumnos[$j] = $tmpAlumno;
      $aluInfo[$j] = $tmpAluInfo;
    } //fin else

    // GRUPOS SUBHEADER
    $arrGrupos = creaArray($gruposHeader, $grupos[$j]);
    $web->smarty->assign('grupo', $arrGrupos);
    $html .= (string) ($web->smarty->fetch('subHeader.html'));

    // ALUMNOS SUBHEADER
    if (is_array($alumnos)) {
      $alumnosHeader = getAssocArray($web, $alumnos);
      if ($alumnosHeader == null) {
        $alumnosHeader = 'No hay alumnos en este grupo'; //no hay alumnos disponibles
      }
      $alumnos = getAssocArray($web, $alumnos, true);
      if ($alumnos == null) {
        $alumnos = 'No hay alumnos';
      }
      $web->smarty->assign('fin', (sizeof($alumnos[$j][0]) / 2 + 1));
    } else {
      $alumnosHeader = 'No hay alumnos en este grupo'; //no hay alumnos disponibles
      $web->smarty->assign('columns', $alumnosHeader);
      $web->smarty->assign('fin', -1);
    }

    // DATOS TABLE PRINCIPAL
    $web->smarty->assign('titulo', 'Listado de Alumnos');
    $web->smarty->assign('subtitulo', 'Periodo: ' . $periodo[0]['fechainicio'] . " : " . $periodo[0]['fechafinal']);
    $web->smarty->assign('columns', $alumnosHeader);
    $web->smarty->assign('rows', $alumnos[$j]);
    $html .= (string) ($web->smarty->fetch('table.html'));

    // INFORMACION ALUMNOS TABLE
    $usersHeader = getAssocArray($web, $aluInfo);
    if ($usersHeader == null) {
      $usersHeader = 'No hay alumnos en este grupo'; //no hay alumnos disponibles
    }
    $users = getAssocArray($web, $aluInfo, true);
    if ($users == null) {
      $users = 'No hay alumnos';
    }
    $web->smarty->assign('fin', (sizeof($aluInfo[$j][0]) / 2 - 1));
    $web->smarty->assign('titulo', 'Información específica');
    $web->smarty->assign('subtitulo', 'Alumnos');
    $web->smarty->assign('columns', $usersHeader);
    $web->smarty->assign('rows', $users[$j]);
    $html .= (string) ($web->smarty->fetch('table.html'));

  } //fin foreach

  $header = (string) ($web->smarty->fetch('header.html'));
  $html   = $header . $html;
  // echo $html;
  $pdf->createPDF('Reporte', $html);
}

/**
 * Crea un array para ser utilizado en los tamplates: admin/pdf
 */
function creaArray($header, $body)
{
  for ($i = 0; $i < sizeof($header); $i++) {
    $res[$i]['titulo'] = $header[$i];
    $res[$i]['nombre'] = $body[$i];
  }
  return $res;
}

/**
 * Elimina campos numéricos para dejar solo los encabezados
 * @param $numeric true===deja encabezados numericos ; false===elimina encabezados numericos
 */
function getAssocArray($web, $array, $numeric = false)
{
  for ($i = 0; $i < count($array); $i++) {

    for ($j = 0; $j < count($array[0]); $j++) {
      $tmpHeaders = array_keys($array[$i][$j]);
      $headers    = array();

      $cont = 0;
      foreach ($tmpHeaders as $h) {
        if (!is_numeric($h)) {
          if (!$numeric) {
            $headers[$cont] = $h;
            ++$cont;
          } else {
            unset($array[$i][$j][$h]);
          }
        } //fin if
      } //fin foreach

      if (!$numeric && count($headers) > 0) {
        return $headers;
      }

    } //end for j

    if ($numeric && count($array) > 0) {
      return $array;
    } else {
      return null;
    }
  } //end for i
}
