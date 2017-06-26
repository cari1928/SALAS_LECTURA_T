<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web->iniClases('promotor', "index grupos redactar");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$accion  = $para  = "";
$periodo = $web->periodo();
if ($periodo == "") {
  $web->simple_message('danger', 'No hay periodo actual');
  $web->smarty->display('redacta.html');
  die();
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
    $sql   = "SELECT cve FROM abecedario WHERE letra=?";
    $datos = $web->DB->GetAll($sql, $para);
    $letra = $datos[0]['cve'];

    $sql   = "SELECT * FROM lectura WHERE cveperiodo=? AND cveletra=? AND cvepromotor=?";
    $datos = $web->DB->GetAll($sql, array($periodo, $letra, $_SESSION['cveUser']));
    if (isset($datos[0])) {
      $web->smarty->assign('para', $para);
      $web->smarty->assign('cveperiodo', $periodo);
      $web->smarty->display('redacta.html');
      exit();
    }
    $web->smarty->assign('msj',
      "No existe el destinatario o no tienes permiso para mandar este mensaje");
    $web->smarty->display('redacta.html');
    exit();
    break;

  case 'redactarI':
    $receptor = "";
    if (isset($_GET['info2'])) {
      $receptor = $_GET['info2'];
    } else {
      $web->smarty->assign('msj', "Falta información");
      $web->smarty->display('redacta.html');
      die();
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

    $web->smarty->assign('msj',
      "No existe el destinatario o no tienes permiso para mandar este mensaje");
    $web->smarty->display('redacta.html');
    exit();
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
    } else {
      $web->smarty->assign('msj', "No se pudo mandar el mensaje");
      $web->smarty->display('redacta.html');
    }
    break;

  case 'enviarI':
    $receptor = $cveletra = "";
    if (isset($_GET['receptor'])) {
      $receptor = $_GET['receptor'];
    }
    if (isset($_GET['para'])) {
      $cveletra = $_GET['para'];
    }

    $sql = "SELECT * FROM lectura
        INNER JOIN laboral ON laboral.cveletra = lectura.cveletra
        INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
        WHERE laboral.cvepromotor=? AND lectura.nocontrol=?";
    $datos = $web->DB->GetAll($sql, array(
      $_SESSION['cveUser'],
      $receptor,
    ));

    if (isset($datos[0])) {
      if (isset($_POST)) {
        $sql = "INSERT INTO msj(introduccion, descripcion, tipo, emisor, fecha, expira, receptor, cveletra, cveperiodo)
        VALUES (?, ?,'I', ?,'" . date('Y-m-j') . "', ?, ?, ?, ?)";
        $parameters = array(
          $_POST['introduccion'],
          $_POST['descripcion'],
          $_SESSION['cveUser'],
          $_POST['expira'],
          $receptor,
          $cveletra,
          $periodo,
        );
        $web->query($sql, $parameters);
      } else {
        $web->smarty->assign('msj', "No se pudo mandar el mensaje");
        $web->smarty->display('redacta.html');
      }
    } else {
      $web->smarty->assign('msj', "No existe el destinatario o no tienes permiso para mandar este mensaje");
    }
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
      $datos = $web->DB->GetAll($sql, array(
        $grupo,
        $periodo,
        $_SESSION['cveUser'],
      ));
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
