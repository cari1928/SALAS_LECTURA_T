<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web = new PromotorControllers;
//por si viene de historial
$flag = false;
showMessages();

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
      update_professor();
      break;
    case 'delete':
      delete_professor();
      break;
    case 'mostrar':
      show_professor_groups();
      break;
    case 'historial':
      $flag = show_history($web);
      break;
  }
}

$promotores = $flag; //viene de historial
if (!$flag) {
  //no viene de historial
  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $promotores = $web->getPromotores();
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
 * Muestra avisos
 */
function showMessages()
{
  global $web;
  if (isset($_GET['a'])) {
    switch ($_GET['a']) {
      case 'value':
        $web->simple_message('info', 'Los cambios han sido guardados correctamente');
        break;
      case 1:
        $web->simple_message('info', 'Se ha eliminado el promotor correctamente');
        break;
      case 2:
        $web->simple_message('danger', 'No se ha podido eliminar al promotor seleccionado');
        break;
    }
  }
}

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
}

/**
 * Mostrar mensajes de error en casos específicos
 * @param  String $msg        Mensaje a mostrar
 * @param  String $ruta       Ruta de la página
 * @param  String $cveusuario Número de control
 * @param  Class  $web        Objeto para usar las herramientas smarty
 * @return Error desplegado en una plantilla
 */
function errores($msg, $ruta, $cveusuario = null)
{
  global $web;
  $web->simple_message('danger', $msg);
  $web->iniClases('admin', $ruta);

  $sql = "SELECT cveespecialidad, nombre FROM especialidad
    WHERE cveespecialidad <> 'O' ORDER BY nombre";
  $combito = $web->combo($sql, null, '../');
  $web->smarty->assign('combito', $combito);

  if ($cveusuario != null) {
    $datos = $web->getAll('*', array('cveusuario' => $cveusuario), 'usuarios');
    $web->smarty->assign('promotores', $datos[0]);
  }
  $web->smarty->display('form_promotores.html');die();
}

