<?php

include "../sistema.php";

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web = new AdminGrupoControllers;
//verifica el periodo
$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  $web->iniClases('admin', "index alumnos grupos");
  $web->simple_message('warning', 'No hay periodos actuales');
  break;
}

if (isset($_GET['accion'])) {

  switch ($_GET['accion']) {

    case 'delete_alumno':
      delete_alumno($web);
      break;
    case 'alumnos':
      mostrar_alumnos($web);
      break;

    case 'insert':
      $type = (isset($_POST['promotor'])) ? 'promotor' : 'alumno';
      insertar_libro_alumno($type);
      break;

    case 'delete':
      eliminar_libro_alumno($web);
      break;

    case 'delete_promotor':
      eliminar_libro_alumno($web, 'promotor');
      break;

    case 'grupos':
    case 'historial':
      mostrar_grupos_promotor($web);
      break;

    case 'reporte':
      ver_reporte($web);
      break;

    case 'index_grupos_libros':
    case 'libros':
      mostrar_libros_promotor($web);
      $web->smarty->assign('libros_promo', 'libros');
      if ($_GET['accion'] == 'index_grupos_libros') {
        $web->smarty->assign('libros_promo', 'index');
      }

      // respaldos
      $web->smarty->assign('rInfo1', $_GET['info1']);
      $web->smarty->assign('rInfo2', $_GET['info2']);
      $web->smarty->assign('rInfo3', $_GET['info3']);
      break;

    case 'index_grupos':
      mostrar_alumnos_grupo();
      break;
  }
}

if (isset($_GET['e'])) {
  if ($_GET['e'] == 1) {
    $web->simple_message('warning', 'Falta información para continuar');
  }
}
$web->smarty->display('grupo.html');
/************************************************************************************************
 * FUNCIONES
 ************************************************************************************************/
/**
 * Muestra: Barra gris superior con los datos del grupo
 * Lista de calificaciones en base a un alumno
 * También datos sobre los libros, usa el metodo mostrarLibros()
 * @param  Class   $web Objeto para poder usar smarty
 * @return boolean False = Mostrar mensaje de error
 */
function mostrar_alumnos($web)
{
  global $cveperiodo;

  //verifica que se haya mandado el grupo
  if (!isset($_GET['info1'])) {
    $web->iniClases('admin', "index alumnos grupos");
    $web->simple_message('warning', 'Hacen falta datos para continuar');
    return false;
  }

  //verifica la existencia del grupo
  $grupo = $web->getGroup($_GET['info1'], $cveperiodo);
  if (!isset($grupo[0])) {
    $web->iniClases('admin', "index alumnos grupos");
    $web->simple_message('danger', 'El grupo seleccionado no existe');
    return false;
  }

  $web->iniClases('admin', "index alumnos grupo-" . $_GET['info1']);

  //verifica que se haya mandado el alumno
  if (!isset($_GET['info2'])) {
    $web->simple_message('danger', 'Hace falta información para continuar');
    return false;
  }

  // verifica la existencia del alumno
  $sql = "SELECT * FROM lectura
  WHERE cveletra in (SELECT cve FROM abecedario WHERE letra=?)
  and nocontrol=? and cveperiodo=?";
  $alumno = $web->DB->GetAll($sql, array($_GET['info1'], $_GET['info2'], $cveperiodo));

  if (!isset($alumno[0])) {
    $web->simple_message('danger',
      'El alumno seleccionado no está registrado por completo en el grupo');
    return false;
  }

  //Info de encabezado
  $sql = "SELECT distinct letra, laboral.nombre as \"nombre_grupo\", sala.ubicacion,
    fechainicio, fechafinal, nocontrol, usuarios.nombre as \"nombre_promotor\" FROM laboral
    INNER JOIN sala on laboral.cvesala = sala.cvesala
    INNER JOIN abecedario on laboral.cveletra = abecedario.cve
    INNER JOIN periodo on laboral.cveperiodo= periodo.cveperiodo
    INNER JOIN lectura on abecedario.cve = abecedario.cve
    INNER JOIN usuarios on laboral.cvepromotor = usuarios.cveusuario
    WHERE nocontrol=? and laboral.cveperiodo=? and letra=? and lectura.cveperiodo=?
    ORDER BY letra";
  $parameters = array($alumno[0]['nocontrol'], $cveperiodo, $_GET['info1'], $cveperiodo);
  $datos_rs   = $web->DB->GetAll($sql, $parameters);
  $web->smarty->assign('info', $datos_rs[0]);

  //para obtener el nombre del alumno
  $sql   = "SELECT cveusuario, nombre FROM usuarios WHERE cveusuario=?";
  $datos = $web->DB->GetAll($sql, $alumno[0]['nocontrol']);
  $web->smarty->assign('info2', $datos[0]);

  //Datos de la tabla = Calificaciones del alumno
  $sql = "SELECT distinct usuarios.nombre, comprension, motivacion, participacion, asistencia,
  terminado, nocontrol, cveeval, laboral.cveperiodo, lectura.cvelectura FROM lectura
  INNER JOIN evaluacion on evaluacion.cvelectura = lectura.cvelectura
  INNER JOIN abecedario on lectura.cveletra = abecedario.cve
  INNER JOIN usuarios on lectura.nocontrol = usuarios.cveusuario
  INNER JOIN laboral on abecedario.cve = laboral.cveletra
  WHERE letra=? and laboral.cveperiodo=? and nocontrol=? and lectura.cveperiodo=?
  ORDER BY usuarios.nombre";
  $parameters = array($_GET['info1'], $cveperiodo, $alumno[0]['nocontrol'], $cveperiodo);
  $datos      = $web->DB->GetAll($sql, $parameters);

  if (!isset($datos[0])) {
    $web->simple_message('warning', 'El alumno no está registrado en este grupo');
    return false;
  }

  mostrar_libros($web, $alumno); //Combo y tabla
  $web->smarty->assign('datos', $datos);
  $web->smarty->assign('alumnos', 'alumnos');
}

