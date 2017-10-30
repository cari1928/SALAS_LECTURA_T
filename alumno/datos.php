<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web = new AlumnoDatosControllers;
$web->iniClases('usuario', "index datos");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

showMessages();

if (isset($_GET['accion'])) {
  switch ($_GET['accion']) {
    case 'update':
      update();
      break;
  }
}

$datos = $web->getUsuario($_SESSION['cveUser'], 3);
if (!isset($datos[0])) {
  message('index datos', 'Error, el alumno no existe', 'danger', $web);
}

$foto = $web->getAll(array('foto'), array('cveusuario' => $_SESSION['cveUser']), 'usuarios');
if (!isset($foto[0])) {
  $web->simple_message('warning', 'No se ha encontrado una foto');
}

$selected = null;
if ($datos[0]['cveespecialidad'] != 'O') {
  $selected = $datos[0]['cveespecialidad'];
}
$sql   = "SELECT cveespecialidad, nombre FROM especialidad WHERE cveespecialidad <> 'O'";
$combo = $web->combo($sql, $selected, '../');

$web->smarty->assign('foto', $foto[0]['foto']);
$web->smarty->assign('alumno', $datos[0]);
$web->smarty->assign('combo', $combo);
$web->smarty->assign('especialidad', $combo);
$web->smarty->display('datos.html');
/*******************************************************************************************************************
 * FUNCIONES
 *******************************************************************************************************************/
/**
 *
 */
function message($msg, $alert)
{
  global $web;
  $web->simple_message($alert, $msg);
  $web->smarty->display('datos.html');
  die();
}

/**
 * Muestra avisos generados por otras o esta misma página
 */
function showMessages()
{
  global $web;
  if (isset($_GET['a'])) {
    switch ($_GET['a']) {
      case 1:
        $web->simple_message('info', 'Se han guardado los cambios correctamente');
        break;
      case 2:
        $web->simple_message('warning', 'Ocurrió un error mientras se realizaban los cambios');
        break;
      case 3:
        $web->simple_message('info', 'El archivo es mayor a un MB');
        break;
      case 4:
        $web->simple_message('info', 'Solo esta permitido subir archivos de tipo JPG o PNG');
        break;
    }
  }
}

/**
 * Actualiza los datos de la persona
 */
function update()
{
  global $web;

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

  $datos = $web->getAll('*', array('cveusuario' => $cveusuario), 'usuarios');
  if (!isset($datos[0])) {
    message('El alumno no existe', 'danger');
  }

  $datosp = $datos;
  if (!$web->valida($_POST['datos']['correo'])) {
    message('Ingrese un correo valido', 'danger');
  }

  $correo_usuario = $web->getAll(array('correo'), array('cveusuario' => $cveusuario), 'usuarios');
  $correos        = $web->getAll(array('correo'), array('correo' => $correo), 'usuarios');
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

    $tmp = array(
      $_POST['datos']['nombre'],
      $_POST['datos']['correo'],
      md5($_POST['datos']['contrasenaN']),
      $cveusuario);
    if (!$web->updateUsuariosPass($tmp)) {
      $web->simple_message('danger', 'No es posible actualizar los datos del alumno');
      return;
    }

  } else {
    $tmp = array($_POST['datos']['nombre'], $_POST['datos']['correo'], $cveusuario);
    if (!$web->updateUsuarios($tmp)) {
      $web->simple_message('danger', 'No es posible actualizar los datos del alumno');
      return;
    }

    if (isset($_POST['datos']['especialidad'])) {
      ($_POST['datos']['especialidad'] == 'true') ?
      $web->updateEspUsuario($_POST['datos']['cveespecialidad'], null, $cveusuario) :
      $web->updateEspUsuario('O', $_POST['datos']['otro'], $cveusuario);

    } else {
      $cveespecialidad = $web->getAll(array('cveespecialidad'), array('cveusuario' => $cveusuario), 'especialidad_usuario');
      ($cveespecialidad[0]['cveespecialidad'] == 'O') ?
      $web->updateEspUsuario('O', $_POST['datos']['otro'], $cveusuario) :
      $web->updateEspUsuario($_POST['datos']['cveespecialidad'], null, $cveusuario);
    }
  }

  if (uploadImage()) {
    header("Location: datos.php?a=1");die();
  }
  header("Location: datos.php?a=2");die();
}

/**
 * Sube la imagen de perfil al servidor
 */
function uploadImage()
{
  global $web;

  if (empty($_FILES['foto']['type'])) {return true;}

  $dir_subida = $web->route_images . "fotos/";
  if ($_FILES['foto']['size'] > 1000000) {
    header('Location: datos.php?a=3');die();
  }

  $extension = explode("/", $_FILES['foto']['type'])[1];
  if ($extension != 'jpeg' && $extension != 'png') {
    header('Location: datos.php?a=4');die();
  }

  $nombre = $_SESSION['cveUser'] . "." . $extension;
  if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir_subida . $nombre)) {
    redimensionar($dir_subida, $nombre);

    if (!$web->query("UPDATE usuarios SET foto=? WHERE cveusuario=?", array($nombre, $_SESSION['cveUser']))) {
      unlink($dir_subida . $nombre); //eliminar foto
      return false;
    }
    return true;
  }
  return false;
}

/**
 *
 */
function redimensionar($direccion, $nombre)
{
  global $web;
  $obj_simpleimage = new SimpleImage(); //creamos un objeto de la clase SimpleImage
  $obj_simpleimage->load($direccion . $nombre); //leemos la imagen

  $var_nuevo_archivo = $nombre; //asignamos un nombre
  $obj_simpleimage->resize(127, 137);

  $obj_simpleimage->save($direccion . $var_nuevo_archivo); //guardamos los cambios efectuados en la imagen
}
