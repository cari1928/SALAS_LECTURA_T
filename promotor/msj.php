<?php
include "../sistema.php";

if ($_SESSION['roles'] == 'P') {
  $web->iniClases('promotor', "index vergrupos mesnajes");
  $grupos = $web->grupos($_SESSION['cveUser']);
  $web->smarty->assign('grupos', $grupos);
  $accion = "";

  if (!isset($_GET['cvemsj'])) {
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
      // var_dump($mensaje);
      $web->smarty->assign('mensaje', $mensaje);
      $web->smarty->assign('accion', $accion);
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
  }
}
