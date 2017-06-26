<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web->iniClases('promotor', "index salas");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodos actuales');
  $web->smarty->display("promosala.html");
  die();
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'horario':
      $web->iniClases('promotor', "index salas horario");

      if (!isset($_GET['info'])) {
        $web->simple_message('danger', 'No se especificó la sala');
        break;
      }

      $sql  = "select * from sala where cvesala=? and cveperiodo=?";
      $sala = $web->DB->GetAll($sql, array($_GET['info'], $cveperiodo));
      if (!isset($sala[0])) {
        $web->simple_message('danger', 'No existe la sala seleccionada');
        break;
      }

      $sql          = "select * from dia";
      $dias         = $web->DB->GetAll($sql);
      $horas_semana = array();
      for ($i = 1; $i <= sizeof($dias); $i++) {
        $sql = "select cvehoras, hora_inicial, hora_final from horas
        EXCEPT
        select horas.cvehoras, hora_inicial, hora_final from laboral
        inner join horas on laboral.cvehoras = horas.cvehoras
        inner join sala on sala.cvesala = laboral.cvesala
        where cvedia=? and ubicacion=? and laboral.cveperiodo=?
        order by hora_inicial, hora_final";
        $horas = $web->DB->GetAll($sql, array($i, $sala[0]['ubicacion'], $cveperiodo));

        if (isset($horas[0])) {
          $web->smarty->assign('horas' . $i, $horas);
        }
      }
      
      $sql    = 'select cvelibro, titulo from libro order by titulo';
      $libros = $web->combo($sql, null, "../");
      $web->smarty->assign('cvesala', $_GET['info']);
      $web->smarty->assign('libros', $libros);
      $web->smarty->assign('horas', 'horas');
      $web->smarty->display("promosala.html");
      die();
      break;

    case 'insert':
      register_room($web);
      break;

    default:
    	$web->simple_message('danger', 'No existe la acción seleccionada');
  }
}

//antes que nada se verifica si el promotor ya tiene 3 grupos
$sql = "select distinct cveletra from laboral where cvepromotor=? and cveperiodo=?";
$grupos = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo));
if(sizeof($grupos) == 3) {
  $web->simple_message('warning', " Ya tiene 3 grupos, no es posible registrar otros");

} else {
  $sql = "select cvesala, ubicacion from sala where cveperiodo=? order by cvesala";
	$web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
	$datos = $web->DB->GetAll($sql, $cveperiodo);
	$datos = array('data'=>$datos);
	
	for ($i = 0; $i < sizeof($datos['data']); $i++) {
	  $datos['data'][$i]['cvesala'] = "<a href='salas.php?accion=horario&info=" . $datos['data'][$i]['cvesala'] . "'>" . $datos['data'][$i]['cvesala'] . "</a>";
	}
	
	$web->DB->SetFetchMode(ADODB_FETCH_NUM);
	$datos = json_encode($datos);
	
	$file = fopen("TextFiles/promosala.txt", "w");
	fwrite($file, $datos);
	
	$web->smarty->assign('datos', $datos);
}

$web->smarty->display("promosala.html");

/**
 * Checa los casos:
 * 1 Campos numéricos
 * 2 Selección de horas y modificaciones hacia ellas
 * 3 Existencia de registro duplicado en base a periodo, horas, días y ubicación
 * 4 Registro del grupo dentro de laboral
 * @param  int   $op        Correspondiente al caso a checar
 * @param  Class $web       Objeto para poder hacer uso de smarty
 * @param  array $elementos Cuyo contenido debe contener los encabezados: cveperiodo, grupo, nombre, cvelibro_grupal
 * @return int | boolean
 */
