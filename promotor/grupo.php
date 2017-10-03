<?php

include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web->iniClases('promotor', "index grupos grupo");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('danger', 'No hay periodos actuales', $web);
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'estado':
      if (!isset($_GET['estado'])) {
        message('warning', 'Hace falta información', $web);
      }

      if ($_GET['estado'] == -1) {
        message('warning', 'Selecciona una opción válida', $web);
      }

      $sql = "SELECT cveestado
      FROM lista_libros
      WHERE cvelectura=?
      AND cvelectura in (SELECT cvelectura FROM lectura WHERE nocontrol=?)";
      $estados = $web->DB->GetAll($sql, array($_GET['info'], $_GET['info2']));

      if (!isset($estados[0])) {
        message('danger', 'El alumno no tiene libros registrados', $web);
      }

      if ($_GET['estado'] == 2) {

        for ($i = 0; $i < sizeof($estados); $i++) {
          if ($estados[$i]['cveestado'] == 2) {
            message('danger', 'El alumno no puede tener dos libros en proceso', $web);
          }
        }

        $sql = "UPDATE lista_libros SET cveestado = ? where cvelista = ?";
        $web->query($sql, array(2, $_GET['info3']));
        header('Location: grupo.php?accion=libros&info=' . $_GET['info'] . '&info2=' . $_GET['info2']);
        die();
      } else if ($_GET['estado'] == 3) {
        $sql   = "SELECT calif_reporte FROM lista_libros where cvelista = ?";
        $calif = $web->DB->GetAll($sql, $_GET['info3']);
        if ($calif[0]['calif_reporte'] < 70) {
          message('danger', 'No se puede marcar como terminado, no cuenta con la calificación suficiente', $web);
        }
        $sql = "UPDATE lista_libros SET cveestado = ? where cvelista = ?";
        $web->query($sql, array($_GET['estado'], $_GET['info3']));
        header('Location: grupo.php?accion=libros&info=' . $_GET['info'] . '&info2=' . $_GET['info2']);
        die();
      } else {
        $sql = "UPDATE lista_libros SET cveestado = ? where cvelista = ?";
        $web->query($sql, array($_GET['estado'], $_GET['info3']));
        header('Location: grupo.php?accion=libros&info=' . $_GET['info'] . '&info2=' . $_GET['info2']);
        die();
      }
      break;

    case 'libros':
      if (!isset($_GET['info'])) {
        message('danger', 'Información incompleta - libros', $web);
      }

      $sql     = "SELECT * FROM lectura WHERE cvelectura=?";
      $lectura = $web->DB->GetAll($sql, $_GET['info']);
      if (!isset($lectura[0])) {
        message('danger', 'No altere la estructura de la interfaz', $web);
      }

      $sql = "SELECT * FROM lista_libros
        INNER JOIN lectura ON lista_libros.cvelectura = lectura.cvelectura
        INNER JOIN libro ON libro.cvelibro = lista_libros.cvelibro
        INNER JOIN estado ON lista_libros.cveestado = estado.cveestado
        WHERE nocontrol=? AND lectura.cveperiodo=?
        ORDER BY libro.cvelibro";
      $libros = $web->DB->GetAll($sql, array($lectura[0]['nocontrol'], $cveperiodo));
      if (!isset($libros[0])) {
        $web->simple_message('warning', 'No hay libros registrados');
      } else {
        $sql          = "SELECT letra FROM abecedario WHERE cve=?";
        $letra_subida = $web->DB->GetAll($sql, $libros[0]["cveletra"]);

        for ($i = 0; $i < count($libros); $i++) {
          $nombre_fichero = "/home/slslctr/periodos/" .
            $libros[$i]["cveperiodo"] . "/" .
            $letra_subida[0][0] . "/" .
            $libros[$i]["nocontrol"] . "/" .
            $libros[$i]["cvelibro"] . "_" .
            $libros[$i]["nocontrol"] . ".pdf";
          if (file_exists($nombre_fichero)) {
            $libros[$i]["archivoExiste"] = explode(
              "/home/slslctr/periodos/",
              $nombre_fichero)[1];
          }
          $sqlEstado = "SELECT * FROM estado";
          $estados   = $web->DB->GetAll($sqlEstado);

          $selected              = $libros[$i]['cveestado'];
          $redireccion['accion'] = "?accion=estado";
          $redireccion['nombre'] = "&estado";
          $redireccion['pagina'] = "grupo.php";
          $redireccion['get']    = "&info=" . $_GET['info'] . "&info2=" . $_GET['info2'] . "&info3=" . $libros[$i]['cvelista'];

          $combo = '<select class="form-control" name="cveestado" onchange="location=this.value">';
          $combo .= '<option value="' . $redireccion['pagina'] . $redireccion['accion'] . $redireccion['nombre'] . '=-1">Selecciona una opción</option>';
          for ($j = 0; $j < sizeof($estados); $j++) {
            $combo .= '<option value="' . $redireccion['pagina'] . $redireccion['accion'] . $redireccion['nombre'] . '=' . $estados[$j]['cveestado'] . $redireccion['get'] . '"';
            if ($selected == $estados[$j]['cveestado']) {
              $combo .= "selected>" . $estados[$j]['estado'] . "</option>";
            } else {
              $combo .= ">" . $estados[$j]['estado'] . "</option>";
            }
          }
          $combo .= "</select>";
          $libros[$i]['combo'] = $combo;
        }
        $web->smarty->assign('libros', $libros);
      }

      if (isset($_GET['aviso'])) {
        switch ($_GET['aviso']) {
          case 1:
            $web->simple_message('warning', 'No se pudo calcular el promedio de los reportes');
            break;
        }
      }

      $web->iniClases('promotor', "index grupos libros");
      $web->smarty->display('grupo.html');
      die();
      break;

    case 'reporte':
      header("Content-disposition: attachment; filename=" . $_GET['info3']);
      header("Content-type: MIME");
      readfile("/home/slslctr/periodos/" . $_GET['info3']);
      break;

    case 'calificar_reporte': //info1 = cvelista, info2 = cvelectura, info3 = nocontrol

      $cveperiodo = $web->periodo();
      if ($cveperiodo == "") {
        message('danger', 'No hay periodos actuales', $web);
      }

      if (!isset($_POST['calificacion']) || !isset($_GET['info1']) || !isset($_GET['info3']) || !isset($_GET['info3'])) {
        message('danger', 'Falta información', $web);
      }

      $sql        = "SELECT * FROM lectura WHERE cveperiodo = ? and cvelectura = ?";
      $existencia = $web->DB->GetAll($sql, array($cveperiodo, $_GET['info2']));
      if (!isset($existencia[0])) {
        message('danger', 'No existe la lectura', $web);
      }

      $existencia = "";
      $sql        = "SELECT * FROM usuarios WHERE cveusuario = ?";
      $existencia = $web->DB->GetAll($sql, $_GET['info3']);
      if (!isset($existencia[0])) {
        message('danger', 'No existe el alumno', $web);
      }

      $existencia = "";
      $sql        = "SELECT * FROM laboral
      WHERE cveletra in (SELECT cveletra FROM lectura WHERE cvelectura=? and cveperiodo=?)";
      $existencia = $web->DB->GetAll($sql, array($_GET['info2'], $cveperiodo));
      if (!isset($existencia[0])) {
        message('danger', 'No tienes permisos', $web);
      }

      if ($_POST['calificacion'] == "") {
        message('danger', 'No se envio la calificación del reporte', $web);
      }

      if ($_POST['calificacion'] > 100 || $_POST['calificacion'] < 0) {
        message('danger', 'Envíe una califición válida', $web);
      }
      $sql = "update lista_libros set calif_reporte = ? WHERE cvelista = ? ";
      $web->query($sql, array($_POST['calificacion'], $_GET['info1']));

      if (promReporte($web) && promTerminado($web, 'cvelectura', $_GET['info2'])) {
        header('Location: grupo.php?accion=libros&info=' . $_GET['info2'] . '&info2=' . $_GET['info3']);
      } else {
        header('Location: grupo.php?accion=libros&info=' . $_GET['info2'] . '&info2=' . $_GET['info3'] . '&aviso=1');
      }
      die();
      break;

    case 'formato_preguntas':
      header("Content-disposition: attachment; filename=formato_preguntas.pdf");
      header("Content-type: MIME");
      readfile("/home/slslctr/archivos/pdf/" . $cveperiodo . "/formato_preguntas.pdf");
      break;

    case 'form_observaciones':
      m_formObservaciones();
      break;

    case 'observacion':
      m_Observaciones();
      break;

    case 'lista_asistencia':
      mListaAsistencia();
      break;
  }
}

