<?php
include 'sistema.php';

if (!isset($_GET['info'])) {
  header('Location: index.php?m=2'); //falta informacion del foro
  die();
}

$sql   = "SELECT * FROM libro where cvelibro=?";
$libro = $web->DB->GetAll($sql, $_GET['info']);
if (!isset($libro[0])) {
  header('Location: index.php?m=3'); //el libro no existe en nuestra base de datos
  die();
}

$nombre_fichero      = "/home/slslctr/Images/portadas/" . $libro[0]['portada'];
$libro[0]['portada'] = (!file_exists($nombre_fichero)) ? "no_disponible.jpg" : $libro[0]['portada'];

$sql = "SELECT * FROM comentario inner join usuarios on usuarios.cveusuario = comentario.cveusuario
                         WHERE cvelibro=? AND cverespuesta IS NULL";
$comentarios = $web->DB->GetAll($sql, $_GET['info']);

$sql = "SELECT * FROM comentario inner join usuarios on usuarios.cveusuario = comentario.cveusuario
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

$no_comentarios = 0;
if (isset($comentarios[0])) {
  $no_comentarios = sizeof($comentarios);
  $web->smarty->assign('comentarios', $comentarios);
  $web->smarty->assign('no_comentarios', $no_comentarios);
}
$web->smarty->assign('libro', $libro[0]);
$web->smarty->display('foro_libro.html');
