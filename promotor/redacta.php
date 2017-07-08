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
    $datos = $web->DB->GetAll($sql, $_GET['info']);
    if (!isset($datos[0])) {
      $web->simple_message('warning', 'No modifique la interfaz');
      $web->smarty->display('redacta.html');
      die();
    }

    $letra = $datos[0]['cve'];
    $sql   = "SELECT * FROM lectura WHERE cveperiodo=? AND cveletra=? AND cveletra in
              (SELECT cveletra FROM laboral WHERE cvepromotor = ? and cveperiodo = ?)";
    $datos = $web->DB->GetAll($sql, array($periodo, $letra, $_SESSION['cveUser'], $periodo));
    if (isset($datos[0])) {
      $web->smarty->assign('para', $para);
      $web->smarty->assign('cveperiodo', $periodo);
      $web->smarty->display('redacta.html');
      exit();
    }

    $web->simple_message('warning', 'No existe el destinatario o no tienes permiso para mandar este mensaje');
    $web->smarty->display('redacta.html');
    die();
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
      $max_size = 2000000;
      // $web->debug($_FILES);
      if ($_FILES['archivo']['size'] > 0) {
        if ($_FILES['archivo']['size'] <= $max_size) {
          $dir_subida = "/home/slslctr/archivos/msj/" . $periodo . "/";
          $nombre     = $_FILES['archivo']['name'];

          if (file_exists($dir_subida . $nombre)) {
            header('Location: grupos.php?aviso=1'); // ya existe un archivo con este mismo nombre por favor cambie el nombre
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
            die();
          } else {
            header('Location: grupos.php?aviso=3'); // Se envio el mensaje satisfactoriamente
            die();
          }
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

    //$web->debug($_FILES);
    if (!isset($datos[0])) {
      header('Location: grupos.php?aviso=4'); //No existe el destinatario o no tienes permiso para mandar este mensaje
    }

    if (isset($_POST)) {
      $encabezado = "";
      $contenido  = "";
      $max_size   = 2000000;
      if ($_FILES['archivo']['size'] > 0) {
        if ($_FILES['archivo']['size'] <= $max_size) {
          $dir_subida = "/home/slslctr/archivos/msj/" . $periodo . "/";
          $nombre     = $_FILES['archivo']['name'];

          if (file_exists($dir_subida . $nombre)) {
            header('Location: grupos.php?aviso=1'); // ya existe un archivo con este mismo nombre por favor cambie el nombre
          }

          if (move_uploaded_file($_FILES['archivo']['tmp_name'], $dir_subida . $nombre)) {
            $sql = "INSERT INTO msj(introduccion, descripcion, tipo, emisor, fecha, expira, receptor, cveletra, cveperiodo, archivo)
                VALUES (?, ?,'I', ?,'" . date('Y-m-j') . "', ?, ?, ?, ?, ?)";
            $parameters = array(
              $_POST['introduccion'],
              $_POST['descripcion'],
              $_SESSION['cveUser'],
              $_POST['expira'],
              $receptor,
              $cveletra,
              $periodo,
              $nombre,
            );
            $web->query($sql, $parameters);
            header('Location: grupos.php?aviso=2'); // Se envio el mensaje satisfactoriamente
          } else {
            header('Location: grupos.php?aviso=3'); // Ocurrio un error al enviar el mensaje
          }
        } else {
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
        }
      }
    } else {
      $web->smarty->assign('msj', "No se pudo mandar el mensaje");
      $web->smarty->display('redacta.html');
    }
    die('error');
    // } else {
    //   $web->smarty->assign('msj', "No existe el destinatario o no tienes permiso para mandar este mensaje");
    // }
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
      //$web->debug_line($sql);
      $parameters = array(
        $grupo,
        $periodo,
        $_SESSION['cveUser']);
      //$web->debug($parameters);
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