if (!isset($_GET['info1'])) {
  $web->debug($_GET); //no sé porque, pero necesita esto para funcionar, marca error después de mandar una observación, pero no debería
  message('danger', 'Información incompleta - general', $web);
}
$grupo = $_GET['info1'];

$sql = "SELECT cvepromotor FROM laboral WHERE cveletra in
(SELECT cve FROM abecedario WHERE letra=?) and cveperiodo=?";
$grupo_promotor = $web->DB->GetAll($sql, array($grupo, $cveperiodo));
if (!isset($grupo_promotor[0])) {
  message('danger', 'No existe el grupo en este periodo', $web);
}

if ($grupo_promotor[0]['cvepromotor'] != $_SESSION['cveUser']) {
  message('danger', 'Permiso denegado', $web);
}

if (isset($_POST['datos'])) {

  if (!isset($_POST['datos']['cveeval']) ||
    !isset($_POST['datos']['comprension']) ||
    !isset($_POST['datos']['participacion']) ||
    !isset($_POST['datos']['asistencia']) ||
    !isset($_POST['datos']['actividades']) ||
    !is_numeric($_POST['datos']['cveeval']) ||
    !is_numeric($_POST['datos']['comprension']) ||
    !is_numeric($_POST['datos']['participacion']) ||
    !is_numeric($_POST['datos']['asistencia']) ||
    !is_numeric($_POST['datos']['actividades'])) {
    message("danger", "No alteres la estructura de la interfaz", $web);
  }

  if ($_POST['datos']['cveeval'] < 0 ||
    $_POST['datos']['comprension'] < 0 ||
    $_POST['datos']['participacion'] < 0 ||
    $_POST['datos']['asistencia'] < 0 ||
    $_POST['datos']['actividades'] < 0) {
    message("danger", "Ingrese solo valores positivos", $web);
  }

  if ($_POST['datos']['cveeval'] > 100 ||
    $_POST['datos']['comprension'] > 100 ||
    $_POST['datos']['participacion'] > 100 ||
    $_POST['datos']['asistencia'] > 100 ||
    $_POST['datos']['actividades'] > 100) {
    message("danger", "Ingrese solo valores positivos", $web);
  }

  $sql = "SELECT * FROM evaluacion
  INNER JOIN lectura on lectura.cvelectura = evaluacion.cvelectura
  INNER JOIN abecedario on abecedario.cve = lectura.cveletra
  INNER JOIN laboral on abecedario.cve = laboral.cveletra
  WHERE cvepromotor=? and cveeval=?";
  $eval = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $_POST['datos']['cveeval']));
  if (!isset($eval[0])) {
    message('danger', 'Permiso denegado', $web);
  }

  $web->DB->startTrans(); //por si hay errores durante la ejecusión del query
  $sql = "update evaluacion set comprension=?, participacion=?, asistencia=?,
  actividades=? WHERE cveeval=?";
  $parametros = array(
    $_POST['datos']['comprension'],
    $_POST['datos']['participacion'],
    $_POST['datos']['asistencia'],
    $_POST['datos']['actividades'],
    $_POST['datos']['cveeval']);
  $web->query($sql, $parametros);

  if (!promTerminado($web, 'cveeval', $_POST['datos']['cveeval'])) {
    $web->simple_message('warning', 'No se pudo calcular el promedio final');
  }

  if ($web->DB->HasFailedTrans()) {
    //falta programar esta parte para que no muestre directamente el resultado de sql
  }

  $web->DB->CompleteTrans();
  $web->query($sql, $parametros);
}

