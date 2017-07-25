<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web->iniClases('promotor', "index grupos redactar");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);
$web->smarty->assign('avisos', true);

$accion  = $para  = "";
$periodo = $web->periodo();
if ($periodo == "") {
  mMessage('danger', 'No hay periodo actual', 'redacta.html');
}

mShowMessages();

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
    $letra = $para = "";
    if (isset($_GET['para'])) {
      $letra = $_GET['para'];
    }
    if (isset($_GET['cveperiodo'])) {
      $periodo = $_GET['cveperiodo'];
    }

    $sql   = "SELECT cve FROM abecedario WHERE letra=?";
    $datos = $web->DB->GetAll($sql, $letra);
    $letra = $datos[0]['cve'];

    if (isset($_POST)) {
      $max_size = 2000000;

      if ($_FILES['archivo']['size'] > 0) {
        if ($_FILES['archivo']['size'] <= $max_size) {
          $dir_subida = "/home/slslctr/archivos_msj/" . $periodo . "/";
          $nombre     = $_FILES['archivo']['name'];

          if (file_exists($dir_subida . $nombre)) {
            header('Location: grupos.php?aviso=1'); // ya existe un archivo con este mismo nombre
          }

          if (move_uploaded_file($_FILES['archivo']['tmp_name'], $dir_subida . $nombre)) {
            $sql = "INSERT INTO msj(introduccion, descripcion, tipo, emisor, fecha, expira, cveletra, cveperiodo, archivo)
              VALUES (?, ?,'G', ?,'" . date('Y-m-j') . "', ?, ?, ?, ?)";
            $datos = $web->DB->GetAll($sql, array(
              $_POST['introduccion'],
              $_POST['descripcion'],
              $_SESSION['cveUser'],
              $_POST['expira'],
              $letra,
              $periodo,
              $nombre,
            ));
            header('Location: grupos.php?aviso=2'); // Se envio el mensaje satisfactoriamente
          } else {
            header('Location: grupos.php?aviso=3'); // Ocurrió un error
          }
          die();
        }
      } else {
        $sql = "INSERT INTO msj(introduccion, descripcion, tipo, emisor, fecha, expira, cveletra, cveperiodo)
          VALUES (?, ?,'G', ?,'" . date('Y-m-j') . "', ?, ?, ?)";
        $datos = $web->DB->GetAll($sql, array(
          $_POST['introduccion'],
          $_POST['descripcion'],
          $_SESSION['cveUser'],
          $_POST['expira'],
          $letra,
          $periodo,
        ));
        header('Location: grupos.php?aviso=2'); // Se envio el mensaje satisfactoriamente
        die();
      }
    } else {
      header('Location: grupos.php?aviso=3'); // Se envio el mensaje satisfactoriamente
      die();
    }
    break;

  case 'enviarI':
    mEnviarIndividual();
    break;

  case 'ver':
    $grupo = $periodo = "";
    if (isset($_GET['info'])) {
      $grupo = $_GET['info'];
    }

    $periodo = $web->periodo($web);
    if ($periodo != "") {
      $sql = "SELECT cvemsj, introduccion, tipomsj.descripcion AS tipo, e.nombre, fecha, expira, abecedario.letra AS letra
        FROM msj
        INNER JOIN usuarios e ON e.cveusuario = msj.emisor
        INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
        INNER JOIN abecedario ON msj.cveletra = abecedario.cve
        WHERE msj.tipo='G' AND abecedario.letra=? AND msj.cveperiodo=? AND emisor=?
          AND expira >='" . date('Y-m-j') . "'";
      $parameters = array(
        $grupo,
        $periodo,
        $_SESSION['cveUser']);
      $datos = $web->DB->GetAll($sql, $parameters);
      $web->smarty->assign('datos', $datos);

      $sql = "SELECT cvemsj, introduccion, tipomsj.descripcion AS tipo, e.nombre AS nombree,
        r.nombre AS nombrer, fecha, expira
        FROM msj
        INNER JOIN usuarios e ON e.cveusuario = msj.emisor
        INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
        INNER JOIN usuarios r ON msj.receptor = r.cveusuario
        WHERE emisor=? AND expira >='" . date('Y-m-j') . "' AND tipomsj.cvetipomsj='I'";
      $datosI = $web->DB->GetAll($sql, $_SESSION['cveUser']);
      $web->smarty->assign('datosI', $datosI);
    } else {
      $web->smarty->assign('datos', "No se puede acceder a los mensajes");
    }
    $web->smarty->display('mensajes.html');
    exit();
    break;
}

