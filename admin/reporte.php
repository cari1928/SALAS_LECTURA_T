<?php

require_once '../sistema.php';
require_once '../controllers/admin/pdf.class.php';

$web = new ReporteControllers;
$pdf = new PDF;
$web->smarty->setTemplateDir('../templates/admin/pdf/');
$web->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c

if (!isset($_GET['accion']) && !isset($_GET['info1']) && !isset($_GET['info2'])) {
  header('Location: index.php?e=1'); //no modifique la estructura de la interfaz
}

/**
 * Los e=1 e=2 estan en verificar_periodo y verificar_promotor
 */
switch ($_GET['accion']) {

  case 'promotor_alumnos':
    switch (casePromotorAlumnos()) { // para mensajes de error
      case 'promotor':
        header('promotor.php?accion=historial&e=4'); //no se obtuvo info del promotor
        break;
    }
    break;
    
  case 'promotores_alumnos':
    switch (casePromotoresAlumnos()) { // para mensajes de error
      // case 'promotor':
      //   header('promotor.php?accion=historial&e=4'); //no se obtuvo info del promotor
      //   break;
    }
    break;

  case 'promotor_calif':
    // para mensajes de error
    switch (casePromotorCalif($web, $pdf)) {
      case 'promotor':
        header('periodos.php?accion=historial&e=4'); //no se obtuvo info del promotor
        break;
    }
    break;

  case 'alumno':
    // para mensajes de error
    switch (caseAlumno($pdf)) {
      case 'alumno':
        // header('periodos.php?accion=historial&e=4'); //no se obtuvo info del promotor
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
function casePromotorAlumnos()
{
  global $web;
  global $pdf;

  $data = promoSubHeader();
  if (!isset($data['cveperiodo'])) {
    return $data; //error msg
  }
  $cveperiodo  = $data['cveperiodo'];
  $cvepromotor = $data['cvepromotor'];
  $periodo     = $data['periodo'];

  $data         = grupoSubHeader(array('cveperiodo' => $cveperiodo, 'cvepromotor' => $cvepromotor));
  $grupos       = $data['grupos'];
  $gruposHeader = $data['gruposHeader'];

  $data   = headerFooter(150);
  $header = $data['header'];
  $footer = $data['footer'];

  // se comienzan a checar los grupos para obtener los alumnos
  $html = '';
  for ($j = 0; $j < count($grupos); $j++) {
    $alumnos = null;
    $lecturas = $web->getAllLecturas($cveperiodo, $cvepromotor, $grupos[$j]['cveletra']);

    if (!isset($lecturas[0])) {
      $alumnos = 'No hay alumnos en este grupo';

    } else {
      // Obtiene todos los alumnos de un grupo
      $tmpAlumno = array();
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
      } //fin for
      $alumnos[0] = $tmpAlumno;
    } //fin else

    // FULL SUBHEADER
    $html .= grupoSubHeader(array('grupos' => $grupos, 'gruposHeader' => $gruposHeader, 'position' => $j));

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
      $web->smarty->assign('fin', (sizeof($alumnos[0][0]) / 2 + 1));
      
    } else {
      $alumnosHeader = 'No hay alumnos en este grupo'; //no hay alumnos disponibles
      $web->smarty->assign('columns', $alumnosHeader);
      $web->smarty->assign('fin', -1);
    }
    
    page_break($j, $grupos); //habilita o deshabilita el salto de página

    // DATOS TABLE PRINCIPAL
    $web->smarty->assign('titulo', 'Listado de Alumnos');
    $web->smarty->assign('subtitulo', 'Periodo: ' . $periodo[0]['fechainicio'] . " : " . $periodo[0]['fechafinal']);
    $web->smarty->assign('columns', $alumnosHeader);
    $web->smarty->assign('rows', $alumnos[0]);
    $html .= (string) ($web->smarty->fetch('table.html'));
  } //fin for

  $html = $header . $html . $footer;
  // echo $html;
  $pdf->createPDF('Reporte', $html, 'portrait');
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

/**
 * Estructura PDF - Promotor - Calificaciones
 */
function casePromotorCalif()
{
  global $web;
  global $pdf;

  $data = promoSubHeader();
  if (!isset($data['cveperiodo'])) {
    return $data; //error msg
  }
  $cveperiodo  = $data['cveperiodo'];
  $cvepromotor = $data['cvepromotor'];
  $periodo     = $data['periodo'];

  $data         = grupoSubHeader(array('cveperiodo' => $cveperiodo, 'cvepromotor' => $cvepromotor));
  $grupos       = $data['grupos'];
  $gruposHeader = $data['gruposHeader'];

  $data   = headerFooter(380);
  $header = $data['header'];
  $footer = $data['footer'];

  // se comienzan a checar los grupos para obtener los alumnos
  $html = '';
  for ($j = 0; $j < count($grupos); $j++) {
    $lecturas = $web->getAllLecturas($cveperiodo, $cvepromotor, $grupos[$j]['cveletra']);

    if (!isset($lecturas[0])) {
      $alumnos = 'No hay alumnos en este grupo';

    } else {
      // Obtiene todos los alumnos de un grupo
      $tmpAluInfo = array();
      for ($i = 0; $i < count($lecturas); $i++) {
        $evaluacion = $web->getEvaluation($lecturas[$i]['cvelectura']);
        $tmpAluInfo = array_merge($tmpAluInfo, $evaluacion);
      } //end for
      $aluInfo[$j] = $tmpAluInfo;
    } //end else

    // FULL SUBHEADER
    $html .= grupoSubHeader(array('grupos' => $grupos, 'gruposHeader' => $gruposHeader, 'position' => $j));

    // TABLE INFO
    $usersHeader = getAssocArray($web, $aluInfo);
    if ($usersHeader == null) {
      $usersHeader = 'No hay alumnos en este grupo'; //no hay alumnos disponibles
    }
    $users = getAssocArray($web, $aluInfo, true);
    if ($users == null) {
      $users = 'No hay alumnos';
    }
    
    page_break($j, $grupos);
    
    $web->smarty->assign('fin', (sizeof($aluInfo[$j][0]) / 2 - 1));
    $web->smarty->assign('titulo', 'Información específica');
    $web->smarty->assign('subtitulo', 'Alumnos');
    $web->smarty->assign('columns', $usersHeader);
    $web->smarty->assign('rows', $users[$j]);

    // OBSERVACIONES
    $obs = observaciones(array(
      'cveletra'    => $lecturas[0]['cveletra'],
      'cveperiodo'  => $cveperiodo,
      'cvepromotor' => $cvepromotor,
    ));

    $html .= (string) ($web->smarty->fetch('table.html'));
  } //end for

  $html = $header . $html . $footer;
  // echo $html;
  $pdf->createPDF('Reporte', $html, 'landscape');
}

/**
 * Estructura el SubHeader-Promotor
 */
function promoSubHeader()
{
  global $web;
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

  //prepara el array a mandar por smarty
  $arrPromo = creaArray($promotorHeader, $promotor[0]);
  $web->smarty->assign('usuario', $arrPromo);
  $web->smarty->assign('table', 'alumnos'); //para definir un ancho a las columnas de la tabla

  return $data = array(
    'cvepromotor' => $cvepromotor,
    'cveperiodo'  => $cveperiodo,
    'periodo'     => $periodo,
  );
}

/**
 * Obtiene Header y Footer
 */
function headerFooter($size)
{
  global $web;

  $web->smarty->assign('size', $size);

  $header = (string) ($web->smarty->fetch('header.html'));
  // $footer = (string) ($web->smarty->fetch('footer.html'));
  $footer = '';

  return $data = array(
    'header' => $header,
    'footer' => $footer,
  );
}

/**
 * Estructura el SubHeader-Grupo
 */
function grupoSubHeader($data)
{
  global $web;

  if (isset($data['cveperiodo']) && isset($data['cvepromotor'])) {
    // $grupos incluye la cveletra, se necesita para obtener datos de lectura
    $grupos = $web->getAllGrupos($data['cveperiodo'], $data['cvepromotor'], true);
    if ($grupos == null) {
      $grupos = '';
    }
    $gruposHeader = $web->getAllGrupos($data['cveperiodo'], $data['cvepromotor']);
    $gruposHeader = array_keys($gruposHeader[0]);

    return $data = array(
      'grupos'       => $grupos,
      'gruposHeader' => $gruposHeader,
    );

  } elseif (isset($data['grupos']) && isset($data['gruposHeader']) && isset($data['position'])) {
    $grupos   = $data['grupos'];
    $position = $data['position'];

    $arrGrupos = creaArray($data['gruposHeader'], $grupos[$position]);
    $web->smarty->assign('grupo', $arrGrupos);
    $html = (string) ($web->smarty->fetch('subHeader.html'));
    return $html;

  } else {
    // checa la sintaxis!!
    return null;
  }
}

/**
 * Estructura la sección de Observaciones
 */
function observaciones($data)
{
  global $web;

  $obs = $web->getAllObservaciones($data);
  if (!isset($obs[0])) {
    $web->smarty->assign('calif', false);
  } else {
    $web->smarty->assign('calif', true);
    $web->smarty->assign('observaciones', $obs);
  }
}

function caseAlumno($pdf)
{
  global $web;

  $cveperiodo  = verifica_periodo($web);
  $cvepromotor = verifica_usuario($web);
  $periodo     = $web->getPeriodo($cveperiodo); //esto es para mostrarlo

  // PENDIENTE
}

/**
 * Salto de Página
 */
function page_break($j, $grupos) {
  global $web;
  
  if($j == count($grupos) - 1) {
      $web->smarty->assign('page_break', false);
    } else {
      $web->smarty->assign('page_break', true);
    }
}

function casePromotoresAlumnos() {
  global $web;
  global $pdf;
  
  // PENDIENTE
}