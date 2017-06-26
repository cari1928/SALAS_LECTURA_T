<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'form_insert':
      $web->iniClases('admin', "index libros nuevo");
      $web->smarty->display('form_libros.html');
      die();
      break;

    case 'form_update':
      if (!isset($_GET['info2'])) {
        $web->smarty->assign('alert', 'danger');
        $web->smarty->assign('msg', 'No se especificó el libro');
        break;
      }

      $sql    = "select * from libro where cvelibro=?";
      $libros = $web->DB->GetAll($sql, $_GET['info2']);
      if (sizeof($libros) == 0) {
        $web->smarty->assign('alert', 'danger');
        $web->smarty->assign('msg', 'No existe el libro');
        break;
      }

      $sql                 = "select * from libro where cvelibro=" . $_GET['info2'];
      $libros              = $web->DB->GetAll($sql);
      $libros[0]['precio'] = substr($libros[0]['precio'], 1);

      $web->iniClases('admin', "index libros actualizar");
      $web->smarty->assign('libros', $libros[0]);
      $web->smarty->display('form_libros.html');
      die();
      break;

    case 'insert':
      //verifica existencia de todos los campos
      if (!isset($_POST['autor']) ||
        !isset($_POST['titulo']) ||
        !isset($_POST['editorial']) ||
        !isset($_POST['precio'])) {
        message("index libros nuevo", "No alteres la estructura de la interfaz", $web);
      }

      //verifica que los campos contengan algo
      if ($_POST['autor'] == "" ||
        $_POST['titulo'] == "" ||
        $_POST['editorial'] == "" ||
        $_POST['precio'] == "") {
        message("index libros nuevo", "Llena todos los campos", $web);
      }

      $sql = "insert into libro (autor, titulo, editorial, precio) values (?, ?, ?, ?)";
      $tmp = array($_POST['autor'], $_POST['titulo'], $_POST['editorial'], $_POST['precio']);
      if (!$web->query($sql, $tmp)) {
        $web->simple_message('danger', 'No se pudo completar la operación');
        break;
      }

      header('Location: libros.php');
      break;

    case 'update':
      //verifica existencia de todos los campos
      if (!isset($_POST['autor']) ||
        !isset($_POST['titulo']) ||
        !isset($_POST['editorial']) ||
        !isset($_POST['precio'])) {
        message("index libros actualizar", "No alteres la estructura de la interfaz", $web, $_GET['accion']);
      }

      //verifica que los campos contengan algo
      if ($_POST['autor'] == "" ||
        $_POST['titulo'] == "" ||
        $_POST['editorial'] == "" ||
        $_POST['precio'] == "") {
        message("index libros actualizar", "Llena todos los campos", $web, $_GET['accion']);
      }

      $sql = "update libro set autor=?, titulo=?, editorial=?, precio=? where cvelibro=?";
      $tmp = array($_POST['autor'], $_POST['titulo'], $_POST['editorial'], $_POST['precio'], $_POST['cvelibro']);
      if (!$web->query($sql, $tmp)) {
        $web->smarty->assign('alert', 'danger');
        $web->smarty->assign('msg', 'No se pudo completar la operación');
        break;
      }

      header('Location: libros.php');
      break;

    case 'delete':
      delete_book($web);
      break;
  }
}

$web->iniClases('admin', "index libros");

$sql    = "select cvelibro, autor, titulo, editorial, precio from libro order by cvelibro";
$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$datos = $web->DB->GetAll($sql);
$datos = array('data' => $datos);

//se preparan los campos extra (estado_credito, eliminar, actualizar y mostrar)
for ($i = 0; $i < sizeof($datos['data']); $i++) {

  $datos['data'][$i][5] = "libros.php?accion=delete&info1=".$datos['data'][$i][0];

  $datos['data'][$i][6] = "<center><a href='libros.php?accion=form_update&info2=".$datos['data'][$i][0]."'> 
					<img src='../Images/edit.png'></a></center>";
}

$web->DB->SetFetchMode(ADODB_FETCH_NUM);
$datos = json_encode($datos);

$file = fopen("TextFiles/libros.txt", "w");
fwrite($file, $datos);

$web->smarty->assign('datos', $datos);
$web->smarty->display("libros.html");

/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases Ruta a mostrar en links
 * @param  String $msg       Mensaje a desplegar
 * @param  $web              Para poder aplicar las funciones de $web
 * @param  String $cveusuario   Usado en caso de que se trate de un formulario de actualización
 */
function message($iniClases, $msg, $web, $cvelibro = null)
{
  $web->iniClases('admin', $iniClases);

  $web->smarty->assign('alert', 'danger');
  $web->smarty->assign('msg', $msg);

  if ($cvelibro != null) {
    $sql   = "select * from libro where cvelibro=?";
    $libro = $web->DB->GetAll($sql, $cvelibro);
    $web->smarty->assign('libros', $libro[0]);
  }

  $web->smarty->display('form_libros.html');
  die();
}

/**
 * Ahorro de código, elimina un elemento de la tabla libro junto con los elementos relacionados en
 * otras tablas, o simplemente coloca en null los valores fuera de la tabla libro
 * @param  Class    $web Objeto para poder usar smarty
 * @return boolean  false = Mostrar mensaje de error
 */
function delete_book($web)
{
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
  $sql    = "select * from libro where cvelibro=?";
  $libros = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($libros[0])) {
    $web->simple_message('danger', 'No existe el libro');
    return false;
  }
  
  $web->DB->startTrans();
  //actualiza laboral colocando el libro grupal como nulo
  $sql = "update laboral set cvelibro_grupal=null where cvelibro_grupal=?";
  $web->query($sql, $_GET['info1']);
  
  //elimina de lista_libros y libro
  $sql = "delete from lista_libros where cvelibro=?";
  $web->query($sql, $_GET['info1']);
  $sql = "delete from libro where cvelibro=?";
  $web->query($sql, $_GET['info1']);
  if ($web->DB->HasFailedTrans()) {
    $web->simple_message('danger', 'No se pudo completar la operación');
    $web->DB->CompleteTrans();
    return false;
  }
  $web->DB->CompleteTrans();
  header('Location: libros.php');
}
