<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web        = new LibrosControllers;
$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('warning', "No hay periodo actual");
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_insert':
      $web->iniClases('admin', "index libros nuevo");
      $web->smarty->assign('upload_libros', true);
      $web->smarty->assign('portada', 'no_disponible.jpg');
      $web->smarty->display('form_libros.html');
      die();
      break;

    case 'form_update':
      if (!isset($_GET['info2'])) {
        $web->simple_message('warning', 'No se especificó el libro');
        break;
      }

      $sql    = "SELECT * FROM libro WHERE cvelibro=?";
      $libros = $web->DB->GetAll($sql, $_GET['info2']);
      if (sizeof($libros) == 0) {
        $web->simple_message('warning', 'No existe el libro');
        break;
      }

      $libros[0]['portada'] = (empty($libros[0]['portada'])) ? "no_disponible.jpg" : $libros[0]['portada'];
      $web->iniClases('admin', "index libros actualizar");
      $web->smarty->assign('libros', $libros[0]);
      $web->smarty->assign('upload_libros', true);
      $web->smarty->assign('portada', $libros[0]['portada']);
      $web->smarty->display('form_libros.html');
      die();
      break;

    case 'insert':
      mInsertBook();
      break;

    case 'update':
      mUpdateBook();
      break;

    case 'delete':
      delete_book();
      break;

    case 'upload_file':
      mUploadFile();
      break;
  }
}

$web->iniClases('admin', "index libros");

$sql = "SELECT cvelibro, autor, titulo, editorial, cantidad FROM libro ORDER BY cvelibro";
$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$datos = $web->DB->GetAll($sql);
$datos = array('data' => $datos);

//se preparan los campos extra (estado_credito, eliminar, actualizar y mostrar)
for ($i = 0; $i < sizeof($datos['data']); $i++) {
  $datos['data'][$i][5] = "libros.php?accion=delete&info1=" . $datos['data'][$i][0];
  $datos['data'][$i][6] = "<center><a href='libros.php?accion=form_update&info2=" .
    $datos['data'][$i][0] . "'><img src='../Images/edit.png'></a></center>";
}

$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$datos = json_encode($datos);

$file = fopen("TextFiles/libros.txt", "w");
fwrite($file, $datos);

// mostrar posibles mensajes
showMessage();

$web->smarty->assign('datos', $datos);
$web->smarty->display("libros.html");

/************************************************************************************
 * FUNCIONES
 ************************************************************************************/
/**
 * Show info and error messages
 */
function showMessage()
{
  global $web;

  if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {

      case 1:
        $web->simple_message('info', 'Libro guardado correctamente');
        break;

      case 2:
        $web->simple_message('info', 'Libro actualizado correctamente');
        break;

      case 3:
        $web->simple_message('danger', 'Ocurrió un error al intentar guardar o actualizar la portada');
        break;
    }
  }
}

/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases Ruta a mostrar en links
 * @param  String $alert     Tipo de mensaje
 * @param  String $msg       Mensaje a desplegar
 * @param  String $cveusuario  Usado en caso de que se trate de un formulario de actualización
 */
function message($iniClases, $alert, $msg, $cvelibro = null)
{
  global $web;
  $web->iniClases('admin', $iniClases);
  $web->simple_message($alert, $msg);

  if ($cvelibro != null) {
    $sql   = "SELECT * FROM libro WHERE cvelibro=?";
    $libro = $web->DB->GetAll($sql, $cvelibro);
    $web->smarty->assign('libros', $libro[0]);
  }

  $web->smarty->display('form_libros.html');
  die();
}

/**
 * Ahorro de código, elimina un elemento de la tabla libro junto con los elementos relacionados en
 * otras tablas, o simplemente coloca en null los valores fuera de la tabla libro
 * @return boolean  false = Mostrar mensaje de error
 */
