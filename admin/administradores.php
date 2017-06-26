<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('warning', 'No hay periodo actual');
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_insert':
      showFormInsert($web);
      break;

    case 'form_update':
      showFormUpdate($web);
      break;

    case 'insert':
      insertAdmin($web);
      break;

    case 'update':
      updateAdmin($web);
      break;

    case 'delete':
      deleteAdmin($web);
      break;
  }
}

$web->iniClases('admin', "index administradores");
$sql = "select usuarios.cveusuario, usuarios.nombre, especialidad.nombre, correo from usuarios
inner join especialidad_usuario on especialidad_usuario.cveusuario = usuarios.cveusuario
inner join especialidad on especialidad_usuario.cveespecialidad = especialidad.cveespecialidad
where usuarios.cveusuario in (select cveusuario from usuario_rol where cverol=1)
order by usuarios.cveusuario";
$web->DB->SetFetchMode(ADODB_FETCH_NUM); //cambio para crear JSON
$datos = $web->DB->GetAll($sql);

//Modificaciones para que muestre la especialidad o el contenido de 'Otro'
for ($i = 0; $i < sizeof($datos); $i++) {
  if ($datos[$i][2] == 'Otro') {
    $sql          = "select otro from especialidad_usuario where cveusuario=?";
    $otro         = $web->DB->GetAll($sql, $datos[$i][0]);
    $datos[$i][2] = $otro[0][0];
  }
}

$datos = array('data' => $datos);

for ($i = 0; $i < sizeof($datos['data']); $i++) {
  //eliminar
  $datos['data'][$i][4] = "administradores.php?accion=delete&info1=" . $datos['data'][$i][0];
  //editar
  $datos['data'][$i][5] = "<center><a href='administradores.php?accion=form_update&info1=" . $datos['data'][$i][0] . "'><img src='../Images/edit.png'></a></center>";
}

$web->DB->SetFetchMode(ADODB_FETCH_NUM); //cambio de nuevo
$datos = json_encode($datos);
$file  = fopen("TextFiles/administradores.txt", "w");
fwrite($file, $datos);

$web->smarty->assign('datos', $datos);
$web->smarty->display("administradores.html");

/**
 * Mostrar mensajes de error en casos específicos
 * @param  String $msg        Mensaje a mostrar
 * @param  String $ruta       Ruta de la página
 * @param  String $cveusuario Número de control
 * @param  Class  $web        Objeto para usar las herramientas smarty
 * @return Error desplegado en una plantilla
 */
function errores($msg, $ruta, $web, $cveusuario = null)
{
  $web->simple_message('danger', $msg);
  $web->iniClases('admin', $ruta);

  $sql = "select cveespecialidad, nombre from especialidad
  where cveespecialidad <> 'O'";
  $cmb_especialidad = $web->combo($sql, null, '../');
  $web->smarty->assign('cmb_especialidad', $cmb_especialidad);

  if ($cveusuario != null) {
    $sql = 'select u.cveusuario, u.nombre AS "nombreUsuario", e.nombre, eu.cveespecialidad,
    eu.otro, u.correo from usuarios u
    inner join especialidad_usuario eu on eu.cveusuario=u.cveusuario
    inner join especialidad e on e.cveespecialidad = eu.cveespecialidad
    where u.cveusuario=?';
    $datos = $web->DB->GetAll($sql, $cveusuario);
    $web->smarty->assign('administrador', $datos[0]);
  }

  $web->smarty->display('form_administradores.html');
  die();
}

function showFormInsert($web)
{
  $web->iniClases('admin', "index administradores nuevo");

  //<> Usado para no seleccionar la opción O, creo...
  $sql = "select cveespecialidad, nombre from especialidad
  where cveespecialidad <> 'O' order by nombre";
  $combo = $web->combo($sql, null, '../');

  $web->smarty->assign('cmb_especialidad', $combo);
  $web->smarty->display('form_administradores.html');
  die();
}

function showFormUpdate($web)
{
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', "No se ha especificado el administrador a modificar");
    return false;
  }

  $web->iniClases('admin', "index administradores actualizar");

  $sql = 'select u.cveusuario, u.nombre AS "nombreUsuario", e.nombre, eu.cveespecialidad,
  eu.otro, u.correo from usuarios u
  inner join especialidad_usuario eu on eu.cveusuario=u.cveusuario
  inner join especialidad e on e.cveespecialidad = eu.cveespecialidad
  where u.cveusuario=?';
  $datos = $web->DB->GetAll($sql, $_GET['info1']);
  // $web->debug($datos);
  if (!isset($datos[0])) {
    $web->simple_message('danger', "No existe el administrador seleccionado");
    return false;
  }

  $sql = "select cveespecialidad, nombre from especialidad
  where cveespecialidad <> 'O'
  order by nombre";
  $cmb_especialidad = $web->combo($sql, $datos[0]['cveespecialidad'], '../');

  $web->smarty->assign('cmb_especialidad', $cmb_especialidad);
  $web->smarty->assign('administrador', $datos[0]);
  $web->smarty->display('form_administradores.html');
  die();
}

