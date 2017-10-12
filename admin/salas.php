<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  mMessage('index salas', 'warning', "No hay periodo actual", 'salas.html');
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_insert':
      $web->iniClases('admin', "index salas nuevo");
      $web->smarty->display('form_salas.html');
      die();
      break;

    case 'form_update':
      if (!isset($_GET['info2'])) {
        mMessage('index salas', 'warning', 'Hace falta información para continuar', 'salas.html');
      }

      $salas = $web->getAll('*', array('cvesala' => $_GET['info2']), 'sala');
      if (sizeof($salas) == 0) {
        mMessage('index salas', 'danger', 'No existe la sala', 'salas.html');
      }

      $web->iniClases('admin', "index salas actualizar");
      $web->smarty->assign('salas', $salas[0]);
      $web->smarty->display('form_salas.html');
      die();
      break;

    case 'insert':
      if (!isset($_POST['datos']['disponible']) ||
        !isset($_POST['datos']['ubicacion'])) {
        mMessage("index salas nuevo", 'warning', "No alteres la estructura de la interfaz", 'form_salas.html');
      }
      if ($_POST['datos']['disponible'] == "" ||
        $_POST['datos']['ubicacion'] == "") {
        mMessage("index salas nuevo", 'warning', "Llena todos los campos", 'form_salas.html');
      }

      $sql = "INSERT INTO sala (ubicacion, disponible) values(?, ?)";
      $tmp = array(
        $_POST['datos']['ubicacion'],
        $_POST['datos']['disponible']);
      if (!$web->query($sql, $tmp)) {
        mMessage("index salas nuevo", 'danger', 'No se pudo completar la operación', 'form_salas.html');
      }

      header('Location: salas.php?aviso=1'); //sala guardada correctamente
      break;

    case 'update':
      if (!isset($_POST['datos']['cvesala']) ||
        !isset($_POST['datos']['ubicacion']) ||
        !isset($_POST['datos']['disponible'])) {
        mMessage("index salas actualizar", 'warning', "No alteres la estructura de la interfaz", 'form_salas.html', $_GET['accion']);
      }
      if ($_POST['datos']['cvesala'] == "" ||
        $_POST['datos']['ubicacion'] == "" ||
        $_POST['datos']['disponible'] == "") {
        mMessage("index salas actualizar", 'warning', "Llena todos los campos", 'form_salas.html', $_GET['accion']);
      }

      $sql        = "UPDATE sala SET ubicacion=?, disponible=? WHERE cvesala=?";
      $parameters = array(
        $_POST['datos']['ubicacion'],
        $_POST['datos']['disponible'],
        $_POST['datos']['cvesala']);
      if (!$web->query($sql, $parameters)) {
        mMessage("index salas", 'danger', 'No se pudo completar la operación', 'salas.html');
      }

      header('Location: salas.php?aviso=2'); //cambios guardados correctamente
      die(); //sin esto no funciona
      break;

    case 'delete':
      deleteSala($web);
      break;
  }
}

$web->iniClases('admin', "index salas");
mShowMessages();

$sql     = 'SELECT cvesala, ubicacion, disponible FROM sala ORDER BY cvesala';
$salones = $web->DB->GetAll($sql);
if (!isset($salones[0])) {
  $web->simple_message('warning', "No hay salones registrados");
} else {
  $web->smarty->assign('salones', $salones);
}

$web->smarty->display("salas.html");

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases    Ruta a mostrar en links
 * @param  String $msg          Mensaje a desplegar
 * @param  $web                 Para poder aplicar las funciones de $web
 * @param  String $cveperiodo   Usado en caso de que se trate de un formulario de actualización
 */
function mMessage($iniClases, $alert, $msg, $html, $cvesala = null)
{
  global $web;
  $web->iniClases('admin', $iniClases);
  $web->simple_message($alert, $msg);

  if ($cvesala != null) {
    $sql   = "SELECT * FROM sala WHERE cvesala=?";
    $salas = $web->DB->GetAll($sql, $cvesala);
    $web->smarty->assign('salas', $salas[0]);
  }

  // $web->smarty->display('form_salas.html');
  $web->smarty->display($html);
  die();
}

/**
 * Elimina de las tablas: evaluacion, lista_libros, lectura, msj, laboral y sala
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false Indica que se va a mostrar un mensaje de error
 */
function deleteSala($web)
{
  //se valida la contraseña
  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;
    case 2:
      $web->simple_message('danger', ' La contraseña de seguridad ingresada no es válida');
      return false;
  }

  if (!isset($_GET['info1'])) {
    $web->simple_message('warning', 'No se especificó la sala');
    return false;
  }

  $sql  = "SELECT * FROM sala WHERE cvesala=?";
  $sala = $web->DB->GetAll($sql, $_GET['info1']);
  if (sizeof($sala) == 0) {
    $web->simple_message('danger', 'La sala seleccionada no existe');
    return false;
  }

  //obtener cveletra
  $sql    = "SELECT DISTINCT cveletra FROM laboral WHERE cvesala=?";
  $grupos = $web->DB->GetAll($sql, $_GET['info1']);

  //obtener la cvelectura de cada sala
  for ($i = 0; $i < sizeof($grupos); $i++) {
    $sql      = "SELECT DISTINCT cvelectura FROM lectura WHERE cveletra=?";
    $lecturas = $web->DB->GetAll($sql, $grupos[$i]['cveletra']);

    for ($j = 0; $j < sizeof($lecturas); $j++) {
      //eliminar de evaluacion y lista_libros
      $sql = "DELETE FROM evaluacion WHERE cvelectura=?";
      $web->query($sql, $lecturas[$j]['cvelectura']);
      $sql = "DELETE FROM lista_libros WHERE cvelectura=?";
      $web->query($sql, $lecturas[$j]['cvelectura']);
    }

    //eliminar de lectura y msj
    $sql = "DELETE FROM lectura WHERE cveletra=?"; //cveletra más rapido que cvelectura
    $web->query($sql, $grupos[$i]['cveletra']);
    $sql = "DELETE FROM msj WHERE cveletra=?";
    $web->query($sql, $grupos[$i]['cveletra']);
  }

  //eliminar de laboral y sala
  $sql = "DELETE FROM laboral WHERE cvesala=?";
  $web->query($sql, $_GET['info1']);
  $sql = "DELETE FROM sala WHERE cvesala=?";
  if (!$web->query($sql, $_GET['info1'])) {
    $web->simple_message('danger', 'No se pudo completar la operación', $web);
    return false;
  }

  header('Location: salas.php');
}

/**
 * Muestra mensajes de error o información
 */
function mShowMessages()
{
  global $web;

  if (isset($_GET['aviso'])) {
    switch ($_GET['aviso']) {
      case 1:
        $web->simple_message('info', 'Sala guardada correctamente');
        break;

      case 2:
        $web->simple_message('info', 'Cambios guardados correctamente');
        break;
    }
  }
}
