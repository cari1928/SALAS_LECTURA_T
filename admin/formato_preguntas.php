<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web->iniClases('admin', "index formato");
$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->simple_message('warning', 'No hay periodo actual');
}

if (isset($_GET['accion'])) {
  
  switch ($_GET['accion']) {
      
      case 'fileinput':
        $dir_subida ="/home/ubuntu/workspace/pdf" . "/" . $cveperiodo . "/";
      
        if($_FILES['datos']['size']['archivo'] > 1000000){
          message('danger', 'El archivo es mayor a un MB.', $web);
        }
        if($_FILES['datos']['type']['archivo'] != 'application/pdf'){
             message('danger', 'Solo esta permitido subir archivos de tipo .pdf', $web);
       }

       $nombre = "formato_preguntas.pdf";
       if (move_uploaded_file($_FILES['datos']['tmp_name']['archivo'], $dir_subida.$nombre)){
         header('Location: index.php?aviso=1');
       }else{
         header('Location: index.php?aviso=2');
       }
      break;
  }
  
}

$web->smarty->display('formato_preguntas.html');