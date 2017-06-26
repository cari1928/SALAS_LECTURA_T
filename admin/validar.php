<?php
include '../sistema.php';
$web->iniClases('admin', "index validar-usuario");

if ($_SESSION['roles'] != 'A') {

  if (isset($_GET['accion'])) {

    if (isset($_GET['clave'])) {
      header('Location: ../login.php?m=2&validar=' . $_GET['accion'] . '&clave=' . $_GET['clave']);
    }
  } else {
    header('Location: ../login.php');
  }
}

if (!isset($_GET['accion'])) {
  $web->simple_message('danger', 'No se especifico la accion a realizar');
  $web->smarty->display('validar.html');
  die();
}

if (!isset($_GET['clave'])) {
  $web->simple_message('danger', 'No se especificó el usuario');
  $web->smaty->display('validar.html');
  die();
}

$sql           = "select * from usuarios where cveusuario = ?";
$datos_usuario = $web->DB->GetAll($sql, $_GET['clave']);

if (!isset($datos_usuario[0])) {
  $web->simple_message('danger', 'El usuario no existe');
  $web->smarty->display('validar.html');
  die();
}

if ($datos_usuario[0]['validacion'] == 'Aceptado') {
  $web->simple_message('danger', 'Este usuario ya fue aceptado');
  $web->smarty->display('validar.html');
  die();
}

$sql = "UPDATE usuarios SET validacion=? WHERE cveusuario=?";
if ($_GET['accion'] == 'aceptar') {
  $web->query($sql, array('Aceptado', $_GET['clave']));
  $web->simple_message('info', 'El usuario ha sido aceptado');
  
} else {
  $web->query($sql, array('Rechazado', $_GET['clave']));
  $web->simple_message('info', 'EL usuario ha sido rechazado');
}

$web->smarty->display('validar.html');
