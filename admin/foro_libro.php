<?php
include '../sistema.php';

$web = new ForoControllers;
$web->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta admin/templates_c
$web->iniClases("admin", "index foros foro");

if(!isset($_GET['info']) || !isset($_GET['action'])) {
  $web->simple_message('danger', 'No es posible mostrar el foro solicitado');
  $web->smarty->display('foro/foro_libro.html');
  die();
}

showMessages();

switch ($_GET['action']) {
  case 'comment':
    $libro = $web->getLibro($_GET['info']);
    if(!isset($libro[0])) {
      header('Location: foro_libro.php'); die();
    }
    
    if($web->insertLibro(array($libro[0]['cvelibro'], $_SESSION['cveUser'], $_POST['review']))) {
      header('Location: foro_libro.php?action=show&info='.$libro[0]['cvelibro'].'&m=1'); die();
    } 
    
    header('Location: foro_libro.php?action=show&info='.$libro[0]['cvelibro'].'&e=1'); die();
    break;
  
  default:
    showForo();
    break;
}

/************************************************************************************
 * FUNCIONES
 ************************************************************************************/
/**
 * Muestra el foro seleccionado
 */
function showForo() {
  global $web;
  
  $libro = $web->getAll('*', array('cvelibro'=>$_GET['info']), 'libro');
  if (!isset($libro[0])) {
    header('Location: index.php?m=3'); die();//el libro no existe en nuestra base de datos
  }
  
  $nombre_fichero      = $web->route_portadas . $libro[0]['portada'];
  $libro[0]['portada'] = (!file_exists($nombre_fichero) || empty($libro[0]['portada'])) 
    ? "no_disponible.jpg" : $libro[0]['portada'];
  $comentarios = $web->getComments($_GET['info']); 
  $respuestas = $web->getAnswers($_GET['info']);
  
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
  
  $web->smarty->assign('rol', $_SESSION['roles']);
  $web->smarty->assign('num_comentarios', $num_comentarios);
  $web->smarty->assign('libro', $libro[0]);
  $web->smarty->display('foro/foro_libro.html');
}

/**
 * 
 */
function showMessages() {
  global $web;
  
  if(isset($_GET['m'])) {
    switch ($_GET['m']) {
      case '1':
      $web->simple_message('info', 'Tu comentario ha sido registrado con éxito');
      break;
    }
  }
  
  if(isset($_GET['e'])) {
    switch ($_GET['e']) {
      case '1':
      $web->simple_message('danger', 'Hubo un error al registrar el comentario');
      break;
    }
  }
} 