/**
 * Muestra:
 * Los libros de cada alumno con sus respectivas opciones
 * Tabla de libros ya enlazados con el alumno
 * @param  Class  $web    Objeto para hacer uso de smarty
 */
function mostrar_libros_promotor($web)
{
  global $cveperiodo;

  //esto es usado para que no haya errores al momento de desplegar los errores
  //viene del menú grupos
  if ($_GET['accion'] == 'index_grupos_libros') {
    $web->iniClases('admin', "index grupos alumnos-libros");
  } else {
    $web->iniClases('admin', "index promotor");
  }

  //Checa que este especificado el grupo
  if (!isset($_GET['info1'])) {
    $web->simple_message('warning', 'No se especificó el grupo');
    return false;
  }
  //Checa que este especificado el alumno
  if (!isset($_GET['info2'])) {
    $web->simple_message('warning', 'No se especificó el alumno');
    return false;
  }
  //Checa que el grupo exista
  $sql = "SELECT * FROM laboral
  WHERE cveletra in (SELECT cve FROM abecedario WHERE letra = ?)
  and cveperiodo = ?";
  $grupo = $web->DB->GetAll($sql, array($_GET['info1'], $cveperiodo));
  if (!isset($grupo[0])) {
    $web->simple_message('danger', 'El grupo no existe');
    return false;
  }
  //Checar que el promotor sea el propietario del grupo
  if ($grupo[0]['cvepromotor'] != $_GET['info3']) {
    $web->simple_message('danger', 'El promotor seleccionado no es propietario del grupo');
    return false;
  }
  //Checa que el alumno exista
  $sql        = "SELECT * FROM usuarios WHERE cveusuario = ?";
  $aux_alumno = $web->DB->GetAll($sql, $_GET['info2']);
  if (!isset($aux_alumno[0])) {
    $web->simple_message('danger', 'El alumno no existe');
    return false;
  }
  //Checar que el alumno pertenezca al grupo
  $sql = "SELECT * FROM lectura
   WHERE nocontrol=?
   AND cveletra IN (SELECT cve FROM abecedario
                     WHERE cve IN (SELECT cveletra FROM laboral
                                   WHERE cveperiodo=?)
                    AND letra=?)
  AND lectura.cveperiodo=?";
  $alumno = $web->DB->GetAll($sql, array($_GET['info2'], $cveperiodo, $_GET['info1'], $cveperiodo));
  if (!isset($alumno[0])) {
    $web->simple_message('danger', 'El alumno no pertenese al grupo');
    return false;
  }

  //éste bloque es usado para desplegar la ruta final
  //viene del menú grupos
  if ($_GET['accion'] == 'index_grupos_libros') {
    $web->iniClases('admin', "index grupos alumnos-libros");
  } else {
    $web->iniClases('admin', "index promotor libros");
  }

  mostrar_libros($web, $alumno);
}

