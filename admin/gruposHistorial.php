<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
    $web->checklogin();
}

$web->iniClases('admin', "index historial grupos");

if (isset($_GET['info1'])) {
    $sql = "select distinct letra,nombre, cveusuario from abecedario
        inner join lectura on lectura.cveletra=abecedario.cve
        inner join usuarios on usuarios.cveusuario=lectura.cvepromotor
        where cveperiodo=" . $_GET['info1'] . "
        order by nombre";

    $tabla = $web->showTable($sql, "grupoHistorial", 5, 1, 'grupos', '&info2=' . $_GET['info1']);
    $web->smarty->assign('tabla', $tabla);
    $web->smarty->display('grupos.html');

} else {
    $web->smarty->assign('tabla', "<label style='color:red'>Hacen falta datos</label>");
    $web->smarty->display('grupos.html');
}
