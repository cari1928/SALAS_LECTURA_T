<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web = new PromoSalaControllers;
$web->iniClases('promotor', "index salas");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodos actuales');
  $web->smarty->display("promosala.html");
  die();
}

mMessages();

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'horario':
      mSchedule();
      break;

    case 'insert':
      mRegisterRoom();
      break;

    default:
      $web->simple_message('danger', 'No existe la acción seleccionada');
  }
}

//antes que nada se verifica si el promotor ya tiene 3 grupos
$grupos = $web->checkPromoGroups($_SESSION['cveUser'], $cveperiodo);
if (sizeof($grupos) == 3) {
  $web->simple_message('warning', "Ya tiene 3 grupos, no puede registrar mas");

} else {
  $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
  $salasDisponibles = $web->getAll(array('cvesala', 'ubicacion'), array('disponible' => 't'), 'sala', array('cvesala'));
  // $web->debug($salasDisponibles, false);

  $datos = array('data' => $salasDisponibles);
  for ($i = 0; $i < sizeof($datos['data']); $i++) {
    $datos['data'][$i]['cvesala'] = "<a href='salas.php?accion=horario&info=" . $datos['data'][$i]['cvesala'] . "'>" .
      $datos['data'][$i]['cvesala'] . "</a>";
  }

  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $datos = json_encode($datos);
  $file  = fopen("TextFiles/promosala.txt", "w");
  fwrite($file, $datos);
  $web->smarty->assign('datos', $datos);
}

$web->smarty->display("promosala.html");

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
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
function verificaciones($op, $elementos = null)
{
  global $web;
  $cont = 0;

  for ($i = 1; $i <= 6; $i++) {
    for ($j = 0; $j < 2; $j++) {

      switch ($op) {
        case 1: //checa existencia de campos y que sean numéricos
          if (!isset($_POST['datos']['horas' . $i . '_' . $j]) ||
            !is_numeric($_POST['datos']['horas' . $i . '_' . $j])) {
            header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=2');
            die();
          }
          break;

        case 2: //verfica que se seleccione alguna hora y que no haya sido modificada
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $hora = $web->getHours($_POST['datos']['horas' . $i . '_' . $j]);
            if (!isset($hora[0])) {
              header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=2');
              die();
            }
          } else {
            $cont++;
          }
          break;

        case 3: //checa que no se duplique periodo, horas y dias con la ubicación
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $sql = "SELECT * FROM laboral
              INNER JOIN sala on laboral.cvesala = sala.cvesala
              WHERE laboral.cveperiodo=? AND cvedia=? ";

            $sql .= ($j == 0) ? "AND cvehorario1 IN (SELECT cvehorario1 FROM horario WHERE cvehora=?) " :
            "AND cvehorario2 IN (SELECT cvehorario2 FROM horario WHERE cvehora=?) ";

            $sql .= "AND ubicacion IN (SELECT ubicacion FROM sala WHERE cvesala=?);";

            $parametros = array($elementos, $i, $_POST['datos']['horas' . $i . '_' . $j], $_POST['datos']['cvesala']);
            $datos      = $web->DB->GetAll($sql, $parametros);
            if (isset($datos[0])) {
              header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=5');
              die();
            }
          }
          break;

        case 4: //insert final
          if ($_POST['datos']['horas' . $i . '_' . $j] == -1) {
            break;
          }

          $flag = false;
          //verifica si el horario ya está registrado
          if (!is_array($web->getSchedule($_POST['datos']['horas' . $i . '_' . $j], $i))) {
            $web->insertSchedule($_POST['datos']['horas' . $i . '_' . $j], $i); //inserta en la tabla horario
            $flag = true;
          }

          //se obtiene la información del horario
          $horario = ($flag) ?
          $web->getSchedule($_POST['datos']['horas' . $i . '_' . $j], $i) : /*obtiene el horario en base a hora y día*/
          $web->getLastSchedule(); //obtiene el último horario registrado

          $web->DB->startTrans();
          if ($j == 0) {
            $result = $web->insertLaboral($elementos['cveperiodo'], $_POST['datos']['cvesala'], $elementos['grupo'], $elementos['nombre'], $_SESSION['cveUser'], $elementos['cvelibro_grupal'], $_POST['datos']['horas' . $i . '_' . $j]);
          } else {
            $laboral = $web->getLastLaboral();
            $result  = $web->updateLaboral($_POST['datos']['horas' . $i . '_' . $j], $laboral[0]['cvelaboral']);
          }

          if (!$result) {
            header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=6');
            die();
          }

          if ($j != 0) {
            $letra = $web->getLetter($elementos['grupo']);
            mkdir("../archivos/periodos/" . $elementos['cveperiodo'] . "/" . $letra[0]['letra'], 0777, true);
          }

          if ($web->DB->HasFailedTrans()) {
            header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=6');
            die();
          }
          $web->DB->CompleteTrans();
      }
    }
  }

  if ($op == 2) {
    return $cont;
  }
  return true;
}

