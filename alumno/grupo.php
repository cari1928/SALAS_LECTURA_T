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

showMessages();

$nombre_fichero = $web->route_pdf . $cveperiodo . "/formato_reporte.pdf";
if (file_exists($nombre_fichero)) {
  $web->smarty->assign('formato_reporte', true);
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
    case 'down':
      down();
      break;
    case 'del':
      die('PENDIENTE');
      break;
  }
}

if (!isset($_GET['info1'])) {
  die('Información incompleta'); //por alguna razón no funciona sin esto
  message('danger', 'Información incompleta 001');
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
 * Muestra el formulario para que el alumno seleccione sus libros
 */
function form_libro()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info1']) || !isset($_GET['info2'])) {
    message('danger', 'Información incompleta');
  }

  $lectura = $web->getReading($_GET['info1']);
  if (!isset($lectura[0])) {
    message("danger", "No altere la estructura de la interfaz");
  }

  $web->iniClases('usuario', "index grupos libro");

  //config tabla de libros
  //para no mostrar los libros que ya fueron registrados para ese alumno en ese periodo
  $libros = $web->getTableBooks(array($lectura[0]['nocontrol'], $cveperiodo, $_GET['info1']));
  $datos = array('data' => $libros);
  
  // config de la columna portada
  for ($i = 0; $i < sizeof($datos['data']); $i++) {
    $datos['data'][$i]['titulo'] = "<a href='grupo.php?accion=insert&info=".$datos['data'][$i]['cvelibro']."&info2=".$_GET['info1']."' 
      title='Seleccionar Libro'>".$datos['data'][$i]['titulo']."</a>";
      
    $datos['data'][$i][4] = "<center><a href='grupo.php?accion=insert&info=".$datos['data'][$i]['cvelibro']."&info2=".$_GET['info1']."'
      title='Seleccionar Libro'><img width='50%' src='../Images/portadas/".$datos['data'][$i][4]."'></a></center>";
      
    unset($datos['data'][$i]['cvelibro']);
    unset($datos['data'][$i][0]);
  }
  
  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $datos = json_encode($datos);
  
  $file = fopen("TextFiles/libros.txt", "w");
  fwrite($file, $datos);
  
  //mensaje sobre libros
  $libros = $web->getBooks($lectura[0]['nocontrol'], $_GET['info1']);
  if (!isset($libros[0])) {
    $web->simple_message('warning', 'No hay libros registrados');
  } else {
    if (sizeof($libros) < 5) {
      $web->simple_message('warning', 'Debe seleccionar mínimo 5 libros');
    }

    $libros = existsReports($libros, $lectura); // CHECAR SI HA SUBIDO LOS REPORTES DE LOS LIBROS
    $web->smarty->assign('libros', $libros);
  }

  $web->smarty->assign('datos', $datos);
  $web->smarty->assign('upload', true);
  $web->smarty->assign('grupo', $_GET['info2']);
  $web->smarty->assign('cvelectura', $_GET['info1']);
  $web->smarty->assign('form_libro', true);
  $web->smarty->display('form_libro.html');
  die();
}

/**
 * 
 */
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

  $dir_subida = $web->route_periodos . $cveperiodo . "/" . $letra_subida[0][0] .
  "/" . $_SESSION['cveUser'] . "/";

  if ($_FILES['datos']['size']['archivo'] > 1000000) {
    message('danger', 'El archivo es mayor a un MB.');
  }
  if ($_FILES['datos']['type']['archivo'] != 'application/pdf' &&
    $_FILES['datos']['type']['archivo'] != "application/msword" &&
    $_FILES['datos']['type']['archivo'] != "application/vnd.openxmlformats-officedocument.wordprocessingml.document" &&
    $_FILES['datos']['type']['archivo'] != "image/jpeg" &&
    $_FILES['datos']['type']['archivo'] != "image/png") {
    message('danger', 'Solo esta permitido subir archivos de tipo .pdf, .doc y .docx');
  }
  if (!isset($_POST['datos']['reporte'])) {
    message('danger', 'Información incompleta');
  }

  $cvelibro_subida = $web->getBook($_POST['datos']['reporte']);
  if (!isset($cvelibro_subida[0])) {
    message('danger', 'El libro no existe');
  }

  $redirect = array(
    'accion'=>'form_libro',
    'info1'=> $_GET['info1'],
    'info2'=> $letra_subida[0][0],
    'e'=>3);
  $nombre = $cvelibro_subida[0][2] . "_" . $_SESSION['cveUser'];
  $web->delFile($dir_subida . $nombre); //elimina archivos con el mismo nombre
  
  $nombre .= $web->getExtension($_FILES['datos']['type']['archivo']);
  if (!move_uploaded_file($_FILES['datos']['tmp_name']['archivo'], $dir_subida . $nombre)) {
    $redirect['e'] = 4;
  }
  header('Location: grupo.php?' . http_build_query($redirect));
}

