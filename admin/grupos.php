<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$web->iniClases('admin', "index grupos");
$cveperiodo = $web->periodo();
//verifica que exista periodo actual
if ($cveperiodo == "") {
  $web->simple_message('danger', 'No hay periodo actual');
  $web->smarty->display('grupos.html');
  die();
}

if(isset($_GET['accion'])) {
  switch($_GET['accion']) {
    case 'delete':
      delete_group($web);
      break;
      
      case 'add_group':
        add_group($web, $_GET['step'], $cveperiodo);
        break;
  }
}

//su nombre es nocontrol para que funcione en grupos.html
$sql = "select distinct abecedario.letra, usuarios.cveusuario AS \"nocontrol\", 
usuarios.nombre AS \"nombre_promotor\", laboral.nombre, sala.ubicacion, laboral.cvepromotor, 
titulo from laboral
inner join sala on sala.cvesala = laboral.cvesala
inner join abecedario on abecedario.cve = laboral.cveletra
inner join usuarios on usuarios.cveusuario = laboral.cvepromotor
inner join libro on laboral.cvelibro_grupal = libro.cvelibro
where laboral.cveperiodo = ? order by abecedario.letra";
$grupos = $web->DB->GetAll($sql, $cveperiodo);

//verifica que haya grupos en el periodo actual
if (!isset($grupos[0])) {
  $web->simple_message('danger', 'No hay grupos registrados');
  $web->smarty->display('grupos.html');
  die();
}

//coloca horarios
$sql = "select abecedario.letra, dia.cvedia, hora_inicial,hora_final, dia.nombre from laboral
inner join horas on horas.cvehoras = laboral.cvehoras
inner join abecedario on abecedario.cve = laboral.cveletra
inner join dia on dia.cvedia = laboral.cvedia
where laboral.cveperiodo = ? 
order by abecedario.letra, dia.cvedia, hora_inicial";
$horas = $web->DB->GetAll($sql, $cveperiodo);

for ($i = 0; $i < sizeof($grupos); $i++) {
  $grupos[$i]['horario'] = "";
  
  for ($j = 0; $j < sizeof($horas); $j++) {
    
    if ($grupos[$i]['letra'] == $horas[$j]['letra']) {
      $grupos[$i]['horario'] .= $horas[$j]['nombre'] . ' - ' . $horas[$j]['hora_inicial'] . ' a ' . $horas[$j]['hora_final'] . "<br>";
    }
  }
}

$web->smarty->assign('tablegrupos', $grupos);
$web->smarty->assign('bandera', 'index_grupos');
$web->smarty->display('grupos.html');

/**
 * Crea un grupo nuevo
 * Step: pasos paa la creacion de un grupo
 * Step 1: Seleccionar las salas disponibles
 * Setp 2: Seleccionar Los horarios disponibles de la sala seleccionada, y el libro del grupo :3
 * Step 3: Seleccionar al promotor
 * */
