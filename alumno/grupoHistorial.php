<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
  die();
}

$web->iniClases('admin', "index historial grupo");

if (!(isset($_GET['info2']))) {
  header('location : historial.php');
  exit;
}

if (isset($_GET['info1'])) {
  $sql = "select distinct sala.cvesala,ubicacion,sala.horario,fechainicio,fechafinal from lectura inner join sala on sala.cvesala=lectura.cvesala and lectura.horario=sala.horario inner join periodo on periodo.cveperiodo = lectura.cveperiodo where cveletra in (select cve from abecedario where letra ='" . $_GET['info1'] . "') and lectura.cveperiodo='" . $_GET['info2'] . "' and lectura.cvepromotor='" . $_GET['info'] . "'";
  echo $sql . "<br>";
  $datos_rs = $web->DB->GetAll($sql);

  $info = "Sala: " . $datos_rs[0]['cvesala'] . "<br>";
  $info .= "Ubicacion: " . $datos_rs[0]['ubicacion'] . "<br>";
  $info .= "Horario: " . $datos_rs[0]['horario'] . "<br>";
  $info .= "Periodo: " . $datos_rs[0]['fechainicio'] . " : " . $datos_rs[0]['fechafinal'];
  $web->smarty->assign('info', $info);

  $tabla = $web->evaluacion($_GET['info1'], "none", array('promoaux' => $_GET['info'], 'periodaux' => $_GET['info2']));
  $web->smarty->assign('tabla', $tabla);
  $web->smarty->display("grupo.html");

} else {
  if (isset($_POST['datos'])) {
    $sql      = "select distinct sala.cvesala,ubicacion,sala.horario,fechainicio,fechafinal from lectura  inner join sala on sala.cvesala=lectura.cvesala and lectura.horario=sala.horario inner join periodo on periodo.cveperiodo = lectura.cveperiodo where cveletra in (select cve from abecedario where letra ='" . $_POST['datos']['grupo'] . "') and cveperiodo='" . $_GET['info2'] . "'";
    $datos_rs = $web->DB->GetAll($sql);

    $info = "Sala: " . $datos_rs[0]['cvesala'] . "<br>";
    $info .= "Ubicacion: " . $datos_rs[0]['ubicacion'] . "<br>";
    $info .= "Horario: " . $datos_rs[0]['horario'] . "<br>";
    $info .= "Periodo: " . $datos_rs[0]['fechainicio'] . " : " . $datos_rs[0]['fechafinal'];
    $web->smarty->assign('info', $info);

    $sql = "update evaluacion set comprension='" . $_POST['datos']['comprension'] . "',motivacion='" . $_POST['datos']['motivacion'] . "',reporte='" . $_POST['datos']['reporte'] . "',tema='" . $_POST['datos']['tema'] . "',participacion='" . $_POST['datos']['participacion'] . "',terminado='" . $_POST['datos']['terminado'] . "' where cveeval='" . $_POST['datos']['cveeval'] . "'";
    $web->query($sql);

    $tabla = $web->evaluacion($_POST['datos']['grupo']);
    $web->smarty->assign('tabla', $tabla);
    header('Location: grupo.php?info1=' . $_POST['datos']['grupo']);

  } else {
    $web->smarty->assign('info', "");
    $tabla = "No hay informacion sobre algun grupo";
    $web->smarty->display("grupo.html");
  }

  $web->smarty->assign('tabla', $tabla);
}
