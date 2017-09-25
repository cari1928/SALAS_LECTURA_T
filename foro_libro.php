<?php
include 'sistema.php';
$web = new ForoControllers;

if (!isset($_GET['info']) || !isset($_GET['action'])) {
  $web->simple_message('danger', 'No es posible mostrar el foro solicitado');
  $web->smarty->display('foro_libro.html');
  die();
}

$web->iniClases(null, "index foros foro");

$sql   = "SELECT * FROM libro WHERE cvelibro=?";
$libro = $web->DB->GetAll($sql, $_GET['info']);
if (!isset($libro[0])) {
  header('Location: index.php?m=3'); //el libro no existe en nuestra base de datos
  die();
}

$nombre_fichero      = "/home/slslctr/Images/portadas/" . $libro[0]['portada'];
$libro[0]['portada'] = (!file_exists($nombre_fichero)) ? "no_disponible.jpg" : $libro[0]['portada'];

$sql = "SELECT * FROM comentario
INNER JOIN usuarios ON usuarios.cveusuario=comentario.cveusuario
WHERE cvelibro=? AND cverespuesta IS NULL";
$comentarios = $web->DB->GetAll($sql, $_GET['info']);

$sql = "SELECT * FROM comentario
INNER JOIN usuarios ON usuarios.cveusuario=comentario.cveusuario
WHERE cvelibro=? AND cverespuesta IS NOT NULL";
$respuestas = $web->DB->GetAll($sql, $_GET['info']);

if (isset($respuestas[0])) {
  foreach ($respuestas as $respuesta) {
    for ($i = 0; $i < count($comentarios); $i++) {
      if ($respuesta['cverespuesta'] == $comentarios[$i]['cvecomentario']) {
        if (isset($comentarios[$i]['respuesta'][0])) {
          $comentarios[$i]['respuesta'][count($comentarios[$i]['respuesta'])] = $respuesta;
        } else {
          $comentarios[$i]['respuesta'][0] = $respuesta;
        }
      }
    }
  }
}

$num_comentarios = 0;
if (isset($comentarios[0])) {
  $num_comentarios = sizeof($comentarios);
  $web->smarty->assign('comentarios', $comentarios);
}

if (isset($_SESSION['roles'])) {
  $web->smarty->assign('rol', $_SESSION['roles']);
}
$web->smarty->assign('num_comentarios', $num_comentarios);
$web->smarty->assign('libro', $libro[0]);
$web->smarty->display('foro_libro.html');
