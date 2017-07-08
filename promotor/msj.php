<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web->iniClases('promotor', "index vergrupos mesnajes");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);
$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('danger', 'No hay periodos actuales', $web);
}
$accion = "";

if (!isset($_GET['cvemsj']) && !isset($_GET['info'])) {
  header('location: index.php');
}

if (isset($_GET['accion'])) {
  $accion = $_GET['accion'];
}

switch ($accion) {

  case 'ver':
    $cvemsj = "";
    if (isset($_GET['cvemsj'])) {
      $cvemsj = $_GET['cvemsj'];
    }

    $sql     = "select * from msj where cvemsj=" . $cvemsj;
    $mensaje = $web->DB->GetAll($sql);
    $web->smarty->assign('mensaje', $mensaje);
    $web->smarty->assign('accion', $accion);
    //$web->debug($mensaje);
    if ($mensaje[0]['archivo'] != '') {
      $nombre_fichero = "/home/slslctr/archivos/msj/" . $cveperiodo . "/" . $mensaje[0]['archivo'];
      if (!file_exists($nombre_fichero)) {
        $mensaje[0]['archivo'] = "El archivo " . $mensaje[0]['archivo'] . " ha sido eliminado";
        $web->smarty->assign('eliminado', true);
      }
      $web->smarty->assign('archivo', $mensaje[0]['archivo']);
    }
    $web->smarty->display('redacta.html');
    exit();
    break;

  case 'editar':
    if (isset($_GET['cvemsj'])) {
      $cvemsj = $_GET['cvemsj'];
    }

    $sql     = "select* from msj where cvemsj=" . $cvemsj;
    $mensaje = $web->DB->GetAll($sql);
    $web->smarty->assign('mensaje', $mensaje);
    $web->smarty->assign('accion', $accion);
    $web->smarty->display('redacta.html');
    exit();
    break;

  case 'eliminar':
    if (isset($_GET['cvemsj'])) {
      $cvemsj = $_GET['cvemsj'];
    }
    $sql = "delete from msj where cvemsj=" . $cvemsj;
    $web->DB->GetAll($sql);
    break;

  case 'archivo':
    $nombre_fichero = "/home/slslctr/archivos/msj/" . $cveperiodo . "/" . $_GET['info'];
    if (!file_exists($nombre_fichero)) {
      header('Location: grupos.php?aviso=5'); //El archivo no existe
    }
    header("Content-disposition: attachment; filename=" . $_GET['info']);
    header("Content-type: MIME");
    readfile("/home/slslctr/archivos/msj/" . $cveperiodo . "/" . $_GET['info']);
    break;
}
