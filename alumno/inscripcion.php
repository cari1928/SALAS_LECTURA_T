<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'U') {
    $web->checklogin();
}

$grupos = $web->grupos($_SESSION['cveUser']);
$web->iniClases('usuario', "index inscripcion");
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodos actuales');
  $web->smarty->display("inscripcion.html");
  die();
}

if(isset($_GET['info'])) {
  //verifica que el grupo mandado exista
  $sql = "select * from laboral 
  where cveletra in (select cve from abecedario where letra=?)
    and cveperiodo=?";
  $datos = $web->DB->GetAll($sql, array($_GET['info'], $cveperiodo));
  if(!isset($datos[0])) {
    $web->simple_message('danger', 'No modifique la estructura de la interfaz');
    $web->smarty->display("inscripcion.html");
    die();
  }
  
  $query = "select * from lectura 
  where nocontrol=? and cveletra in (select cve from abecedario 
    where cve in (select cveletra from laboral where cveletra=? and cveperiodo=?))";
  $alumno = $web->DB->GetAll($query, array($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo));
  if(isset($alumno[0])) {
    $web->simple_message('danger', 'Ya está inscrito');
    $web->smarty->display("inscripcion.html");
    die();
  }
  
  $web->DB->startTrans();
  $sql = "INSERT INTO lectura(nocontrol, cveletra, cveperiodo) values(?, ?, ?)";
  $web->query($sql, array($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo));
  
  $cvelectura = $web->DB->GetAll($query, array($_SESSION['cveUser'], $datos[0]['cveletra'], $cveperiodo));
  
  $sql = "INSERT INTO evaluacion(comprension, participacion, terminado, asistencia, actividades, cvelectura, reporte) values(0, 0, 0, 0, 0, ?, 0)";
  $web->query($sql, $cvelectura[0]['cvelectura']);
  
  if($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No fue posible realizar la inscripción');
    $web->smarty->display("inscripcion.html");
    die();
  }
  $sql = "select * from lectura where nocontrol = ? and cveperiodo = ?";
  $lectura_folder = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));
  if(isset($lectura_folder[0])){
      $sql = "select letra from abecedario where cve = ?";
      $letra_folder = $web->DB->GetAll($sql, $datos[0]['cveletra']);
      if(isset($letra_folder[0][0])){
        mkdir("../periodos/" . $cveperiodo. "/" . $letra_folder[0][0] . "/" . $_SESSION['cveUser'] , 0777); 
      }
  }
  
  $web->DB->CompleteTrans();
  header('Location: grupos.php');
    
}

//se verifica si el alumno ya está inscrito a algún grupo
$sql = "select * from lectura 
where nocontrol=? and cveperiodo=?";
$datos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));
if(sizeof($datos) == 1) {
  $web->simple_message('warning', 'Ya está registrado a un grupo, no puede registrar más');

} else {
  $sql = "select distinct letra, laboral.nombre as \"nombre_grupo\", laboral.cvesala as \"cvesala\", ubicacion, usuarios.nombre as \"nombre_promotor\" from laboral
  inner join abecedario on abecedario.cve = laboral.cveletra
  inner join sala on sala.cvesala = laboral.cvesala
  inner join usuarios on laboral.cvepromotor = usuarios.cveusuario
  where laboral.cveperiodo=? order by letra";
  $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
  $datos = $web->DB->GetAll($sql, $cveperiodo);
  $datos = array('data'=>$datos);
  
  for ($i = 0; $i < sizeof($datos['data']); $i++) {
     $datos['data'][$i]['letra'] = "<a href='inscripcion.php?info=".$datos['data'][$i]['letra']."'>".$datos['data'][$i]['letra']."</a>";
  }
  
  $web->DB->SetFetchMode(ADODB_FETCH_NUM); 
  $datos = json_encode($datos);
  
  $file = fopen("TextFiles/inscripcion.txt", "w");
  fwrite($file, $datos);
  
  $web->smarty->assign('datos', $datos);
}

$web->smarty->display("inscripcion.html");