function add_group($web, $step, $cveperiodo){
  switch($step){
    
    case '1':
        $sql = "SELECT cvesala, ubicacion FROM sala WHERE cveperiodo=? ORDER BY cvesala";
        $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
        $datos = $web->DB->GetAll($sql, $cveperiodo);
        $datos = array('data' => $datos);
      
        for ($i = 0; $i < sizeof($datos['data']); $i++) {
          $datos['data'][$i]['cvesala'] = "<a href='grupos.php?accion=add_group&step=2&info=" . $datos['data'][$i]['cvesala'] . "'>" . $datos['data'][$i]['cvesala'] . "</a>";
        }
      
        $web->DB->SetFetchMode(ADODB_FETCH_NUM);
        $datos = json_encode($datos);
      
        $file = fopen("TextFiles/promosala.txt", "w");
        fwrite($file, $datos);
        $web->smarty->assign('datos', $datos);
      break;
      
    case '2':
        //$web->iniClases('promotor', "index salas horario");
        if (!isset($_GET['info'])) {
          $web->simple_message('danger', 'No se especificó la sala');
          break;
        }
      
        $sql  = "SELECT * FROM sala WHERE cvesala=? AND cveperiodo=?";
        $sala = $web->DB->GetAll($sql, array($_GET['info'], $cveperiodo));
        if (!isset($sala[0])) {
          $web->simple_message('danger', 'No existe la sala seleccionada');
          break;
        }
      
        $sql          = "SELECT * FROM dia";
        $dias         = $web->DB->GetAll($sql);
        $horas_semana = array();
        for ($i = 1; $i <= sizeof($dias); $i++) {
          $sql = "SELECT cvehoras, hora_inicial, hora_final FROM horas
          EXCEPT
          SELECT horas.cvehoras, hora_inicial, hora_final FROM laboral
          INNER JOIN horas ON laboral.cvehoras = horas.cvehoras
          INNER JOIN sala ON sala.cvesala = laboral.cvesala
          WHERE cvedia=? AND ubicacion=? AND laboral.cveperiodo=?
          ORDER BY hora_inicial, hora_final";
          $horas = $web->DB->GetAll($sql, array($i, $sala[0]['ubicacion'], $cveperiodo));
      
          if (isset($horas[0])) {
            $web->smarty->assign('horas' . $i, $horas);
          }
        }
      
        $sql    = 'SELECT cvelibro, titulo FROM libro ORDER BY titulo';
        $libros = $web->combo($sql, null, "../");
        $web->smarty->assign('cvesala', $_GET['info']);
        $web->smarty->assign('libros', $libros);
        $web->smarty->assign('horas', 'horas');
      break;
      
    case '3':
      $flag = true;

  if (!verificaciones(1, $web)) {return false;}

  for ($i = 1; $i <= 6 && $flag; $i++) {
    if ($_POST['datos']['horas' . $i . '_0'] == $_POST['datos']['horas' . $i . '_1']
      && $_POST['datos']['horas' . $i . '_0'] != -1) {
      $web->simple_message('danger', 'No duplique los horarios en un mismo día');
      $flag = false;
    }
  }
  if (!$flag) {return false;}

  if (!isset($_POST['datos']['cvesala']) ||
    $_POST['datos']['cvesala'] == "") {
    $web->simple_message('danger', 'No alteres la estructura de la interfaz');
    return false;
  }

  $sql  = "SELECT * FROM sala WHERE cvesala=? and cveperiodo=?";
  $sala = $web->DB->GetAll($sql, array($_POST['datos']['cvesala'], $cveperiodo));
  if (!isset($sala[0])) {
    $web->simple_message('danger', 'No alteres la estructura de la interfaz');
    return false;
  }

  $res = verificaciones(2, $web);
  if (!$res) {return false;}

  // Para cuando el usario no escoge nada
  if ($res == 12) {
    $web->simple_message('danger', 'Seleccione alguna hora, por favor');
    return false;
  }
  // Para cuando el usuario escoge mas de dos horas
  if ($res < 10) {
    $web->simple_message('danger', 'Solo debe seleccionar dos horas');
    return false;
  }

  if (!isset($_POST['datos']['cvelibro'])) {
    $web->simple_message('danger', 'No altere la estructura de la interfaz');
    return false;
  }

  if ($_POST['datos']['cvelibro'] == -1) {
    $web->simple_message('danger', 'Seleccione un libro grupal');
    return false;
  }

  $cvelibro = $_POST['datos']['cvelibro'];
  $sql      = "SELECT COALESCE(MAX(cveletra),0) as cveletra FROM laboral WHERE cveperiodo=?";
  $grupo    = $web->DB->GetAll($sql, $cveperiodo);
  $grupo    = ($grupo[0]['cveletra'] + 1);

  $sql    = "SELECT letra FROM abecedario WHERE cve=?";
  $letra  = $web->DB->GetAll($sql, $grupo);
  $nombre = "SALA - " . $letra[0]['letra'];

  if (!verificaciones(3, $web, $cveperiodo)) { return false; }
  verificaciones(4, $web, array('cveperiodo' => $cveperiodo, 'grupo' => $grupo, 'nombre' => $nombre, 'cvelibro_grupal' => $cvelibro));
  
  //Obtener lista de los promotores disponibles
  $sql = "select cveusuario, usuarios.nombre from usuarios where cveusuario not in (select cvepromotor from laboral inner join usuarios on laboral.cvepromotor = usuarios.cveusuario where cveperiodo = ? group by 1 having count(cvepromotor) = 6) and cveusuario in (select cveusuario from usuario_rol where cverol = 2);";
  $web->DB->SetFetchMode(ADODB_FETCH_ASSOC);
  $datos = $web->DB->GetAll($sql, $cveperiodo);
  $datos = array('data' => $datos);

  for ($i = 0; $i < sizeof($datos['data']); $i++) {
    $datos['data'][$i]['cveusuario'] = "<a href='grupos.php?accion=add_group&step=4&info1=" . $datos['data'][$i]['cveusuario'] . "&info2=" . $nombre . "'>" . $datos['data'][$i]['cveusuario'] . "</a>";
  }

  $web->DB->SetFetchMode(ADODB_FETCH_NUM);
  $datos = json_encode($datos);

  $file = fopen("TextFiles/promodisponibles.txt", "w");
  fwrite($file, $datos);
  $web->smarty->assign('promodisponibles', $datos);

      break;
      case '4':
        if(!isset($_GET['info1']) || !isset($_GET['info2'])){
          $web->simple_message('danger', 'No alteres la estructura de la interfaz');
            return false;
        }
        
        $sql = "select cveusuario, usuarios.nombre from usuarios where cveusuario not in (select cvepromotor from laboral inner join usuarios on laboral.cvepromotor = usuarios.cveusuario where cveperiodo = ? and cveusuario in (select cveusuario from usuario_rol where cverol = 2) and cveusuario = ? group by 1 having count(cvepromotor) = 6) ";
        $promo = $web->DB->GetAll($sql, array($cveperiodo, $_GET['info1']) );
        
        if(!isset($promo[0])){
          $web->simple_message('danger', 'No se puede asignar esta sala al promotor');
            return false;
        }
        
        $sql = "SELECT * FROM laboral where cveperiodo = ? and nombre = ?";
        $grupo = $web->DB->GetAll($sql, array($cveperiodo, $_GET['info2']) );
        
        if(!isset($grupo[0])){
          $web->simple_message('danger', 'No existe el grupo');
            return false;
        }
        
        $sql = "update laboral set cvepromotor = ? where nombre = ? and cveperiodo = ?";
        $web->query($sql, array($_GET['info1'], $_GET['info2'], $cveperiodo));
        header('Location: grupos.php');
        die();
        break;
        
        
  }
  $web->smarty->display("adminsala.html");
  die();
}

