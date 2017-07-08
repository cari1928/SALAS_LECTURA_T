<?php

include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web->iniClases('usuario', "index grupos grupo");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('danger', 'No hay periodos actuales', $web);
}

if (isset($_GET['accion'])) {
  switch ($_GET['accion']) {
    case 'listado':

      if (!isset($_GET['info'])) {
        deadMessage($web, 'warning', 'Falta información');
      }

      $sql = "SELECT * FROM laboral
      WHERE cveletra in (SELECT cve FROM abecedario WHERE letra=?)
      AND cveletra in (SELECT cveletra FROM lectura WHERE cveletra in
                            (SELECT cve FROM abecedario WHERE letra=?))
      AND laboral.cveperiodo=?";
      $grupo = $web->DB->GetAll($sql, array($_GET['info'], $_GET['info'], $cveperiodo));
      if (!isset($grupo[0])) {
        deadMessage($web, 'warning', 'El grupo no existe o no cuenta con los permisos para acceder');
      }

      $sql = "SELECT cvemsj, introduccion, tipomsj.descripcion, fecha, expira
      FROM msj
      INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
      WHERE receptor=?
      AND cveperiodo=?
      AND cveletra in (SELECT cve FROM abecedario WHERE letra=?)
      AND expira > NOW()";
      $mensajes = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo, $_GET['info']));

      if (!isset($mensajes[0])) {
        deadMessage($web, 'danger', 'No hay mensajes');
      }
      $web->smarty->assign('mensajes', $mensajes);
      $web->smarty->display('msj.html');
      break;

    case 'leer':
      $cvemsj = "";
      if (isset($_GET['info'])) {
        $cvemsj = $_GET['info'];
      }

      $sql     = "select * from msj where cvemsj=" . $cvemsj;
      $mensaje = $web->DB->GetAll($sql);
      $web->smarty->assign('mensaje', $mensaje);
      $web->smarty->assign('accion', $accion);
      //$web->debug($mensaje);
      if ($mensaje[0]['archivo'] != '') {
        $nombre_fichero = "/home/slslctr/archivos/msj/" . $cveperiodo . "/" . $mensaje[0]['archivo'];
        if (!file_exists($nombre_fichero)) {
          $mensaje[0]['archivo'] = "El archivo " . $mensaje[0]['archivo'] . " ha sido eliminado";
          $web->smarty->assign('eliminado', true);
        }
        $web->smarty->assign('archivo', $mensaje[0]['archivo']);
      }
      $web->smarty->display('msj.html');
      exit();
      break;

    case 'archivo':
      $nombre_fichero = "/home/slslctr/archivos/msj/" . $cveperiodo . "/" . $_GET['info'];
      if (!file_exists($nombre_fichero)) {
        header('Location: grupos.php?aviso=5'); //El archivo no existe
      }
      header("Content-disposition: attachment; filename=" . $_GET['info']);
      header("Content-type: MIME");
      readfile("/home/slslctr/archivos/msj/" . $cveperiodo . "/" . $_GET['info']);
      break;

  }
}

/*************************************************************************************************
 * FUNCIONES
 *************************************************************************************************/
function deadMessage($web, $type, $msg)
{
  $web->simple_message($type, $msg);
  $web->smarty->display('msj.html');
  die();
}