function verificaciones($op, $web, $elementos = null)
{
  $cont = 0;
  for ($i = 1; $i <= 6; $i++) {
    for ($j = 0; $j < 2; $j++) {

      switch ($op) {
        case 1: //checa existencia de campos y que sean numéricos
          if (!isset($_POST['datos']['horas' . $i . '_' . $j]) ||
            !is_numeric($_POST['datos']['horas' . $i . '_' . $j])) {
          	$web->simple_message('danger', 'No alteres la estructura de la interfaz');
            return false;
          }
          break;

        case 2: //verfica que se seleccione alguna hora y que no haya sido modificada
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $sql  = "select * from horas where cvehoras=?";
            $hora = $web->DB->GetAll($sql, $_POST['datos']['horas' . $i . '_' . $j]);

            if (!isset($hora[0])) {
            	$web->simple_message('danger', 'No alteres la estructura de la interfaz');
              return false;
            }
          } else {
            $cont++;
          }
          break;

        case 3: //checa que no se duplique periodo, horas y dias con la ubicación
          // $cveperiodo = $web->periodo();
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $sql = "select * from laboral
              inner join sala on laboral.cvesala = sala.cvesala
              where laboral.cveperiodo=? and cvehoras=? and cvedia=? and ubicacion in
              (select ubicacion from sala where cvesala=?)";
            $parametros = array($elementos, $_POST['datos']['horas' . $i . '_' . $j], $i, $_POST['datos']['cvesala']);
            $datos      = $web->DB->GetAll($sql, $parametros);

            if (isset($datos[0])) {
            	$web->simple_message('danger', 'La sala u horario ya están ocupados');
              return false;
            }
          }
          break;

        case 4: //insert final
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
          	$web->DB->startTrans();
            $sql        = "INSERT INTO laboral(cveperiodo, cvehoras, cvedia, cvesala, cveletra, nombre, cvepromotor, cvelibro_grupal) values(?, ?, ?, ?, ?, ?, ?, ?)";
            $parametros = array($elementos['cveperiodo'], $_POST['datos']['horas' . $i . '_' . $j], $i, $_POST['datos']['cvesala'], $elementos['grupo'], $elementos['nombre'], $_SESSION['cveUser'], $elementos['cvelibro_grupal']);
            $web->query($sql, $parametros);
          	$sql    = "select letra from abecedario where cve=?";
  					$letra  = $web->DB->GetAll($sql, $elementos['grupo']);
            mkdir("../periodos/" . $elementos['cveperiodo'] . "/" . $letra[0]['letra'] , 0777);
            if($web->DB->HasFailedTrans()) {
            	$web->simple_message('danger', 'No fue posible registrar el grupo, contacte al administrador');
              return false;
            }
            $web->DB->CompleteTrans();
          }
          break;
      }
    }
  }

  if ($op == 2) {
    return $cont;
  } else {
    return true;
  }
}

function register_room($web) {
	global $cveperiodo;
	$flag = true;
	
	// $web->debug($_POST);

  if (!verificaciones(1, $web)) { return false; }

  for ($i = 1; $i <= 6 && $flag; $i++) {
    if ($_POST['datos']['horas' . $i . '_0'] == $_POST['datos']['horas' . $i . '_1']
      && $_POST['datos']['horas' . $i . '_0'] != -1) {
			$web->simple_message('danger', 'No duplique los horarios en un mismo día');
      $flag = false;
    }
  }
  if (!$flag) { return false; }

  if (!isset($_POST['datos']['cvesala']) ||
    $_POST['datos']['cvesala'] == "") {
  	$web->simple_message('danger', 'No alteres la estructura de la interfaz');
    return false;
  }

  $sql  = "select * from sala where cvesala=? and cveperiodo=?";
  $sala = $web->DB->GetAll($sql, array($_POST['datos']['cvesala'], $cveperiodo));
  if (!isset($sala[0])) {
  	$web->simple_message('danger', 'No alteres la estructura de la interfaz');
    return false;
  }

  $res = verificaciones(2, $web);
  if (!$res) { return false;}

  // Para cuando el usario no escoge nada
  if ($res == 12) {
  	$web->simple_message('danger', 'Seleccione alguna hora, por favor');
    return false;
  } 
  // Para cuando el usuario escoge mas de dos horas
  if ($res < 10) {
  	$web->simple_message('danger', 'Solo debe seleccionar dos horas');
    return false;
  }

  if (!isset($_POST['datos']['cvelibro'])) {
  	$web->simple_message('danger', 'No altere la estructura de la interfaz');
    return false;
  }

  if ($_POST['datos']['cvelibro'] == -1) {
  	$web->simple_message('danger', 'Seleccione un libro grupal');
    return false;
  }

  $cvelibro = $_POST['datos']['cvelibro'];
  $sql      = "select COALESCE(MAX(cveletra),0) as cveletra from laboral where cveperiodo=?";
  $grupo    = $web->DB->GetAll($sql, $cveperiodo);
  $grupo    = ($grupo[0]['cveletra'] + 1);

  $sql    = "select letra from abecedario where cve=?";
  $letra  = $web->DB->GetAll($sql, $grupo);
  $nombre = "SALA - " . $letra[0]['letra'];

  if (!verificaciones(3, $web, $cveperiodo)) {break;}

  verificaciones(4, $web, array('cveperiodo'=>$cveperiodo, 'grupo'=>$grupo, 'nombre'=>$nombre, 'cvelibro_grupal' => $cvelibro));

  header('Location: grupos.php');
}
