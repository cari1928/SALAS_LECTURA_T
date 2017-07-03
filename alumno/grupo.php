<?php

include '../sistema.php';

if ($_SESSION['roles'] != 'U') {
  $web->checklogin();
}

$web->iniClases('usuario', "index grupos grupo");
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('danger', 'No hay periodos actuales', $web);
}

$nombre_fichero = "/home/slslctr/archivos/pdf/" . $cveperiodo . "/formato_preguntas.pdf";
if (file_exists($nombre_fichero)) {
  $web->smarty->assign('formato_preguntas', true);
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'fileinput':
      if (!isset($_GET['info1'])) {
        message('danger', 'Información incompleta', $web);
      }

      $sql          = "select letra from abecedario where cve in (select cveletra from lectura where cvelectura = ?)";
      $letra_subida = $web->DB->GetAll($sql, $_GET['info1']);
      if (!isset($letra_subida[0])) {
        message('danger', 'No existe el grupo', $web);
      }
      $dir_subida = "/home/slslctr/archivos/periodos" . "/" . $cveperiodo . "/" . $letra_subida[0][0] . "/" . $_SESSION['cveUser'] . "/";

      if ($_FILES['datos']['size']['archivo'] > 1000000) {
        message('danger', 'El archivo es mayor a un MB.', $web);
      }
      if ($_FILES['datos']['type']['archivo'] != 'application/pdf') {
        message('danger', 'Solo esta permitido subir archivos de tipo .pdf', $web);
      }

      if (!isset($_POST['datos']['reporte'])) {
        message('danger', 'Información incompleta', $web);
      }

      $sql             = "select cvelibro from libro where cvelibro = ?";
      $cvelibro_subida = $web->DB->GetAll($sql, $_POST['datos']['reporte']);
      if (!isset($cvelibro_subida[0])) {
        message('danger', 'El libro no existe', $web);
      }

      $nombre = $cvelibro_subida[0][0] . "_" . $_SESSION['cveUser'] . ".pdf";
      if (move_uploaded_file($_FILES['datos']['tmp_name']['archivo'], $dir_subida . $nombre)) {
        message('success', 'Se subio el reporte satisfactoriamente', $web);
      } else {
        message('danger', 'Ocurrio un error mientras se subia el archivo', $web);
      }
      break;

    case 'form_libro':
      if (!isset($_GET['info1'])) {
        message('danger', 'Información incompleta', $web);
      }

      $sql     = "select * from lectura where cvelectura=?";
      $lectura = $web->DB->GetAll($sql, $_GET['info1']);

      if (!isset($lectura[0])) {
        message("danger", "No altere la estructura de la interfaz", $web);
      }

      $web->iniClases('usuario', "index grupos libro");

      //para no mostrar los libros que ya fueron registrados para ese alumno en ese periodo
      $sql = "select cvelibro, titulo from libro
        where cvelibro not in
        (select cvelibro from lista_libros
          inner join lectura on lectura.cvelectura = lista_libros.cvelectura
          inner join abecedario on abecedario.cve = lectura.cveletra
          inner join laboral on laboral.cveletra = abecedario.cve
          where nocontrol=? and laboral.cveperiodo=? and lectura.cvelectura=?)
        order by titulo";
      $combo = $web->combo($sql, null, '../', array($lectura[0]['nocontrol'], $cveperiodo, $_GET['info1']));

      $sql = "select libro.cvelibro, titulo, estado from lista_libros
          inner join estado on estado.cveestado = lista_libros.cveestado
          inner join lectura on lista_libros.cvelectura = lectura.cvelectura
          inner join libro on libro.cvelibro = lista_libros.cvelibro
          where nocontrol=? and lectura.cvelectura=?
          order by titulo";
      $tmp    = array($lectura[0]['nocontrol'], $_GET['info1']);
      $libros = $web->DB->GetAll($sql, $tmp);
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
      break;

    case 'insert':
      if (!isset($_POST['datos']['cvelibro']) ||
        !isset($_POST['datos']['cvelectura'])) {
        message("danger", "No alteres la estructura de la interfaz", $web);
      }

      if ($_POST['datos']['cvelibro'] == "" ||
        $_POST['datos']['cvelectura'] == "") {
        message("danger", "Llena todos los campos", $web);
      }

      $cvelibro   = $_POST['datos']['cvelibro'];
      $cvelectura = $_POST['datos']['cvelectura'];

      $sql   = "select * from libro where cvelibro=?";
      $libro = $web->DB->GetAll($sql, $cvelibro);

      if (!isset($libro[0])) {
        message("danger", "No existe el libro seleccionado", $web);
      }

      $sql = "select * from lectura
        inner join abecedario on lectura.cveletra = abecedario.cve
        inner join laboral on laboral.cveletra = abecedario.cve
        where cvelectura=?
        and lectura.cveperiodo=?";
      $lectura = $web->DB->GetAll($sql, array($cvelectura, $cveperiodo));

      if (!isset($lectura[0])) {
        message("danger", "No altere la estructura de la interfaz", $web);
      }

      $sql = "insert into lista_libros(cvelibro, cvelectura, cveperiodo, cveestado, calif_reporte)
        values (?, ?, ?, 1, 0)";
      $web->query($sql, array($cvelibro, $cvelectura, $cveperiodo));
      header('Location: grupo.php?accion=form_libro&info1=' . $cvelectura);
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
  message('danger', 'Información incompleta', $web);
}
$grupo = $_GET['info1'];