/**
 * Creación del grupo
 */
function mRegisterRoom()
{
  global $web, $cveperiodo;
  $flag = true;

  if (!verificaciones(1)) {return false;} //checa existencia de campos y que sean numéricos

  for ($i = 1; $i <= 6 && $flag; $i++) {
    if ($_POST['datos']['horas' . $i . '_0'] == $_POST['datos']['horas' . $i . '_1'] &&
      $_POST['datos']['horas' . $i . '_0'] != -1) {
      header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=1');
      die();
    }
  }
  if (!$flag) {return false;}

  if (!isset($_POST['datos']['cvesala']) ||
    $_POST['datos']['cvesala'] == "") {
    header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=2');
    die();
  }

  $sala = $web->getClass($_POST['datos']['cvesala']);
  if (!isset($sala[0])) {
    header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=2');
    die();
  }

  $res = verificaciones(2); //verfica que se seleccione alguna hora y que no haya sido modificada
  if (!$res) {return false;}

  // Para cuando el usario no escoge nada
  if ($res == 12) {
    header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=3');
    die();
  }
  // Para cuando el usuario escoge mas de dos horas
  if ($res < 10) {
    header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=3');
    die();
  }

  if (!isset($_POST['datos']['cvelibro'])) {
    header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=2');
    die();
  }

  if ($_POST['datos']['cvelibro'] == -1) {
    header('Location: salas.php?accion=horario&info=' . $_POST['datos']['cvesala'] . '&e=4');
    die();
  }

  $cvelibro = $_POST['datos']['cvelibro'];
  $grupo    = $web->getLastLetterLaboral($cveperiodo);
  $grupo    = ($grupo[0]['cveletra'] + 1);

  $letra  = $web->getLetter($grupo);
  $nombre = "SALA - " . $letra[0]['letra'];

  if (!verificaciones(3, $cveperiodo)) {return false;} //checa que no se duplique periodo, horas y dias con la ubicación

  //insert final
  verificaciones(4, array('cveperiodo' => $cveperiodo, 'grupo' => $grupo, 'nombre' => $nombre, 'cvelibro_grupal' => $cvelibro));
  mCreateFolders($letra[0]['letra']);
  header('Location: grupos.php');
}

/**
 * Asigna el horario a los promotores
 */
function mSchedule()
{
  global $web, $cveperiodo;

  $web->iniClases('promotor', "index salas horario");
  if (!isset($_GET['info'])) {
    $web->simple_message('danger', 'No se especificó la sala');
    return false;
  }

  $sala = $web->getClass($_GET['info']);
  if (!isset($sala[0])) {
    $web->simple_message('danger', 'No existe la sala seleccionada');
    return false;
  }

  $dias = $web->getDays();
  for ($i = 1; $i <= sizeof($dias); $i++) {
    $horas = $web->getPromoHours($i, $sala[0]['cvesala'], $cveperiodo);
    if (isset($horas[0])) {
      $web->smarty->assign('horas' . $i, $horas);
    }
  }

  $sql    = 'SELECT cvelibro, titulo FROM libro ORDER BY titulo';
  $libros = $web->combo($sql, null, "../");
  $web->smarty->assign('cvesala', $_GET['info']);
  $web->smarty->assign('libros', $libros);
  $web->smarty->assign('horas', 'horas');
  $web->smarty->display("promosala.html");
  die();
}

/**
 * Crea la carpeta necesaria para guardar los recursos de los avisos grupales e individuales para este nuevo grupo
 */
function mCreateFolders($letra)
{
  global $web, $cveperiodo;
  if (!mkdir($web->route_msj . $cveperiodo . "/" . $letra, 0777, true)) {
    $web->simple_message('warning', 'No fue posible crear los recursos necesarios');
  }
}

function mMessages()
{
  global $web;

  if (isset($_GET['m'])) {

  }
  if (isset($_GET['e'])) {
    switch ($_GET['e']) {
      case 1:
        $web->simple_message('danger', 'No duplique los horarios en un mismo día');
        break;
      case 2:
        $web->simple_message('danger', 'No alteres la estructura de la interfaz');
        break;
      case 3:
        $web->simple_message('danger', 'Por favor, seleccione dos hora');
        break;
      case 4:
        $web->simple_message('danger', 'Por favor, seleccione un libro grupal');
        break;
      case 5:
        $web->simple_message('danger', 'La sala u horario ya están ocupados');
        break;
      case 6:
        $web->simple_message('danger', 'No fue posible registrar el grupo, contacte al administrador');
        break;
    }
  }
}
