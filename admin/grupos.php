<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web = new AdminGruposControllers;
$web->iniClases('admin', "index grupos");

showMessages();

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodo actual');
  $web->smarty->display('grupos.html');
  die();
}

if (isset($_GET['accion'])) {
  switch ($_GET['accion']) {
    case 'delete':
      delete_group();
      break;
    case 'add_group':
      add_group();
      break;
  }
}

//su nombre es nocontrol para que funcione en grupos.html
$grupos = $web->getAllGrupos($cveperiodo);
if (!isset($grupos[0])) {
  $web->simple_message('danger', 'No hay grupos registrados');
  $web->smarty->display('grupos.html');
  die();
}

//coloca horarios
$horas = $web->getSchedule($cveperiodo);
for ($i = 0; $i < sizeof($grupos); $i++) {
  $grupos[$i]['horario'] = "";
  for ($j = 0; $j < sizeof($horas); $j++) {
    if ($grupos[$i]['letra'] == $horas[$j]['letra']) {
      $grupos[$i]['horario'] .= $horas[$j]['nombre'] . ' - ' . $horas[$j]['hora_inicial'] . ' a ' . $horas[$j]['hora_final'] . "<br>";
    }
  }
}

// $web->debug($grupos);

$web->smarty->assign('tablegrupos', $grupos);
$web->smarty->assign('bandera', 'index_grupos');
$web->smarty->display('grupos.html');
/***********************************************************************************************************
 * FUNCIONES
 **********************************************************************************************************/
/**
 *
 */
function showMessages()
{
  global $web;
  if (isset($_GET['a'])) {
    switch ($_GET['a']) {
      case 1:
        $web->simple_message('info', 'Se ha creado el grupo correctamente');
        break;
      case 2:
        $web->simple_message('info', 'Se ha eliminado el grupo');
        break;
      case 3:
        $web->simple_message('danger', 'Ha ocurrido un error');
        break;
    }
  }
}

/**
 * Crea un grupo nuevo
 * Step: pasos paa la creacion de un grupo
 * Step 1: Seleccionar las salas disponibles
 * Setp 2: Seleccionar Los horarios disponibles de la sala seleccionada, y el libro del grupo :3
 * Step 3: Seleccionar al promotor
 * */
