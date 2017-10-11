<?php

include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web = new GrupoControllers;
$web->iniClases('promotor', "index grupos grupo");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

showMessage(); //mensajes de error o avisos

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('danger', 'No hay periodos actuales', $web);
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {
    case 'estado':
      estado();
      break;

    case 'libros':
      libros();
      break;

    case 'reporte':
      reporte();
      break;

    case 'calificar_reporte': //info1 = cvelista, info2 = cvelectura, info3 = nocontrol
      calificar_reporte();
      break;

    case 'formato_preguntas':
      header("Content-disposition: attachment; filename=formato_preguntas.pdf");
      header("Content-type: MIME");
      readfile($web->route_pdf . $cveperiodo . "/formato_preguntas.pdf");
      break;

    case 'form_observaciones':
      m_formObservaciones();
      break;

    case 'observacion':
      m_Observaciones();
      break;

    case 'lista_asistencia':
      mListaAsistencia();
      break;
  }
}

if (!isset($_GET['info1'])) {
  $web->debug($_GET); //no sé porque, pero necesita esto para funcionar, marca error después de mandar una observación, pero no debería
  message('danger', 'Información incompleta - general', $web);
}

$grupo          = $_GET['info1'];
$grupo_promotor = $web->getPromoGroup($grupo, $cveperiodo);
if (!isset($grupo_promotor[0])) {
  message('danger', 'No existe el grupo en este periodo', $web);
}
if ($grupo_promotor[0]['cvepromotor'] != $_SESSION['cveUser']) {
  message('danger', 'Permiso denegado', $web);
}

if (isset($_POST['datos'])) {
  if (!isset($_POST['datos']['cveeval']) ||
    !isset($_POST['datos']['comprension']) ||
    !isset($_POST['datos']['participacion']) ||
    !isset($_POST['datos']['asistencia']) ||
    !isset($_POST['datos']['actividades']) ||
    !is_numeric($_POST['datos']['cveeval']) ||
    !is_numeric($_POST['datos']['comprension']) ||
    !is_numeric($_POST['datos']['participacion']) ||
    !is_numeric($_POST['datos']['asistencia']) ||
    !is_numeric($_POST['datos']['actividades'])) {
    message("danger", "No alteres la estructura de la interfaz", $web);
  }
  if ($_POST['datos']['cveeval'] < 0 ||
    $_POST['datos']['comprension'] < 0 ||
    $_POST['datos']['participacion'] < 0 ||
    $_POST['datos']['asistencia'] < 0 ||
    $_POST['datos']['actividades'] < 0) {
    message("danger", "Ingrese solo valores positivos", $web);
  }
  if ($_POST['datos']['cveeval'] > 100 ||
    $_POST['datos']['comprension'] > 100 ||
    $_POST['datos']['participacion'] > 100 ||
    $_POST['datos']['asistencia'] > 100 ||
    $_POST['datos']['actividades'] > 100) {
    message("danger", "Ingrese solo valores positivos", $web);
  }

  $eval = $web->getEvaluations($_SESSION['cveUser'], $_POST['datos']['cveeval']);
  if (!isset($eval[0])) {
    message('danger', 'Permiso denegado', $web);
  }

  $web->DB->startTrans(); //por si hay errores durante la ejecusión del query
  $web->update(array(
    'comprension'   => $_POST['datos']['comprension'],
    'participacion' => $_POST['datos']['participacion'],
    'asistencia'    => $_POST['datos']['asistencia'],
    'actividades'   => $_POST['datos']['actividades']),
    array('cveeval' => $_POST['datos']['cveeval']), 'evaluacion');

  if (!promTerminado($web, 'cveeval', $_POST['datos']['cveeval'])) {
    $web->simple_message('warning', 'No se pudo calcular el promedio final');
  }

  if ($web->DB->HasFailedTrans()) {
    //falta programar esta parte para que no muestre directamente el resultado de sql
    $web->simple_message('danger', 'No se pudo calcular el promedio final');
  }
  $web->DB->CompleteTrans();
}

