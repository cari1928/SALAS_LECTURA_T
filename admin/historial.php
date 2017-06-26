<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'periodo':
      $web->iniClases('admin', "index historial promotor-alumno");
      $web->smarty->assign('accion', $_GET['accion']);
      $web->smarty->assign('cveperiodo', $_GET['info1']);
      $web->smarty->display('historial.html');
      die();
      break;

    case 'promotor':
      
      break;

    case 'alumnos':
      die('promotor-alumno');
      break;
  }
}

if(isset($_GET['e'])) {
  
  if($_GET['e'] == 1) {
    $web->iniClases('admin', "index historial promotor-alumno");
    $web->smarty->assign('accion', 'periodo');
    $web->smarty->assign('periodo', $_GET['info1']);
    $web->simple_message('danger', 'No hay promotores para este periodo');
    $web->smarty->display('historial.html');
    die();
  }
}

header("Location: periodos.php?accion=historial");

/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases Ruta a mostrar en links
 * @param  String $msg       Mensaje a desplegar
 * @param  $web              Para poder aplicar las funciones de $web
 */
function message($iniClases, $msg, $alert, $web)
{
  $web->iniClases('admin', $iniClases);
  $web->simple_message('alert', $msg);
  $web->smarty->display('historial.html');
  die();
}
