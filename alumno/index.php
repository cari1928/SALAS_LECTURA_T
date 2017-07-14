<?php
include '../sistema.php';
if ($_SESSION['roles'] == 'U') {
  $web->iniClases('usuario', "index");
  $grupos = $web->grupos($_SESSION['cveUser']);
  $web->smarty->assign('grupos', $grupos);

  if (isset($_GET['aviso'])) {

    switch ($_GET['aviso']) {

      case 1:
        $web->simple_message('success', 'Se subió correctamente la imagen');
        break;

      case 2:
        $web->simple_message('warning', 'Ocurrió un error mientras se subía la imagen');
        break;
    }
  }
  $web->smarty->display('index.html');

} else {
  $web->checklogin();
}
