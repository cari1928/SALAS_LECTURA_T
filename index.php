<?php
include 'sistema.php';
$date = getdate();
$fecha = date('Y-m-j');

if(isset($_GET['m'])) {
  switch($_GET['m']) {
      case 1:
        $web->simple_message('info', 'No se a iniciado sesión');
        break;
  }
}

$sql="select introduccion, cvemsj from msj 
where tipo='PU' and expira >= '".$fecha."' order by fecha";
$mensajes=$web->muestraMSJ($sql,'PU');

$web->smarty->assign('mensaje', $mensajes);
$web->smarty->display('index.html');
?>