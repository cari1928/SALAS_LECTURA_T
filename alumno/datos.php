<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web->iniClases('usuario', "index datos");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'update':

      $cveusuario = $_POST['cveusuario'];

      if ($cveusuario != $_SESSION['cveUser']) {
        message('No alteres la estructura de la interfaz', 'warning');
      }

      if (!isset($_POST['datos']['nombre']) ||
        !isset($_POST['datos']['cveespecialidad']) ||
        !isset($_POST['datos']['correo'])) {
        message('No alteres la estructura de la interfaz', 'warning');
      }
      if (($_POST['datos']['nombre']) == '' ||
        ($_POST['datos']['cveespecialidad']) == '' ||
        ($_POST['datos']['correo']) == '') {
        message('Llene todos los campos', 'warning');
      }

      $sql   = "SELECT * FROM usuarios WHERE cveusuario=?";
      $datos = $web->DB->GetAll($sql, $cveusuario);
      if (!isset($datos[0])) {
        message('El alumno no existe', 'danger');
      }

      $datosp = $datos;
      if (!$web->valida($_POST['datos']['correo'])) {
        message('Ingrese un correo valido', 'danger');
      }

      $sql            = "SELECT correo FROM usuarios WHERE cveusuario=?";
      $correo_usuario = $web->DB->GetAll($sql, $cveUsuario);

      $sql     = "SELECT correo FROM usuarios WHERE correo=?";
      $correos = $web->DB->GetAll($sql, $correo);
      if (sizeof($correos) == 1) {
        if ($correo_usuario[0]['correo'] != $correos[0]['correo']) {
          message('El correo ya existe', 'danger');
        }
      }

      if ($_POST['datos']['pass'] == 'true') {
        if (!isset($_POST['datos']['contrasena']) ||
          !isset($_POST['datos']['contrasenaN']) ||
          !isset($_POST['datos']['confcontrasenaN'])) {
          message('No altere la estructura de la interfaz', 'danger');
        }

        if (isset($_POST['datos']['contrasena']) == '' ||
          isset($_POST['datos']['contrasenaN']) == '' ||
          isset($_POST['datos']['confcontrasenaN']) == '') {
          message('Llene todos los campos', 'danger');
        }

        if ($datosp[0]['pass'] != md5($_POST['datos']['contrasena'])) {
          message('La contraseña es incorrecta', 'danger');
        }

        if ($_POST['datos']['confcontrasenaN'] != $_POST['datos']['contrasenaN']) {
          message('La contraseña nueva debe coincidir con la confirmación', 'danger');
        }

        $sql = "UPDATE usuarios SET nombre=?, correo= , pass=? WHERE cveusuario=?";
        $tmp = array(
          $_POST['datos']['nombre'],
          $_POST['datos']['correo'],
          md5($_POST['datos']['contrasenaN']),
          $cveusuario);
        if (!$web->query($sql, $tmp)) {
          $web->simple_message('danger', 'No es posible actualizar los datos del promotor');
          break;
        }

      } else {
        $sql = "UPDATE usuarios SET nombre=?, correo=? WHERE cveusuario=?";
        $tmp = array($_POST['datos']['nombre'], $_POST['datos']['correo'], $cveusuario);
        if (!$web->query($sql, $tmp)) {
          $web->simple_message('danger', 'No es posible actualizar los datos del promotor');
          break;
        }

        if (isset($_POST['datos']['especialidad'])) {
          if ($_POST['datos']['especialidad'] == 'true') {
            $sql = "UPDATE especialidad_usuario SET cveespecialidad=?, otro=null WHERE cveusuario=?";
            $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));
          } else {
            $sql = "UPDATE especialidad_usuario SET cveespecialidad='O', otro=? WHERE cveusuario=?";
            $web->query($sql, array($_POST['datos']['otro'], $cveusuario));
          }
        } else {
          $sql             = "SELECT cveespecialidad FROM especialidad_usuario WHERE cveusuario=?";
          $cveespecialidad = $web->DB->GetAll($sql, $cveusuario);
          if ($cveespecialidad[0]['cveespecialidad'] == 'O') {
            $sql = "UPDATE especialidad_usuario SET cveespecialidad='O', otro=? WHERE cveusuario=? ";
            $web->query($sql, array($_POST['datos']['otro'], $cveusuario));
          } else {
            $sql = "UPDATE especialidad_usuario SET cveespecialidad=?, otro = null WHERE cveusuario=? ";
            $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));
          }
        }
      }

      header("Location: datos.php");
      die(); //para que funcione bien
      break;

  }
}

$sql = "SELECT u.cveusuario, u.nombre AS \"nombreUsuario\", e.nombre, eu.cveespecialidad, eu.otro, u.correo
FROM usuarios u
INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
INNER JOIN usuario_rol ur ON ur.cveusuario = u.cveusuario
WHERE u.cveusuario=? AND ur.cverol=?";
$datos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], 3));
//Verificar si se tiene serultados
if (!isset($datos[0])) {
  message('index datos', 'Error, el alumno no existe', 'danger', $web);
}

if ($datos[0]['cveespecialidad'] != 'O') {
  $selected = $datos[0]['cveespecialidad'];
} else {
  $selected = null;
}

$sql   = "SELECT cveespecialidad, nombre FROM especialidad WHERE cveespecialidad <> 'O'";
$combo = $web->combo($sql, $selected, '../');
$web->smarty->assign('alumno', $datos[0]);
$web->smarty->assign('combo', $combo);
$web->smarty->assign('especialidad', $combo);
$web->smarty->display('datos.html');

/*******************************************************************************************************************
 * FUNCIONES
 *******************************************************************************************************************/
function message($msg, $alert)
{
  global $web;
  $web->simple_message($alert, $msg);
  $web->smarty->display('datos.html');
  die();
}
