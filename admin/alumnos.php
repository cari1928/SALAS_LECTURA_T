<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('warning', "No hay periodo actual");
}

$flag = false; //para cuando viene de historial
if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_insert':
      $web->iniClases('admin', "index alumnos nuevo");

      $sql   = "select cveespecialidad, nombre from especialidad
      where cveespecialidad <> 'O'
      order by nombre";
      $combo = $web->combo($sql, null, '../');

      $web->smarty->assign('cmb_especialidad', $combo);
      $web->smarty->display('form_alumnos.html');
      die();
      break;

    case 'form_update':
      form_update_student($web);
      break;

    case 'insert':
      insert_student($web);
      break;

    case 'update':
      update_student($web);
      break;

    case 'delete':
      delete_student($web);
      break;

    case 'show':
      show_groups($web);
      break;

    case 'historial':
      $flag = 'historial';
      break;
  }
}

$web->iniClases('admin', "index alumnos");

$sql = "select usuarios.cveusuario, usuarios.nombre AS \"usuario\", especialidad.nombre AS
\"especialidad\", correo, usuarios.estado_credito from usuarios
inner join especialidad_usuario on especialidad_usuario.cveusuario = usuarios.cveusuario
inner join especialidad on especialidad_usuario.cveespecialidad = especialidad.cveespecialidad
where usuarios.cveusuario in (select cveusuario from usuario_rol where cverol=3)";
$parameters = array();

//Se realiza la consulta para obtener el estado de los libros de cada alumno
$sql_libros = 'select lectura.nocontrol, e.estado from lista_libros
inner join estado e on e.cveestado = lista_libros.cveestado
inner join lectura on lectura.cvelectura = lista_libros.cvelectura
where lectura.cveperiodo = ?';
$parameters_b = $cveperiodo;

//si viene de historial
if ($flag == 'historial') {
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No es posible continuar, hacen falta datos');
  } else {
    $sql .= " and usuarios.cveusuario in (select nocontrol from lectura where cveperiodo=?)";
    $parameters   = $cveperiodo   = $_GET['info1'];
    $parameters_b = $parameters;
    $web->iniClases('admin', "index historial alumnos");
    $web->smarty->assign('bandera', 'historial');
    $web->smarty->assign('cveperiodo', $_GET['info1']);
  }
}

$sql_libros .= " order by lectura.nocontrol, e.estado";
$sql .= " order by usuarios.cveusuario";
$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$datos = $web->DB->GetAll($sql, $parameters);

if (!isset($datos[0])) {
  $web->simple_message('warning', 'No hay alumnos registrados');
}

$datos_libros = $web->DB->GetAll($sql_libros, $parameters_b);
$datos        = array('data' => $datos);

