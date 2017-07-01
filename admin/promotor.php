<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

//por si viene de historial
$flag = false;

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_insert':
      show_form_insert($web);
      break;

    case 'form_update':
      show_form_update($web);
      break;

    case 'insert':
      insert_professor($web);
      break;

    case 'update':
      update_professor($web);
      break;

    case 'delete':
      delete_professor($web);
      break;

    case 'mostrar':
      show_professor_groups($web);
      break;

    case 'historial':
      $flag = show_history($web);
      break;
  }
}

//viene de historial
if ($flag) {
  $promotores = $flag;

} else {
  //no viene de historial
  $sql = "SELECT u.cveusuario, u.nombre, u.correo, otro AS \"Otro\", e.nombre AS \"Especialidad\",
  eu.cveespecialidad FROM usuarios u
  INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
  INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
  WHERE u.cveusuario in (SELECT cveusuario FROM usuario_rol WHERE cverol=2)
  ORDER BY u.cveusuario";
  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $promotores = $web->DB->GetAll($sql);
}

$web->iniClases('admin', "index promotor");

if (!isset($promotores[0])) {
  $web->simple_message('warning', 'No hay promotores registrados');
}

arreglaEspecialidad($web);
$datos = array('data' => $promotores);

//contenido de las colummnas
for ($i = 0; $i < sizeof($datos['data']); $i++) {

  if (!isset($flag[0])) {

    //editar
    $datos['data'][$i][5] = "<center><a href='promotor.php?accion=form_update&info1=" . $datos['data'][$i][0] . "'><img src='../Images/edit.png'></a></center>";
    //mostrar_grupos
    $datos['data'][$i][6] = "<center><a href='promotor.php?accion=mostrar&info1=" . $datos['data'][$i][0] . "'><img src='../Images/mostrar.png'></a></center>";

  } else {
    $web->smarty->assign('bandera', true);

    //mostrar grupos
    $datos['data'][$i][4] = "<center><a href='promotor.php?accion=mostrar&info1=" . $datos['data'][$i][0] . "&info2=" . $_GET['info1'] . "'><img src='../Images/mostrar.png'></a></center>";
    //reporte pdf - listado de alumnos
    $datos['data'][$i][5] = "<center><a href='reporte.php?accion=promotor_alumnos&info1=1&info2=" . $_GET['info1'] . "&info3=" . $datos['data'][$i][0] . "' target='_blank'><img src='../Images/pdf.png'></a></center>";
    //reporte pdf - listado de calificaciones
    $datos['data'][$i][6] = "<center><a href='reporte.php?accion=promotor_calif&info1=1&info2=" . $_GET['info1'] . "&info3=" . $datos['data'][$i][0] . "' target='_blank'><img src='../Images/pdf.png'></a></center>";
  }

}

$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$datos = json_encode($datos);
$file  = fopen("TextFiles/promotores.txt", "w");
fwrite($file, $datos);

$web->smarty->assign('datos', $datos);
$web->smarty->display("promotor.html");

/*************************************************************************************************
 * FUNCIONES
 *************************************************************************************************/
/**
 * Define lo que se mostrará en caso de que la especialidad esté indicada o no con la opción 'Otro'
 */
function arreglaEspecialidad($web)
{
  global $promotores;

  for ($i = 0; $i < count($promotores); $i++) {
    if ($promotores[$i][5] != "O") {
      $promotores[$i][3] = $promotores[$i][4];
    }
  }
  // $web->debug($promotores);
}

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

  $sql     = "SELECT cveespecialidad, nombre FROM especialidad WHERE cveespecialidad <> 'O'";
  $combito = $web->combo($sql, null, '../');
  $web->smarty->assign('combito', $combito);

  if ($cveusuario != null) {
    $sql   = 'SELECT * FROM usuarios WHERE cveusuario=?';
    $datos = $web->DB->GetAll($sql, $cveusuario);
    $web->smarty->assign('promotores', $datos[0]);
  }

  $web->smarty->display('form_promotores.html');
  die();
}

