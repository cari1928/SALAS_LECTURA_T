<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'P') { 
    $web->checklogin(); 
}

$web->iniClases('promotor', "index");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);
$web->smarty->display('index.html');