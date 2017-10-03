<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web    = new InscripcionControllers;
$grupos = $web->grupos($_SESSION['cveUser']);
$web->iniClases('usuario', "index inscripcion");
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodos actuales');
  $web->smarty->display("inscripcion.html");
  die();
}

if (isset($_GET['info'])) {
  //verifica que el grupo mandado exista
  $datos = $web->checkGroup($_GET['info'], $cveperiodo);
  if (!isset($datos[0])) {
    $web->simple_message('danger', 'No modifique la estructura de la interfaz');
    $web->smarty->display("inscripcion.html");
    die();
  }

  //REVISA SI YA ESTÁ INSCRITO AL GRUPO SELECCIONADO
  $alumno = $web->isRolledOnGroup($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo);
  if (isset($alumno[0])) {
    $web->simple_message('warning', 'Ya está inscrito');
    $web->smarty->display("inscripcion.html");
    die();
  }

  $web->DB->startTrans();
  $web->insertReading($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo); //inserta en tabla lectura
  //ya hay trigger para insertar en evaluacion

  if ($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No fue posible realizar la inscripción');
    $web->smarty->display("inscripcion.html");
    die();
  }

  $lectura_folder = $web->getReading($_SESSION['cveUser'], $cveperiodo);
  if (isset($lectura_folder[0])) {
    $letra_folder = $web->getLetter($datos[0]['cveletra']);
    if (isset($letra_folder[0][0])) {
      mkdir("../archivos/periodos/" . $cveperiodo . "/" . $letra_folder[0][0] . "/" . $_SESSION['cveUser'], 0777, true);
    }
  }
  $web->DB->CompleteTrans();
  header('Location: grupos.php?aviso=9');
  die();
}

//se verifica si el alumno ya está inscrito a algún grupo
$datos = $web->isRolledOn($_SESSION['cveUser'], $cveperiodo);
if (sizeof($datos) == 1) {
  $web->simple_message('warning', 'Ya está registrado a un grupo, no puede registrar más');

} else {

  $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
  $datos = array('data' => $web->listGroups($cveperiodo));

  for ($i = 0; $i < sizeof($datos['data']); $i++) {
    $datos['data'][$i]['letra'] = "<a href='inscripcion.php?info=" . $datos['data'][$i]['letra'] . "'>" . $datos['data'][$i]['letra'] . "</a>";
  }

  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $datos = json_encode($datos);
  $file  = fopen("TextFiles/inscripcion.txt", "w");
  fwrite($file, $datos);
  $web->smarty->assign('datos', $datos);
}
$web->smarty->display("inscripcion.html");

/************************************************************************************
 * FUNCIONES
 ************************************************************************************/