//se preparan los campos extra (estado_credito, eliminar, actualizar y mostrar)
for ($i = 0; $i < sizeof($datos['data']); $i++) {

  //estado-crédito
  if ($datos['data'][$i][4] == null) {
    $datos['data'][$i][4] = "<label display='color:red'> NO PERMITIDO </label>";
    // $cont=0;
    // $bandera_libros = true;
    // for($h = 0; $h < sizeof($datos_libros) && $bandera_libros == true; $h++){
    //   if($datos_libros[$h][0] == $datos['data'][$i][0] ){
    //     if($datos_libros[$h][1] == 'Terminado'){
    //       $cont++;
    //     }
    //     else{
    //       $bandera_libros = false;
    //     }
    //   }
    // }
    // if($bandera_libros == false){
    //   $datos['data'][$i][4] = "<label display='color:red'> NO PERMITIDO </label>";
    // }
    // else{
    //   if($cont >= 5){
    //     if($flag == 'historial'){
    //       $datos['data'][$i][4] = "<a href='credito_pdf.php?info2=" . $datos['data'][$i][0] . "&info3=". $parameters . "'><label display='color:red'> PERMITIDO </label></a>";
    //     }
    //     else{
    //       $datos['data'][$i][4] = "<a href='credito_pdf.php?info2=" . $datos['data'][$i][0] . "'><label display='color:red'> PERMITIDO </label></a>";
    //     }
    //   }
    //   else{
    //     $datos['data'][$i][4] = "<label display='color:red'> NO PERMITIDO </label>";
    //   }
    // }
  }

  //$datos['data'][$i][4] = "FALTA PROGRAMAR!!!";

  if ($flag != 'historial') {
    //se preparan parametros

    //eliminar
    $datos['data'][$i][5] = "alumnos.php?accion=delete&info1=" . $datos['data'][$i][0];
    //editar
    $datos['data'][$i][6] = "<center><a href='alumnos.php?accion=form_update&info2=" . $datos['data'][$i][0] . "'><img src='../Images/edit.png'></a></center>";
    //mostrar_grupos
    $datos['data'][$i][7] = "<center><a href='alumnos.php?accion=show&info1=" . $datos['data'][$i][0] . "'><img src='../Images/mostrar.png'></a></center>";

  } else {
    //reporte
    $datos['data'][$i][5] = "<center><a href='reporte_pdf.php?accion=alumno&info1=1&info2=" . $cveperiodo . "&info3=" . $datos['data'][$i][0] . "'><img src='../Images/pdf.png'></a></center>";
    //mostrar_grupos
    $datos['data'][$i][6] = "<center><a href='alumnos.php?accion=show&info1=" . $datos['data'][$i][0] . "&info2=" . $cveperiodo . "'><img src='../Images/mostrar.png'></a></center>";
  }
}

$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$datos = json_encode($datos);

$file = fopen("TextFiles/alumnos.txt", "w");
fwrite($file, $datos);

$web->smarty->assign('datos', $datos);
$web->smarty->display("alumnos.html");

/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases Ruta a mostrar en links
 * @param  String $msg       Mensaje a desplegar
 * @param  $web              Para poder aplicar las funciones de $web
 * @param  String $cveusuario   Usado en caso de que se trate de un formulario de actualización
 */
function message($iniClases, $msg, $web, $cveusuario = null)
{
  $web->iniClases('admin', $iniClases);

  $sql   = "select cveespecialidad, nombre from especialidad where cveespecialidad <> 'O'";
  $combo = $web->combo($sql, null, '../');

  $web->smarty->assign('alert', 'danger');
  $web->smarty->assign('msg', $msg);
  $web->smarty->assign('cmb_especialidad', $combo);

  if ($cveusuario != null) {
    $sql    = "select * from usuarios where cveusuario=?";
    $alumno = $web->DB->GetAll($sql, $cveusuario);

    $web->smarty->assign('alumno', $alumno[0]);
  }

  $web->smarty->display('form_alumnos.html');
  die();
}

/**
 * Ahorro de código, elimina un alumno de usuarios junto con todas las tablas relacionadas
 * @param  Class    $web Objeto para poder usar smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function delete_student($web)
{
  //se valida la contraseña
  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;

    case 2:
      $web->simple_message('danger', 'La contraseña de seguridad ingresada no es válida');
      return false;
  }

  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No altere la estructura de la interfaz, no se especificó el alumno');
    return false;
  }

  //verifica que el alumno exista
  $sql    = "select * from usuarios where cveusuario=?";
  $alumno = $web->DB->GetAll($sql, $_GET['info1']);
  if (sizeof($alumno) == 0) {
    $web->simple_message('danger', 'No existe el alumno');
    return false;
  }

  //obtiene la cvelectura
  $sql     = "select * from lectura where nocontrol=?";
  $lectura = $web->DB->GetAll($sql, $_GET['info1']);

  for ($i = 0; $i < sizeof($lectura); $i++) {
    //elimina evaluacion, lista_libros y lectura con cvelectura
    $sql = "delete from evaluacion where cvelectura=?";
    $web->query($sql, $lectura[$i]['cvelectura']);
    $sql = "delete from lista_libros where cvelectura=?";
    $web->query($sql, $lectura[$i]['cvelectura']);
    $sql = "delete from lectura where cvelectura=?";
    $web->query($sql, $lectura[$i]['cvelectura']);
  }

  //elimina los mensajes
  $sql = "delete from msj where emisor=? or receptor=?";
  $web->query($sql, array($_GET['info1'], $_GET['info1']));

  //elimina la especialidad
  $sql = "delete from especialidad_usuario where cveusuario=?";
  $web->query($sql, $_GET['info1']);

  //elimina los roles
  $sql = "delete from usuario_rol where cveusuario=?";
  $web->query($sql, $_GET['info1']);

  //elimina al usuario
  $sql = "delete from usuarios where cveusuario=?";
  if (!$web->query($sql, $_GET['info1'])) {
    $web->simple_message('danger', 'No se pudo completar la operación');
    return false;
  }

  header('Location: alumnos.php');
}

/**
 * Ingresa los datos en las tablas: usuarios, usuario_rol y especialidad_usuario
 * @param  Class   $web Objeto para poder hacer uso de smarty
 * @return boolean false = Mostrar mensaje de error
 */