/**
 * Elimina datos de las tablas: evaluacion, lista_libros, lectura, sala, laboral, msj,
 * usuario_rol, especialidad_usuario y usuario
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function delete_professor($web)
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
    $web->simple_message('danger', "No se especificó el promotor a eliminar");
    return false;
  }

  //verifica que el promotor exista
  $sql   = "SELECT * FROM usuarios WHERE cveusuario=?";
  $datos = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($datos[0])) {
    $web->simple_message('danger', "El promotor no existe");
    return false;
  }

  //obtiene grupos del promotor
  $sql    = "SELECT DISTINCT cveletra, sala FROM laboral WHERE cvepromotor=?";
  $grupos = $web->DB->GetAll($sql, $_GET['info1']);

  for ($i = 0; $i < sizeof($grupos); $i++) {
    //obtiene la cvelectura de cada grupo
    $sql     = "SELECT cvelectura FROM lectura WHERE cveletra=?";
    $lectura = $web->DB->GetAll($sql, $grupos[$i]['cveletra']);

    for ($j = 0; $j < sizeof($lectura); $j++) {
      //elimina de evaluacion, lista_libros y lectura
      $sql = "DELETE FROM evaluacion WHERE cvelectura=?";
      $web->query($sql, $lectura[$j]['cvelectura']);
      $sql = "DELETE FROM lista_libros WHERE cvelectura=?";
      $web->query($sql, $lectura[$j]['cvelectura']);
      $sql = "DELETE FROM lectura WHERE cvelectura=?";
      $web->query($sql, $lectura[$j]['cvelectura']);
    }
  }

  //obtiene las salas que ha apartado el promotor
  $sql   = "SELECT DISTINCT cvesala FROM laboral WHERE cvepromotor=?";
  $salas = $web->DB->GetAll($sql, $_GET['info1']);
  //elimina las salas
  for ($i = 0; $i < sizeof($salas); $i++) {
    $sql = "DELETE FROM sala WHERE cvesala=?";
    $web->query($sql, $salas[$i]['cvesala']);
  }

  //elimina laboral, msj, usuario_rol, especialidad_usuario y promotor
  $sql = "DELETE FROM laboral WHERE cvepromotor=?";
  $web->query($sql, $_GET['info1']);
  $sql = "DELETE FROM msj WHERE emisor=? or receptor=?";
  $web->query($sql, array($_GET['info1'], $_GET['info1']));
  $sql = "DELETE FROM usuario_rol WHERE cveusuario=?";
  $web->query($sql, $_GET['info1']);
  $sql = "DELETE FROM especialidad_usuario WHERE cveusuario=?";
  $web->query($sql, $_GET['info1']);
  $sql = "DELETE FROM usuarios WHERE cveusuario=?";
  if (!$web->query($sql, $_GET['info1'])) {
    $web->simple_message('danger', 'No se pudo completar la operación');
    return false;
  }
  header('Location: promotor.php');
}

/**
 * Muestra los grupos del profesor
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function show_professor_groups($web)
{
  //verifica que se mandó y sea válido la cvepromotor
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No altere la estructura de la interfaz, no se especificó el promotor');
    return false;
  }

  $web->iniClases('admin', "index promotor grupos");
  $web->smarty->assign('bandera', 'grupos');

  //viene de historial
  if (isset($_GET['info2'])) {
    $web->iniClases('admin', "index historial grupos");
    $web->smarty->assign('bandera', 'historial');
    //ya manda la cveperiodo
    $cveperiodo = $_GET['info2'];
    $web->smarty->assign('cveperiodo', $cveperiodo);
  } else {
    $cveperiodo = $web->periodo();
  }

  $sql      = "SELECT * FROM usuarios WHERE cveusuario=?";
  $promotor = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($promotor[0])) {
    $web->simple_message('danger', 'No existe el promotor');
    return false;
  }

  if ($cveperiodo == '') {
    $web->simple_message('danger', 'No se ha iniciado un periodo nuevo');
    return false;
  }

  $sql = "SELECT DISTINCT letra, nombre, ubicacion, titulo, laboral.cveperiodo
  FROM laboral
  INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
  INNER JOIN sala ON laboral.cvesala = sala.cvesala
  LEFT JOIN libro ON laboral.cvelibro_grupal = libro.cvelibro
  WHERE cvepromotor=? AND laboral.cveperiodo=?
  ORDER BY letra";
  $tablegrupos = $web->DB->GetAll($sql, array($_GET['info1'], $cveperiodo));
  if (!isset($tablegrupos[0])) {
    $web->simple_message('danger', 'No ha creado algún grupo');
    return false;
  }

  $sql = "SELECT dia.cvedia, abecedario.letra, dia.nombre, horas.hora_inicial,
  horas.hora_final
  FROM laboral
  INNER JOIN dia ON dia.cvedia=laboral.cvedia
  INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
  INNER JOIN horas ON horas.cvehoras=laboral.cvehoras
  WHERE cvepromotor=? AND laboral.cveperiodo=?
  ORDER BY letra, dia.cvedia, horas.hora_inicial";
  $horas = $web->DB->GetAll($sql, array($_GET['info1'], $cveperiodo));

  for ($i = 0; $i < sizeof($tablegrupos); $i++) {
    $tablegrupos[$i]['horario'] = "";

    for ($j = 0; $j < sizeof($horas); $j++) {

      if ($tablegrupos[$i]['letra'] == $horas[$j]['letra']) {
        $tablegrupos[$i]['horario'] .= $horas[$j]['nombre'] . ' - ' . $horas[$j]['hora_inicial'] . ' a ' . $horas[$j]['hora_final'] . "<br>";
      }
    }
  }

  $web->smarty->assign('cveusuario', $_GET['info1']);
  $web->smarty->assign('tablegrupos', $tablegrupos);
  $web->smarty->display('grupos.html');
  die();
}

/**
 * Muestra los profesores de un periodo específico
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function show_history($web)
{
  $web->iniClases('admin', "index historial promotor");

  $sql = "SELECT DISTINCT u.cveusuario ,u.nombre, u.correo, otro AS \"Otro\",
  e.nombre AS \"Especialidad\", eu.cveespecialidad
  FROM usuarios u
  INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
  INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
  INNER JOIN laboral ON laboral.cvepromotor = u.cveusuario
  WHERE u.cveusuario IN (SELECT cveusuario FROM usuario_rol WHERE cverol=2)
  AND cveperiodo=?
  ORDER BY u.cveusuario";
  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $promotores = $web->DB->GetAll($sql, $_GET['info1']);

  if (!isset($promotores[0])) {
    header('Location: historial.php?e=1&info1=' . $_GET['info1']);
    return false;
  }

  $web->smarty->assign('cveperiodo', $_GET['info1']);
  return $promotores;
}

/**
 * Muestra el formulario para poder insertar promotores
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function show_form_insert($web)
{
  $web->iniClases('admin', "index promotor nuevo");

  //<> Usado para no seleccionar la opción O, creo...
  $sql = "SELECT cveespecialidad, nombre
  FROM especialidad
  WHERE cveespecialidad <> 'O'
  ORDER BY nombre";
  $combito = $web->combo($sql, null, '../');

  $web->smarty->assign('combito', $combito);
  $web->smarty->display('form_promotores.html');
  die();
}

/**
 * Muestra el formulario para poder insertar promotores
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function show_form_update($web)
{
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', "No se especifico el promotor");
    return false;
  }

  $web->iniClases('admin', "index promotor actualizar");

  $sql = 'SELECT u.cveusuario, u.nombre AS "nombreUsuario", e.nombre, eu.cveespecialidad,
  eu.otro, u.correo
  FROM usuarios u
  INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
  INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
  WHERE u.cveusuario=?';
  $datos = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($datos[0])) {
    $web->simple_message('danger', "No existe el promotor");
    return false;
  }

  $sql = "SELECT cveespecialidad, nombre FROM especialidad
  WHERE cveespecialidad <> 'O'
  ORDER BY nombre";
  $combito = $web->combo($sql, $datos[0]['cveespecialidad'], '../');

  $web->smarty->assign('combito', $combito);
  $web->smarty->assign('promotores', $datos[0]);
  $web->smarty->display('form_promotores.html');
  die();
}

/**
 * Inserta un profesor nuevo
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function insert_professor($web)
{
  global $cveperiodo;
  $usuario = '';

  if (!isset($_POST['datos']['usuario']) ||
    !isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['correo']) ||
    !isset($_POST['datos']['contrasena']) ||
    !isset($_POST['datos']['confcontrasena'])) {
    errores('No alteres la estructura de la interfaz', 'index promotor nuevo', $web);
  }

  if (($_POST['datos']['usuario']) == '' ||
    ($_POST['datos']['nombre']) == '' ||
    ($_POST['datos']['correo']) == '' ||
    ($_POST['datos']['contrasena']) == '' ||
    ($_POST['datos']['confcontrasena'] == '')) {
    errores('Llene todos los campos', 'index promotor nuevo', $web);
  }

  if (strlen($_POST['datos']['usuario']) != 13) {
    errores('La longitud del usuario debe de ser de 13 caracteres', 'index promotor nuevo', $web);
  }

  if (!$web->valida($_POST['datos']['correo'])) {
    errores('Ingrese un correo valido', 'index promotor nuevo', $web);
  }

  if ($_POST['datos']['contrasena'] != $_POST['datos']['confcontrasena']) {
    errores('Las contraseñas no coinciden', 'index promotor nuevo', $web);
  }

  $sql   = "SELECT * FROM usuarios WHERE correo=?";
  $datos = $web->DB->GetAll($sql, $_POST['datos']['correo']);
  if (isset($datos[0])) {
    errores('El correo ya existe', 'index promotor nuevo', $web);
  }

  $sql   = "SELECT * FROM usuarios WHERE cveusuario=?";
  $datos = $web->DB->GetAll($sql, $_POST['datos']['usuario']);
  if (isset($datos[0])) {
    errores('El usuario ya existe', 'index promotor nuevo', $web);
  }

  $usuario    = $_POST['datos']['usuario'];
  $contrasena = $_POST['datos']['contrasena'];
  $nombre     = $_POST['datos']['nombre'];
  $correo     = $_POST['datos']['correo'];

  $web->DB->startTrans();

  $sql = "INSERT INTO usuarios(cveusuario, nombre, pass, correo, validacion)
  VALUES(?,?,?,?, 'Aceptado')";
  $tmp = array($usuario, $nombre, md5($contrasena), $correo);
  $web->query($sql, $tmp);
  $sql = "INSERT INTO usuario_rol VALUES(?, ?)";
  $web->query($sql, array($usuario, 2));

  if (isset($_POST['datos']['especialidad'])) {

    if ($_POST['datos']['especialidad'] == 'true') {
      $sql = "INSERT INTO especialidad_usuario (cveusuario, cveespecialidad) VALUES(?, ?)";
      $web->query($sql, array($usuario, $_POST['datos']['cveespecialidad']));
    } else {
      $sql = "INSERT INTO especialidad_usuario VALUES(?, ?, ?)";
      $web->query($sql, array($usuario, 'O', $_POST['datos']['otro']));
    }
  } else {
    $sql = "INSERT INTO especialidad_usuario (cveusuario, cveespecialidad) VALUES(?, ?)";
    $web->query($sql, array($usuario, $_POST['datos']['cveespecialidad']));
  }

  if ($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No se pudo completar la operación');
    $web->DB->CompleteTrans();
    return false;
  }
  $web->DB->CompleteTrans();
  header('Location: promotor.php');
}

/**
 * Actualiza los datos de un profesor
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function update_professor($web)
{
  global $cveperiodo;
  $cveusuario = $_POST['cveusuario'];

  if (!isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['cveespecialidad']) ||
    !isset($_POST['datos']['correo'])) {
    errores('No alteres la estructura de la interfaz', 'index promotor nuevo', $cveusuario, $web);
  }

  if (($_POST['datos']['nombre']) == '' ||
    ($_POST['datos']['cveespecialidad']) == '' ||
    ($_POST['datos']['correo']) == '') {
    errores('Llene todos los campos', 'index promotor nuevo', $cveusuario, $web);
  }

  $sql   = "SELECT * FROM usuarios WHERE cveusuario=?";
  $datos = $web->DB->GetAll($sql, $cveusuario);
  if (!isset($datos[0])) {
    errores('El promotor no existe', 'index promotor nuevo', $cveusuario, $web);
  }

  $datosp = $datos;
  if (!$web->valida($_POST['datos']['correo'])) {
    errores('Ingrese un correo valido', 'index promotor nuevo', $cveusuario, $web);
  }

  $sql            = "SELECT correo FROM usuarios WHERE cveusuario=?";
  $correo_usuario = $web->DB->GetAll($sql, $cveUsuario);

  $sql     = "SELECT correo FROM usuarios WHERE correo=?";
  $correos = $web->DB->GetAll($sql, $correo);
  if (sizeof($correos) == 1) {
    if ($correo_usuario[0]['correo'] != $correos[0]['correo']) {
      errores('El correo ya existe', 'index promotor nuevo', $cveusuario, $web);
    }
  }

  if ($_POST['datos']['pass'] == 'true') {

    if (!isset($_POST['datos']['contrasena']) ||
      !isset($_POST['datos']['contrasenaN']) ||
      !isset($_POST['datos']['confcontrasenaN'])) {
      errores('No altere la estructura de la interfaz', 'index promotor nuevo', $cveusuario, $web);
    }

    if (isset($_POST['datos']['contrasena']) == '' ||
      isset($_POST['datos']['contrasenaN']) == '' ||
      isset($_POST['datos']['confcontrasenaN']) == '') {
      errores('Llene todos los campos', 'index promotor nuevo', $cveusuario, $web);
    }

    if ($datosp[0]['pass'] != md5($_POST['datos']['contrasena'])) {
      errores('La contraseña es incorrecta', 'index promotor nuevo', $cveusuario, $web);
    }

    if ($_POST['datos']['confcontrasenaN'] != $_POST['datos']['contrasenaN']) {
      errores('La contraseña nueva debe coincidir con la confirmación', 'index promotor nuevo', $cveusuario, $web);
    }

    $sql = "UPDATE usuarios SET nombre=?, correo= ?, pass=? WHERE cveusuario=?";
    $tmp = array(
      $_POST['datos']['nombre'],
      $_POST['datos']['correo'],
      md5($_POST['datos']['contrasenaN']),
      $cveusuario);

    if (!$web->query($sql, $tmp)) {
      $web->simple_message('danger', 'No se pudo completar la operación');
      return false;
    }
  } else {
    $sql = "UPDATE usuarios SET nombre=?, correo= ? WHERE cveusuario=?";
    $tmp = array($_POST['datos']['nombre'], $_POST['datos']['correo'], $cveusuario);
    if (!$web->query($sql, $tmp)) {
      $web->simple_message('danger', 'No se pudo completar la operación');
      return false;
    }

    if (isset($_POST['datos']['especialidad'])) {
      if ($_POST['datos']['especialidad'] == 'true') {
        $sql = "UPDATE especialidad_usuario SET cveespecialidad=?, otro=null WHERE cveusuario=? ";
        $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));
      } else {
        $sql = "UPDATE especialidad_usuario SET cveespecialidad='O', otro=? WHERE cveusuario=? ";
        $web->query($sql, array($_POST['datos']['otro'], $cveusuario));
      }
    } else {
      $sql             = "SELECT cveespecialidad FROM especialidad_usuario WHERE cveusuario=?";
      $cveespecialidad = $web->DB->GetAll($sql, $cveusuario);
      if ($cveespecialidad[0]['cveespecialidad'] == 'O') {
        $sql = "UPDATE especialidad_usuario SET cveespecialidad='O', otro=? WHERE cveusuario=? ";
        $web->query($sql, array($_POST['datos']['otro'], $cveusuario));
      } else {
        $sql = "UPDATE especialidad_usuario SET cveespecialidad=?, otro=null WHERE cveusuario=? ";
        $web->query($sql, array($_POST['datos']['cveespecialidad'], $cveusuario));
      }
    }
  }
  header('Location: promotor.php');
}
