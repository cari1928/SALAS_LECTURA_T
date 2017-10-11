<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web = new RedactaControllers;
$web->iniClases('promotor', "index grupos redactar");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);
$web->smarty->assign('avisos', true);

mShowMessages();

$accion  = $para  = "";
$periodo = $web->periodo();
if ($periodo == "") {
  mMessage('danger', 'No hay periodo actual', 'redacta.html');
}

if (isset($_GET['info'])) {
  $para = $_GET['info'];
}
if (isset($_GET['periodo'])) {
  $periodo = $_GET['periodo'];
}
if (isset($_GET['accion'])) {
  $accion = $_GET['accion'];
}

switch ($accion) {
  case 'redactar':
    mRedactar();
    break;

  case 'redactarI':
    mRedactarIndividual();
    break;

  case 'enviar':
    mEnviarGrupal();
    break;

  case 'enviarI':
    mEnviarIndividual();
    break;

  case 'ver':
    mListado();
    break;
}

header("Location: grupos.php");

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
/**
 * Muestra mensajes
 */
function mMessage($alert, $msg, $html)
{
  $web->simple_message($alert, $msg);
  $web->smarty->display($html);
  die();
}

/**
 *
 */
function mRedactar()
{
  global $web, $periodo, $para;

  $datos = $web->getAll(array('*'), array('letra' => $_GET['info']), 'abecedario');
  if (!isset($datos[0])) {
    mMessage('warning', 'No modifique la interfaz', 'redacta.html');
  }

  $letra = $datos[0]['cve'];
  $datos = $web->getReading($periodo, $letra, $_SESSION['cveUser']);
  if (isset($datos[0])) {
    $web->smarty->assign('para', $para);
    $web->smarty->assign('upload', true);
    $web->smarty->assign('cveperiodo', $periodo);
    $web->smarty->assign('type', 'Grupal');
    $web->smarty->display('redacta.html');
    exit();
  }

  mMessage('warning', 'No existe el destinatario o no tienes permiso para mandar este mensaje', 'redacta.html');
}

/**
 * Muestra el formulario para redactar un mensaje a un destinatario específico
 */
function mRedactarIndividual()
{
  global $web, $periodo, $accion;

  if (!isset($_GET['info2'])) {
    mMessage('warning', "Falta información", 'redacta.html');
  }

  $receptor = $_GET['info2'];
  $grupo    = (isset($_GET['info1'])) ? $_GET['info1'] : "";

  $datos = $web->getAll(array('cveusuario'), array('cveusuario' => $receptor), 'usuarios');
  if (isset($datos[0])) {
    $datos_g = $web->getIndividualReading($grupo, $periodo);
    if (isset($datos_g[0])) {
      $web->smarty->assign('receptor', $receptor);
      $web->smarty->assign('accion', $accion);

      if (isset($periodo)) {
        $web->smarty->assign('cveperiodo', $periodo);
      }

      $datos = $web->getAll(array('cve'), array('letra' => $grupo), 'abecedario');
      $web->smarty->assign('upload', true);
      $web->smarty->assign('grupo', $datos[0]['cve']);
      $web->smarty->assign('type', 'Individual');
      $web->smarty->display('redacta.html');
      die();
    }
  }

  mMessage('danger', "No existe el destinatario o no tienes permiso para mandar este mensaje", 'redacta.html');
}

/**
 *
 */
function mEnviarIndividual()
{
  global $web, $periodo;
  $receptor = $cveletra = $encabezado = $contenido = $nombre = "";

  if (isset($_GET['receptor'])) {
    $receptor = $_GET['receptor'];
  }
  if (isset($_GET['para'])) {
    $cveletra = $_GET['para'];
  }
  if (!isset($_POST)) {
    mMessage('danger', "No se pudo mandar el mensaje", 'redacta.html');
  }

  $sql = "SELECT * FROM lectura
    INNER JOIN laboral ON laboral.cveletra = lectura.cveletra
    INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
    WHERE laboral.cvepromotor=? AND lectura.nocontrol=?";
  $datos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $receptor));
  if (!isset($datos[0])) {
    header('Location: grupos.php?aviso=4'); //No existe el destinatario o no hay permiso
  }

  $sql    = "SELECT * FROM abecedario WHERE cve=?";
  $data   = $web->DB->GetAll($sql, $cveletra);
  $nombre = mUploadFiles('I', $data[0]['letra'], $receptor);

  $sql = "INSERT INTO msj(introduccion, descripcion, tipo, emisor, fecha, expira, receptor, cveletra, cveperiodo, archivo)
    VALUES (?, ?, 'I', ?, ?, ?, ?, ?, ?, ?)";
  $parameters = array(
    $_POST['introduccion'],
    $_POST['descripcion'],
    $_SESSION['cveUser'],
    date('Y-m-j'),
    $_POST['expira'],
    $receptor,
    $cveletra,
    $periodo,
    $nombre,
  );
  if (!$web->query($sql, $parameters)) {
    header('Location: grupos.php?aviso=3'); // ocurrió un error
    die();
  }

  header('Location: grupos.php?aviso=2'); // Se envio el mensaje satisfactoriamente
  die(); //no funciona sin esto
}

