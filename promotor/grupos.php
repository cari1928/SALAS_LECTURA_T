<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {$web->checklogin();}

$web->iniClases('promotor', "index grupos");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodo actual');
  $web->smarty->display('vergrupos.html');
  die();
}

mShowMessages();

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_update':
      if (!isset($_GET['info'])) {
        $web->simple_message('danger', 'No se especificó el grupo');
        break;
      }

      $sql = "SELECT * FROM laboral WHERE cveletra IN
          (SELECT cve FROM abecedario WHERE letra=?)";
      $grupo = $web->DB->GetAll($sql, $_GET['info']);

      if (!isset($grupo[0])) {
        $web->simple_message('danger', 'No existe el grupo seleccionado');
        break;
      }

      $web->iniClases('promotor', "index grupos actualizar");
      $web->smarty->assign('grupos', $grupo[0]);
      $web->smarty->display('form_vergrupos.html');
      die();
      break;

    case 'update':
      if (!isset($_POST['datos']['nombre'])) {
        $web->simple_message('warning', "No alteres la estructura de la interfaz");
        break;
      }

      if ($_POST['datos']['nombre'] == "") {
        $web->simple_message('warning', "Llena todos los campos");
        break;
      }

      $nombre   = $_POST['datos']['nombre'];
      $cveletra = $_POST['datos']['cveletra'];

      $sql = "UPDATE laboral SET nombre=? WHERE cveletra=?";
      $web->query($sql, array($nombre, $cveletra));
      header('Location: grupos.php');
      break;
  }
}

$sql = "SELECT letra, la.nombre, ubicacion
  FROM lectura le
  INNER JOIN abecedario abc ON abc.cve = le.cveletra
  INNER JOIN laboral la ON la.cveletra = abc.cve
  INNER JOIN sala s ON s.cvesala = la.cvesala
  WHERE la.cveperiodo=? AND le.cveperiodo=? AND nocontrol=?
  ORDER BY letra";
$tablegrupos = $web->DB->GetAll($sql, array($cveperiodo, $cveperiodo, $_SESSION['cveUser']));
if (!isset($tablegrupos[0])) {
  $web->simple_message('danger', 'No ha registrado algún grupo');
}

$sql = "SELECT dia.cvedia, abecedario.letra, dia.nombre, horas.hora_inicial, horas.hora_final
FROM laboral
INNER JOIN dia ON dia.cvedia=laboral.cvedia
INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
INNER JOIN horas ON horas.cvehoras = laboral.cvehoras
WHERE cvepromotor=? AND laboral.cveperiodo=?
ORDER BY letra, dia.cvedia, horas.hora_inicial";
$horas = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));

for ($i = 0; $i < sizeof($tablegrupos); $i++) {
  $tablegrupos[$i]['horario'] = "";

  for ($j = 0; $j < sizeof($horas); $j++) {

    if ($tablegrupos[$i]['letra'] == $horas[$j]['letra']) {
      $tablegrupos[$i]['horario'] .= $horas[$j]['nombre'] . ' - ' . $horas[$j]['hora_inicial'] . ' a ' . $horas[$j]['hora_final'] . "<br>";
    }
  }
}

$web->smarty->assign('tablegrupos', $tablegrupos);
$web->smarty->display('vergrupos.html');

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
/**
 *
 */
function mShowMessages()
{
  global $web;

  if (isset($_GET['aviso'])) {
    switch ($_GET['aviso']) {
      case 1:
        $web->simple_message('warning', 'Ya existe un archivo con el mismo nombre');
        break;
      case 2:
        $web->simple_message('info', 'Mensaje publicado');
        break;
      case 3:
        $web->simple_message('danger', 'Ocurrió un error mientras se enviaba el mensaje');
        break;
      case 4:
        $web->simple_message('warning', 'No existe el destinatario o no tiene permiso para mandar este mensaje');
        break;
      case 5:
        $web->simple_message('warning', 'El archivo no existe o fue eliminado');
        break;
      case 6:
        $web->simple_message('warning', 'Hacen falta datos para mostrar los mensajes');
        break;
      case 7:
        $web->simple_message('warning', 'No existe el mensaje seleccionado');
        break;
      case 8:
        $web->simple_message('warning', 'No existe el grupo seleccionado');
        break;
    }
  }
}