/**
 * Muestra:
 * Combo con libros para enlazarlos con el alumno
 * Tabla de libros ya enlazados con el alumno
 * @param  Class  $web    Objeto para hacer uso de smarty
 * @param  array  $alumno Arreglo de objetos que contiene datos de la tabla lectura
 */
function mostrar_libros($web, $alumno)
{
  global $cveperiodo;
  //datos del combo
  $sql = "SELECT cvelibro, titulo FROM libro
  WHERE cvelibro not in
  (SELECT cvelibro FROM lista_libros
    INNER JOIN lectura on lectura.cvelectura = lista_libros.cvelectura
    INNER JOIN abecedario on abecedario.cve = lectura.cveletra
    INNER JOIN laboral on laboral.cveletra = abecedario.cve
    WHERE nocontrol=? and laboral.cveperiodo=? and lectura.cvelectura=? and lectura.cveperiodo=? )
  ORDER BY titulo";
  $parameters = array($alumno[0]['nocontrol'], $cveperiodo, $alumno[0]['cvelectura'], $cveperiodo);
  $combo      = $web->combo($sql, null, '../', $parameters);

  //Datos de la tabla = Libros
  $sql = "SELECT *
    FROM lista_libros
    INNER JOIN lectura on lista_libros.cvelectura = lectura.cvelectura
    INNER JOIN libro on libro.cvelibro = lista_libros.cvelibro
    INNER JOIN estado on estado.cveestado = lista_libros.cveestado
    WHERE nocontrol=? and lectura.cvelectura=?
    ORDER BY titulo";
  $tmp    = array($alumno[0]['nocontrol'], $alumno[0]['cvelectura']);
  $libros = $web->DB->GetAll($sql, $tmp);

  if (!isset($libros[0])) {
    $web->simple_message('warning', 'No hay libros registrados');
    //La agregue por que no mandaba la cvelectura si no se encontraba algun libro registrado
    $web->smarty->assign('cvelectura', $alumno[0]['cvelectura']);
  } else {

    $sql          = "SELECT letra FROM abecedario WHERE cve=?";
    $letra_subida = $web->DB->GetAll($sql, $libros[0]["cveletra"]);

    for ($i = 0; $i < count($libros); $i++) {
      $nombre_fichero = $web->route_periodos .
        $libros[$i]["cveperiodo"] . "/" .
        $letra_subida[0][0] . "/" .
        $libros[$i]["nocontrol"] . "/" .
        $libros[$i]["cvelibro"] . "_" .
        $libros[$i]["nocontrol"] . ".pdf";
      if (file_exists($nombre_fichero)) {
        $libros[$i]["archivoExiste"] = explode($web->route_periodos, $nombre_fichero)[1];
      }
    }

    $web->smarty->assign('libros', $libros);
    $web->smarty->assign('cvelectura', $libros[0]['cvelectura']);
  }
  $web->smarty->assign('cmb_libro', $combo);
}

/**
 * Insertar en lista_libros, realizando las validaciones correspondientes
 * @param  Class   $web Objeto para hacer uso de smarty
 * @return boolean False -> Mostrar mensaje de error
 */
