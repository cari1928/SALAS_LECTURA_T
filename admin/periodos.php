<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web        = new PeriodosControllers;
$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('warning', 'No hay periodo actual');
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_insert':
      mMessage('index periodos nuevo', null, null); //despliega el formulario sin mostrar mensaje
      break;

    case 'form_update':
      mFormUpdate();
      break;

    case 'insert':
      mInsertPeriod();
      break;

    case 'update':
      mUpdatePeriodo();
      break;

    case 'delete':
      deletePeriodo();
      break;

    case 'historial':
      $web->iniClases('admin', "index historial-periodos");
      $web->smarty->assign('bandera', 'historial');
  }
} else {
  $web->iniClases('admin', "index periodos");
}

$web->showMessages();

$periodos = $web->getPeriodos();
if (!isset($periodos[0])) {
  mMessage('index periodos', 'danger', "No hay periodos registrados", 'periodos.html');
}
$periodos = array('data' => $periodos);

//se preparan los campos extra (eliminar y actualizar)
for ($i = 0; $i < sizeof($periodos['data']); $i++) {
  //eliminar
  $periodos['data'][$i][3] =
    "periodos.php?accion=delete&info1=" . $periodos['data'][$i][0];
  //editar
  $periodos['data'][$i][4] = "<center><a href='periodos.php?accion=form_update&info2=" .
    $periodos['data'][$i][0] . "'><img src='../Images/edit.png'></a></center>";

  if (isset($_GET['accion'])) {
    if ($_GET['accion'] == 'historial') {
      //mostrar_grupos
      $periodos['data'][$i][3] = "<center><a href='historial.php?accion=periodo&info1=" .
        $periodos['data'][$i][0] . "'><img src='../Images/grupo.png'></a></center>";
    }
  }
}

$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$periodos = json_encode($periodos);

$file = fopen("TextFiles/periodos.txt", "w");
fwrite($file, $periodos);

$web->smarty->assign('periodos', $periodos);
$web->smarty->display("periodos.html");

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases    Ruta a mostrar en links
 * @param  String $alert        Tipo de alerta
 * @param  String $msg          Mensaje a desplegar
 * @param  $web                 Para poder aplicar las funciones de $web
 * @param  String $cveperiodo   Usado en caso de que se trate de un formulario de actualización
 */
function mMessage($iniClases, $alert, $msg, $html = 'form_periodos.html', $periodo = null)
{
  global $web;
  $web->iniClases('admin', $iniClases);

  if ($alert != null && $msg != null) {
    $web->simple_message($alert, $msg);
  }
  if ($periodo != null) {
    $web->smarty->assign('periodos', $periodo[0]);
  }

  $web->smarty->display($html);
  die();
}

/**
 * Elimina la info relacionada con periodos.
 * Tablas: lista_libros, evaluacion, laboral, msj, sala y perioodo
 * @param  Class  $web Objeto para poder usar smarty
 */
function deletePeriodo()
{
  global $web;

  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      mMessage('index periodos', 'danger', 'No se especificó la contraseña de seguridad', 'periodos.html');
    case 2:
      mMessage('index periodos', 'danger', 'La contraseña de seguridad ingresada no es válida', 'periodos.html');
  }

  if (!isset($_GET['info1'])) {
    mMessage('index periodos', 'warning', 'No se especificó el periodo', 'periodos.html');
  }

  $periodo = $web->getPeriodo($_GET['info1']);
  if (!isset($periodo[0])) {
    mMessage('index periodos', 'danger', 'No existe el periodo', 'periodos.html');
  }

  $web->DB->startTrans();
  if (!$web->deletePeriodo($_GET['info1'])) {
    mMessage('index periodos', 'danger', 'No fue posible eliminar el periodo seleccionado', 'periodos.html');
  }

  $web->deleteFiles($_GET['info1']); //elimina carpetas y archivos relacionados con el periodo

  if ($web->DB->HasFailedTrans()) {
    mMessage('index periodos', 'danger', 'No fue posible eliminar el periodo seleccionado', 'periodos.html');
  }
  $web->DB->CompleteTrans();
  header('Location: periodos.php?aviso=3');
}