//Info de encabezado
$datos_rs = $web->getInfoHeader($_SESSION['cveUser'], $cveperiodo, $grupo);
$web->smarty->assign('info', $datos_rs[0]);

//Datos de la tabla = Alumnos
$datos = $web->getDataTable($grupo, $cveperiodo);
if (!isset($datos[0])) {
  message('warning', 'No hay alumnos inscritos');
}

$nombre_fichero = $web->route_pdf . $cveperiodo . "/formato_preguntas.pdf";
if (file_exists($nombre_fichero)) {
  $web->smarty->assign('formato_preguntas', true);
}

$web->smarty->assign('bandera', 'true');
$web->smarty->assign('cveperiodo', $cveperiodo);
$web->smarty->assign('datos', $datos);
$web->smarty->assign('grupo', $grupo);
$web->smarty->display("grupo.html");
/****************************************************************************************************
 * FUNCIONES
 ****************************************************************************************************/
/**
 * Ahorro de código para mostrar mensajes al usuario
 * @param  String $alert warning | danger principalmente
 * @param  String $msg   Texto a mostrar al usuario
 * @param  Class  $web   Objeto para hacer uso de smarty
 */
function message($alert, $msg)
{
  global $web;
  $web->simple_message($alert, $msg);
  $web->smarty->display("grupo.html");
  die();
}

/**
 *
 */
function promReporte($web)
{
  $cvelectura    = $_GET['info2'];
  $cali_reportes = $web->getAll('*', array('cvelectura' => $cvelectura), 'lista_libros');
  $prom          = 0;
  for ($i = 0; $i < sizeof($cali_reportes); $i++) {
    $prom += $cali_reportes[$i]['calif_reporte'];
  }

  if (sizeof($cali_reportes) < 5) {
    $prom /= 5;
  } else {
    $prom /= sizeof($cali_reportes);
  }

  $prom = round($prom);
  if (!$web->update(array('reporte' => $prom), array('cvelectura' => $cvelectura), 'evaluacion')) {
    return false;
  }
  return true;
}

/**
 *
 */
function promTerminado($web, $campo, $valor)
{
  $sql            = "SELECT * FROM evaluacion WHERE " . $campo . "=?";
  $calificaciones = $web->DB->GetAll($sql, $valor);
  if (!isset($calificaciones[0])) {
    return false;
  }

  $prom = $calificaciones[0]['asistencia'];
  $prom += $calificaciones[0]['comprension'];
  $prom += $calificaciones[0]['participacion'];
  $prom += $calificaciones[0]['reporte'];
  $prom += $calificaciones[0]['actividades'];
  $prom /= 5;
  $prom = round($prom);
  $sql  = "UPDATE evaluacion SET terminado=? WHERE " . $campo . "=?";
  if (!$web->query($sql, array($prom, $valor))) {
    return false;
  }

  return true;
}

/**
 *
 */
function m_formObservaciones()
{
  global $web;

  if (!isset($_GET['info'])) {
    message('warning', 'Falta información');
  }

  $cveletra = $web->getCveLetter($_GET['info']);
  if (!isset($cveletra[0])) {
    message('danger', 'No existe el grupo');
  }

  $web->smarty->assign('cveletra', $cveletra[0]['cveletra']);
  $web->smarty->display('form_observaciones.html');
  die();
}

/**
 *
 */
function m_Observaciones()
{
  global $web;
  global $cveperiodo;

  if (!isset($_GET['info']) ||
    !isset($_POST['observacion'])) {
    message('warning', 'Falta información');
  }
  // obtiene la letra porque se usa para los header-location
  $letra = $web->getAll(array('DISTINCT letra'), array('cve' => $_GET['info']), 'abecedario');
  if (!isset($letra[0])) {
    message('danger', 'No existe el grupo');
  }

  $redirect   = array('info1' => $letra[0]['letra'], 'a' => 3);
  $parameters = array(
    'observacion' => $_POST['observacion'],
    'cveletra'    => $_GET['info'],
    'cveperiodo'  => $cveperiodo,
    'cvepromotor' => $_SESSION['cveUser'],
  );
  if (!$web->insert('observacion', $parameters)) {
    $redirect['a'] = 4;
  }
  header('Location: grupo.php?' . http_build_query($redirect));
}

