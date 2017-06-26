<?php
include 'sistema.php';

$contraseña = '';
$web         = new sistema;

$web->iniClases(null, "index registrar");
$sql = "SELECT * FROM especialidad where cveespecialidad != 'O' order by nombre";
$web->smarty->assign('especialidad', $web->combo($sql));
$web->smarty->assign('encabezado', '<h3>¡Bienvenido! <br> Por favor Ingrese datos. <br/></h3>');

if (isset($_POST['datos'])) {
  registerStudent($web);
}

$web->smarty->display('registrar.html');

/**
 * Ingresa un usuario en la BD
 * Se envía un correo y desde ahí se completa el registro
 * @param  Class $web Objeto para hacer uso de Smarty
 * @return boolean    En caso de algún error
 */
function registerStudent($web)
{

  if (!isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['usuario']) ||
    !isset($_POST['datos']['contrasena']) ||
    !isset($_POST['datos']['cveespecialidad']) ||
    !isset($_POST['datos']['correo']) ||
    !isset($_POST['datos']['confcontrasena'])) {
    $web->simple_message('danger', "No alteres la estructura de la interfaz");
    return false;
  }

  if ($_POST['datos']['nombre'] == "" ||
    $_POST['datos']['usuario'] == "" ||
    $_POST['datos']['cveespecialidad'] == "" ||
    $_POST['datos']['correo'] == "" ||
    $_POST['datos']['contrasena'] == "" ||
    $_POST['datos']['confcontrasena'] == "") {
    $web->simple_message('danger', "Llena todos los campos");
    return false;
  }

  $nombre          = $_POST['datos']['nombre'];
  $cveUsuario      = $_POST['datos']['usuario'];
  $contrasena      = $_POST['datos']['contrasena'];
  $confcontrasena  = $_POST['datos']['confcontrasena'];
  $cveespecialidad = $_POST['datos']['cveespecialidad'];
  $correo          = $_POST['datos']['correo'];

  if ($contrasena != $confcontrasena) {
    $web->simple_message('danger', "Las contraseñas no coinciden");
    return false;
  }

  $tamano = strlen($cveUsuario);
  if ($tamano != 8 || !is_numeric($cveUsuario)) {
    $web->simple_message('danger', "El número de control debe tener 8 caracteres numéricos");
    return false;
  }

  $sql      = "select cveusuario from usuarios where cveusuario=?";
  $datos_rs = $web->DB->GetAll($sql, $cveUsuario);
  if ($datos_rs != null) {
    $web->simple_message('danger', "El usuario ya existe");
    return false;
  }

  if (!$web->valida($correo)) {
    $web->simple_message('danger', "Ingrese un correo válido");
    return false;
  }

  $sql     = "select * from usuarios where correo=?";
  $correos = $web->DB->GetAll($sql, $correo);
  if (sizeof($correos) == 1) {
    $web->simple_message('danger', "El correo ya existe");
    return false;
  }

  $sql                 = "select nombre from especialidad where cveespecialidad = ?";
  $nombre_especialidad = $web->DB->GetAll($sql, $cveespecialidad);
  if (!isset($nombre_especialidad[0])) {
    $web->simple_message('danger', "La especialidad seleccionada no existe");
    return false;
  }

  //$web->DB->startTrans(); //por si falla algún query y no se realicen cambios

  //inserta en usuarios, usuario_rol y especialidad_usuario
  $sql = "insert into usuarios (cveusuario, nombre, pass, correo, estado_credito)
  values (?, ?, ?, ?, 'No Permitido')";
  $web->query($sql, array($cveUsuario, $nombre, md5($contrasena), $correo));
  $sql = "insert into usuario_rol(cveusuario, cverol) values(?, 3)";
  $web->query($sql, $cveUsuario);
  $sql = "insert into especialidad_usuario(cveusuario, cveespecialidad) values(?, ?)";
  $web->query($sql, array($cveUsuario, $cveespecialidad));

  // if ($web->DB->HasFailedTrans()) {
  //   //si falló algo entra al if
  //   $web->simple_message('danger', 'No se pudo completar la operación');
  //   $web->DB->CompleteTrans();
  //   return false;
  // }

  // $web->DB->CompleteTrans();

  //Inicia el proceso para enviar correos a los administradores
  $sql = "SELECT correo, nombre FROM usuarios
  WHERE cveusuario in (SELECT cveusuario FROM usuario_rol WHERE cverol=1)";
  $correos = $web->DB->GetAll($sql);
  if (!isset($correos[0])) {
    $web->simple_message('danger', 'No existe un administrador que apruebe tu registro');
    return false;
  }

  for ($i = 0; $i < sizeof($correos); $i++) {

    $mensaje = "Hola " . $correos[$i]['nombre'] . "\n Se solicita que apruebe un usuario para Salas Lectura.<br><br> Numero de control: " . $cveUsuario . "<br><br>Nombre del usuario: " . $nombre . "<br><br>Especialidad: " . $nombre_especialidad[0]['nombre'] . "<br><br>Correo del usuario: " . $correo . "<br><br>Por lo tanto, para realizar dicha accion de click en el siguiente enlace: " . " <a href='http://tigger.itc.mx/salasLectura/admin/validar.php?accion=aceptar&clave=" . $cveUsuario . "'>Aceptar</a>" . ".<br><br> De lo contrario, si usted Desea rechazar al usuario de click al siguiente enlace." . "<a href='http://tigger.itc.mx/salasLectura/admin/validar.php?accion=rechazar&clave=" . $cveUsuario . "'>Rechazar</a>" . "<br><br> ¡Gracias!";
    $web->sendEmail($correos[$i]['correo'], $correos[$i]['nombre'], "Aprobar registro", $mensaje);
  }

  header('Location: login.php?m=1');
}
