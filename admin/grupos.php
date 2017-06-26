<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web->iniClases('admin', "index grupos");
$cveperiodo = $web->periodo();
//verifica que exista periodo actual
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodo actual');
  $web->smarty->display('grupos.html');
  die();
}

if(isset($_GET['accion'])) {
  switch($_GET['accion']) {
    case 'delete':
      delete_group($web);
      break;
  }
}

//su nombre es nocontrol para que funcione en grupos.html
$sql = "select distinct abecedario.letra, usuarios.cveusuario AS \"nocontrol\", 
usuarios.nombre AS \"nombre_promotor\", laboral.nombre, sala.ubicacion, laboral.cvepromotor, 
titulo from laboral
inner join sala on sala.cvesala = laboral.cvesala
inner join abecedario on abecedario.cve = laboral.cveletra
inner join usuarios on usuarios.cveusuario = laboral.cvepromotor
inner join libro on laboral.cvelibro_grupal = libro.cvelibro
where laboral.cveperiodo = ? order by abecedario.letra";
$grupos = $web->DB->GetAll($sql, $cveperiodo);

//verifica que haya grupos en el periodo actual
if (!isset($grupos[0])) {
  $web->simple_message('danger', 'No hay grupos registrados');
  $web->smarty->display('grupos.html');
  die();
}

//coloca horarios
$sql = "select abecedario.letra, dia.cvedia, hora_inicial,hora_final, dia.nombre from laboral
inner join horas on horas.cvehoras = laboral.cvehoras
inner join abecedario on abecedario.cve = laboral.cveletra
inner join dia on dia.cvedia = laboral.cvedia
where laboral.cveperiodo = ? 
order by abecedario.letra, dia.cvedia, hora_inicial";
$horas = $web->DB->GetAll($sql, $cveperiodo);

for ($i = 0; $i < sizeof($grupos); $i++) {
  $grupos[$i]['horario'] = "";
  
  for ($j = 0; $j < sizeof($horas); $j++) {
    
    if ($grupos[$i]['letra'] == $horas[$j]['letra']) {
      $grupos[$i]['horario'] .= $horas[$j]['nombre'] . ' - ' . $horas[$j]['hora_inicial'] . ' a ' . $horas[$j]['hora_final'] . "<br>";
    }
  }
}

$web->smarty->assign('tablegrupos', $grupos);
$web->smarty->assign('bandera', 'index_grupos');
$web->smarty->display('grupos.html');

function delete_group($web) {
  global $cveperiodo;
  
  //se valida la contraseña
  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;

    case 2:
      $web->simple_message('danger', 'La contraseña de seguridad ingresada no es válida');
      return false;
  }
  
  //se verifica que la letra haya sido mandada y sea válida
  if(!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No es posible continuar, hacen falta datos');
  }
  $sql = "select * from abecedario where letra=?";
  $letra = $web->DB->GetAll($sql, $_GET['info1']);
  if(!isset($letra[0])) {
    $web->simple_message('danger', 'No es posible que exista tal grupo');
  }
  
  $web->DB->startTrans();
  //se obtiene las del grupo
  $sql = "select * from lectura 
  where cveperiodo=? and cveletra in (select cve from abecedario where letra=?)";
  $lecturas = $web->DB->GetAll($sql, array($cveperiodo, $_GET['info1']));
  
  for ($i = 0; $i < sizeof($lecturas); $i++) {
     //elimina de evaluacion, lista_libros y lectura
     $sql = "delete from evaluacion where cvelectura=?";
     $web->query($sql, $lecturas[$i]['cvelectura']);
     $sql = "delete from lista_libros where cvelectura=?";
     $web->query($sql, $lecturas[$i]['cvelectura']);
     $sql = "delete from lectura where cvelectura=?";
     $web->query($sql, $lecturas[$i]['cvelectura']);
  }
  //elimina de msj y laboral
  $sql = "delete from msj 
  where cveperiodo=? and cveletra = (select cve from abecedario where letra=?)";
  $web->query($sql, array($cveperiodo, $_GET['info1']));
  $sql = "delete from laboral 
  where cveperiodo=? and cveletra = (select cve from abecedario where letra=?)";
  $web->query($sql, array($cveperiodo, $_GET['info1']));
  
  if($web->DB->HasFailedTrans()) {
    //falta programar esta parte para que no muestre directamente el resultado de sql
    $web = ADODB_Pear_Error();  
    echo $e->message;
  }
  
  $web->DB->CompleteTrans();
  return false;
}
