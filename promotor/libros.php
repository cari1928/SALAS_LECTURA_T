<?php
include "../sistema.php";

if ($_SESSION['roles'] != 'P') {
  $web->checklogin();
}

$web = new PromoLibrosControllers;
$web->iniClases('promotor', "index grupos libros");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

showMessages();

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodos actuales');
  $web->smarty->display("promosala.html");
  die();
}

databasic();
$grupo = $nocontrol = $cvelectura = "";
assigndata();

if (isset($_GET['accion'])) {
  switch ($_GET['accion']) {
    case 'form_add':
      $web->smarty->display('form_libros.html');
      die();
      break;
    case 'save_book':
      save_book();
      break;
    case 'assign':
      assign_book();
      break;
  }
}

$books_next      = $web->getAll(array('cvelibro', 'titulo', 'autor', 'editorial'), array('status' => 'no existente'), 'libro');
$books_next_read = $web->getBooks('no existente', $_GET['info']);
if (!isset($books_next[0]) && !isset($books_next_read[0])) {
  message('warning', 'No hay libros para mostrar');
}

$books_next = array('data' => $books_next);
$flag       = false;
if (empty($books_next['data'])) {
  for ($i = 0; $i < sizeof($books_next_read); $i++) {
    $books_next['data'][$i]             = $books_next_read[$i];
    $books_next['data'][$i]['opciones'] = "<centet><label>Ya has leído este libro</label></center>";
  }
}

for ($i = 0; $i < sizeof($books_next['data']); $i++) {
  $flag = false;

  for ($j = 0; $j < sizeof($books_next_read); $j++) {
    if ($books_next['data'][$i]['cvelibro'] == $books_next_read[$j]['cvelibro']) {
      $books_next['data'][$i]['opciones'] = "<centet><label>Ya has leído este libro</label></center>";
      $flag                               = true;
    }
  }

  if (!$flag) {
    $books_next['data'][$i]['opciones'] = "<center><a href='libros.php?accion=assign&info=" . $cvelectura . "&info2=" . $nocontrol . "&info3=" . $grupo . "&info4=" . $books_next['data'][$i]['cvelibro'] . "'/><img src = '../Images/add_book.png' /></a></center>";
  }
}

// $web->debug($books_next);

$books_next = json_encode($books_next);
$file       = fopen("TextFiles/libros.txt", "w");
fwrite($file, $books_next);

$web->smarty->assign('libros', true);
$web->smarty->display('libros.html');
/****************************************************************************************************
 * FUNCIONES
 ****************************************************************************************************/
/**
 *
 */
function showMessages()
{
  global $web;

  if (isset($_GET['a'])) {
    switch ($_GET['a']) {
      case 1:
        $web->simple_message('info', 'Libro asignado correctamente');
        break;
      case 2:
        $web->simple_message('danger', 'No se ha podidio asignar el libro');
        break;
      case 3:
        $web->simple_message('info', 'Libro agregado correctamente');
        break;
      case 4:
        $web->simple_message('danger', 'No se ha podidio agregar el libro');
        break;
    }
  }
}

/**
 * Used to display error and success mesages
 */
function message($alert, $msg)
{
  global $web;
  $web->simple_message($alert, $msg);
  $web->smarty->display('libros.html');
  die();
}

/**
 *
 */
function save_book()
{
  global $web, $cveperiodo, $grupo, $cvelectura, $nocontrol;

  //verificar que todos los campos se hayan llenado
  if (!isset($_POST['titulo']) || !isset($_POST['autor']) || !isset($_POST['editorial']) ||
    !isset($_POST['sinopsis'])) {
    message('warning', 'No modifique la estructura');
  }
  //verificar que todos los campos se hayan llenado
  if (empty($_POST['titulo']) || empty($_POST['autor']) || empty($_POST['editorial']) ||
    empty($_POST['sinopsis'])) {
    message('warning', 'No se llenaron todos los campos');
  }

  $redirect = array(
    'info'  => $cvelectura,
    'info2' => $nocontrol,
    'info3' => $grupo,
    'a'     => 3,
  );
  $parameters = array(
    'titulo'    => $_POST['titulo'],
    'autor'     => $_POST['autor'],
    'editorial' => $_POST['editorial'],
    'sinopsis'  => $_POST['sinopsis'],
    'status'    => 'no existente',
  );
  if (!$web->insert('libro', $parameters)) {
    $redirect['a'] = 4;
  }
  header('Location: libros.php?' . http_build_query($redirect));
}

/**
 * Verifica que se mande la infomacion necesaria por GET
 */
function databasic()
{
  global $cveperiodo, $web;

  if (!isset($_GET['info']) || !isset($_GET['info2']) || !isset($_GET['info3'])) {
    message('warning', 'No altere la estructura');
  }
  if (empty($_GET['info']) || empty($_GET['info2']) || empty($_GET['info3'])) {
    message('warning', 'No se mando la informacion completa');
  }

  //verificar que el alumno exista
  $alumno = $web->getAll('*', array('cveusuario' => $_GET['info2']), 'usuarios');
  if (!isset($alumno[0])) {
    $web->simple_message('warning', 'El alumno no existe');
  }

  //verificar que el alumno pertenezca a su grupo
  $alumno_grupo = $web->getLaboral($_SESSION['cveUser'], $cveperiodo, $_GET['info3'], $_GET['info2']);
  if (!isset($alumno_grupo[0])) {
    message('warning', 'El alumno no pertenece al grupo');
  }
  return true;
}

/**
 * Realiza la asignacion de la informacion necesaria por GET
 */
function assigndata()
{
  global $grupo, $nocontrol, $cvelectura, $web;
  $cvelectura = $_GET['info'];
  $grupo      = $_GET['info3'];
  $nocontrol  = $_GET['info2'];

  $web->smarty->assign('grupo', $grupo);
  $web->smarty->assign('nocontrol', $nocontrol);
  $web->smarty->assign('cvelectura', $cvelectura);
}

/**
 *
 */
function assign_book()
{
  global $cveperiodo, $web;

  if (!isset($_GET['info']) || !isset($_GET['info2']) || !isset($_GET['info3'])
    || !isset($_GET['info4'])) {
    message('warning', 'Falta información del libro');
  }
  if (empty($_GET['info']) || empty($_GET['info2']) || empty($_GET['info3']) ||
    empty($_GET['info4'])) {
    message('warning', 'Falta información del libro');
  }

  $libro = $web->getAll('*', array('cvelibro' => $_GET['info4']), 'libro');
  if (!isset($libro[0])) {
    message('warning', 'El libro no está dado de alta');
  }

  $redirect = array(
    'info'  => $_GET['info'],
    'info2' => $_GET['info2'],
    'info3' => $_GET['info3'],
    'a'     => 1,
  );
  $parameters = array('cvelibro' => $_GET['info4'], 'cvelectura' => $_GET['info'],
    'cveperiodo'                   => $cveperiodo, 'cveestado'     => 1, 'calif_reporte' => 0);
  if (!$web->insert('lista_libros', $parameters)) {
    $redirect['a'] = 2;
  }
  header('Location: libros.php?' . http_build_query($redirect));
}
