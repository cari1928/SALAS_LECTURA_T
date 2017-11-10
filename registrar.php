<?php
include 'sistema.php';

$web = new RegistrarControllers;
$contraseña = '';
$web->iniClases(null, "index registrar");

$sql = "SELECT * FROM especialidad where cveespecialidad != 'O' order by nombre";
$web->smarty->assign('especialidad', $web->combo($sql));
$web->smarty->assign('encabezado', '<h3>¡Bienvenido! <br> Por favor Ingrese datos. <br/></h3>');

if (isset($_POST['datos'])) {
  registerStudent();
}

$web->smarty->display('registrar.html');

/************************************************************************************
 * FUNCIONES
 ************************************************************************************/
/**
 * Ingresa un usuario en la BD
 * Se envía un correo y desde ahí se completa el registro
 * @param  Class $web Objeto para hacer uso de Smarty
 * @return boolean    En caso de algún error
 */
function registerStudent()
{
  global $web;
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

  $nombre         = $_POST['datos']['nombre'];
  $cveUsuario     = $_POST['datos']['usuario'];
  $contrasena     = $_POST['datos']['contrasena'];
  $confcontrasena = $_POST['datos']['confcontrasena'];
  $cveespecialidad   = $_POST['datos']['cveespecialidad'];
  $correo         = $_POST['datos']['correo'];

  if ($contrasena != $confcontrasena) {
    $web->simple_message('danger', "Las contraseñas no coinciden");
    return false;
  }

  $tamano = strlen($cveUsuario);
  if ($tamano != 8 || !is_numeric($cveUsuario)) {
    $web->simple_message('danger', "El número de control debe tener 8 caracteres numéricos");
    return false;
  }
  
  $datos_rs = $web->getAll(array('cveusuario'), array('cveusuario'=>$cveUsuario), 'usuarios');
  if ($datos_rs != null) {
    $web->simple_message('danger', "El usuario ya existe");
    return false;
  }
  if (!$web->valida($correo)) {
    $web->simple_message('danger', "ERR0012, Ingrese un correo válido");
    return false;
  }

  $correos = $web->getAll('*', array('correo'=>$correo), 'usuarios');
  if (sizeof($correos) == 1) {
    $web->simple_message('danger', "ERR0013, El correo ya existe");
    return false;
  }
  
  $nombre_especialidad = $web->getAll(array('nombre'), array('cveespecialidad'=>$cveespecialidad), 'especialidad');
  if (!isset($nombre_especialidad[0])) {
    $web->simple_message('danger', "ERR0014, La especialidad seleccionada no existe");
    return false;
  }
  
  //inserta en usuarios, usuario_rol y especialidad_usuario
  $tmpUsuario = array(
    'cveusuario'=>$cveUsuario, 
    'nombre'=>$nombre, 
    'pass'=>md5($contrasena), 
    'correo'=>$correo,
    'estado_credito'=>'No Permitido');
  $tmpUserRol = array('cveusuario'=>$cveUsuario, 'cverol'=>3);
  $tmpEspUser = array('cveusuario'=>$cveUsuario, 'cveespecialidad'=> $cveespecialidad);
  $resInsert = $web->insertUser($tmpUsuario, $tmpUserRol, $tmpEspUser);
  if($resInsert < 3) {
    // $web->debug($resInsert, false); //para resolución de problemas
    $web->simple_message('danger', "ERR0015, No se ha podido registrar al usuario, contacte con el administrador.");
    return false;
  }
  
  
  //Inicia el proceso para enviar correos a los administradores
  $correos = $web->getCorreos();
  if (!isset($correos[0])) {
    $web->simple_message('danger', 'ERR0016, No existe un administrador que apruebe tu registro');
    return false;
  }

  for ($i = 0; $i < sizeof($correos); $i++) {
    $mensaje = "Hola " . $correos[$i]['nombre'] . "<br><br>Se solicita que apruebe un usuario para Salas Lectura.<br><br> No. Control: " . $cveUsuario . "<br><br>Nombre del usuario: " . $nombre . "<br><br>Especialidad: " . $nombre_especialidad[0]['nombre'] . "<br><br>Correo del usuario: " . $correo . "<br><br>Por lo tanto, para realizar dicha accion de clic en el siguiente enlace: " . " <a href='https://salas-lectura-cari1928.c9users.io/admin/validar.php?accion=aceptar&clave=" . $cveUsuario . "'>Aceptar</a>" . ".<br><br> De lo contrario, si usted desea rechazar al usuario de clic al siguiente enlace: " . "<a href='https://salas-lectura-cari1928.c9users.io/admin/validar.php?accion=rechazar&clave=" . $cveUsuario . "'>Rechazar</a>" . "<br><br> Gracias.";

    if(!$web->sendEmail($correos[$i]['correo'], $correos[$i]['nombre'], "Aprobar registro", $mensaje)) {
      break;
    }
  }

  header('Location: login.php?m=1');
}