function insert_student($web)
{
  //verifica existencia de todos los campos
  if (!isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['usuario']) ||
    !isset($_POST['datos']['contrasena']) ||
    !isset($_POST['datos']['cveespecialidad']) ||
    !isset($_POST['datos']['correo']) ||
    !isset($_POST['datos']['confcontrasena'])) {
    message("index alumnos nuevo", "No alteres la estructura de la interfaz", $web, $tmp);
  }

  //verifica que los campos contengan algo
  if ($_POST['datos']['nombre'] == "" ||
    $_POST['datos']['usuario'] == "" ||
    $_POST['datos']['cveespecialidad'] == "" ||
    // $_POST['datos']['correo'] == "" ||
    $_POST['datos']['contrasena'] == "" ||
    $_POST['datos']['confcontrasena'] == "") {
    message("index alumnos nuevo", "Llena todos los campos", $web, $tmp);
  }

  //ahora que se pasaron las pruebas anteriores, se obtienen los datos de los campos
  $nombre         = $_POST['datos']['nombre'];
  $cveUsuario     = $_POST['datos']['usuario'];
  $contrasena     = $_POST['datos']['contrasena'];
  $confcontrasena = $_POST['datos']['confcontrasena'];
  $especialidad   = $_POST['datos']['cveespecialidad'];
  $correo         = $_POST['datos']['correo'];

  if ($contrasena != $confcontrasena) {
    message("index alumnos nuevo", "Las contraseñas no coinciden", $web);
  }

  $tamano = strlen($cveUsuario);
  if ($tamano != 8 || !is_numeric($cveUsuario)) {
    message("index alumnos nuevo", "El número de control debe tener 8 caracteres numéricos", $web);
  }

  $sql      = "select cveusuario from usuarios where cveusuario=?";
  $datos_rs = $web->DB->GetAll($sql, $cveUsuario);
  if ($datos_rs != null) {
    message("index alumnos nuevo", "El usuario ya existe", $web);
  }

  if (!$web->valida($correo)) {
    message("index alumnos nuevo", "Ingrese un correo válido", $web);
  }

  $sql     = "select * from usuarios where correo=?";
  $correos = $web->DB->GetAll($sql, $correo);
  if (sizeof($correos) == 1) {
    message("index alumnos nuevo", "El correo ya existe", $web);
  }

  $web->DB->startTrans();

  //insertar en usuarios, usuario_rol y especialidad_usuario
  $query = "insert into usuarios(cveusuario, nombre, pass, correo, validacion)
  values(?, ?, ?, ?, 'Aceptado')";
  $tmp = array($cveUsuario, $nombre, md5($contrasena), $correo);
  $web->query($query, $tmp);
  $sql = "insert into usuario_rol(cveusuario, cverol) values(?, ?)";
  $web->query($sql, array($cveUsuario, 3));
  $sql = "insert into especialidad_usuario(cveusuario, cveespecialidad) values(?, ?)";
  $web->query($sql, array($cveUsuario, $especialidad));

  if ($web->DB->HasFailedTrans()) {
    message("index alumnos nuevo", "No fue posible realizar la operación", $web);
    $web->DB->CompleteTrans();
    return false;
  }

  $web->DB->CompleteTrans();
  header('Location: alumnos.php');
}

