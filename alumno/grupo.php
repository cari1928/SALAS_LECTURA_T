<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web->iniClases('usuario', "index grupos grupo");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('danger', 'No hay periodos actuales', $web);
}

$nombre_fichero = "/home/slslctr/archivos/pdf/" . $cveperiodo . "/formato_preguntas.pdf";
if (file_exists($nombre_fichero)) {
  $web->smarty->assign('formato_preguntas', true);
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'fileinput':
      if (!isset($_GET['info1'])) {
        message('danger', 'Información incompleta', $web);
      }

      $sql = "SELECT letra FROM abecedario
      WHERE cve IN (SELECT cveletra FROM lectura WHERE cvelectura=?)";
      $letra_subida = $web->DB->GetAll($sql, $_GET['info1']);
      if (!isset($letra_subida[0])) {
        message('danger', 'No existe el grupo', $web);
      }

      $dir_subida = "/home/slslctr/periodos/" .
        $cveperiodo . "/" .
        $letra_subida[0][0] . "/" .
        $_SESSION['cveUser'] . "/";

      if ($_FILES['datos']['size']['archivo'] > 1000000) {
        message('danger', 'El archivo es mayor a un MB.', $web);
      }
      if ($_FILES['datos']['type']['archivo'] != 'application/pdf') {
        message('danger', 'Solo esta permitido subir archivos de tipo .pdf', $web);
      }
      if (!isset($_POST['datos']['reporte'])) {
        message('danger', 'Información incompleta', $web);
      }

      $sql             = "SELECT cvelibro FROM libro WHERE cvelibro=?";
      $cvelibro_subida = $web->DB->GetAll($sql, $_POST['datos']['reporte']);
      if (!isset($cvelibro_subida[0])) {
        message('danger', 'El libro no existe', $web);
      }

      $nombre = $cvelibro_subida[0][0] . "_" . $_SESSION['cveUser'] . ".pdf";
      if (move_uploaded_file($_FILES['datos']['tmp_name']['archivo'], $dir_subida . $nombre)) {
        message('success', 'Se subió el reporte satisfactoriamente', $web);
      } else {
        message('danger', 'Ocurrió un error mientras se subía el archivo', $web);
      }
      break;

    case 'form_libro':
      if (!isset($_GET['info1'])) {
        message('danger', 'Información incompleta', $web);
      }

      $sql     = "SELECT * FROM lectura WHERE cvelectura=?";
      $lectura = $web->DB->GetAll($sql, $_GET['info1']);
      if (!isset($lectura[0])) {
        message("danger", "No altere la estructura de la interfaz", $web);
      }

      $web->iniClases('usuario', "index grupos libro");

      //para no mostrar los libros que ya fueron registrados para ese alumno en ese periodo
      $sql = "SELECT cvelibro, titulo FROM libro
        WHERE cvelibro NOT IN
        (SELECT cvelibro FROM lista_libros
          INNER JOIN lectura ON lectura.cvelectura = lista_libros.cvelectura
          INNER JOIN abecedario ON abecedario.cve = lectura.cveletra
          INNER JOIN laboral ON laboral.cveletra = abecedario.cve
          WHERE nocontrol=? AND laboral.cveperiodo=? AND lectura.cvelectura=?)
        ORDER BY titulo";
      $combo = $web->combo($sql, null, '../', array($lectura[0]['nocontrol'], $cveperiodo, $_GET['info1']));

      $sql = "SELECT libro.cvelibro, titulo, estado FROM lista_libros
          INNER JOIN estado ON estado.cveestado = lista_libros.cveestado
          INNER JOIN lectura ON lista_libros.cvelectura = lectura.cvelectura
          INNER JOIN libro ON libro.cvelibro = lista_libros.cvelibro
          WHERE nocontrol=? AND lectura.cvelectura=?
          ORDER BY titulo";
      $tmp    = array($lectura[0]['nocontrol'], $_GET['info1']);
      $libros = $web->DB->GetAll($sql, $tmp);
      if (!isset($libros[0])) {
        $web->simple_message('warning', 'No hay libros registrados');
      } else {
        if (sizeof($libros) < 5) {
          $web->simple_message('warning', 'Debe seleccionar mínimo 5 libros');
        }
        $web->smarty->assign('libros', $libros);
      }

      $web->smarty->assign('cvelectura', $_GET['info1']);
      $web->smarty->assign('cmb_libro', $combo);

      $web->smarty->display('form_libro.html');
      die();
      break;

    case 'insert':
      if (!isset($_POST['datos']['cvelibro']) ||
        !isset($_POST['datos']['cvelectura'])) {
        message("danger", "No alteres la estructura de la interfaz", $web);
      }

      if ($_POST['datos']['cvelibro'] == "" ||
        $_POST['datos']['cvelectura'] == "") {
        message("danger", "Llena todos los campos", $web);
      }

      $cvelibro   = $_POST['datos']['cvelibro'];
      $cvelectura = $_POST['datos']['cvelectura'];

      $sql   = "SELECT * FROM libro WHERE cvelibro=?";
      $libro = $web->DB->GetAll($sql, $cvelibro);

      if (!isset($libro[0])) {
        message("danger", "No existe el libro seleccionado", $web);
      }

      $sql = "SELECT * FROM lectura
        INNER JOIN abecedario ON lectura.cveletra = abecedario.cve
        INNER JOIN laboral ON laboral.cveletra = abecedario.cve
        WHERE cvelectura=?
        AND lectura.cveperiodo=?";
      $lectura = $web->DB->GetAll($sql, array($cvelectura, $cveperiodo));

      if (!isset($lectura[0])) {
        message("danger", "No altere la estructura de la interfaz", $web);
      }

      $sql = "INSERT INTO lista_libros(cvelibro, cvelectura, cveperiodo, cveestado, calif_reporte)
        VALUES (?, ?, ?, 1, 0)";
      $web->query($sql, array($cvelibro, $cvelectura, $cveperiodo));
      header('Location: grupo.php?accion=form_libro&info1=' . $cvelectura);
      break;

    case 'formato_preguntas':
      header("Content-disposition: attachment; filename=formato_preguntas.pdf");
      header("Content-type: MIME");
      readfile("/home/slslctr/archivos/pdf/" . $cveperiodo . "/formato_preguntas.pdf");
      break;
  }

}