//Info de encabezado
$sql = "SELECT distinct letra, nombre, ubicacion, fechainicio, fechafinal FROM laboral
INNER JOIN sala on laboral.cvesala = sala.cvesala
INNER JOIN abecedario on laboral.cveletra = abecedario.cve
INNER JOIN periodo on laboral.cveperiodo= periodo.cveperiodo
WHERE cvepromotor=? and laboral.cveperiodo=? and letra=?
ORDER BY letra";
$datos_rs = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo, $grupo));
$web->smarty->assign('info', $datos_rs[0]);

//Datos de la tabla = Alumnos
$sql = "SELECT distinct usuarios.nombre, comprension, participacion,
terminado, asistencia, reporte, actividades, nocontrol, cveeval, lectura.cveperiodo,
lectura.cvelectura, asistencia FROM lectura
INNER JOIN evaluacion on evaluacion.cvelectura = lectura.cvelectura
INNER JOIN abecedario on lectura.cveletra = abecedario.cve
INNER JOIN usuarios on lectura.nocontrol = usuarios.cveusuario
INNER JOIN laboral on abecedario.cve = laboral.cveletra
WHERE letra=? and lectura.cveperiodo=?
ORDER BY usuarios.nombre";
$datos = $web->DB->GetAll($sql, array($grupo, $cveperiodo));
if (!isset($datos[0])) {
  message('warning', 'No hay alumnos inscritos');
}