/**
 * Actualiza los datos de un alumno: especialidad_usuario y usuarios
 * @param  Class   $web  Objeto para poder hacer uso de smarty
 * @return boolean false = Mostrar mensajes de error
 */
function update_student($web)
{
  if (!isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['usuario']) ||
    !isset($_POST['datos']['contrasena']) ||
    !isset($_POST['datos']['cveespecialidad']) ||
    !isset($_POST['datos']['correo']) ||
    !isset($_POST['datos']['contrasenaN']) ||
    !isset($_POST['datos']['confcontrasenaN'])) {
    message("index alumnos actualizar", "No alteres la estructura de la interfaz", $web, $_POST['datos']['usuario']);
  }

  if ($_POST['datos']['nombre'] == "" ||
    $_POST['datos']['usuario'] == "" ||
    $_POST['datos']['cveespecialidad'] == "" ||
    $_POST['datos']['correo'] == "") {
    message("index alumnos actualizar", "Llena todos los campos", $web, $_POST['datos']['usuario']);
  }

  $nombre          = $_POST['datos']['nombre'];
  $cveUsuario      = $_POST['datos']['usuario'];
  $contrasena      = $_POST['datos']['contrasena'];
  $contrasenaN     = $_POST['datos']['contrasenaN'];
  $confcontrasenaN = $_POST['datos']['confcontrasenaN'];
  $especialidad    = $_POST['datos']['cveespecialidad'];
  $correo          = $_POST['datos']['correo'];

  if (!$web->valida($correo)) {
    message("index alumnos actualizar", "Ingrese un correo válido", $web, $cveUsuario);
  }

  $sql            = "select correo from usuarios where cveusuario=?";
  $correo_usuario = $web->DB->GetAll($sql, $cveUsuario);

  $sql     = "select correo from usuarios where correo=?";
  $correos = $web->DB->GetAll($sql, $correo);
  if (sizeof($correos) == 1) {
    if ($correo_usuario[0]['correo'] != $correos[0]['correo']) {
      message("index alumnos actualizar", "El correo ya existe", $web, $cveUsuario);
    }
  }

  $query = "update usuarios set nombre=?, correo=?";

  //se activó el radio button
  if ($_POST['datos']['pass'] == 'true') {

    if ($_POST['datos']['contrasena'] == "" ||
      $_POST['datos']['contrasenaN'] == "" ||
      $_POST['datos']['confcontrasenaN'] == "") {
      message("index alumnos actualizar", "Ingrese los datos solicitados para el cambio de contraseña", $web, $cveUsuario);
    }

    $contrasena = md5($contrasena);
    $sql        = "select pass from usuarios where cveusuario=?";
    $datos_rs   = $web->DB->GetAll($sql, $cveUsuario);
    if ($datos_rs[0]['pass'] != $contrasena) {
      message("index alumnos actualizar", "La contraseña ingresada no es válida", $web, $cveUsuario);
    }

    if ($contrasenaN != $confcontrasenaN) {
      message("index alumnos actualizar", "Las contraseñas no coinciden", $web, $cveUsuario);
    }

    $query .= ", pass=? where cveusuario=?";
    $web->query($query, array($nombre, $correo, md5($contrasenaN), $cveUsuario));
  } else {
    //No se activó el radio button
    $query .= " where cveusuario=?";
    $web->query($query, array($nombre, $correo, $cveUsuario));
  }

  $query = "update especialidad_usuario set cveespecialidad=? where cveusuario=?";
  $web->query($query, array($especialidad, $cveUsuario));

  header('Location: alumnos.php');
}

/**
 * Asigna los elementos necesarios para desplegar la plantilla de form_alumnos para
 * actualizar
 * @param  Class   $web  Objeto para poder hacer uso de smarty
 * @return boolean false = Mostrar mensajes de error
 */