function insertar_libro_alumno($tipo = 'alumno')
{
  global $web, $cveperiodo;

  if (!isset($_POST['datos']['cvelibro']) ||
    !isset($_POST['datos']['cvelectura'])) {
    $web->simple_message('danger', 'No alteres la estructura de la interfaz');
    return false;
  }

  //verifica que el libro exista
  $sql   = "SELECT * FROM libro WHERE cvelibro=?";
  $libro = $web->DB->GetAll($sql, $_POST['datos']['cvelibro']);
  if (!isset($libro[0])) {
    $web->simple_message('danger', 'El libro seleccionado no existe');
    return false;
  }

  //verifica que la cvelectura exista
  $sql = "SELECT distinct letra, nocontrol, laboral.cvepromotor FROM lectura
  INNER JOIN abecedario on lectura.cveletra = abecedario.cve
  INNER JOIN laboral on laboral.cveletra = abecedario.cve
  WHERE lectura.cvelectura=? and lectura.cveperiodo=?";
  $lectura = $web->DB->GetAll($sql, array($_POST['datos']['cvelectura'], $cveperiodo));
  if (!isset($lectura[0])) {
    $web->simple_message('danger', 'ERROR, no se puede continuar con la operación');
    return false;
  }

  $cvelibro   = $_POST['datos']['cvelibro'];
  $cvelectura = $_POST['datos']['cvelectura'];

  //verifica si el libro ya está registrado para ese alumno
  $sql = "SELECT * FROM lista_libros
  INNER JOIN lectura on lectura.cvelectura = lista_libros.cvelectura
  WHERE cvelibro=? and lectura.cvelectura=? and lectura.cveperiodo";
  $libro = $web->DB->GetAll($sql, array($cvelibro, $cvelectura, $cveperiodo));
  if (isset($libro[0])) {
    $web->simple_message('danger', 'El libro ya está para este alumno');
    return false;
  }

  $sql = "INSERT INTO lista_libros(cvelibro, cvelectura, cveperiodo, cveestado)
  VALUES(?, ?, ?, ?)";
  $web->query($sql, array($cvelibro, $cvelectura, $cveperiodo, '1'));

  if ($tipo == 'promotor') {
    header('Location: grupo.php?accion=libros&info1=' . $lectura[0]['letra'] . '&info2=' . $lectura[0]['nocontrol'] . '&info3=' . $lectura[0]['cvepromotor']);
  } else {
    header('Location: grupo.php?accion=alumnos&info1=' . $lectura[0]['letra'] . '&info2=' . $lectura[0]['nocontrol']);
  }

}

/**
 * Elimina de lista_libros realizando las validaciones correspondientes y redirigiendo a grupo.php
 * @param  Class   $web Objeto para poder hacer uso de smarty
 * @return boolean False -> Mostrar mensaje de error
 */
function eliminar_libro_alumno($web, $tipo = null)
{
  global $cveperiodo;

  if ($tipo == 'promotor') {
    $web->iniClases('admin', "index promotor grupos");
  } else {
    $web->iniClases('admin', "index alumnos grupos");
  }

  if (!isset($_GET['info1']) ||
    !isset($_GET['info2'])) {
    $web->simple_message('danger', 'Hacen falta más datos para continuar');
    return false;
  }

  //verifica que el libro exista
  $sql   = "SELECT * FROM libro WHERE cvelibro=?";
  $libro = $web->DB->GetAll($sql, $_GET['info1']);
  if (!isset($libro[0])) {
    $web->simple_message('danger', 'El libro seleccionado no existe');
    return false;
  }

  //verifica que la cvelectura exista
  $sql = "SELECT distinct letra, nocontrol FROM lista_libros
  INNER JOIN lectura on lectura.cvelectura = lista_libros.cvelectura
  INNER JOIN abecedario on lectura.cveletra = abecedario.cve
  WHERE lectura.cvelectura=?";
  $lectura = $web->DB->GetAll($sql, $_GET['info2']);
  if (!isset($lectura[0])) {
    $web->simple_message('danger', 'ERROR, no se puede continuar con la operación');
    return false;
  }

  $cvelibro   = $_GET['info1'];
  $cvelectura = $_GET['info2'];

  //verificar que el libro está enlazado al alumno
  $sql = "SELECT * FROM lista_libros
  WHERE cveperiodo=? and cvelectura=?";
  $lista_libros = $web->DB->GetAll($sql, array($cveperiodo, $cvelectura));
  if (!isset($lista_libros[0])) {
    $web->simple_message('danger', 'El libro no está para este alumno');
    return false;
  }

  //verificar que si se elimina el libro, el alumno continua teniendo 5 libros
  if (sizeof($lista_libros) - 1 < 5) {
    $web->simple_message('danger',
      'No es posible eliminar el libro, el alumno debe tener mínimo 5 libros');
    return false;
  }

  $sql = "SELECT * FROM lista_libros
  WHERE cveperiodo=? and cvelectura=? and cvelibro=?";
  $lista_libros = $web->DB->GetAll($sql, array($cveperiodo, $cvelectura, $cvelibro));

  $sql = "delete FROM lista_libros WHERE cvelista=?";
  $web->query($sql, $lista_libros[0]['cvelista']);

  $sql = "SELECT letra, nocontrol,  laboral.cvepromotor FROM lectura
  INNER JOIN abecedario on lectura.cveletra = abecedario.cve
  INNER JOIN laboral on laboral.cveletra = abecedario.cve
  WHERE cvelectura=?";
  $lectura = $web->DB->GetAll($sql, $cvelectura);

  if ($tipo == 'promotor') {
    header('Location: grupo.php?accion=libros&info1=' . $lectura[0]['letra'] . '&info2=' . $lectura[0]['nocontrol'] . '&info3=' . $lectura[0]['cvepromotor']);
  } else {
    header('Location: grupo.php?accion=alumnos&info1=' . $lectura[0]['letra'] . '&info2=' . $lectura[0]['nocontrol']);
  }
  die(); //no funciona bien sin esto
}