function insertAdmin($web)
{
  if (!isset($_POST['datos']['usuario']) ||
    !isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['cveespecialidad']) ||
    !isset($_POST['datos']['otro']) ||
    !isset($_POST['datos']['correo']) ||
    !isset($_POST['datos']['contrasena']) ||
    !isset($_POST['datos']['confcontrasena'])) {
    errores('No altere la estructura de la interfaz', 'index administradores nuevo', $web);
  }

  if (($_POST['datos']['usuario']) == '' ||
    ($_POST['datos']['nombre']) == '' ||
    ($_POST['datos']['correo']) == '' ||
    ($_POST['datos']['contrasena']) == '' ||
    ($_POST['datos']['confcontrasena'] == '')) {
    errores('Llene todos los campos', 'index administradores nuevo', $web);
  }

  if (strlen($_POST['datos']['usuario']) != 13) {
    errores('La longitud del RFC debe de ser de 13 caracteres', 'index administradores nuevo', $web);
  }

  if (!$web->valida($_POST['datos']['correo'])) {
    errores('Ingrese un correo valido', 'index administradores nuevo', $web);
  }

  if ($_POST['datos']['contrasena'] != $_POST['datos']['confcontrasena']) {
    errores('Las contraseñas no coinciden', 'index administradores nuevo', $web);
  }

  $sql   = "select * from usuarios where correo=?";
  $datos = $web->DB->GetAll($sql, $_POST['datos']['correo']);
  if (isset($datos[0])) {
    errores('El correo ingresado ya está registrado', 'index administradores nuevo', $web);
  }

  $sql   = "select * from usuarios where cveusuario=?";
  $datos = $web->DB->GetAll($sql, $_POST['datos']['usuario']);
  if (isset($datos[0])) {
    errores('El RFC ingresado ya está registrado', 'index administradores nuevo', $web);
  }

  $usuario    = $_POST['datos']['usuario'];
  $contrasena = $_POST['datos']['contrasena'];
  $nombre     = $_POST['datos']['nombre'];
  $correo     = $_POST['datos']['correo'];

  $web->DB->startTrans(); //inicia transacción
  $sql = "INSERT INTO usuarios(cveusuario, nombre, pass, correo, validacion)
  values (?, ?, ?, ?, 'Aceptado')";
  $tmp = array($usuario, $nombre, md5($contrasena), $correo);
  $web->query($sql, $tmp);

  $sql = "INSERT INTO usuario_rol values(?, 1)";
  $web->query($sql, $usuario);

  if (isset($_POST['datos']['especialidad'])) {

    if ($_POST['datos']['especialidad'] == 'true') {
      $sql = "INSERT INTO especialidad_usuario (cveusuario, cveespecialidad) values(?, ?)";
      $web->query($sql, array($usuario, $_POST['datos']['cveespecialidad']));
    } else {
      $sql = "INSERT INTO especialidad_usuario(cveusuario, cveespecialidad, otro) values(?, 'O', ?)";
      $web->query($sql, array($usuario, $_POST['datos']['otro']));
    }
  } else {
    $sql = "INSERT INTO especialidad_usuario (cveusuario, cveespecialidad) values(?, ?)";
    $web->query($sql, array($usuario, $_POST['datos']['cveespecialidad']));
  }

  if ($web->DB->HasFailedTrans()) {
    //verifica errores durante la transacción
    //falta programar esta parte para que no muestre directamente el resultado de sql
    $web->simple_message('danger', 'No fue posible completar el registro');
    $web->DB->CompleteTrans(); //termina la transacción haya sido exitosa o no
    return false;
  }

  $web->DB->CompleteTrans(); //termina la transacción haya sido exitosa o no
  header('Location: administradores.php');
}

