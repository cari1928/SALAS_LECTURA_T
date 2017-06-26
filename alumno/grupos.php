<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web->iniClases('usuario', "index grupos");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if($cveperiodo == "") {
  $web->smarty->assign('alert', 'danger');
  $web->smarty->assign('msg', 'No hay periodo actual');
  $web->smarty->display('vergrupos.html');
  die();
}

$sql = "select distinct letra, nombre, ubicacion from laboral 
  inner join abecedario on laboral.cveletra = abecedario.cve
  inner join lectura on lectura.cveletra = abecedario.cve 
  inner join sala on laboral.cvesala = sala.cvesala
  where nocontrol=? and laboral.cveperiodo=? order by letra";
$tablegrupos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));

if (isset($tablegrupos[0])) {
    $web->smarty->assign('tablegrupos', $tablegrupos);
} else {
  $web->smarty->assign('alert', 'danger');
  $web->smarty->assign('msg', 'No ha registrado algún grupo');
}

$web->smarty->display('vergrupos.html');