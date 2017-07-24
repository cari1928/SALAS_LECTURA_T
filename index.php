<?php
include 'sistema.php';

$date  = getdate();
$fecha = date('Y-m-j');

if (isset($_GET['m'])) {
  switch ($_GET['m']) {
    case 1:
      $web->simple_message('warinig', 'No se ha iniciado sesión');
      break;
    case 2:
      $web->simple_message('warning', 'Falta información para poder mostrar el foro');
      break;
    case 3:
      $web->simple_message('danger', 'Foro-Libro no encontrado');
      break;
  }
}

$sql = "SELECT introduccion, cvemsj FROM msj
WHERE tipo='PU' AND expira >= ?
ORDER BY fecha";
$mensajes = $web->muestraMSJ($sql, $fecha);
$web->smarty->assign('mensaje', $mensajes);
$web->smarty->display('index.html');