function add_group()
{
  global $web, $cveperiodo;
  $step = $_GET['step'];
  switch ($step) {
    case '1':
      $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
      $datos = array('data' => $web->getAll(array('cvesala', 'ubicacion'), array('disponible' => 't'), 'sala', array('cvesala')));
      for ($i = 0; $i < sizeof($datos['data']); $i++) {
        $datos['data'][$i]['cvesala'] =
          "<a href='grupos.php?accion=add_group&step=2&info=" . $datos['data'][$i]['cvesala'] . "'>" . $datos['data'][$i]['cvesala'] . "</a>";
      }

      $web->DB->SetFetchMode(ADODB_FETCH_NUM);
      $datos = json_encode($datos);

      $file = fopen("TextFiles/promosala.txt", "w");
      fwrite($file, $datos);
      $web->smarty->assign('datos', $datos);
      $web->smarty->assign('adminsala_sala', true);
      break;

    /***********************************************************************************************************************************/

    case '2':
      if (!isset($_GET['info'])) {
        $web->simple_message('danger', 'No se especificó la sala');
        break;
      }

      $sala = $web->getAll('*', array('cvesala' => $_GET['info']), 'sala');
      if (!isset($sala[0])) {
        $web->simple_message('danger', 'No existe la sala seleccionada');
        break;
      }

      $dias = $web->getAll('*', null, 'dia');
      for ($i = 1; $i <= sizeof($dias); $i++) {
        $horas = $web->getHoras($i, $sala[0]['cvesala'], $cveperiodo);
        if (isset($horas[0])) {
          $web->smarty->assign('horas' . $i, $horas);
        }
      }

      //Obtener lista de los promotores disponibles
      $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
      $datos = array('data' => $web->getPromotores($cveperiodo));

      for ($i = 0; $i < sizeof($datos['data']); $i++) {
        $datos['data'][$i]['seleccion'] =
          "<input id='r4' type='radio' class='btn btn-default' value='" . $datos['data'][$i]['cveusuario'] . "' name='datos[promotor]'>";
      }

      $web->DB->SetFetchMode(ADODB_FETCH_NUM);
      $datos = json_encode($datos);
      $file  = fopen("TextFiles/promodisponibles.txt", "w");
      fwrite($file, $datos);

      $sql    = 'SELECT cvelibro, titulo FROM libro ORDER BY titulo';
      $libros = $web->combo($sql, null, "../");
      $web->smarty->assign('cvesala', $_GET['info']);
      $web->smarty->assign('libros', $libros);
      $web->smarty->assign('horas', 'horas');
      $web->smarty->assign('promodisponibles', $datos);
      break;

    /***********************************************************************************************************************************/

    case '3':
      $flag = true;
      if (!isset($_POST['datos']['promotor'])) {
        $web->simple_message('danger', 'Seleccione un promotor');
        return false;
      }

      $promotor = $web->getAll('*', array('cveusuario' => $_POST['datos']['promotor']), 'usuarios');
      if (!isset($promotor[0])) {
        $web->simple_message('danger', 'El promotor no existe');
        return false;
      }

      if (!verificaciones(1)) {return false;}

      for ($i = 1; $i <= 6 && $flag; $i++) {
        if ($_POST['datos']['horas' . $i . '_0'] == $_POST['datos']['horas' . $i . '_1']
          && $_POST['datos']['horas' . $i . '_0'] != -1) {
          $web->simple_message('danger', 'No duplique los horarios en un mismo día');
          $flag = false;
        }
      }

      if (!$flag) {return false;}

      if (!isset($_POST['datos']['cvesala']) ||
        $_POST['datos']['cvesala'] == "") {
        $web->simple_message('danger', 'No alteres la estructura de la interfaz');
        return false;
      }

      $sala = $web->getAll('*', array('cvesala' => $_POST['datos']['cvesala']), 'sala');
      if (!isset($sala[0])) {
        $web->simple_message('danger', 'No alteres la estructura de la interfaz');
        return false;
      }

      $res = verificaciones(2); //verfica que se seleccione alguna hora y que no haya sido modificada
      if (!$res) {return false;}

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

      if (!isset($_POST['datos']['1'])) {
        $web->simple_message('danger', 'No altere la estructura de la interfaz');
        return false;
      }

      if ($_POST['datos']['cvelibro'] == -1) {
        $web->simple_message('danger', 'Seleccione un libro grupal');
        return false;
      }

      $cvelibro = $_POST['datos'][1];
      $grupo    = $web->getGrupos($cveperiodo);
      $grupo    = ($grupo[0]['cveletra'] + 1);

      $letra  = $web->getAll(array('letra'), array('cve' => $grupo), 'abecedario');
      $nombre = "SALA - " . $letra[0]['letra'];

      if (!verificaciones(3, $cveperiodo)) {return false;} //checa que no se duplique periodo, horas y dias con la ubicación

      $horarios = verificaciones(4, array(
        'cveperiodo'      => $cveperiodo,
        'grupo'           => $grupo,
        'nombre'          => $nombre,
        'cvelibro_grupal' => $cvelibro));

      // $web->debug($horarios, false);
      $cvehorario1 = $horarios[1][0][0];
      $cvehorario2 = $horarios[2][0][0];

      if (!$web->insertLaboral(
        $cveperiodo, $_POST['datos']['cvesala'],
        $grupo,
        $nombre,
        $_POST['datos']['promotor'],
        $cvelibro,
        $cvehorario1,
        $cvehorario2)) {
        $web->simple_message('danger', 'No fue posible registrar el grupo, contacte al administrador');
        return false;
      }

      $letra = $web->getAll(array('letra'), array('cve' => $grupo), 'abecedario');
      $dir   = $web->route_periodos . $cveperiodo . "/" . $letra[0]['letra'];
      if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
      }

      header('Location: grupos.php?a=1');
      break;
  }
  $web->smarty->display("adminsala.html");
  die();
}

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
  $cont     = 0;
  $flag_aux = false;

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
            $hora = $web->getAll('*', array('cvehoras' => $_POST['datos']['horas' . $i . '_' . $j]), 'horas');
            if (!isset($hora[0])) {
              $web->simple_message('danger', 'No alteres la estructura de la interfaz');
              return false;
            }
          } else {
            $cont++;
          }
          break;

        case 3: //checa que no se duplique periodo, horas y dias con la ubicación
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {

            $datos = $web->getSalas(
              $elementos, $_POST['datos']['horas' . $i . '_' . $j], $i, $_POST['datos']['cvesala']);
            if (isset($datos[0])) {
              $web->simple_message('danger', 'La sala u horario ya están ocupados');
              return false;
            }
          }
          break;

        case 4: //insert final
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $horario = $web->getAll(array('cvehorario'), array('cvehora' => $_POST['datos']['horas' . $i . '_' . $j], 'cvedia' => $i), 'horario');

            $flag = false;
            if (!isset($horario[0])) {
              $res  = $web->insertHorario($_POST['datos']['horas' . $i . '_' . $j], $i);
              $flag = true;
            }

            $cont++;
            $horarios[$cont] = ($flag) ? $web->getMaxCveHorario()[0] : $horario;
          }

          if ($cont == 2) {
            return $horarios;
          }
          break;
      }
    }
  }

  if ($op == 2) {
    return $cont;
  }
  return true;
}

function delete_group()
{
  global $web, $cveperiodo;

  //se valida la contraseña
  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;

    case 2:
      $web->simple_message('danger', 'La contraseña de seguridad ingresada no es válida');
      return false;
  }
  //se verifica que la letra haya sido mandada y sea válida
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No es posible continuar, hacen falta datos');
  }

  $cveletra = $web->getAll(array('cve'), array('letra' => $_GET['info1']), 'abecedario');
  if (!isset($cveletra[0])) {
    $web->simple_message('danger', 'No es posible que exista tal grupo');
  }

  $web->DB->startTrans();
  $lecturas = $web->getLectura($cveperiodo, $cveletra[0]['cve']);
  for ($i = 0; $i < sizeof($lecturas); $i++) {
    //elimina de evaluacion, lista_libros, lectura
    $web->delete('evaluacion', array('cvelectura' => $lecturas[$i]['cvelectura']));
    $web->delete('lista_libros', array('cvelectura' => $lecturas[$i]['cvelectura']));
    $web->delete('lectura', array('cvelectura' => $lecturas[$i]['cvelectura']));
  }

  //funcion en postgres, elimina de msj, observación y laboral
  $web->delete('msj', array('cveperiodo' => $cveperiodo, 'cveletra' => $cveletra[0]['cve']));
  $web->delete('observacion', array('cveperiodo' => $cveperiodo, 'cveletra' => $cveletra[0]['cve']));
  $web->delete('laboral', array('cveperiodo' => $cveperiodo, 'cveletra' => $cveletra[0]['cve']));

  if ($web->DB->HasFailedTrans()) {
    header('Location: grupos.php?a=3');die();
  }

  $web->DB->CompleteTrans();
  header('Location: grupos.php?a=2');die();
}
