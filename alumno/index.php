<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web->iniClases('usuario', "index");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);
$web->smarty->display('index.html');