/**
 * Elimina datos de las tablas: evaluacion, lista_libros, lectura, sala, laboral, msj,
 * usuario_rol, especialidad_usuario y usuario
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function delete_professor()
{
  global $web;
  $cveperiodo = $web->periodo();
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
  $datos = $web->getAll('*', array('cveusuario' => $_GET['info1']), 'usuarios');
  if (!isset($datos[0])) {
    $web->simple_message('danger', "El promotor no existe");
    return false;
  }

  //obtiene grupos del promotor
  $grupos = $web->getAll(array('DISTINCT cveletra', 'sala'), array('cvepromotor' => $_GET['info1']), 'laboral');
  for ($i = 0; $i < sizeof($grupos); $i++) {
    //obtiene la cvelectura de cada grupo
    $lectura = $web->getAll(array('cvelectura'), array('cveletra' => $grupos[$i]['cveletra']), 'lectura');
    for ($j = 0; $j < sizeof($lectura); $j++) {
      //trigger, elimina de evaluacion, lista_libros y lectura
      $web->dbFunction('*', 'del_reading', array($lectura[$j]['cvelectura']));
    }
  }

  //elimina laboral, msj, usuario_rol, especialidad_usuario y promotor
  if (!$web->dbFunction('*', 'del_laboral', array($cveperiodo, $grupos[$i]['cveletra']))) {
    header('Location: promotor.php?a=2');die();
  }
  header('Location: promotor.php?a=1');die();
}

/**
 * Muestra los grupos del profesor
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function show_professor_groups()
{
  global $web;
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
    $cveperiodo = $_GET['info2']; //ya manda la cveperiodo
    $web->smarty->assign('cveperiodo', $cveperiodo);

  } else {
    $cveperiodo = $web->periodo();
  }

  $promotor = $web->getAll('*', array('cveusuario' => $_GET['info1']), 'usuarios');
  if (!isset($promotor[0])) {
    $web->simple_message('danger', 'No existe el promotor');
    return false;
  }
  if ($cveperiodo == '') {
    $web->simple_message('danger', 'No se ha iniciado un periodo nuevo');
    return false;
  }

  $tablegrupos = $web->getTableGroups($_GET['info1'], $cveperiodo);
  if (!isset($tablegrupos[0])) {
    $web->simple_message('danger', 'No ha creado algún grupo');
    return false;
  }

  $horas = $web->getHours($cveperiodo);
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

  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $promotores = $web->getPromotoresByPeriod($_GET['info1']);
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
  $datos = $web->getPromotor($_GET['info1']);
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
    errores('No alteres la estructura de la interfaz', 'index promotor nuevo');
  }

  if (($_POST['datos']['usuario']) == '' ||
    ($_POST['datos']['nombre']) == '' ||
    ($_POST['datos']['correo']) == '' ||
    ($_POST['datos']['contrasena']) == '' ||
    ($_POST['datos']['confcontrasena'] == '')) {
    errores('Llene todos los campos', 'index promotor nuevo');
  }

  if (strlen($_POST['datos']['usuario']) != 13) {
    errores('La longitud del usuario debe de ser de 13 caracteres', 'index promotor nuevo');
  }

  if (!$web->valida($_POST['datos']['correo'])) {
    errores('Ingrese un correo valido', 'index promotor nuevo');
  }

  if ($_POST['datos']['contrasena'] != $_POST['datos']['confcontrasena']) {
    errores('Las contraseñas no coinciden', 'index promotor nuevo');
  }

  $datos = $web->getAll('*', array('correo' => $_POST['datos']['correo']), 'usuarios');
  if (isset($datos[0])) {
    errores('El correo ya existe', 'index promotor nuevo');
  }

  $datos = $web->getAll('*', array('cveusuario' => $_POST['datos']['usuario']), 'usuarios');
  if (isset($datos[0])) {
    errores('El usuario ya existe', 'index promotor nuevo');
  }

  $usuario    = $_POST['datos']['usuario'];
  $contrasena = $_POST['datos']['contrasena'];
  $nombre     = $_POST['datos']['nombre'];
  $correo     = $_POST['datos']['correo'];

  $web->DB->startTrans();
  $web->insert('usuarios', array(
    'cveusuario' => $usuario, 'nombre' => $nombre, 'pass' => md5($contrasena), 'correo' => $correo, 'validacion' => 'Aceptado'));
  $web->insert('usuario_rol', array('cveusuario' => $usuario, 'cverol' => 2));

  if (isset($_POST['datos']['especialidad'])) {

    if ($_POST['datos']['especialidad'] == 'true') {
      $web->insert('especialidad_usuario', array(
        'cveusuario' => $usuario, 'cveespecialidad' => $_POST['datos']['cveespecialidad']));
    } else {
      $web->insert('especialidad_usuario', array(
        'cveusuario' => $usuario, 'cveespecialidad' => 'O', 'otro' => $_POST['datos']['otro']));
    }
  } else {
    $web->insert('especialidad_usuario', array(
      'cveusuario' => $usuario, 'cveespecialidad' => $_POST['datos']['cveespecialidad']));
  }

  if ($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No se pudo completar la operación, E003');
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
function update_professor()
{
  global $web, $cveperiodo;
  $cveusuario = $_POST['cveusuario'];

  if (!isset($_POST['datos']['nombre']) ||
    !isset($_POST['datos']['cveespecialidad']) ||
    !isset($_POST['datos']['correo'])) {
    errores('No alteres la estructura de la interfaz', 'index promotor actualizar', $cveusuario);
  }
  if (($_POST['datos']['nombre']) == '' ||
    ($_POST['datos']['cveespecialidad']) == '' ||
    ($_POST['datos']['correo']) == '') {
    errores('Llene todos los campos', 'index promotor actualizar', $cveusuario);
  }

  $datos = $web->getAll('*', array('cveusuario' => $cveusuario), 'usuarios');
  if (!isset($datos[0])) {
    errores('El promotor no existe', 'index promotor actualizar', $cveusuario);
  }

  $datosp = $datos;
  if (!$web->valida($_POST['datos']['correo'])) {
    errores('Ingrese un correo valido', 'index promotor actualizar', $cveusuario);
  }

  $correo_usuario = $web->getAll(array('correo'), array('cveusuario' => $cveusuario), 'usuarios');
  $correos        = $web->getAll(array('correo'), array('correo' => $_POST['datos']['correo']), 'usuarios');
  if (sizeof($correos) == 1) {
    if ($correo_usuario[0]['correo'] != $correos[0]['correo']) {
      errores('El correo ya existe', 'index promotor nuevo', $cveusuario);
    }
  }

  if ($_POST['datos']['pass'] == 'true') {

    if (!isset($_POST['datos']['contrasena']) ||
      !isset($_POST['datos']['contrasenaN']) ||
      !isset($_POST['datos']['confcontrasenaN'])) {
      errores('No altere la estructura de la interfaz', 'index promotor nuevo', $cveusuario);
    }
    if (isset($_POST['datos']['contrasena']) == '' ||
      isset($_POST['datos']['contrasenaN']) == '' ||
      isset($_POST['datos']['confcontrasenaN']) == '') {
      errores('Llene todos los campos', 'index promotor nuevo', $cveusuario);
    }
    if ($datosp[0]['pass'] != md5($_POST['datos']['contrasena'])) {
      errores('La contraseña es incorrecta', 'index promotor nuevo', $cveusuario);
    }
    if ($_POST['datos']['confcontrasenaN'] != $_POST['datos']['contrasenaN']) {
      errores('La contraseña nueva debe coincidir con la confirmación', 'index promotor nuevo', $cveusuario);
    }

    $tmp = array(
      $_POST['datos']['nombre'],
      $_POST['datos']['correo'],
      md5($_POST['datos']['contrasenaN']),
      $cveusuario);
    if (!$web->updatePromoPass($tmp)) {
      $web->simple_message('danger', 'No se pudo completar la operación, E001');
      return false;
    }
  } else {
    if ($correo_usuario[0][0] == $_POST['datos']['correo']) {
      // UPDATE SIN CORREO
      $web->update(
        array('nombre' => $_POST['datos']['nombre'], 'pass' => md5($_POST['datos']['contrasenaN'])),
        array('cveusuario' => $cveusuario),
        'usuarios');
    } else {
      // UPDATE CON CORREO
      $web->update(
        array('nombre' => $_POST['datos']['nombre'],
          'correo'       => $_POST['datos']['correo'],
          'pass'         => md5($_POST['datos']['contrasenaN'])),
        array('cveusuario' => $cveusuario),
        'usuarios');
    }

    $tmp = array($_POST['datos']['nombre'], $_POST['datos']['correo'], $cveusuario);
    if (!$web->updatePromotor($tmp)) {
      $web->simple_message('danger', 'No se pudo completar la operación, E002');
      return false;
    }

    if (isset($_POST['datos']['especialidad'])) {
      if ($_POST['datos']['especialidad'] == 'true') {
        $web->updateEspUsuario($_POST['datos']['cveespecialidad'], null, $cveusuario);
      } else {
        $web->updateEspUsuario('O', $_POST['datos']['otro'], $cveusuario);
      }
    } else {
      $cveespecialidad = $web->getAll(array('cveespecialidad'), array('cveusuario' => $cveusuario), 'especialidad_usuario');
      if ($cveespecialidad[0]['cveespecialidad'] == 'O') {
        $web->updateEspUsuario('O', $_POST['datos']['otro'], $cveusuario);
      } else {
        $web->updateEspUsuario($_POST['datos']['cveespecialidad'], null, $cveusuario);
      }
    }
  }
  header('Location: promotor.php?a=1');
}