$sql = "select distinct nocontrol from laboral
      inner join abecedario on abecedario.cve = laboral.cveletra
      inner join lectura on abecedario.cve = lectura.cveletra
      where laboral.cveletra in (select cve from abecedario where letra=?)
          and laboral.cveperiodo=? and nocontrol=?";
$grupo_promotor = $web->DB->GetAll($sql, array($grupo, $cveperiodo, $_SESSION['cveUser']));

if (!isset($grupo_promotor[0])) {
  message('danger', 'No existe el grupo en este periodo y/o no tiene permiso para acceder', $web);
}

//Info de encabezado
$sql = "select distinct letra, laboral.nombre as \"nombre_grupo\", sala.ubicacion, fechainicio, fechafinal, nocontrol, usuarios.nombre as \"nombre_promotor\" from laboral
    inner join sala on laboral.cvesala = sala.cvesala
    inner join abecedario on laboral.cveletra = abecedario.cve
    inner join periodo on laboral.cveperiodo= periodo.cveperiodo
    inner join lectura on abecedario.cve = abecedario.cve
    inner join usuarios on laboral.cvepromotor = usuarios.cveusuario
    where nocontrol=? and laboral.cveperiodo=? and letra=?
    order by letra";
$datos_rs = $web->DB->GetAll($sql, array($_SESSION['cveUser'], $cveperiodo, $grupo));
$web->smarty->assign('info', $datos_rs[0]);

$tmp = array($grupo, $cveperiodo, $_SESSION['cveUser']);

//Datos de la tabla = Alumnos
$sql = "select distinct usuarios.nombre, asistencia, comprension, reporte,
  asistencia, actividades, participacion, terminado, nocontrol, cveeval, lectura.cveperiodo,
  lectura.cvelectura from lectura
  inner join evaluacion on evaluacion.cvelectura = lectura.cvelectura
  inner join abecedario on lectura.cveletra = abecedario.cve
  inner join usuarios on lectura.nocontrol = usuarios.cveusuario
  inner join laboral on abecedario.cve = laboral.cveletra
  where letra=? and lectura.cveperiodo=? and nocontrol=?
  order by usuarios.nombre";
$datos = $web->DB->GetAll($sql, array($grupo, $cveperiodo, $_SESSION['cveUser']));
if (!isset($datos[0])) {
  message('warning', 'No hay alumnos inscritos', $web);
}

$web->smarty->assign('bandera', 'true');
$web->smarty->assign('cveperiodo', $cveperiodo);
$web->smarty->assign('datos', $datos);
$web->smarty->assign('grupo', $grupo);
$web->smarty->display("grupo.html");

function message($alert, $msg, $web)
{
  $web->simple_message($alert, $msg);
  $web->smarty->display("grupo.html");
  die();
}
