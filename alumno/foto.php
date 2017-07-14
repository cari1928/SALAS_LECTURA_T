<?php
include '../sistema.php';
include "../lib/simpleImage/SimpleImage.class.php"; //incluimos la clase

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web->iniClases('usuario', "index foto");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);
$bandera_mensaje = false;

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'camara':
      if (isset($_POST['foto'])) {
        $web->guarda_foto(trim($_POST['foto']));
        die();
      }
      break;

    case 'upload':
      //$web->debug($_FILES);
      $dir_subida = "/home/slslctr/archivos/fotos/";
      if ($_FILES['foto']['size'] > 1000000) {
        $web->simple_message('danger', 'El archivo es mayor a un MB.');
        $bandera_mensaje = true;
        break;
      }
      if ($_FILES['foto']['type'] != 'image/jpeg') {
        $web->simple_message('danger', 'Solo esta permitido subir archivos de tipo JPG');
        $bandera_mensaje = true;
        break;
      }

      $nombre = $_SESSION['cveUser'] . ".jpg";
      if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir_subida . $nombre)) {
        redimencionar($web, $dir_subida, $nombre);
        header('Location: index.php?aviso=1');
      } else {
        header('Location: index.php?aviso=2');
      }
      break;

    case 'galeria':
      break;
  }
}

if (!$bandera_mensaje) {
  $aviso_camara = "Si toma una foto desde la webcam, se guardará automáticamente como foto de perfil.";
  $web->simple_message('warning', $aviso_camara);
}

$sql  = "SELECT foto FROM usuarios WHERE cveusuario=?";
$foto = $web->DB->GetAll($sql, $_SESSION['cveUser']);
if (!isset($foto[0])) {
  die('mensaje de error');
}

$web->smarty->assign('foto', $foto[0]['foto']);
$web->smarty->display('foto.html');

function redimencionar($web, $direccion, $nombre)
{
  $obj_simpleimage = new SimpleImage(); //creamos un objeto de la clase SimpleImage
  $obj_simpleimage->load($direccion . $nombre); //leemos la imagen

  $var_nuevo_archivo = $nombre; //asignamos un nombre
  $obj_simpleimage->resize(127, 137);

  $obj_simpleimage->save($direccion . $var_nuevo_archivo); //guardamos los cambios efectuados en la imagen
  //  unlink($var_img_dir . $var_name_img); //eliminamos del temporal la imagen que estabamos subiendo
  //  echo "<div align=\"center\">Imagen subida correctamente. <br /><h4>Vista de la imagen:</h4><br /><img src=\"".$var_img_dir.$var_nuevo_archivo."\" alt=\"".$var_nuevo_archivo."\" /></div>"; //mostramos los resultados
}
