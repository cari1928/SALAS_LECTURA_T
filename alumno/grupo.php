<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web = new GruposControllers;
$web->iniClases('usuario', "index grupos grupo");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('danger', 'No hay periodos actuales');
}

$nombre_fichero = "/home/slslctr/archivos/pdf/" . $cveperiodo . "/formato_preguntas.pdf";
if (file_exists($nombre_fichero)) {
  $web->smarty->assign('formato_preguntas', true);
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'fileinput':
      fileinput();
      break;

    case 'form_libro':
      form_libro();
      break;

    case 'insert':
      insert();
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
  message('danger', 'Información incompleta');
}
$grupo = $_GET['info1'];

$grupo_promotor = $web->getGroups($grupo, $cveperiodo, $_SESSION['cveUser']);
if (!isset($grupo_promotor[0])) {
  message('danger', 'No existe el grupo en este periodo y/o no tiene permiso para acceder');
}

//Info de encabezado
$datos_rs = $web->getInfoHeader($_SESSION['cveUser'], $cveperiodo, $grupo);
$web->smarty->assign('info', $datos_rs[0]);

//Datos de la tabla = Alumnos
$datos = $web->getDataUsers($grupo, $cveperiodo, $_SESSION['cveUser']);
if (!isset($datos[0])) {
  message('warning', 'No hay alumnos inscritos');
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
/**
 * Habilta el bloque de mensaje
 */
function message($alert, $msg)
{
  global $web;
  $web->simple_message($alert, $msg);
  $web->smarty->display("grupo.html");
  die();
}

/**
 * Ejecuta el case form_libro
 * Muestra el formulario para que el alumno seleccione sus libros
 */
function form_libro()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info1'])) {
    message('danger', 'Información incompleta');
  }

  $lectura = $web->getReading($_GET['info1']);
  if (!isset($lectura[0])) {
    message("danger", "No altere la estructura de la interfaz");
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

  $libros = $web->getBooks($lectura[0]['nocontrol'], $_GET['info1']);
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
}

function fileinput()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info1'])) {
    message('danger', 'Información incompleta');
  }

  $letra_subida = $web->getLetter($_GET['info1']);
  if (!isset($letra_subida[0])) {
    message('danger', 'No existe el grupo');
  }

  $dir_subida = "/home/slslctr/archivos/periodos/" .
    $cveperiodo . "/" .
    $letra_subida[0][0] . "/" .
    $_SESSION['cveUser'] . "/";

  if ($_FILES['datos']['size']['archivo'] > 1000000) {
    message('danger', 'El archivo es mayor a un MB.');
  }
  if ($_FILES['datos']['type']['archivo'] != 'application/pdf') {
    message('danger', 'Solo esta permitido subir archivos de tipo .pdf');
  }
  if (!isset($_POST['datos']['reporte'])) {
    message('danger', 'Información incompleta');
  }

  $cvelibro_subida = $web->getBook($_POST['datos']['reporte']);
  if (!isset($cvelibro_subida[0])) {
    message('danger', 'El libro no existe');
  }

  $nombre = $cvelibro_subida[0][2] . "_" . $_SESSION['cveUser'] . ".pdf";
  if (move_uploaded_file($_FILES['datos']['tmp_name']['archivo'], $dir_subida . $nombre)) {
    message('success', 'Se subió el reporte satisfactoriamente');
  } else {
    message('danger', 'Ocurrió un error mientras se subía el archivo');
  }
}

function insert()
{
  global $web, $cveperiodo;

  if (!isset($_POST['datos']['cvelibro']) ||
    !isset($_POST['datos']['cvelectura'])) {
    message("danger", "No alteres la estructura de la interfaz");
  }

  if ($_POST['datos']['cvelibro'] == "" ||
    $_POST['datos']['cvelectura'] == "") {
    message("danger", "Llena todos los campos");
  }

  $cvelibro   = $_POST['datos']['cvelibro'];
  $cvelectura = $_POST['datos']['cvelectura'];

  $libro = $web->getBook($cvelibro);
  if (!isset($libro[0])) {
    message("danger", "No existe el libro seleccionado");
  }

  $lectura = $web->getReadingMesh($cvelectura, $cveperiodo);
  if (!isset($lectura[0])) {
    message("danger", "No altere la estructura de la interfaz");
  }

  $web->insertBookList($cvelibro, $cvelectura, $cveperiodo);
  header('Location: grupo.php?accion=form_libro&info1=' . $cvelectura);
}
