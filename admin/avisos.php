<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

mShowMessage();

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('warning', "No hay periodo actual");
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case "eliminar":
      mDelete();
      break;

    case "actualizar_form":
      if (!isset($_GET['info1'])) {
        $web->simple_message('warning', 'Falta información. Por favor, no altere la estructura de la interfaz');
        break;
      }

      $sql   = "SELECT * FROM msj WHERE cvemsj=?";
      $aviso = $web->DB->GetAll($sql, $_GET['info1']);
      if (!isset($aviso[0])) {
        $web->simple_message('danger', 'No fue posible encontrar el aviso');
        break;
      }

      $web->iniClases('admin', 'index avisos actualizar');
      $web->smarty->assign('aviso_header', true);
      $web->smarty->assign('aviso', $aviso[0]);
      $web->smarty->display('form_avisos.html');
      die();
      break;

    case "insertar_form":
      $web->iniClases('admin', 'index avisos nuevo');
      $web->smarty->assign('aviso_header', true);
      $web->smarty->display('form_avisos.html');
      die();
      break;

    case "actualizar":
      mUpdate();
      break;

    case "insertar":
      mInsert();
      break;
  }
}

$web->iniClases('admin', "index avisos");
$sql = "SELECT cvemsj, introduccion, fecha, expira FROM msj
  INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
  WHERE msj.tipo='PU' AND cveperiodo=?
  ORDER BY cvemsj";
$avisos = $web->DB->GetAll($sql, $cveperiodo);
if (!isset($avisos[0])) {
  mMessage('warning', 'No hay mensajes actuales');
}

$web->smarty->assign('avisos', $avisos);
$web->smarty->display('avisos.html');

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
function mInsert()
{
  global $web;
  global $cveperiodo;
  if (!isset($_POST['introduccion']) ||
    !isset($_POST['descripcion']) ||
    !isset($_POST['expira'])) {
    mMessage('warning', 'Falta información. Por favor, no altere la estructura de la interfaz', 'nuevo');
  }
  if ($_POST['introduccion'] == "" ||
    $_POST['descripcion'] == "" ||
    $_POST['expira'] == "") {
    mMessage('warning', 'Falta información. Por favor, no altere la estructura de la interfaz', 'nuevo');
  }
  $sql = "INSERT INTO msj(introduccion, descripcion, tipo, fecha, expira, cveperiodo)
    VALUES(?, ?, 'PU', ?, ?, ?)";
  $parameters = array(
    $_POST['introduccion'],
    $_POST['descripcion'],
    date('Y-m-j'),
    $_POST['expira'],
    $cveperiodo);
  if (!$web->query($sql, $parameters)) {
    mMessage('danger', 'Ocurrió un error al crear el aviso', 'nuevo');
  } else {
    header('Location: avisos.php?m=1');
  }
}

/**
 * $type === nuevo | actualizar
 */
function mMessage($alert, $msg, $type = null)
{
  global $web;

  $html = ($type == 'nuevo' || $type == 'actualizar') ? 'form_avisos.html' : 'avisos.html';
  $web->simple_message($alert, $msg);
  $web->iniClases('admin', 'index avisos ' . $type);
  $web->smarty->display($html);
  die();
}

function mShowMessage()
{
  global $web;
  if (isset($_GET['m'])) {
    switch ($_GET['m']) {
      case 1:
        $web->simple_message('info', 'Se publicó el aviso correctamente');
        break;

      case 2:
        $web->simple_message('info', 'Se actualizó el aviso correctamente');
        break;
    }
  }
}

function mUpdate()
{
  global $web;
  if (!isset($_POST['introduccion']) ||
    !isset($_POST['descripcion']) ||
    !isset($_POST['expira']) ||
    !isset($_POST['cvemsj'])) {
    mMessage('warning', 'Falta información. Por favor, no altere la estructura de la interfaz', 'actualizar');
  }
  if ($_POST['introduccion'] == "" ||
    $_POST['descripcion'] == "" ||
    $_POST['expira'] == "" ||
    $_POST['cvemsj'] == "") {
    mMessage('warning', 'Falta información. Por favor, no altere la estructura de la interfaz', 'actualizar');
  }

  $sql        = "UPDATE msj SET introduccion=?, descripcion=?, expira=? WHERE cvemsj=?";
  $parameters = array(
    $_POST['introduccion'],
    $_POST['descripcion'],
    $_POST['expira'],
    $_POST['cvemsj']);
  if (!$web->query($sql, $parameters)) {
    mMessage('danger', 'Ocurrió un error al actualizar el aviso', 'actualizar');
  } else {
    header('Location: avisos.php?m=2');
    die();
  }
}

function mDelete()
{
  global $web;
  if (!isset($_GET['info1'])) {
    $web->simple_message('warning', 'Falta información, por favor no altere la estructura de la interfaz');
    return;
  }
  if ($_GET['info1'] == "") {
    $web->simple_message('warning', 'Falta información, por favor no altere la estructura de la interfaz');
    return;
  }

  $sql       = "SELECT * FROM msj WHERE cvemsj=?";
  $datos_msj = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($datos_msj[0])) {
    $web->simple_message('danger', 'No existe el aviso seleccionado');
    return;
  }

  if (!$web->query('DELETE FROM msj WHERE cvemsj=?', $_GET['info1'])) {
    $web->simple_message('danger', 'No se pudo eliminar el aviso');
    return;
  }

  $web->simple_message('info', 'Eliminado con éxito');
  return;
}