/**
 * Muestra la lista de alumnos de un grupo en base a un promotor
 * @param  Class   $web Objeto para hacer uso de smarty
 * @return boolean False -> Mostrar mensaje de error
 */
function mostrar_grupos_promotor($web)
{
  global $cveperiodo;

  //verifica que se haya mandado el promotor
  if (!isset($_GET['info2'])) {
    $web->iniClases('admin', "index promotor grupos");
    $web->simple_message('warning', 'Hacen falta datos para continuar');
    return false;
  }

  //Verificar que exista el promotor
  $Aux_usuario = $web->getAll(array('cveusuario'), array('cveusuario' => $_GET['info2']), 'usuarios');
  if (!isset($Aux_usuario[0]['cveusuario'])) {
    $web->iniClases('admin', "index promotor grupos");
    $web->simple_message('danger', 'No existe el promotor');
    return false;
  }

  //verifica la existencia del grupo
  $sql = "SELECT * FROM lectura
  INNER JOIN laboral ON laboral.cveletra = lectura.cveletra
  WHERE laboral.cveletra in (SELECT cve FROM abecedario WHERE letra=?)
  AND laboral.cveperiodo=?
  AND cvepromotor=?";
  $grupo = $web->DB->GetAll($sql, array($_GET['info1'], $cveperiodo, $_GET['info2']));
  if (!isset($grupo[0])) {
    $web->iniClases('admin', "index promotor grupos");
    $web->simple_message('danger', 'El grupo seleccionado no existe');
    return false;
  }

  $web->iniClases('admin', "index promotor grupo-" . $_GET['info1']);
  if ($_GET['accion'] == 'historial') {
    $web->iniClases('admin', "index historial grupo-" . $_GET['info1']);
    $web->smarty->assign('bandera', 'historial');
  }

  //Info de encabezado
  $sql = "SELECT distinct letra, laboral.nombre AS \"nombre_grupo\", sala.ubicacion,
  fechainicio, fechafinal, usuarios.nombre AS \"nombre_promotor\"
  FROM laboral
  INNER JOIN sala ON laboral.cvesala = sala.cvesala
  INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
  INNER JOIN periodo ON laboral.cveperiodo = periodo.cveperiodo
  INNER JOIN lectura ON abecedario.cve = abecedario.cve
  INNER JOIN usuarios ON laboral.cvepromotor = usuarios.cveusuario
  WHERE laboral.cveperiodo=? AND letra=?
  ORDER BY letra";
  $info = $web->DB->GetAll($sql, array($cveperiodo, $_GET['info1']));
  $web->smarty->assign('info', $info[0]);

  //Datos de la tabla = Calificaciones del alumno
  $sql = "SELECT distinct usuarios.nombre, comprension, participacion, asistencia, actividades, reporte,
  terminado, nocontrol, lectura.cvelectura, laboral.cvepromotor, abecedario.letra
  FROM lectura
  INNER JOIN evaluacion ON evaluacion.cvelectura = lectura.cvelectura
  INNER JOIN abecedario ON lectura.cveletra = abecedario.cve
  INNER JOIN usuarios ON lectura.nocontrol = usuarios.cveusuario
  INNER JOIN laboral ON abecedario.cve = laboral.cveletra
  WHERE letra=? AND lectura.cveperiodo=? AND cvepromotor=?
  ORDER BY usuarios.nombre";
  $parameters = array($_GET['info1'], $cveperiodo, $_GET['info2']);
  $datos      = $web->DB->GetAll($sql, $parameters);
  if (!isset($datos[0])) {
    $web->simple_message('warning', 'Aún no hay alumnos inscritos en el grupo o el promotor seleccionado no tiene acceso para este grupo');
    return false;
  }

  $web->smarty->assign('datos', $datos);
  $web->smarty->assign('bandera_mensajes', 'true'); //para icono de mensaje en el header
  $web->smarty->assign('promotor', 'promotor');
}