/**
 *
 */
function mShowMessages()
{
  global $web;
  if (isset($_GET['aviso'])) {
    switch ($_GET['aviso']) {
      case 1:
        $web->simple_message('success', 'Se envío el mensaje satisfactoriamente');
        break;

      case 2:
        $web->simple_message('warning', 'Ocurrió un error mientras se enviaba el mensaje');
        break;
    }
  }
}

function mEnviarGrupal()
{
  global $web, $periodo;
  $letra = $para = "";

  if (isset($_GET['para'])) {
    $letra = $_GET['para'];
  }
  if (!isset($_POST)) {
    header('Location: grupos.php?aviso=3'); // ocurrió un error
    die();
  }

  $datos  = $web->getAll(array('*'), array('letra' => $letra), 'abecedario');
  $letra  = $datos[0]['cve'];
  $nombre = mUploadFiles('G', $datos[0]['letra']);

  if (!$web->insertMsj($_POST['introduccion'], $_POST['descripcion'], $_SESSION['cveUser'], $_POST['expira'], $letra,
    $periodo, $nombre)) {
    unlink($web->route_msj . $periodo . "/" . $datos[0]['letra'] . "/" . $nombre); //elimina el archivo
    header('Location: grupos.php?aviso=3'); // Ocurrio un error al enviar el mensaje
    die();
  }

  header('Location: grupos.php?aviso=2'); // Se envio el mensaje satisfactoriamente
  die(); //no funciona sin esto
}

/**
 * Sube el archivo solo si es necesario
 * @param $type === 'I' | 'G'
 */
function mUploadFiles($type, $letra, $receptor = null)
{
  global $web, $periodo;
  $max_size = 2000000;

  if ($_FILES['archivo']['size'] > 0) {
    if ($_FILES['archivo']['size'] <= $max_size) {
      $fileRoute = $web->route_msj . $periodo . "/" . $letra . "/";
      $nombre    = $_FILES['archivo']['name'];
      $data      = $web->getNameAndExtension($nombre);

      //prepara la ruta
      if ($type == 'I') {
        $fileRoute .= $receptor . "_" . $data[0] . "_";
      } else {
        $fileRoute .= $data[0] . "_";
      }

      $numFiles = $web->countFiles($fileRoute); //cuenta cuántos archivos ya hay con esa ruta
      $fileRoute .= ($numFiles + 1) . $data[1]; //completa la ruta agregando el número de archivo y extensión
      if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $fileRoute)) {
        header('Location: grupos.php?aviso=3'); // Ocurrio un error al enviar el mensaje
        die();
      }

      $nombre   = explode("/", $fileRoute);
      $nameSize = count($nombre);
      return $nombre[($nameSize - 1)];
    }
  }
  return '';
}

/**
 *
 */
function mListado()
{
  global $web, $periodo;
  $web->iniClases('promotor', "index grupos mensajes");

  $grupo = "";
  if (isset($_GET['info'])) {
    $grupo = $_GET['info'];
  }

  $sql = "SELECT cvemsj, introduccion, tipomsj.descripcion AS tipo, e.nombre, fecha, expira FROM msj
    INNER JOIN usuarios e ON e.cveusuario = msj.emisor
    INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
    WHERE msj.cveperiodo=? AND emisor=? AND expira >= ?
    AND cveletra IN (SELECT cve FROM abecedario WHERE letra=?)
    ORDER BY fecha DESC";
  $parameters = array(
    $periodo,
    $_SESSION['cveUser'],
    date('Y-m-j'),
    $grupo);
  $datos = $web->DB->GetAll($sql, $parameters);
  $web->smarty->assign('grupo', $grupo);
  $web->smarty->assign('datos', $datos);
  $web->smarty->display('mensajes.html');
  exit();
}