/**
 *
 */
function showMessage()
{
  global $web;

  // Mensajes de error
  if (isset($_GET['e'])) {
    switch ($_GET['e']) {
      case 'guardar':
        $web->simple_message('danger', 'No fue posible guardar la información');
        break;
    }
  }
  // Mensajes de éxito
  if (isset($_GET['m'])) {
    switch ($_GET['m']) {
      case 'guardar':
        $web->simple_message('info', 'Datos guardados');
        break;
      case '1':
        $web->simple_message('info', 'Calificación asignada correctamente');
        break;
      case '2':
        $web->simple_message('danger', 'No fue posible asignar calificación');
        break;
      case '3':
        $web->simple_message('info', 'Datos guardados');
        break;
      case '4':
        $web->simple_message('danger', 'No fue posible guardar la información');
        break;
    }
  }
}

function mListaAsistencia()
{
  require_once '../controllers/pdf.class.php';
  global $web;

  $web = new ListAsiControllers;
  $pdf = new PDF;
  $web->smarty->setTemplateDir('../templates/promotor/pdf/');
  $web->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c

  // OBTIENE HEADER
  $data   = $web->headerFooter();
  $header = $data['header'];
  $footer = $data['footer'];

  if (!isset($_GET['info'])) {
    mSetMessage($pdf, $header, 'e2');
  }

  // OBTIENE INFO DE PROMOTOR PARA SUBHEADER
  $data = $web->promoSubHeader();
  if (!isset($data['cveperiodo'])) {
    mSetMessage($pdf, $header, $data);
  }
  $cveperiodo  = $data['cveperiodo'];
  $cvepromotor = $data['cvepromotor'];
  $periodo     = $data['periodo'];

  $data = $web->grupoSubHeader(array('cveperiodo' => $cveperiodo, 'cvepromotor' => $cvepromotor));
  if (!isset($data['grupos'])) {
    mSetMessage($pdf, $header, $data);
  }
  $grupos       = $data['grupos'];
  $gruposHeader = $data['gruposHeader'];

  // se comienza a checar el grupo para obtener los alumnos
  $lecturas = $web->getAllLecturas($cveperiodo, $cvepromotor, $grupos[0]['cveletra']);
  if (!isset($lecturas[0])) {
    mSetMessage($pdf, $header, 'e3'); //no hay alumnos en este grupo

  } else {
    // Obtiene todos los alumnos de un grupo
    $tmpAlumno = array();
    $tmpData   = array();
    for ($i = 0; $i < count($lecturas); $i++) {
      $p['#'] = ($i + 1); //enumera

      $datos[0] = $web->getAlumno(
        $lecturas[$i]['nocontrol'],
        $cveperiodo,
        $lecturas[$i]['cveletra'],
        $lecturas[$i]['cvelectura']
      );

      $datos         = array_merge($p, $datos[0][0]);
      $tmpData[0][0] = $web->mMoveFields($datos);
      $tmpAlumno     = array_merge($tmpAlumno, $tmpData[0]);

    } //fin for
    $alumnos[0] = $tmpAlumno;
  } //end else

  // FULL SUBHEADER
  $html = $web->grupoSubHeader(array('grupos' => $grupos, 'gruposHeader' => $gruposHeader, 'position' => 0));

  // ALUMNOS SUBHEADER
  if (!is_array($alumnos)) {
    mSetMessage($pdf, $header, 'e3'); //no hay alumnos en este grupo
  }

  $alumnosHeader = $web->getAssocArray($alumnos);
  $alumnos       = $web->getAssocArray($alumnos, true);
  if ($alumnosHeader == null || $alumnos == null) {
    mSetMessage($pdf, $header, 'e3'); //no hay alumnos en este grupo
  }

  $web->smarty->assign('fin', (sizeof($alumnos[0][0]) / 2 + 1));
  $web->page_break(0, $grupos); //habilita o deshabilita el salto de página

  // DATOS TABLE PRINCIPAL
  $web->smarty->assign('columns', $alumnosHeader);
  $web->smarty->assign('rows', $alumnos[0]);
  $web->smarty->assign('title', 'Lista de Asistencia');
  $html .= (string) ($web->smarty->fetch('table.html'));

  $html = $header . $html . $footer;
  $pdf->createPDF('Lista de Asistencia', $html, 'landscape');
  die();
}