/**
 * 
 */
function insert()
{
  global $web, $cveperiodo;
  
  if (!isset($_GET['info']) || !isset($_GET['info2'])) {
    message("danger", "No alteres la estructura de la interfaz");
  }
  if ($_GET['info'] == "" || $_GET['info2'] == "") {
    message("danger", "Llena todos los campos");
  }

  $cvelibro   = $_GET['info'];
  $cvelectura = $_GET['info2'];
  $libro = $web->getBook($cvelibro);
  if (!isset($libro[0])) {
    message("danger", "No existe el libro seleccionado");
  }

  $lectura = $web->getReadingMesh($cvelectura, $cveperiodo);
  if (!isset($lectura[0])) {
    message("danger", "No altere la estructura de la interfaz");
  }

  $web->insertBookList($cvelibro, $cvelectura, $cveperiodo);
  header('Location: grupo.php?accion=form_libro&info1=' . $cvelectura . "&info2=" . $lectura[0]['letra']);
}

/**
 *
 */
function existsReports($libros, $lectura)
{
  global $web, $cveperiodo;
  
  $dir_subida = $web->route_periodos . $cveperiodo . "/" . 
    $web->getLetter($lectura[0][0])[0][0] . "/" . $_SESSION['cveUser'] . "/";
    
  for ($i = 0; $i < count($libros); $i++) {
    $reporte = $dir_subida . $libros[$i][0] . "_" . $_SESSION['cveUser'];
    
    if (file_exists($reporte.".pdf") || file_exists($reporte.".doc") 
      || file_exists($reporte.".docx") || file_exists($reporte.".png") 
      || file_exists($reporte.".jpg")) {
      $libros[$i][3]         = 1;
      $libros[$i]['reporte'] = 1;
    } else {
      $libros[$i][3]         = 0;
      $libros[$i]['reporte'] = 0;
    }
  }
  return $libros;
}

/**
 *
 */
function down()
{
  global $web, $cveperiodo;

  if (!isset($_GET['info2'])) {
    header('Location: grupo.php?e=1');
    die();
  }
  if (!isset($_GET['info'])) {
    header('Location: grupo.php?info1=' . $_GET['info2'] . "&e=2");
    die();
  }

  $file = "formato_reporte.pdf";
  $dir  = $web->route_pdf . $cveperiodo . "/";
  if ($_GET['info'] != 1) {
    if (!isset($_GET['info3'])) {
      header('Location: grupo.php?info1=' . $_GET['info2'] . "&e=2");
      die();
    }
    $dir  = $cveperiodo . "/" . $_GET['info2'] . "/" . $_SESSION['cveUser'] . "/";
    $file = $web->getFile($dir, $_GET['info3']);
    $dir  = $web->route_periodos . $dir;
  }

  header("Content-disposition: attachment; filename=" . $file);
  header("Content-type: MIME");
  readfile($dir . $file);
}

/**
 *
 */
function showMessages()
{
  global $web;

  if (isset($_GET['e'])) {
    switch ($_GET['e']) {
      case 1:
        $web->simple_message('danger', 'No fue posible localizar el grupo');
        break;
      case 2:
        $web->simple_message('danger', 'No modifique la estructura de la interfaz');
        break;
      case 3:
        $web->simple_message('success', 'Se subió el reporte satisfactoriamente');
        break;
      case 4:
        $web->simple_message('danger', 'Ocurrió un error mientras se subía el archivo');
        break;
    }
  }
}
