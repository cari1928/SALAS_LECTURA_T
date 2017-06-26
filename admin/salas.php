<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('warning', "No hay periodo actual");
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
        $web->smarty->assign('alert', 'danger');
        $web->smarty->assign('msg', 'No se está recibiendo la información necesaria para continuar con la operación');
        break;
      }

      $sql   = "select * from sala where cvesala=?";
      $salas = $web->DB->GetAll($sql, $_GET['info2']);
      if (sizeof($salas) == 0) {
        $web->smarty->assign('alert', 'danger');
        $web->smarty->assign('msg', 'No existe la sala');
        break;
      }

      $web->iniClases('admin', "index salas actualizar");
      $web->smarty->assign('salas', $salas[0]);
      $web->smarty->display('form_salas.html');
      die();
      break;

    case 'insert':
      //verifica la existencia de los campos
      if (!isset($_POST['datos']['cvesala']) ||
        !isset($_POST['datos']['ubicacion'])) {
        message("index periodos nuevo", "No alteres la estructura de la interfaz", $web);
      }

      //verifica que los campos contengan algo
      if ($_POST['datos']['cvesala'] == "" ||
        $_POST['datos']['ubicacion'] == "") {
        message("index periodos nuevo", "Llena todos los campos", $web);
      }

      $cveperiodo = $web->periodo();
      $sql        = "INSERT INTO sala (cvesala, ubicacion, cveperiodo) values(?, ?, ?)";
      $tmp        = array(
        $_POST['datos']['cvesala'],
        $_POST['datos']['ubicacion'],
        $cveperiodo);

      if (!$web->query($sql, $tmp)) {
        $web->smarty->assign('alert', 'danger');
        $web->smarty->assign('msg', 'No se pudo completar la operación');
        break;
      }

      header('Location: salas.php');
      break;

    case 'update':
      //verifica la existencia de los campos
      if (!isset($_POST['datos']['cvesala']) ||
        !isset($_POST['datos']['ubicacion'])) {
        message("index periodos nuevo", "No alteres la estructura de la interfaz", $web, $_GET['accion']);
      }

      //verifica que los campos contengan algo
      if ($_POST['datos']['cvesala'] == "" ||
        $_POST['datos']['ubicacion'] == "") {
        message("index periodos nuevo", "Llena todos los campos", $web, $_GET['accion']);
      }

      $sql        = "update sala set ubicacion=? where cvesala=?";
      $parameters = array(
        $_POST['datos']['ubicacion'],
        $_POST['cvesala']);

      if (!$web->query($sql, $parameters)) {
        $web->smarty->assign('alert', 'danger');
        $web->smarty->assign('msg', 'No se pudo completar la operación');
        break;
      }

      header('Location: salas.php');
      break;

    case 'delete':
      delete_room($web);
      break;
  }
}

$web->iniClases('admin', "index salas");

//obtiene todas las salas a mostrar
$sql     = 'select cvesala, ubicacion from sala where cveperiodo=? order by cvesala';
$salones = $web->DB->GetAll($sql, $cveperiodo);

if (!isset($salones[0])) {
  $web->simple_message('warning', " No hay salones registrados");
} else {
  $web->smarty->assign('salones', $salones);
}

$web->smarty->display("salas.html");

/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases    Ruta a mostrar en links
 * @param  String $msg          Mensaje a desplegar
 * @param  $web                 Para poder aplicar las funciones de $web
 * @param  String $cveperiodo   Usado en caso de que se trate de un formulario de actualización
 */
function message($iniClases, $msg, $web, $cvesala = null)
{
  $web->iniClases('admin', $iniClases);

  $web->smarty->assign('alert', 'danger');
  $web->smarty->assign('msg', $msg);

  if ($cvesala != null) {
    $sql   = "select * from sala where cvesala=?";
    $salas = $web->DB->GetAll($sql, $cvesala);

    $web->smarty->assign('salas', $salas[0]);
  }

  $web->smarty->display('form_salas.html');
  die();
}

/**
 * Elimina de las tablas: evaluacion, lista_libros, lectura, msj, laboral y sala
 * @param  Class    $web Objeto para hacer uso de smarty
 * @return boolean  false Indica que se va a mostrar un mensaje de error
 */
function delete_room($web)
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
    $web->simple_message('danger', 'No altere la estructura de la interfaz, no se especificó la sala', $web);
    return false;
  }

  $sql  = "select * from sala where cvesala=?";
  $sala = $web->DB->GetAll($sql, $_GET['info1']);
  if (sizeof($sala) == 0) {
    $web->simple_message('danger', 'No existe la sala', $web);
    return false;
  }

  //obtener cveletra
  $sql    = "select distinct cveletra from laboral where cvesala=?";
  $grupos = $web->DB->GetAll($sql, $_GET['info1']);

  //obtener la cvelectura de cada sala
  for ($i = 0; $i < sizeof($grupos); $i++) {
    $sql      = "select distinct cvelectura from lectura where cveletra=?";
    $lecturas = $web->DB->GetAll($sql, $grupos[$i]['cveletra']);

    for ($j = 0; $j < sizeof($lecturas); $j++) {
      //eliminar de evaluacion y lista_libros
      $sql = "delete from evaluacion where cvelectura=?";
      $web->query($sql, $lecturas[$j]['cvelectura']);
      $sql = "delete from lista_libros where cvelectura=?";
      $web->query($sql, $lecturas[$j]['cvelectura']);
    }

    //eliminar de lectura y msj
    $sql = "delete from lectura where cveletra=?"; //cveletra más rapido que cvelectura
    $web->query($sql, $grupos[$i]['cveletra']);
    $sql = "delete from msj where cveletra=?";
    $web->query($sql, $grupos[$i]['cveletra']);
  }

  //eliminar de laboral y sala
  $sql = "delete from laboral where cvesala=?";
  $web->query($sql, $_GET['info1']);
  $sql = "delete from sala where cvesala=?";
  if (!$web->query($sql, $_GET['info1'])) {
    $web->simple_message('danger', 'No se pudo completar la operación', $web);
    return false;
  }

  header('Location: salas.php');
}
