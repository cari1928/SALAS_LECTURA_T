<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web = new MsjControllers;
$web->iniClases('usuario', "index grupos grupo");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  mMessage('danger', 'No hay periodos actuales', 'grupo.html');
}

if (isset($_GET['accion'])) {
  switch ($_GET['accion']) {

    case 'listado':
      mListado();
      break;

    case 'leer':
      mLeer();
      break;

    case 'archivo':
      archivo();
      break;
  }
}

/*************************************************************************************************
 * FUNCIONES
 *************************************************************************************************/
/**
 *
 */
function mMessage($type, $msg, $html = 'msj.html')
{
  global $web;
  $web->simple_message($type, $msg);
  $web->smarty->display($html);
  die();
}

/**
 *
 */
function mListado()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info'])) {
    mMessage('warning', 'Falta información');
  }
  $grupo = $web->getLaboral($_GET['info'], $cveperiodo);
  if (!isset($grupo[0])) {
    mMessage('warning', 'El grupo no existe o no cuenta con los permisos para acceder');
  }

  $mensajes = $web->getMsj($cveperiodo, $_GET['info']);
  if (!isset($mensajes[0])) {
    mMessage('danger', 'No hay mensajes');
  }

  $web->smarty->assign('mensajes', $mensajes);
  $web->smarty->display('msj.html');
  die();
}

/**
 *
 */
function mLeer()
{
  global $web, $cveperiodo, $accion;

  $cvemsj = "";
  if (isset($_GET['info'])) {
    $cvemsj = $_GET['info'];
  }

  $mensaje = $web->getMessages($cvemsj);
  if (!isset($mensaje[0])) {
    mMessage('warning', 'No hay mensajes');
  }

  $data = $web->getAll('*', array('cve' => $mensaje[0]['cveletra']), 'abecedario');
  if (!isset($data[0])) {
    mMessage('warning', 'No hay mensajes para este grupo');
  }

  $web->smarty->assign('mensaje', $mensaje);
  $web->smarty->assign('accion', $accion);

  if ($mensaje[0]['archivo'] != '') {
    $nombre_fichero = $web->route_msj . $cveperiodo . "/" . $data[0]['letra'] . "/" . $mensaje[0]['archivo'];
    if (!file_exists($nombre_fichero)) {
      $mensaje[0]['archivo'] = "El archivo " . $mensaje[0]['archivo'] . " ha sido eliminado";
      $web->smarty->assign('eliminado', true);
    }
    $web->smarty->assign('archivo', $mensaje[0]['archivo']);
  }
  $web->smarty->display('msj.html');
  die();
}

function archivo()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info']) ||
    !isset($_GET['info2'])) {
    mMessage('warning', 'Hacen falta datos');
  }

  $data = $web->getAll('*', array('cve' => $_GET['info2']), 'abecedario');
  if (!isset($data[0])) {
    mMessage('warning', 'No hay mensajes para este grupo');
  }

  $nombre_fichero = $web->route_msj . $cveperiodo . "/" . $data[0]['letra'] . "/" . $_GET['info'];
  if (!file_exists($nombre_fichero)) {
    header('Location: grupos.php?aviso=5'); //El archivo no existe
    die();
  }

  header("Content-disposition: attachment; filename=" . $_GET['info']);
  header("Content-type: MIME");
  readfile($web->route_msj . $cveperiodo . "/" . $data[0]['letra'] . "/" . $_GET['info']);
}
