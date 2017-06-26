<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
    $web->checklogin();
}

if (isset($_GET['info4'])) {
    $cvepromotor = $_SESSION['cveUser'];
    $cvesala     = $_GET['info1'];
    $nocontrol   = '00000000';
    $cveperiodo  = $_GET['info4'];
    $cvehorario  = $_GET['info2'];

    $sql = "select COALESCE(MAX(cveletra),0) as cveletra from lectura l where l.cveperiodo in (select cveperiodo from lectura where cveperiodo='" . periodo($web) . "') and cvepromotor='" . $cvepromotor . "'";

    $datos_rs = $web->DB->GetAll($sql);
    $letra    = $datos_rs[0]['cveletra'];

    $sql = "insert into lectura (cvepromotor,cvesala,nocontrol,cveperiodo,horario,cvelibro,cveletra) values ('" . $cvepromotor . "','" . $cvesala . "','" . $nocontrol . "'," . $cveperiodo . ",'" . $cvehorario . "',0," . ($letra + 1) . ")";

    $web->query($sql);
    header('Location: vergrupos.php');

} else {
    header('Location: index.php');
}

/**
 * Obtiene el periodo actual para devolver el id correspondiente
 * @param  [Sistema] $web [Objeto de tipo Sistema para poder hacer uso de sus métodos]
 * @return [String|int] [Regresa la clave del periodo actual o un mensaje indicando su nula existencia]
 */
function periodo($web)
{
    $sql      = "select * from periodo";
    $datos_rs = $web->DB->GetAll($sql);

    $date     = getdate();
    $fechaAct = $date['year'] . "-" . $date['mon'] . "-" . $date['mday'];
    $date1    = new DateTime($fechaAct);

    $cont = 0;

    while ($cont < count($datos_rs)) {
        $date2 = new DateTime($datos_rs[$cont]['fechainicio']);
        $date3 = new DateTime($datos_rs[$cont]['fechafinal']);

        if ($date1 >= $date2 && $date1 <= $date3) {
            $cveperiodo = $datos_rs[$cont]['cveperiodo'];
        }
        $cont++;
    }

    if (isset($cveperiodo)) {
        $sql     = "select fechainicio,fechafinal from periodo where cveperiodo='" . $cveperiodo . "'";
        $datos   = $web->DB->GetAll($sql);
        $periodo = "El periodo es: " . $datos[0]['fechainicio'] . " a " . $datos[0]['fechafinal'];

        $web->smarty->assign('periodo', $periodo);
        return $cveperiodo;

    } else {
        $web->smarty->assign('periodo', "No hay periodos actuales");
        return "";
    }
}