/**
 * Muestra mensajes de error y de éxito
 * Usar solo para casos de despliege de PDF
 */
function mSetMessage($pdf, $header, $msg)
{
  switch ($msg) {
    case 'e1':
      $msg = 'No hay periodo actual';
      break;
    case 'promotor':
      $msg = 'Ocurrió un error al intentar identificar a este promotor';
      break;
    case 'grupos':
      $msg = 'Ocurrió un error al intentar identificar a este grupo';
      break;
    case 'e2':
      $msg = 'Hacen falta datos para continuar';
      break;
    case 'e3':
      $msg = 'No hay alumnos en este grupo';
      break;
  }

  $pdf->createPDF('Lista-Asistencia', $header . $msg, 'landscape');
  die();
}

/**
 *
 */
function libros()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info']) ||
    !isset($_GET['info2']) ||
    !isset($_GET['info3'])) {
    message('danger', 'Información incompleta');
  }

  $lectura = $web->getAll('*', array('cvelectura' => $_GET['info']), 'lectura');
  if (!isset($lectura[0])) {
    message('danger', 'No altere la estructura de la interfaz');
  }

  $libros = $web->listBooks($lectura[0]['nocontrol'], $cveperiodo);
  if (!isset($libros[0])) {
    $web->simple_message('warning', 'No hay libros registrados');

  } else {
    $letra_subida = $web->getAll(array('letra'), array('cve' => $libros[0]["cveletra"]), 'abecedario');
    for ($i = 0; $i < count($libros); $i++) {
      $dir            = $web->route_periodos . $libros[$i]["cveperiodo"] . "/" . $letra_subida[0][0] . "/" . $libros[$i]["nocontrol"] . "/";
      $nombre_fichero = $libros[$i]["cvelibro"] . "_" . $libros[$i]["nocontrol"] . ".pdf";
      if (file_exists($dir . $nombre_fichero)) {
        $libros[$i]["archivoExiste"] = $nombre_fichero;
      }

      $estados               = $web->getAll('*', null, 'estado');
      $selected              = $libros[$i]['cveestado'];
      $redireccion['accion'] = "?accion=estado";
      $redireccion['nombre'] = "&estado";
      $redireccion['pagina'] = "grupo.php";
      $redireccion['get']    = "&info=" . $_GET['info'] . "&info2=" . $_GET['info2'] . "&info3=" . $libros[$i]['cvelista'];

      $sql                 = "SELECT * FROM estado";
      $combo               = $web->combo($sql, $libros[$i]['cveestado'], "../", array(), $redireccion);
      $libros[$i]['combo'] = $combo;
    }

    $web->smarty->assign('libros', $libros);
  }

  if (isset($_GET['aviso'])) {
    switch ($_GET['aviso']) {
      case 1:
        $web->simple_message('warning', 'No se pudo calcular el promedio de los reportes');
        break;
    }
  }
  $web->smarty->assign('cvelectura', $_GET['info']);
  $web->smarty->assign('grupo', $_GET['info3']);
  $web->smarty->assign('nocontrol', $_GET['info2']);
  $web->iniClases('promotor', "index grupos libros");
  $web->smarty->display('grupo.html');
  die();
}

/**
 * Permite descargar el reporte del alumno
 */
function reporte()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info']) ||
    !isset($_GET['info2'])) {
    header('Location: grupos.php?e=9');die();
  }

  $nocontrol = explode(".", explode("_", $_GET['info'])[1])[0];
  $dir       = $web->route_periodos . $cveperiodo . "/" . $_GET['info2'] . "/" . $nocontrol . "/" . $_GET['info'];
  header("Content-disposition: attachment; filename=" . $_GET['info']);
  header("Content-type: MIME");
  readfile($dir);
}

