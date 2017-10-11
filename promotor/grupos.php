<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {$web->checklogin();}

$web = new PromoGruposControllers;
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
      form_update();
      break;

    case 'update':
      update();
      break;
  }
}

$horas       = $web->getSchedule($_SESSION['cveUser'], $cveperiodo);
$tablegrupos = $web->getReading($_SESSION['cveUser'], $cveperiodo);
if (!isset($tablegrupos[0])) {
  $web->simple_message('danger', 'No ha registrado algún grupo');
}

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
 * Muestra avisos de error e informativos
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
      case 9:
        $web->simple_message('danger', 'No es posible descargar el archivo');
        break;
    }
  }
}

/**
 * Muestra el formulario para actualizar
 */
function form_update()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info'])) {
    $web->simple_message('danger', 'No se especificó el grupo');
    break;
  }

  $grupo = $web->getLaboral($_GET['info'], $cveperiodo);
  if (!isset($grupo[0])) {
    $web->simple_message('danger', 'No existe el grupo seleccionado');
    break;
  }

  $web->iniClases('promotor', "index grupos actualizar");
  $web->smarty->assign('grupos', $grupo[0]);
  $web->smarty->display('form_vergrupos.html');
  die();
}

/**
 * Realiza la actualización
 */
function update()
{
  global $web, $cveperiodo;

  if (!isset($_POST['datos']['nombre'])) {
    $web->simple_message('warning', "No alteres la estructura de la interfaz");
    break;
  }
  if ($_POST['datos']['nombre'] == "") {
    $web->simple_message('warning', "Llena todos los campos");
    break;
  }

  $web->updateLaboral($_POST['datos']['nombre'], $_POST['datos']['cveletra'], $cveperiodo);
  header('Location: grupos.php');
}