header("Location: grupos.php");

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
/**
 *
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

  $sql   = "SELECT cve FROM abecedario WHERE letra=?";
  $datos = $web->DB->GetAll($sql, $_GET['info']);
  if (!isset($datos[0])) {
    mMessage('warning', 'No modifique la interfaz', 'redacta.html');
  }

  $letra = $datos[0]['cve'];
  $sql   = "SELECT * FROM lectura WHERE cveperiodo=? AND cveletra=? AND cveletra in
    (SELECT cveletra FROM laboral WHERE cvepromotor=? and cveperiodo=?)";
  $datos = $web->DB->GetAll($sql, array($periodo, $letra, $_SESSION['cveUser'], $periodo));
  if (isset($datos[0])) {
    $web->smarty->assign('para', $para);
    $web->smarty->assign('cveperiodo', $periodo);
    $web->smarty->display('redacta.html');
    exit();
  }

  mMessage('warning', 'No existe el destinatario o no tienes permiso para mandar este mensaje', 'redacta.html');
}

/**
 *
 */
function mRedactarIndividual()
{
  global $web, $periodo, $accion;

  $receptor = "";
  if (isset($_GET['info2'])) {
    $receptor = $_GET['info2'];
  } else {
    mMessage('warning', "Falta información", 'redacta.html');
  }

  $grupo = "";
  if (isset($_GET['info1'])) {
    $grupo = $_GET['info1'];
  }

  $sql   = "SELECT cveusuario FROM usuarios WHERE cveusuario=?";
  $datos = $web->DB->GetAll($sql, $receptor);
  if (isset($datos[0])) {
    $sql = "SELECT * FROM lectura
      INNER JOIN abecedario ON abecedario.cve = lectura.cveletra
      WHERE abecedario.letra=? AND lectura.cveperiodo=?";
    $datos_g = $web->DB->GetAll($sql, array($grupo, $periodo));
    if (isset($datos_g[0])) {
      $web->smarty->assign('receptor', $receptor);
      $web->smarty->assign('accion', $accion);

      if (isset($periodo)) {
        $web->smarty->assign('cveperiodo', $periodo);
      }

      $sql   = "SELECT cve FROM abecedario WHERE letra=?";
      $datos = $web->DB->GetAll($sql, $grupo);
      $web->smarty->assign('grupo', $datos[0]['cve']);
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
  $web = new RedactaControllers;

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

  $max_size = 2000000;
  if ($_FILES['archivo']['size'] > 0) {
    if ($_FILES['archivo']['size'] <= $max_size) {

      $dir_subida = "/home/slslctr/archivos_msj/" . $periodo . "/";
      $nombre     = $_FILES['archivo']['name'];
      $data       = $web->getNameAndExtension($nombre);
      $fileRoute  = $dir_subida . $receptor . "_" . $data[0] . "_"; //prepara la ruta
      $numFiles   = $web->countFiles($fileRoute); //cuenta cuántos archivos ya hay con esa ruta
      $fileRoute .= ($numFiles + 1) . $data[1]; //completa la ruta agregando el número de archivo y extensión

      if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $fileRoute)) {
        header('Location: grupos.php?aviso=3'); // Ocurrio un error al enviar el mensaje
        die();
      }

      $nombre   = explode("/", $fileRoute);
      $nameSize = count($nombre);
      $nombre   = $nombre[($nameSize - 1)];
    }
  }

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
  die();
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