/**
 *
 */
function estado()
{
  global $web;

  if (!isset($_GET['estado'])) {
    message('warning', 'Hace falta información', $web);
  }
  if ($_GET['estado'] == -1) {
    message('warning', 'Selecciona una opción válida', $web);
  }

  $redirect = array(
    'accion' => 'libros',
    'info'   => $_GET['info'],
    'info2'  => $_GET['info2'],
    'info3'  => $_GET['info3'],
  );
  if (isset($_GET['info4'])) {
    header('Location: grupo.php?' . http_build_query($redirect));die();
  }

  $estados = $web->getBookList($_GET['info'], $_GET['info2']);
  if (!isset($estados[0])) {
    message('danger', 'El alumno no tiene libros registrados', $web);
  }

  if ($_GET['estado'] == 2) {
    for ($i = 0; $i < sizeof($estados); $i++) {
      if ($estados[$i]['cveestado'] == 2) {
        message('danger', 'El alumno no puede tener dos libros en proceso', $web);
      }
    }
    $web->update(array('cveestado' => 2), array('cvelista' => $_GET['info3']), 'lista_libros');
    header('Location: grupo.php?' . http_build_query($redirect));die();

  } else if ($_GET['estado'] == 3) {
    $calif = $web->getAll(array('calif_reporte'), array('cvelista' => $_GET['info3']), 'lista_libros');
    if ($calif[0]['calif_reporte'] < 70) {
      message('danger', 'No se puede marcar como terminado, no cuenta con la calificación suficiente', $web);
    }
    $web->update(array('cveestado' => $_GET['estado']), array('cvelista' => $_GET['info3']), 'lista_libros');
    header('Location: grupo.php?' . http_build_query($redirect));die();

  } else {
    $web->update(array('cveestado' => $_GET['estado']), array('cvelista' => $_GET['info3']), 'lista_libros');
    header('Location: grupo.php?' . http_build_query($redirect));die();
  }
}

function calificar_reporte()
{
  global $web;

  $cveperiodo = $web->periodo();
  if ($cveperiodo == "") {
    message('danger', 'No hay periodos actuales', $web);
  }
  if (!isset($_POST['calificacion']) || !isset($_GET['info1']) ||
    !isset($_GET['info3']) || !isset($_GET['info3'])) {
    message('danger', 'Falta información', $web);
  }

  $existencia = $web->getAll('*', array('cveperiodo' => $cveperiodo, 'cvelectura' => $_GET['info2']), 'lectura');
  if (!isset($existencia[0])) {
    message('danger', 'No existe la lectura', $web);
  }

  $existencia = $web->getAll('*', array('cveusuario' => $_GET['info3']), 'usuarios');
  if (!isset($existencia[0])) {
    message('danger', 'No existe el alumno', $web);
  }

  $existencia = $web->getLaboral($_GET['info2'], $cveperiodo);
  if (!isset($existencia[0])) {
    message('danger', 'No tienes permisos', $web);
  }
  if ($_POST['calificacion'] == "") {
    message('danger', 'No se envio la calificación del reporte', $web);
  }
  if ($_POST['calificacion'] > 100 || $_POST['calificacion'] < 0) {
    message('danger', 'Envíe una califición válida', $web);
  }

  $web->update(array('calif_reporte' => $_POST['calificacion']), array('cvelista' => $_GET['info1']), 'lista_libros');
  $redirect = array(
    'accion' => 'libros',
    'info'   => $_GET['info2'],
    'info2'  => $_GET['info3'],
    'info3'  => $_GET['info4'],
    'm'      => 1,
  );
  if (promReporte($web) && promTerminado($web, 'cvelectura', $_GET['info2'])) {
    $redirect['a'] = 2;
  }
  header('Location: grupo.php?' . http_build_query($redirect));die();
}