$nombre_fichero = "/home/slslctr/archivos/pdf/" . $cveperiodo . "/formato_preguntas.pdf";
if (file_exists($nombre_fichero)) {
  $web->smarty->assign('formato_preguntas', true);
}

showMessage(); //mensajes de error o avisos

$web->smarty->assign('bandera', 'true');
$web->smarty->assign('cveperiodo', $cveperiodo);
$web->smarty->assign('datos', $datos);
$web->smarty->assign('grupo', $grupo);
$web->smarty->display("grupo.html");

/****************************************************************************************************
 * FUNCIONES
 ****************************************************************************************************/
/**
 * Ahorro de código para mostrar mensajes al usuario
 * @param  String $alert warning | danger principalmente
 * @param  String $msg   Texto a mostrar al usuario
 * @param  Class  $web   Objeto para hacer uso de smarty
 */
function message($alert, $msg)
{
  global $web;
  $web->simple_message($alert, $msg);
  $web->smarty->display("grupo.html");
  die();
}

function promReporte($web)
{
  $cvelectura = $_GET['info2'];

  $sql           = "SELECT * FROM lista_libros WHERE cvelectura=?";
  $cali_reportes = $web->DB->GetAll($sql, $cvelectura);
  $prom          = 0;
  for ($i = 0; $i < sizeof($cali_reportes); $i++) {
    $prom += $cali_reportes[$i]['calif_reporte'];
  }

  if (sizeof($cali_reportes) < 5) {
    $prom /= 5;
  } else {
    $prom /= sizeof($cali_reportes);
  }

  $prom = round($prom);
  $sql  = "UPDATE evaluacion SET reporte=? WHERE cvelectura=?";
  if (!$web->query($sql, array($prom, $cvelectura))) {
    return false;
  }
  return true;
}

function promTerminado($web, $campo, $valor)
{
  $sql            = "SELECT * FROM evaluacion WHERE " . $campo . "=?";
  $calificaciones = $web->DB->GetAll($sql, $valor);
  if (!isset($calificaciones[0])) {
    return false;
  }

  $prom = $calificaciones[0]['asistencia'];
  $prom += $calificaciones[0]['comprension'];
  $prom += $calificaciones[0]['participacion'];
  $prom += $calificaciones[0]['reporte'];
  $prom += $calificaciones[0]['actividades'];
  $prom /= 5;
  $prom = round($prom);
  $sql  = "UPDATE evaluacion SET terminado=? WHERE " . $campo . "=?";
  if (!$web->query($sql, array($prom, $valor))) {
    return false;
  }

  return true;
}

function m_formObservaciones()
{
  global $web;

  if (!isset($_GET['info'])) {
    message('warning', 'Falta información');
  }
  $sql = "SELECT DISTINCT laboral.cveletra FROM laboral
  INNER JOIN abecedario a ON a.cve = laboral.cveletra
  WHERE letra=?";
  $cveletra = $web->DB->GetAll($sql, $_GET['info']);
  if (!isset($cveletra[0])) {
    message('danger', 'No existe el grupo');
  }

  $web->smarty->assign('cveletra', $cveletra[0]['cveletra']);
  $web->smarty->display('form_observaciones.html');
  die();
}

function m_Observaciones()
{
  global $web;
  global $cveperiodo;

  if (!isset($_GET['info']) ||
    !isset($_POST['observacion'])) {
    message('warning', 'Falta información');
  }
  // obtiene la letra porque se usa para los header-location
  $sql   = "SELECT DISTINCT letra FROM abecedario WHERE cve=?";
  $letra = $web->DB->GetAll($sql, $_GET['info']);
  if (!isset($letra[0])) {
    message('danger', 'No existe el grupo');
  }

  $sql = "INSERT INTO observacion(observacion, cveletra, cveperiodo, cvepromotor)
  VALUES(?, ?, ?, ?)";
  $parameters = array(
    $_POST['observacion'],
    $_GET['info'],
    $cveperiodo,
    $_SESSION['cveUser'],
  );

  if (!$web->query($sql, $parameters)) {
    header('Location: grupo.php?info1=' . $letra[0]['letra'] . "&e=guardar");
  }
  header('Location: grupo.php?info1=' . $letra[0]['letra'] . "&m=guardar");
}