/**
 * Muestra la lista de alumnos de un grupo
 * @param  Class   $web Objeto para hacer uso de smarty
 * @return boolean False -> Mostrar mensaje de error
 */
function mostrar_alumnos_grupo()
{
  global $web, $cveperiodo;

  //verifica que se haya mandado y sea válido la letra
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No es posible continuar, hacen falta datos');
    return false;
  }

  //Info de encabezado
  $info          = $web->getInfoHeader($cveperiodo, $_GET['info1']);
  $grupo_alumnos = $web->getStudents($_GET['info1'], $cveperiodo, $info[0]['cvepromotor']);
  if (!isset($grupo_alumnos[0])) {
    $web->simple_message('danger', 'No hay alumnos inscritos a este grupo o el grupo seleccionado no existe');
  } else {
    $web->smarty->assign('datos', $grupo_alumnos);
  }

  $web->iniClases('admin', "index grupos grupo-" . $_GET['info1']);

  $web->smarty->assign('info', $info[0]);
  $web->smarty->assign('bandera_mensajes', 'true'); //La agregue para que aparesca el icono de mensaje en el header
  $web->smarty->assign('bandera', 'index_grupos_libros');
}

function ver_reporte($web)
{
  if (!isset($_GET['info'])) {

    if (!isset($_GET['info1']) ||
      !isset($_GET['info2']) ||
      !isset($_GET['info3'])) {
      header('Location: promotor.php');
    } else {
      header('Location: grupo.php?accion=libros&info1=' . $_GET['info1'] .
        '&info2=' . $_GET['info2'] .
        '&info3=' . $_GET['info3'] .
        '&e=1');
    }
  }

  header("Content-disposition: attachment; filename=" . $_GET['info']);
  header("Content-type: MIME");
  readfile($web->route_periodos . $_GET['info']);
  return true;
}

function delete_alumno($web)
{
  //se valida la contraseña
  $cveperiodo = $web->periodo();
  if ($cveperiodo == "") {
    $web->iniClases('admin', "index alumnos grupos");
    $web->simple_message('warning', 'No hay periodos actuales');
    break;
  }

  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;
      break;
    case 2:
      $web->simple_message('danger', 'La contraseña de seguridad ingresada no es válida');
      return false;
      break;
  }

  //verifica que se reciben los datos necesarios
  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', "No se especificó el grupo");
    return false;
  }

  if (!isset($_GET['info1'])) {
    $web->simple_message('danger', "No se especifico el alumno");
    return false;
  }

  //verifica que el promotor exista
  $sql   = "SELECT * FROM usuarios WHERE cveusuario=?";
  $datos = $web->DB->GetAll($sql, $_GET['info2']);
  if (!isset($datos[0])) {
    $web->simple_message('danger', "El alumno no existe");
    return false;
  }

  //verifica la existencia del grupo
  $sql   = "SELECT * FROM laboral WHERE cveletra in (SELECT cve FROM abecedario WHERE letra = ?) and cveperiodo = ?";
  $datos = $web->DB->GetAll($sql, array($_GET['info1'], $cveperiodo));
  if (!isset($datos[0])) {
    $web->simple_message('danger', "El grupo no existe");
    return false;
  }

  //verifica la existencia del alumno en el grupo
  $sql          = "SELECT * FROM lectura WHERE nocontrol = ? AND cveperiodo = ?";
  $datos_alumno = $web->DB->GetAll($sql, array($_GET['info2'], $cveperiodo));
  if (!isset($datos_alumno[0])) {
    $web->simple_message('danger', "El alumno no esta registrado en el grupo");
    return false;
  }

  //se eliminan las listas
  //elimina de , lista_libros y lectura
  $sql = "DELETE FROM evaluacion WHERE cvelectura= ?";
  $web->query($sql, $datos_alumno[0]['cvelectura']);
  $sql = "DELETE FROM lista_libros WHERE cvelectura= ?";
  $web->query($sql, $datos_alumno[0]['cvelectura']);
  $sql = "DELETE FROM msj WHERE receptor = ? AND cveletra = ? AND cveperiodo = ?";
  $web->query($sql, array($datos_alumno[0]['nocontrol'], $datos_alumno[0]['cveletra'], $cvelectura));
  $sql = "DELETE FROM lectura WHERE cvelectura= ?";
  $web->query($sql, $datos_alumno[0]['cvelectura']);

  header('Location: grupos.php');
}