function delete_book()
{
  global $web;
  //se valida la contraseña
  switch ($web->valida_pass($_SESSION['cveUser'])) {

    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;

    case 2:
      $web->simple_message('danger', 'La contraseña de seguridad ingresada no es válida');
      return false;
  }

  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No altere la estructura de la interfaz, no se especificó el libro');
    return false;
  }

  //se verifica que exista el libro
  $sql    = "SELECT * FROM libro WHERE cvelibro=?";
  $libros = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($libros[0])) {
    $web->simple_message('danger', 'No existe el libro');
    return false;
  }

  $web->DB->startTrans();
  //actualiza laboral colocando el libro grupal como nulo
  $sql = "UPDATE laboral SET cvelibro_grupal=null WHERE cvelibro_grupal=?";
  $web->query($sql, $_GET['info1']);

  //elimina de lista_libros y libro
  $sql = "DELETE FROM lista_libros WHERE cvelibro=?";
  $web->query($sql, $_GET['info1']);
  $sql = "DELETE FROM libro WHERE cvelibro=?";
  $web->query($sql, $_GET['info1']);
  if ($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No se pudo completar la operación');
    $web->DB->CompleteTrans();
    return false;
  }
  $web->DB->CompleteTrans();
  header('Location: libros.php');
}

/**
 * Inserta un elemento de la tabla libro
 * @return boolean  false = Mostrar mensaje de error
 */
function mInsertBook()
{
  global $web;
  if (!isset($_POST['autor']) ||
    !isset($_POST['titulo']) ||
    !isset($_POST['editorial']) ||
    !isset($_POST['cantidad']) ||
    !isset($_POST['sinopsis']) ||
    !isset($_FILES['portada'])) {
    message("index libros nuevo", 'warning', "No alteres la estructura de la interfaz");
  }
  if ($_POST['autor'] == "" ||
    $_POST['titulo'] == "" ||
    $_POST['editorial'] == "" ||
    $_POST['cantidad'] == "" ||
    $_POST['sinopsis'] == "") {
    message("index libros nuevo", 'warning', "Llena todos los campos");
  }

  $sql = "INSERT INTO libro (autor, titulo, editorial, cantidad, sinopsis) VALUES (?, ?, ?, ?, ?)";
  $tmp = array($_POST['autor'],
    $_POST['titulo'],
    $_POST['editorial'],
    $_POST['cantidad'],
    $_POST['sinopsis']);
  if (!$web->query($sql, $tmp)) {
    message("index libros insertar", 'danger', "No fue posible guardar el libro");
    break;
  }

  mUploadFile($web->getLastCveLibro()[0][0], 1);
}

/**
 * @param $type 1 == INSERT | 2 == UPDATE
 */
function mUploadFile($cvelibro, $typeMessage)
{
  global $web;
  $web->deleteOldBanner($cvelibro);
  $extension      = $web->getExtension($_FILES['portada']['name']);
  $nombreTemporal = $_FILES['portada']['tmp_name'];
  $rutaArchivo    = $web->route . $cvelibro . "." . $extension;
  if (move_uploaded_file($nombreTemporal, $rutaArchivo)) {
    $sql = "UPDATE libro SET portada=? WHERE cvelibro=?";
    $web->query($sql, array(($cvelibro . "." . $extension), $cvelibro));
    header('Location: libros.php?msg=' . $typeMessage);
  } else {
    header('Location: libros.php?msg=3');
  }
}

/**
 * Update book information
 */
function mUpdateBook()
{
  global $web;
  if (!isset($_POST['autor']) ||
    !isset($_POST['titulo']) ||
    !isset($_POST['editorial']) ||
    !isset($_POST['cantidad']) ||
    !isset($_POST['sinopsis']) ||
    !isset($_FILES['portada'])) {
    message("index libros actualizar", 'warning', "No alteres la estructura de la interfaz", $_POST['cvelibro']);
  }
  if ($_POST['autor'] == "" ||
    $_POST['titulo'] == "" ||
    $_POST['editorial'] == "" ||
    $_POST['cantidad'] == "" ||
    $_POST['sinopsis'] == "") {
    message("index libros actualizar", 'warning', "Llena todos los campos", $_POST['cvelibro']);
  }

  $sql = "UPDATE libro SET autor=?, titulo=?, editorial=?, cantidad=?, sinopsis=? WHERE cvelibro=?";
  $tmp = array(
    $_POST['autor'],
    $_POST['titulo'],
    $_POST['editorial'],
    $_POST['cantidad'],
    $_POST['sinopsis'],
    $_POST['cvelibro']);
  if (!$web->query($sql, $tmp)) {
    message("index libros actualizar", 'danger', "No fue posible actualizar el libro", $_POST['cvelibro']);
    break;
  }

  mUploadFile($_POST['cvelibro'], 2);
}