function showMessage()
{
  global $web;

  // Mensajes de error
  if (isset($_GET['e'])) {
    switch ($_GET['e']) {
      case 'guardar':
        $web->simple_message('danger', 'No fue posible guardar la información');
        break;
    }
  }
  // Mensajes de éxito
  if (isset($_GET['m'])) {
    switch ($_GET['m']) {
      case 'guardar':
        $web->simple_message('info', 'Datos guardados');
        break;
    }
  }
}

function mListaAsistencia()
{
  require_once '../controllers/pdf.class.php';
  global $web;

  $web = new ListAsiControllers;
  $pdf = new PDF;
  $web->smarty->setTemplateDir('../templates/promotor/pdf/');
  $web->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c

  // OBTIENE HEADER
  $data   = $web->headerFooter();
  $header = $data['header'];
  $footer = $data['footer'];

  if (!isset($_GET['info'])) {
    mSetMessage($pdf, $header, 'e2');
  }

  // OBTIENE INFO DE PROMOTOR PARA SUBHEADER
  $data = $web->promoSubHeader();
  if (!isset($data['cveperiodo'])) {
    mSetMessage($pdf, $header, $data);
  }
  $cveperiodo  = $data['cveperiodo'];
  $cvepromotor = $data['cvepromotor'];
  $periodo     = $data['periodo'];

  $data = $web->grupoSubHeader(array('cveperiodo' => $cveperiodo, 'cvepromotor' => $cvepromotor));
  if (!isset($data['grupos'])) {
    mSetMessage($pdf, $header, $data);
  }
  $grupos       = $data['grupos'];
  $gruposHeader = $data['gruposHeader'];

  // se comienza a checar el grupo para obtener los alumnos
  $lecturas = $web->getAllLecturas($cveperiodo, $cvepromotor, $grupos[0]['cveletra']);
  if (!isset($lecturas[0])) {
    mSetMessage($pdf, $header, 'e3'); //no hay alumnos en este grupo

  } else {
    // Obtiene todos los alumnos de un grupo
    $tmpAlumno = array();
    $tmpData   = array();
    for ($i = 0; $i < count($lecturas); $i++) {
      $p['#'] = ($i + 1); //enumera

      $datos[0] = $web->getAlumno(
        $lecturas[$i]['nocontrol'],
        $cveperiodo,
        $lecturas[$i]['cveletra'],
        $lecturas[$i]['cvelectura']
      );

      $datos         = array_merge($p, $datos[0][0]);
      $tmpData[0][0] = $web->mMoveFields($datos);
      $tmpAlumno     = array_merge($tmpAlumno, $tmpData[0]);

    } //fin for
    $alumnos[0] = $tmpAlumno;
  } //end else

  // FULL SUBHEADER
  $html = $web->grupoSubHeader(array('grupos' => $grupos, 'gruposHeader' => $gruposHeader, 'position' => 0));

  // ALUMNOS SUBHEADER
  if (!is_array($alumnos)) {
    mSetMessage($pdf, $header, 'e3'); //no hay alumnos en este grupo
  }

  $alumnosHeader = $web->getAssocArray($alumnos);
  $alumnos       = $web->getAssocArray($alumnos, true);
  if ($alumnosHeader == null || $alumnos == null) {
    mSetMessage($pdf, $header, 'e3'); //no hay alumnos en este grupo
  }

  $web->smarty->assign('fin', (sizeof($alumnos[0][0]) / 2 + 1));
  $web->page_break(0, $grupos); //habilita o deshabilita el salto de página

  // DATOS TABLE PRINCIPAL
  $web->smarty->assign('columns', $alumnosHeader);
  $web->smarty->assign('rows', $alumnos[0]);
  $web->smarty->assign('title', 'Lista de Asistencia');
  $html .= (string) ($web->smarty->fetch('table.html'));

  $html = $header . $html . $footer;
  // echo $html;
  $pdf->createPDF('Lista de Asistencia', $html, 'landscape');
  die();
}

/*
 * Muestra mensajes de error y de éxito
 * Usar solo para casos de despliege de PDF
 */
function mSetMessage($pdf, $header, $msg)
{
  switch ($msg) {
    case 'e1':
      $msg = 'No hay periodo actual';
      break;

    case 'promotor':
      $msg = 'Ocurrió un error al intentar identificar a este promotor';
      break;

    case 'grupos':
      $msg = 'Ocurrió un error al intentar identificar a este grupo';
      break;

    case 'e2':
      $msg = 'Hacen falta datos para continuar';
      break;

    case 'e3':
      $msg = 'No hay alumnos en este grupo';
      break;

    default:
      echo 'No option';
      die();
  }

  $pdf->createPDF('Lista-Asistencia', $header . $msg, 'landscape');
  die();
}
