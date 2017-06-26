<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

if (isset($_POST['datos']['especial'])) {
  $sql = $_POST['datos']['especial'];
  $web->DB->startTrans();
  $datos = $web->DB->GetAll($sql);

  echo "<pre>";
  print_r($datos);

  if ($web->DB->HasFailedTrans()) {
    //si falló algo entra al if
    $web->simple_message('danger', 'No se pudo completar la operación');
  }
  $web->DB->CompleteTrans();
}

$web->iniClases('admin', "index especial");
$web->smarty->display('especial.html');
