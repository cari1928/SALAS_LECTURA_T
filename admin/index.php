<?php
include '../sistema.php';

if ($_SESSION['roles'] == 'A') {
  $web->iniClases('admin', "index");

  //la opción Querys solo está disponible para DIOS
  if ($_SESSION['cveUser'] == '9999999999999') {
    $web->smarty->assign('especial', 'especial');
  }

  if (isset($_GET['e'])) {
    switch ($_GET['e']) {
      case 1:
        $web->simple_message('warning', 'No modifique la estructura de la interfaz');
        break;
    }
  }

  if(isset($_GET['aviso'])) {
    switch ($_GET['aviso']) {
      case 1:
        $web->simple_message('success', 'Se subió el reporte satisfactoriamente');
        break;
  
      case 2:
        $web->simple_message('warning', 'Ocurrió un error mientras se subia el archivo');
        break;
    } 
  }

  $web->smarty->display('index.html');

} else {
  $web->checklogin();
}