if (!isset($_GET['info1'])) {
  die('Información incompleta'); //por alguna razón no funciona sin esto
  message('danger', 'Información incompleta', $web);
}
$grupo = $_GET['info1'];

$sql = "SELECT distinct nocontrol FROM laboral
INNER JOIN abecedario on abecedario.cve = laboral.cveletra
INNER JOIN lectura on abecedario.cve = lectura.cveletra
WHERE laboral.cveletra in (SELECT cve FROM abecedario WHERE letra=?)
  AND laboral.cveperiodo=? AND nocontrol=?";
$grupo_promotor = $web->DB->GetAll($sql, array($grupo, $cveperiodo, $_SESSION['cveUser']));
if (!isset($grupo_promotor[0])) {
  message('danger', 'No existe el grupo en este periodo y/o no tiene permiso para acceder', $web);
}

//Info de encabezado
$sql = "SELECT distinct letra, laboral.nombre AS \"nombre_grupo\", sala.ubicacion, fechainicio, fechafinal,
nocontrol, usuarios.nombre AS \"nombre_promotor\" FROM laboral
INNER JOIN sala ON laboral.cvesala = sala.cvesala
INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
INNER JOIN periodo ON laboral.cveperiodo= periodo.cveperiodo
INNER JOIN lectura ON abecedario.cve = abecedario.cve
INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
WHERE nocontrol=? AND laboral.cveperiodo=? AND letra=?
ORDER BY letra";
$datos_rs = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo, $grupo));
$web->smarty->assign('info', $datos_rs[0]);

$tmp = array($grupo, $cveperiodo, $_SESSION['cveUser']);

//Datos de la tabla = Alumnos
$sql = "SELECT distinct usuarios.nombre, asistencia, comprension, reporte, asistencia, actividades,
participacion, terminado, nocontrol, cveeval, lectura.cveperiodo, lectura.cvelectura
FROM lectura
INNER JOIN evaluacion ON evaluacion.cvelectura = lectura.cvelectura
INNER JOIN abecedario ON lectura.cveletra = abecedario.cve
INNER JOIN usuarios ON lectura.nocontrol = usuarios.cveusuario
INNER JOIN laboral ON abecedario.cve = laboral.cveletra
WHERE letra=? AND lectura.cveperiodo=? AND nocontrol=?
ORDER BY usuarios.nombre";
$datos = $web->DB->GetAll($sql, array($grupo, $cveperiodo, $_SESSION['cveUser']));
if (!isset($datos[0])) {
  message('warning', 'No hay alumnos inscritos', $web);
}

$web->smarty->assign('bandera', 'true');
$web->smarty->assign('cveperiodo', $cveperiodo);
$web->smarty->assign('datos', $datos);
$web->smarty->assign('cvelectura', $datos[0]['cvelectura']);
$web->smarty->assign('grupo', $grupo);
$web->smarty->display("grupo.html");

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
function message($alert, $msg, $web)
{
  $web->simple_message($alert, $msg);
  $web->smarty->display("grupo.html");
  die();
}
