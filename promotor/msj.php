<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web->iniClases('promotor', "index grupos mensajes");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  header('Location: grupos.php'); //grupos.php ya tiene un mensaje listo para este caso
  die();
}
if (!isset($_GET['cvemsj']) &&
  !isset($_GET['info'])) {
  header('location: index.php');
}
if (isset($_GET['accion'])) {
  $accion = $_GET['accion'];
}

switch ($accion) {

  case 'ver':
    mRead();
    break;

  case 'archivo':
    if (!isset($_GET['info']) ||
      !isset($_GET['info2'])) {
      header('Location: grupos.php?aviso=6');
      die();
    }

    $sql  = "SELECT * FROM abecedario WHERE cve=?";
    $data = $web->DB->GetAll($sql, $_GET['info2']);
    if (!isset($data[0])) {
      header('Location: grups.php?aviso=8');
      die();
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

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
/**
 *
 */
function mRead()
{
  global $web, $cveperiodo, $accion;

  if (!isset($_GET['info'])) {
    header('Location: grupos.php?aviso=6');
    die();
  }
  $cvemsj = $_GET['info'];

  $sql     = "SELECT * FROM msj WHERE cvemsj=" . $cvemsj;
  $mensaje = $web->DB->GetAll($sql);
  if (!isset($mensaje[0])) {
    header('Location: grupos.php?aviso=7');
  }

  $sql  = "SELECT * FROM abecedario WHERE cve=?";
  $data = $web->DB->GetAll($sql, $mensaje[0]['cveletra']);
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

  $web->smarty->assign('avisos', true);
  $web->smarty->display('redacta.html');
  exit();
}
