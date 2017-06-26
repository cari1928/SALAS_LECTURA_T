<?php
include 'sistema.php';
if ($_SESSION['logueado'] != true) {
  session_destroy();
  header('Location: http://tigger.itc.mx/salasLectura/');
}

if (!isset($_SESSION['cveUser'])) {
  session_destroy();
  header('Location: http://tigger.itc.mx/salasLectura/');
}

$web = new Sistema;
$web->iniClases(null, "index login");

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'cambiar':
      if (!isset($_SESSION['cveUser'])) {
        $web->simple_message('danger', 'Inicie sesión para poder acceder');
        $web->smarty->display('formulario_login.html');
        die();
      }

      $sql = "select * from usuario_rol where cveusuario=?";
      $rol = $web->DB->GetAll($sql, $_SESSION['cveUser']);

      if (!isset($rol[0])) {
        $web->iniClases(null, "index login roles");
        $web->simple_message('danger', 'No tiene permiso para acceder');
        $web->smarty->display('roles.html');
        die();
      }

      $web->smarty->assign('roles', $rol);
      $web->smarty->display('roles.html');
      die();
      break;
  }
}
