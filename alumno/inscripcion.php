<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

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
  $sql = "SELECT * FROM laboral
  WHERE cveletra IN (SELECT cve FROM abecedario WHERE letra=?)
  AND cveperiodo=?";
  $datos = $web->DB->GetAll($sql, array($_GET['info'], $cveperiodo));
  if (!isset($datos[0])) {
    $web->simple_message('danger', 'No modifique la estructura de la interfaz');
    $web->smarty->display("inscripcion.html");
    die();
  }

  $query = "SELECT * FROM lectura
  WHERE nocontrol=? AND cveletra IN
    (SELECT cve FROM abecedario
    WHERE cve IN
      (SELECT cveletra FROM laboral WHERE cveletra=? AND cveperiodo=?))";
  $alumno = $web->DB->GetAll($query, array($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo));
  if (isset($alumno[0])) {
    $web->simple_message('danger', 'Ya está inscrito');
    $web->smarty->display("inscripcion.html");
    die();
  }

  $web->DB->startTrans();
  $sql = "INSERT INTO lectura(nocontrol, cveletra, cveperiodo) VALUES(?, ?, ?)";
  $web->query($sql, array($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo));

  $cvelectura = $web->DB->GetAll($query, array($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo));

  $sql = "INSERT INTO evaluacion(comprension, participacion, terminado, asistencia, actividades, cvelectura, reporte) VALUES(0, 0, 0, 0, 0, ?, 0)";
  $web->query($sql, $cvelectura[0]['cvelectura']);

  if ($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No fue posible realizar la inscripción');
    $web->smarty->display("inscripcion.html");
    die();
  }
  $sql            = "SELECT * FROM lectura WHERE nocontrol=? AND cveperiodo=?";
  $lectura_folder = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));
  if (isset($lectura_folder[0])) {
    $sql          = "SELECT letra FROM abecedario WHERE cve=?";
    $letra_folder = $web->DB->GetAll($sql, $datos[0]['cveletra']);
    if (isset($letra_folder[0][0])) {
      mkdir("../periodos/" . $cveperiodo . "/" . $letra_folder[0][0] . "/" . $_SESSION['cveUser'], 0777);
    }
  }
  $web->DB->CompleteTrans();
  header('Location: grupos.php');
}

//se verifica si el alumno ya está inscrito a algún grupo
$sql   = "SELECT * FROM lectura WHERE nocontrol=? AND cveperiodo=?";
$datos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));
if (sizeof($datos) == 1) {
  $web->simple_message('warning', 'Ya está registrado a un grupo, no puede registrar más');

} else {
  $sql = "SELECT DISTINCT letra, laboral.nombre AS \"nombre_grupo\", laboral.cvesala AS \"cvesala\",
  ubicacion, usuarios.nombre AS \"nombre_promotor\"
  FROM laboral
  INNER JOIN abecedario ON abecedario.cve = laboral.cveletra
  INNER JOIN sala ON sala.cvesala = laboral.cvesala
  INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
  WHERE laboral.cveperiodo=?
  ORDER BY letra";
  $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
  $datos = $web->DB->GetAll($sql, $cveperiodo);
  $datos = array('data' => $datos);

  for ($i = 0; $i < sizeof($datos['data']); $i++) {
    $datos['data'][$i]['letra'] = "<a href='inscripcion.php?info=" . $datos['data'][$i]['letra'] . "'>" . $datos['data'][$i]['letra'] . "</a>";
  }

  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $datos = json_encode($datos);

  $file = fopen("TextFiles/inscripcion.txt", "w");
  fwrite($file, $datos);

  $web->smarty->assign('datos', $datos);
}

$web->smarty->display("inscripcion.html");
