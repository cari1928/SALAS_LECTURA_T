<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

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
      if (!isset($_GET['info']) ||
        !isset($_GET['info2'])) {
        mMessage('warning', 'Hacen falta datos');
      }

      $sql  = "SELECT * FROM abecedario WHERE cve=?";
      $data = $web->DB->GetAll($sql, $_GET['info2']);
      if (!isset($data[0])) {
        mMessage('warning', 'No hay mensajes para este grupo');
      }

      $nombre_fichero = "/home/slslctr/archivos_msj/" . $cveperiodo . "/" . $data[0]['letra'] . "/" . $_GET['info'];
      if (!file_exists($nombre_fichero)) {
        header('Location: grupos.php?aviso=5'); //El archivo no existe
        die();
      }
      header("Content-disposition: attachment; filename=" . $_GET['info']);
      header("Content-type: MIME");
      readfile("/home/slslctr/archivos_msj/" . $cveperiodo . "/" . $data[0]['letra'] . "/" . $_GET['info']);
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

  $sql = "SELECT * FROM laboral
  WHERE cveletra in (SELECT cve FROM abecedario WHERE letra=?)
  AND cveletra in (SELECT cveletra FROM lectura WHERE cveletra in (SELECT cve FROM abecedario WHERE letra=?))
  AND laboral.cveperiodo=?";
  $grupo = $web->DB->GetAll($sql, array($_GET['info'], $_GET['info'], $cveperiodo));
  if (!isset($grupo[0])) {
    mMessage('warning', 'El grupo no existe o no cuenta con los permisos para acceder');
  }

  $sql = "SELECT cvemsj, introduccion, tipomsj.descripcion, fecha, expira FROM msj
  INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
  WHERE cveperiodo=?
  AND (tipo='G' OR tipo='I')
  AND cveletra in (SELECT cve FROM abecedario WHERE letra=?)
  AND expira > NOW()
  ORDER BY fecha DESC";
  $parameters = array($cveperiodo, $_GET['info']);
  $mensajes   = $web->DB->GetAll($sql, $parameters);
  if (!isset($mensajes[0])) {
    mMessage('danger', 'No hay mensajes');
  }

  $web->smarty->assign('mensajes', $mensajes);
  $web->smarty->display('msj.html');
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

  $sql     = "SELECT * FROM msj WHERE cvemsj=?";
  $mensaje = $web->DB->GetAll($sql, $cvemsj);
  if (!isset($mensaje[0])) {
    mMessage('warning', 'No hay mensajes');
  }

  $sql  = "SELECT * FROM abecedario WHERE cve=?";
  $data = $web->DB->GetAll($sql, $mensaje[0]['cveletra']);
  if (!isset($data[0])) {
    mMessage('warning', 'No hay mensajes para este grupo');
  }

  $web->smarty->assign('mensaje', $mensaje);
  $web->smarty->assign('accion', $accion);

  if ($mensaje[0]['archivo'] != '') {
    $nombre_fichero = "/home/slslctr/archivos_msj/" . $cveperiodo . "/" . $data[0]['letra'] . "/" . $mensaje[0]['archivo'];
    if (!file_exists($nombre_fichero)) {
      $mensaje[0]['archivo'] = "El archivo " . $mensaje[0]['archivo'] . " ha sido eliminado";
      $web->smarty->assign('eliminado', true);
    }
    $web->smarty->assign('archivo', $mensaje[0]['archivo']);
  }
  $web->smarty->display('msj.html');
  exit();
}