function form_update_student($web)
{
  if (!isset($_GET['info2'])) {
    $web->simple_message('danger', 'No se especificó el alumno');
    return false;
  }

  $sql = "select * from usuarios
  inner join especialidad_usuario on especialidad_usuario.cveusuario = usuarios.cveusuario
  where usuarios.cveusuario=?";
  $alumno = $web->DB->GetAll($sql, $_GET['info2']);
  if (sizeof($alumno) == 0) {
    $web->simple_message('danger', 'No existe el alumno');
    return false;
  }

  $sql   = "select * from especialidad
  where cveespecialidad <> 'O'
  order by nombre";
  $combo = $web->combo($sql, $alumno[0]['cveespecialidad']);

  $web->iniClases('admin', "index alumnos actualizar");
  $web->smarty->assign('cmb_especialidad', $combo);
  $web->smarty->assign('alumno', $alumno[0]);
  $web->smarty->display('form_alumnos.html');
  die();
}

/**
 * Muestra los grupos del alumno seleccionado
 * Contenido de $_GET:
 * accion: show
 * info1:  cvealumno
 * @param  Class   $web  Objeto para poder hacer uso de smarty
 * @return boolean false = Mostrar mensajes de error
 */
function show_groups($web)
{
  global $cveperiodo;

  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No altere la estructura de la interfaz, no se especificó el alumno');
    return false;
  }

  $sql    = "select * from usuarios where cveusuario=?";
  $alumno = $web->DB->GetAll($sql, $_GET['info1']);
  if (sizeof($alumno) == 0) {
    $web->simple_message('danger', 'No existe el alumno');
    return false;
  }

  $sql = "select distinct laboral.cveperiodo, letra, nombre, ubicacion, nocontrol, titulo from laboral
  inner join abecedario on laboral.cveletra = abecedario.cve
  inner join lectura on lectura.cveletra = abecedario.cve
  inner join sala on laboral.cvesala = sala.cvesala
  inner join libro on libro.cvelibro = laboral.cvelibro_grupal
  where nocontrol = ? and laboral.cveperiodo = ? and lectura.cveperiodo = ? order by letra";
  $tablegrupos = $web->DB->GetAll($sql, array($_GET['info1'], $cveperiodo, $cveperiodo));

  if (!isset($tablegrupos[0])) {
    $web->simple_message('danger', 'No ha registrado algún grupo');
    return false;
  }

  $sql = "select dia.cvedia, abecedario.letra, dia.nombre, horas.hora_inicial,
  horas.hora_final from laboral
  inner join dia on dia.cvedia=laboral.cvedia
  inner join abecedario on laboral.cveletra = abecedario.cve
  inner join horas on horas.cvehoras=laboral.cvehoras
  inner join lectura on lectura.cveletra = abecedario.cve
  where lectura.nocontrol=? and laboral.cveperiodo=? order by letra, dia.cvedia, horas.hora_inicial";
  $horas = $web->DB->GetAll($sql, array($_GET['info1'], $cveperiodo));

  for ($i = 0; $i < sizeof($tablegrupos); $i++) {
    $tablegrupos[$i]['horario'] = "";
    for ($j = 0; $j < sizeof($horas); $j++) {
      if ($tablegrupos[$i]['letra'] == $horas[$j]['letra']) {
        $tablegrupos[$i]['horario'] .= $horas[$j]['nombre'] . ' - ' . $horas[$j]['hora_inicial'] . ' a ' . $horas[$j]['hora_final'] . "<br>";
      }
    }
  }

  $web->iniClases('admin', "index alumnos grupos");
  $web->smarty->assign('bandera', 'alumnos');

  //viene de historial
  if (isset($_GET['info2'])) {
    $web->iniClases('admin', "index historial grupos");
    $web->smarty->assign('cveusuario', $_GET['info1']);
    $web->smarty->assign('cveperiodo', $_GET['info2']);
    $web->smarty->assign('bandera', 'historial');
  }

  $web->smarty->assign('tablegrupos', $tablegrupos);
  
  $web->smarty->display('grupos.html');
  die();
}
