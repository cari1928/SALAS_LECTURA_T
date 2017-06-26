<?php

include '../sistema.php';
$web->iniClases(null, "index");

$hola = $web->smarty->fetch('hola.html');
$web->smarty->assign('hola', $hola);

// $web->smarty->display('index.html');

$p = $web->smarty->fetch('prueba.html'); 

echo $p;