/**
 * Checa los casos:
 * 1 Campos numéricos
 * 2 Selección de horas y modificaciones hacia ellas
 * 3 Existencia de registro duplicado en base a periodo, horas, días y ubicación
 * 4 Registro del grupo dentro de laboral
 * @param  int   $op        Correspondiente al caso a checar
 * @param  Class $web       Objeto para poder hacer uso de smarty
 * @param  array $elementos Cuyo contenido debe contener los encabezados: cveperiodo, grupo, nombre, cvelibro_grupal
 * @return int | boolean
 */
function verificaciones($op, $web, $elementos = null)
{
  $cont = 0;
  $flag_aux = false;
  for ($i = 1; $i <= 6; $i++) {
    for ($j = 0; $j < 2; $j++) {

      switch ($op) {
        case 1: //checa existencia de campos y que sean numéricos
          if (!isset($_POST['datos']['horas' . $i . '_' . $j]) ||
            !is_numeric($_POST['datos']['horas' . $i . '_' . $j])) {
            $web->simple_message('danger', 'No alteres la estructura de la interfaz');
            return false;
          }
          break;
        case 2: //verfica que se seleccione alguna hora y que no haya sido modificada
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $sql  = "SELECT * FROM horas WHERE cvehoras=?";
            $hora = $web->DB->GetAll($sql, $_POST['datos']['horas' . $i . '_' . $j]);

            if (!isset($hora[0])) {
              $web->simple_message('danger', 'No alteres la estructura de la interfaz');
              return false;
            }
          } else {
            $cont++;
          }
          break;

        case 3: //checa que no se duplique periodo, horas y dias con la ubicación
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $sql = "SELECT * FROM laboral
              INNER JOIN sala on laboral.cvesala = sala.cvesala
              WHERE laboral.cveperiodo=? and cvehoras=? and cvedia=? and ubicacion in
              (SELECT ubicacion FROM sala WHERE cvesala=?)";
            $parametros = array($elementos, $_POST['datos']['horas' . $i . '_' . $j], $i, $_POST['datos']['cvesala']);
            $datos      = $web->DB->GetAll($sql, $parametros);

            if (isset($datos[0])) {
              $web->simple_message('danger', 'La sala u horario ya están ocupados');
              return false;
            }
          }
          break;

        case 4: //insert final
          if ($_POST['datos']['horas' . $i . '_' . $j] != -1) {
            $web->DB->startTrans();
            $sql        = "INSERT INTO laboral(cveperiodo, cvehoras, cvedia, cvesala, cveletra, nombre, cvepromotor, cvelibro_grupal) values(?, ?, ?, ?, ?, ?, ?, ?)";
            $parametros = array($elementos['cveperiodo'], $_POST['datos']['horas' . $i . '_' . $j], $i, $_POST['datos']['cvesala'], $elementos['grupo'], $elementos['nombre'], NULL, $elementos['cvelibro_grupal']);
            $web->query($sql, $parametros);
            $sql   = "SELECT letra FROM abecedario WHERE cve=?";
            $letra = $web->DB->GetAll($sql, $elementos['grupo']);
            
            if(!$flag_aux){
              mkdir("../archivos/periodos/" . $elementos['cveperiodo'] . "/" . $letra[0]['letra'], 0777);  
              $flag_aux = true;
            }
            
            if ($web->DB->HasFailedTrans()) {
              $web->simple_message('danger', 'No fue posible registrar el grupo, contacte al administrador');
              return false;
            }
            $web->DB->CompleteTrans();
          }
          break;
      }
    }
  }

  if ($op == 2) {
    return $cont;
  } else {
    return true;
  }
}