function updateAdmin($web)
{
  global $cveperiodo;

  // $web->debug($_POST);

  if (!isset($_POST['datos']['usuario']) ||
    !isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['cveespecialidad']) ||
    !isset($_POST['datos']['otro']) ||
    !isset($_POST['datos']['correo']) ||
    !isset($_POST['datos']['pass']) ||
    !isset($_POST['datos']['contrasena']) ||
    !isset($_POST['datos']['contrasenaN']) ||
    !isset($_POST['datos']['confcontrasenaN'])) {
    errores('No altere la estructura de la interfaz', 'index administrador actualizar', $web, $cveusuario);
  }

  if ($_POST['datos']['usuario'] == '' ||
    $_POST['datos']['nombre'] == '' ||
    $_POST['datos']['cveespecialidad'] == '' ||
    $_POST['datos']['correo'] == '' ||
    $_POST['datos']['pass'] == '') {
    errores('Llene todos los campos', 'index administradores actualizar', $web, $cveusuario);
  }

  $cveusuario = $_POST['datos']['usuario'];

  $sql   = "select * from usuarios where cveusuario=?";
  $datos = $web->DB->GetAll($sql, $cveusuario);
  if (!isset($datos[0])) {
    errores('El administrador a actualizar no está registrado', 'index administradores actualizar', $web, $cveusuario);
  }

  if (!$web->valida($_POST['datos']['correo'])) {
    errores('Ingrese un correo valido', 'index administradores actualizar', $web, $cveusuario);
  }

  $sql            = "select correo from usuarios where cveusuario=?";
  $correo_usuario = $web->DB->GetAll($sql, $cveUsuario);

  $sql     = "select correo from usuarios where correo=?";
  $correos = $web->DB->GetAll($sql, $correo);
  if (sizeof($correos) == 1) {
    if ($correo_usuario[0]['correo'] != $correos[0]['correo']) {
      errores('El correo ingresado ya está registrado', 'index administradores actualizar', $cveusuario, $web);
    }
  }

  $sql = "update usuarios set nombre=?, correo=?";
  $tmp = array($_POST['datos']['nombre'], $_POST['datos']['correo'], $cveusuario);

  //actualizar contraseña
  if ($_POST['datos']['pass'] == 'true') {

    //si la contraseña no concuerda con la que está en la BD
    if ($datos[0]['pass'] != md5($_POST['datos']['contrasena'])) {
      errores('La contraseña ingresada es incorrecta', 'index administradores actualizar', $web, $cveusuario);
    }

    //la nueva contraseña y su confirmación no concuerdan
    if ($_POST['datos']['confcontrasenaN'] != $_POST['datos']['contrasenaN']) {
      errores('La contraseña nueva debe coincidir con la confirmación', 'index administradores actualizar', $web, $cveusuario);

      $sql .= ", pass=?";
      $tmp = array(
        $_POST['datos']['nombre'],
        $_POST['datos']['correo'],
        md5($_POST['datos']['contrasenaN']),
        $cveusuario);
    }
  }

  //llegado a este punto, $sql ya tiene todos los parámetros a modificar de la tabla usuarios
  $sql .= " where cveusuario=?";
  $web->DB->startTrans();
  $web->query($sql, $tmp);

  //modificaciones sobre la tabla especialidad_usuario
  if (isset($_POST['datos']['especialidad'])) {

    if ($_POST['datos']['especialidad'] == 'true') {
      $sql = "update especialidad_usuario set cveespecialidad=?, otro=null
        where cveusuario=? ";
      $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));

    } else {
      //no se llenó el campo otro
      if ($_POST['datos']['otro'] == '') {
        $web->DB->CompleteTrans(); //termina transición porque la función errores manda a otra página
        errores('Llene el campo correspondiente a "Otro"', 'index administradores actualizar', $web, $cveusuario);
      }

      $sql = "update especialidad_usuario set cveespecialidad='O', otro=?
      where cveusuario=? ";
      $web->query($sql, array($_POST['datos']['otro'], $cveusuario));
    }

  } else {

    $sql             = "select cveespecialidad from especialidad_usuario where cveusuario=?";
    $cveespecialidad = $web->DB->GetAll($sql, $cveusuario);

    if ($cveespecialidad[0]['cveespecialidad'] == 'O') {
      $sql = "update especialidad_usuario set cveespecialidad='O', otro=? where cveusuario=? ";
      $web->query($sql, array($_POST['datos']['otro'], $cveusuario));

    } else {
      $sql = "update especialidad_usuario set cveespecialidad=?, otro =null where cveusuario=? ";
      $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));
    }

  }

  if ($web->DB->HasFailedTrans()) {
    //falta programar esta parte para que no muestre directamente el resultado de sql
    $web->simple_message('danger', 'No fue posible completar la operación');
    $web->DB->CompleteTrans();
    return false;
  }

  $web->DB->CompleteTrans();
  header('Location: administradores.php');
}

/**
 * Eliminar un administrador
 * @param  Class $web Objeto para poder hacer uso de smarty
 */
function deleteAdmin($web)
{
  //se valida la contraseña
  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;
      break;
    case 2:
      $web->simple_message('danger', 'La contraseña de seguridad ingresada no es válida');
      return false;
      break;
  }

  //verifica que se reciben los datos necesarios
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', "No se especificó el administrador a eliminar");
    return false;
  }

  //verifica que el administrador exista
  $sql   = "select * from usuarios where cveusuario=?";
  $datos = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($datos[0])) {
    $web->simple_message('danger', "El administrador no existe");
    return false;
  }

  $web->DB->startTrans();
  $sql = "DELETE FROM especialidad_usuario WHERE cveusuario=?";
  $web->query($sql, $_GET['info1']);
  $sql = "DELETE FROM usuario_rol WHERE cveusuario=?";
  $web->query($sql, $_GET['info1']);
  $sql = "DELETE FROM usuarios WHERE cveusuario=?";
  $web->query($sql, $_GET['info1']);

  if ($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No fue posible completar la operación');
    $web->DB->CompleteTrans();
    return false;
  }

  $web->DB->CompleteTrans();
  header('Location: administradores.php');
}
