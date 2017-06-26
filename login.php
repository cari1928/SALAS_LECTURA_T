<?php

include 'sistema.php';

$web        = new Sistema;
$email      = '';
$contrasena = '';
$msg        = '';

$web->iniClases(null, "index login");

if (isset($_POST['datos']['contrasena'])) {
  $cveUsuario    = $_POST['datos']['cveUsuario'];
  $contrasena    = $_POST['datos']['contrasena'];
  $usuario_clave = null;
  $validar       = null;

  if (isset($_POST['usuario_clave'])) {
    $usuario_clave = $_POST['usuario_clave'];
  }
  if (isset($_POST['validar'])) {
    $validar = $_POST['validar'];
  }

  if (!$web->login($cveUsuario, $contrasena, $usuario_clave, $validar)) {
    
    switch ($web->aceptacion) {
    
      case "No guardado":
        $web->simple_message('danger', 'La contraseña y/o usuario son incorrectos');
        break;

      case "Rechazado":
        $web->simple_message('danger', 'Lo sentimos, no fue aprobado tu registro. Para mayor información comunícate con el administrador');
        break;

      case "":
        $web->simple_message('danger', 'Tu registro aún no ha sido autorizado. Para mayor información comunícate con el administrador');
        break;
    }
    $web->smarty->display('formulario_login.html');
    die();
  }
}

if (isset($_GET['info'])) {

  if (!isset($_SESSION['cveUser'])) {
    $web->simple_message('danger', 'Inicie sesión para poder acceder');
    $web->smarty->display('formulario_login.html');
    die();
  }

  $sql = "select * from usuario_rol where cveusuario=? and cverol=?";
  $rol = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $_GET['info']));

  if (!isset($rol[0])) {
    $web->iniClases(null, "index login roles");

    $sql   = "select * from usuario_rol where cveusuario=?";
    $roles = $this->DB->GetAll($sql, $email);

    $web->smarty->assign('roles', $roles);
    $web->simple_message('danger', 'No tiene permiso para acceder');
    $web->smarty->display('roles.html');
    die();
  }

  $_SESSION['logueado'] = true;
  $_SESSION['bandera_roles'] = "true";
  switch($_GET['info']) {
    case 1: 
        $_SESSION['roles'] = 'A';
        header('Location: admin');
        break;
    case 2:
      $_SESSION['roles'] = 'P';
      header('Location: promotor');
      break;
    case 3:
      $_SESSION['roles'] = 'U';
      header('Location: alumno');
      break;
  }
}

//para mensajes cuando la página es llamada principalmente por header: Location
if (isset($_GET['m'])) {
  
  switch ($_GET['m']) {
    case 1:
      $web->simple_message('info', 'Espere que un administrador acepte su registro');
      break;

    case 2:
      $web->simple_message('info', 'Inicia sesión como administrador');
      if (isset($_GET['validar'])) {
        $web->smarty->assign('validar', $_GET['validar']);
      }
      if (isset($_GET['clave'])) {
        $web->smarty->assign('usuario_clave', $_GET['clave']);
      }
      break;
  }
}

$web->smarty->display('formulario_login.html');