/**
 * Ingresa un nuevo periodo
 */
function mInsertPeriod()
{
  global $web;

  if (!isset($_POST['datos']['fechaInicio']) || !isset($_POST['datos']['fechaFinal'])) {
    mMessage("index periodos nuevo", 'warning', "No alteres la estructura de la interfaz"); //form_periodos
  }
  if ($_POST['datos']['fechaInicio'] == "" || $_POST['datos']['fechaFinal'] == "") {
    mMessage("index periodos nuevo", 'warning', "Llena todos los campos"); //form_periodos
  }

  $web->DB->startTrans();
  if (!$web->insertPeriod(array($_POST['datos']['fechaInicio'], $_POST['datos']['fechaFinal']))) {
    mMessage("index periodos nuevo", 'danger', "No fue posible guardar los datos", 'periodos.html');
  }

  $cveperiodo = $web->getLastPeriodo();
  $periodo    = $web->getPeriodo($cveperiodo);
  if (isset($periodo[0])) {
    mkdir("../archivos/periodos/" . $periodo[0][2], 0777);
    mkdir("../archivos/pdf/" . $periodo[0][2], 0777);
    mkdir("../archivos/mensajes/" . $periodo[0][2], 0777);
  }

  //si hubo algún problema:
  if ($web->DB->HasFailedTrans()) {
    //si el msg de error contiente periodouq:
    if (strpos($error, 'periodouq') > 0) {
      mMessage('index periodos nuevo', 'warning', 'Registro duplicado');
    }
    mMessage("index periodos nuevo", 'warning', "No alteres la estructura de la interfaz");
  }
  $web->DB->CompleteTrans();
  header('Location: periodos.php?aviso=1'); //guardado correctamente
}

/**
 * Muestra el formulario de actualización
 */
function mFormUpdate()
{
  global $web;
  if (!isset($_GET['info2'])) {
    mMessage('index periodos', 'warning', 'Hace falta mostrar el periodo', 'periodos.html');
  }

  $periodo = $web->getPeriodo($_GET['info2']);
  if (!isset($periodo[0])) {
    mMessage('index periodos', 'danger', 'No existe el periodo', 'periodos.html');
  }

  $web->smarty->assign('periodos', $periodo[0]);
  mMessage('index periodos actualizar', null, null); //muestra el form_periodo.html sin mensaje
}

/**
 * Actualiza un periodo
 */
function mUpdatePeriodo()
{
  global $web;

  if (!isset($_POST['datos']['fechaInicio']) ||
    !isset($_POST['datos']['fechaFinal']) ||
    !isset($_POST['cveperiodo'])) {
    mMessage('index periodos', 'warning', 'Hacen falta datos para continuar', 'periodos.html');
  }

  $periodo = $web->getPeriodo($_POST['cveperiodo']);
  if (!isset($periodo[0])) {
    mMessage('index periodos', 'warning', 'No altere la estructura de la interfaz', 'periodos.html');
  }
  $cveperiodo = $_POST['cveperiodo'];

  if ($_POST['datos']['fechaInicio'] == "" ||
    $_POST['datos']['fechaFinal'] == "") {
    mMessage("index periodos actualizar", 'warning', "Llena todos los campos", 'form_periodos.html', $periodo);
  }

  $web->DB->startTrans();
  $parameters = array(
    $_POST['datos']['fechaInicio'],
    $_POST['datos']['fechaFinal'],
    $_POST['cveperiodo']);
  $error = $web->updatePeriodo($parameters);
  //si hubo algún problema:
  if ($web->DB->HasFailedTrans()) {
    //si el msg de error contiente periodouq:
    if (strpos($error, 'periodouq') > 0) {
      mMessage("index periodos actualizar", 'warning', "Registro duplicado", 'form_periodos.html', $periodo);
    } else {
      mMessage("index periodos actualizar", 'danger', 'No fue posible realizar el cambio', 'form_periodos.html', $periodo);
    }
  }
  $web->DB->CompleteTrans();
  header('Location: periodos.php?aviso=2');
}