function delete_group($web) {
  global $cveperiodo;
  
  //se valida la contraseña
  switch ($web->valida_pass($_SESSION['cveUser'])) {
    case 1:
      $web->simple_message('danger', 'No se especificó la contraseña de seguridad');
      return false;

    case 2:
      $web->simple_message('danger', 'La contraseña de seguridad ingresada no es válida');
      return false;
  }
  
  //se verifica que la letra haya sido mandada y sea válida
  if(!isset($_GET['info1'])) {
    $web->simple_message('danger', 'No es posible continuar, hacen falta datos');
  }
  $sql = "select * from abecedario where letra=?";
  $letra = $web->DB->GetAll($sql, $_GET['info1']);
  if(!isset($letra[0])) {
    $web->simple_message('danger', 'No es posible que exista tal grupo');
  }
  
  $web->DB->startTrans();
  //se obtiene las del grupo
  $sql = "select * from lectura 
  where cveperiodo=? and cveletra in (select cve from abecedario where letra=?)";
  $lecturas = $web->DB->GetAll($sql, array($cveperiodo, $_GET['info1']));
  
  for ($i = 0; $i < sizeof($lecturas); $i++) {
     //elimina de evaluacion, lista_libros y lectura
     $sql = "delete from evaluacion where cvelectura=?";
     $web->query($sql, $lecturas[$i]['cvelectura']);
     $sql = "delete from lista_libros where cvelectura=?";
     $web->query($sql, $lecturas[$i]['cvelectura']);
     $sql = "delete from lectura where cvelectura=?";
     $web->query($sql, $lecturas[$i]['cvelectura']);
  }
  //elimina de msj y laboral
  $sql = "delete from msj 
  where cveperiodo=? and cveletra = (select cve from abecedario where letra=?)";
  $web->query($sql, array($cveperiodo, $_GET['info1']));
  $sql = "delete from laboral 
  where cveperiodo=? and cveletra = (select cve from abecedario where letra=?)";
  $web->query($sql, array($cveperiodo, $_GET['info1']));
  
  if($web->DB->HasFailedTrans()) {
    //falta programar esta parte para que no muestre directamente el resultado de sql
    $web = ADODB_Pear_Error();  
    echo $e->message;
  }
  
  $web->DB->CompleteTrans();
  return false;
}
