<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}
if (isset($_GET['accion'])) {
  switch ($_GET['accion']) {
    case 'update':
      $cveusuario = $_POST['cveusuario'];

      if ($cveusuario != $_SESSION['cveUser']) {
        message('index datos', 'No alteres la estructura de la interfaz', 'danger', $web);
      }
      if (!isset($_POST['datos']['nombre']) ||
        !isset($_POST['datos']['cveespecialidad']) ||
        !isset($_POST['datos']['correo'])) {
        message('index datos', 'No alteres la estructura de la interfaz', 'danger', $web);
      }

      if (($_POST['datos']['nombre']) == '' ||
        ($_POST['datos']['cveespecialidad']) == '' ||
        ($_POST['datos']['correo']) == '') {
        message('index datos', 'Llene todos los campos', 'danger', $web);
      }

      $sql   = "select * from usuarios where cveusuario=?";
      $datos = $web->DB->GetAll($sql, $cveusuario);
      if (!isset($datos[0])) {
        errores('', 'index promotor nuevo', $cveusuario, $web);
        message('index datos', 'El promotor no existe', 'danger', $web);
      }

      $datosp = $datos;
      if (!$web->valida($_POST['datos']['correo'])) {
        message('index datos', 'Ingrese un correo valido', 'danger', $web);
      }

      $sql            = "select correo from usuarios where cveusuario=?";
      $correo_usuario = $web->DB->GetAll($sql, $cveUsuario);

      $sql     = "select correo from usuarios where correo=?";
      $correos = $web->DB->GetAll($sql, $correo);
      if (sizeof($correos) == 1) {
        if ($correo_usuario[0]['correo'] != $correos[0]['correo']) {
          message('index datos', 'El correo ya existe', 'danger', $web);
        }
      }

      if ($_POST['datos']['pass'] == 'true') {
        if (!isset($_POST['datos']['contrasena']) ||
          !isset($_POST['datos']['contrasenaN']) ||
          !isset($_POST['datos']['confcontrasenaN'])) {
          message('index datos', 'No altere la estructura de la interfaz', 'danger', $web);
        }

        if (isset($_POST['datos']['contrasena']) == '' ||
          isset($_POST['datos']['contrasenaN']) == '' ||
          isset($_POST['datos']['confcontrasenaN']) == '') {
          message('index datos', 'Llene todos los campos', 'danger', $web);
        }

        if ($datosp[0]['pass'] != md5($_POST['datos']['contrasena'])) {
          message('index datos', 'La contraseña es incorrecta', 'danger', $web);
        }

        if ($_POST['datos']['confcontrasenaN'] != $_POST['datos']['contrasenaN']) {
          message('index datos', 'La contraseña nueva debe coincidir con la confirmación', 'danger', $web);
        }

        $sql = "update usuarios set nombre=?, correo= ?, pass=? where cveusuario=?";
        $tmp = array(
          $_POST['datos']['nombre'],
          $_POST['datos']['correo'],
          md5($_POST['datos']['contrasenaN']),
          $cveusuario);
        if (!$web->query($sql, $tmp)) {
          $web->smarty->assign('alert', 'danger');
          $web->smarty->assign('msg', 'No se pudo completar la operación');
          break;
        }
      } else {
        $sql = "update usuarios set nombre=?, correo= ? where cveusuario=?";
        $tmp = array($_POST['datos']['nombre'], $_POST['datos']['correo'], $cveusuario);
        if (!$web->query($sql, $tmp)) {
          $web->smarty->assign('alert', 'danger');
          $web->smarty->assign('msg', 'No se pudo completar la operación');
          break;
        }

        if (isset($_POST['datos']['especialidad'])) {
          if ($_POST['datos']['especialidad'] == 'true') {
            $sql = "update especialidad_usuario set cveespecialidad=?, otro = null where cveusuario=? ";
            $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));
          } else {
            $sql = "update especialidad_usuario set cveespecialidad='O', otro=? where cveusuario=? ";
            $web->query($sql, array($_POST['datos']['otro'], $cveusuario));
          }
        } else {
          $sql             = "select cveespecialidad from especialidad_usuario where cveusuario=?";
          $cveespecialidad = $web->DB->GetAll($sql, $cveusuario);
          if ($cveespecialidad[0]['cveespecialidad'] == 'O') {
            $sql = "update especialidad_usuario set cveespecialidad='O', otro=? where cveusuario=? ";
            $web->query($sql, array($_POST['datos']['otro'], $cveusuario));
          } else {
            $sql = "update especialidad_usuario set cveespecialidad=?, otro = null where cveusuario=? ";
            $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));
          }
        }
      }
      header("Location: datos.php");
      break;
  }
}

$sql = "select u.cveusuario, u.nombre AS \"nombreUsuario\", e.nombre, eu.cveespecialidad, eu.otro, u.correo
		from usuarios u
		inner join especialidad_usuario eu on eu.cveusuario = u.cveusuario
		inner join especialidad e on e.cveespecialidad = eu.cveespecialidad
		inner join usuario_rol ur on ur.cveusuario = u.cveusuario
		where u.cveusuario = ? and ur.cverol = ?";
$datos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], 1));

//Verificar si se tiene serultados
if (!isset($datos[0])) {
  message('index datos', 'Ocurrido un error inesperado', 'danger', $web);
}

if ($datos[0]['cveespecialidad'] != 'O') {
  $selected = $datos[0]['cveespecialidad'];
} else {
  $selected = null;
}
$web->iniClases('admin', "index datos");
$sql   = "select cveespecialidad, nombre from especialidad where cveespecialidad <> ?";
$combo = $web->combo($sql, $selected, '../', 'O');
$web->smarty->assign('promotores', $datos[0]);
$web->smarty->assign('combito', $combo);
$web->smarty->assign('especialidad', $combo);
$web->smarty->display('datos.html');

function message($iniClases, $msg, $alert, $web)
{
  $web->iniClases('admin', $iniClases);

  $web->smarty->assign('alert', $alert);
  $web->smarty->assign('msg', $msg);

  $web->smarty->display('datos.html');
  die();
}
