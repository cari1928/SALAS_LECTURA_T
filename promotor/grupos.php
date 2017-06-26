<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') { $web->checklogin(); }

$web->iniClases('promotor', "index grupos");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodo actual');
  $web->smarty->display('vergrupos.html');
  die();
}

if (isset($_GET['accion'])) {

    switch ($_GET['accion']) {

      case 'form_update':
        if (!isset($_GET['info'])) {
            $web->simple_message('danger', 'No se especificó el grupo');
            break;
        }

        $sql = "select * from laboral where cveletra in
          (select cve from abecedario where letra=?)";
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
            $web->simple_message('danger', "No alteres la estructura de la interfaz");
            break;
        }

        if ($_POST['datos']['nombre'] == "") {
            $web->simple_message('danger', "Llena todos los campos");
            break;
        }

        $nombre = $_POST['datos']['nombre'];
        $cveletra = $_POST['datos']['cveletra'];
        
        $sql = "update laboral set nombre=? where cveletra=?";
        $web->query($sql, array($nombre, $cveletra));
        header('Location: grupos.php');
        break;
    }
}

$sql = "select distinct letra, nombre, ubicacion, titulo from laboral
inner join sala on laboral.cvesala = sala.cvesala
inner join abecedario on laboral.cveletra = abecedario.cve
left join libro on laboral.cvelibro_grupal = libro.cvelibro
where cvepromotor=? and laboral.cveperiodo=?
order by letra";
$tablegrupos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));

if (!isset($tablegrupos[0])) {
  $web->simple_message('danger', 'No ha registrado algún grupo');
}

$sql = "select dia.cvedia, abecedario.letra, dia.nombre, horas.hora_inicial, horas.hora_final 
from laboral
inner join dia on dia.cvedia=laboral.cvedia
inner join abecedario on laboral.cveletra = abecedario.cve
inner join horas on horas.cvehoras = laboral.cvehoras
where cvepromotor=? and laboral.cveperiodo=? 
order by letra, dia.cvedia, horas.hora_inicial";
$horas = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));

for($i=0; $i<sizeof($tablegrupos); $i++){
  $tablegrupos[$i]['horario']="";
  
  for($j=0; $j<sizeof($horas); $j++){
    
    if($tablegrupos[$i]['letra'] == $horas[$j]['letra']){
      $tablegrupos[$i]['horario'] .= $horas[$j]['nombre'] . ' - ' . $horas[$j]['hora_inicial'] . ' a ' . $horas[$j]['hora_final'] . "<br>";    
    }  
  }
}

$web->smarty->assign('tablegrupos', $tablegrupos);
$web->smarty->display('vergrupos.html');