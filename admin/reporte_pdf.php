<?php
require_once '../lib/dompdf/autoload.inc.php';
include "../sistema.php";
use Dompdf\Dompdf;

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

$titulo = ""; //necesario para colocar nombre al archivo pdf
if (isset($_GET['accion'])) {

  //verifica si se manda el campo más básico, indica si es pdf o excel
  if (!isset($_GET['info1'])) {
    header('Location: periodos.php?accion=historial&e=1');
  }
  $tipo_archivo = $_GET['info1']; 
  $html_code    = getHeader(); //no se necesita si se va a usar jspdf

  switch ($_GET['accion']) {

    case 'periodos':
      proceso_archivo($web);
      $titulo = "reporte_periodos";
      break;

    case 'promotores':
      //verifica que mande cveperiodo
      $datos = array('cveperiodo' => verifica_periodo($web));
      proceso_archivo($web, $datos);
      $titulo = "reporte_periodo_promotores";
      break;

    case 'promotor':
      //verifica que mande y sea válido cveperiodo y cvepromotor
      $cveperiodo = verifica_periodo($web);
      $cvepromotor = verifica_usuario($web);

      $datos   = array('cveperiodo' => $cveperiodo, 'cvepromotor' => $cvepromotor);
      $periodo = proceso_archivo($web, $datos);
      $titulo  = $periodo[0]['fechainicio'] . "_" . $periodo[0]['fechafinal'] . "_" . $cvepromotor;
      break;

    case 'grupo':
      //verifica que mande y sea válido cveperiodo y cveusuario
      $cveperiodo = verifica_periodo($web);
      $cveusuario = verifica_usuario($web);

      //verifica que mande y sea válida la cveletra
      if (!isset($_GET['info4'])) {
        header('Location: periodos.php?accion=historial&e=1');
      }
      $letra = $_GET['info4'];
      $sql   = "select distinct cveletra from laboral
      where cveletra in (select cve from abecedario where letra=?)";
      $grupo = $web->DB->GetAll($sql, $letra);
      if (!isset($grupo[0])) {
        header('Location: periodos.php?accion=historial&e=2');
      }
      
      if(strlen($cveusuario) == 13) {
        //promotor
        $datos = array('cveperiodo' => $cveperiodo, 'cvepromotor' => $cveusuario, 'letra' => $letra);
        $periodo = proceso_archivo($web, $datos);
      
      } else {
        //alumno
        $datos = array('cveperiodo' => $cveperiodo, 'nocontrol' => $cveusuario, 'letra' => $letra);
        $periodo = proceso_archivo($web, $datos, 'alumno');
      }

      $titulo = $periodo[0]['fechainicio'] . "_" . $periodo[0]['fechafinal'] . "_" . $cveusuario . '_'.$letra;
      break;
      
    case 'alumnos':
      $datos = array('cveperiodo' => verifica_periodo($web));
      proceso_archivo($web, $datos, 'alumno');
      $titulo = "reporte_periodo_alumnos";
      break;
      
    case 'alumno':
      //verifica que mande y sea válido cveperiodo y nocontrol
      $cveperiodo = verifica_periodo($web);
      $nocontrol = verifica_usuario($web);

      $datos   = array('cveperiodo' => $cveperiodo, 'nocontrol' => $nocontrol);
      $periodo = proceso_archivo($web, $datos, 'alumno');
      $titulo  = $periodo[0]['fechainicio'] . "_" . $periodo[0]['fechafinal'] . "_" . $cvepromotor;
      break;
  }

}

$html_code .= getFooter();

//para ver el código en la página web
// echo $html_code;

$web->smarty->assign('pdf', $html_code);
// header();


//para ver el código en pdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html_code);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream($titulo);

/**
 * Genera código para poder aplicar estilos css y bootstrap 
 * @return String Código html para poder usar css en el pdf
 */
function getHeader()
{
  return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

      <!-- Latest compiled and minified CSS -->
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
        <!-- Optional theme -->
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
      <!-- Latest compiled and minified JavaScript -->
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

      <link rel="stylesheet" type="text/css" href="../css/main.css">
      <title>Reporte</title>
    </head>
    <body>';
}

/**
 * Genera código para terminar de forma apropiada el código html
 * @return String Fin del código html
 */
function getFooter()
{
  return '
  </body>
  </html>
  ';
}

/**
 * Ahorra código, verifica que haya sido enviado y sea válido 
 * El periodo es enviado desde $_GET['info2']
 * @param  Class  $web   Objeto para hacer uso de smarty
 * @return String cveperiodo
 */
function verifica_periodo($web)
{
  //verifica que mande cveperiodo
  if (!isset($_GET['info2'])) {
    header('Location: periodos.php?accion=historial&e=1');
    return false;
  }
  $cveperiodo = $_GET['info2'];

  //verifica que exista el periodo
  $sql     = "select * from periodo where cveperiodo=?";
  $periodo = $web->DB->GetAll($sql, $cveperiodo);
  if (!isset($periodo[0])) {
    header('Location: periodos.php?accion=historial&e=2');
    return false;
  }

  return $cveperiodo;
}

/**
 * Ahorra código, verifica que haya sido enviado y sea válido 
 * El periodo es enviado desde $_GET['info2']
 * @param  Class  $web   Objeto para hacer uso de smarty
 * @return String cvepromotor
 */
function verifica_usuario($web)
{
  if (!isset($_GET['info3'])) {
    header('Location: periodos.php?accion=historial&e=1');
  }
  $cveusuario = $_GET['info3'];
  $sql         = "select * from usuarios where cveusuario=?";
  $usuario    = $web->DB->GetAll($sql, $cveusuario);
  if (!isset($usuario[0])) {
    header('Location: periodos.php?accion=historial&e=2');
  }
  return $cveusuario;
}

/**
 * Simplifica código, todos los case de switch ocupan el if
 * @param  Class  $web   Objeto para hacer uso de smarty
 * @param  array $datos  Arreglo asociativo que puede tener campos con los nombres: cveperiodo, cvepromotor y letra como contenido
 * @return String Contenido principal del cuerpo html del pdf
 */
function proceso_archivo($web, $datos=null, $type='promotor')
{
  global $tipo_archivo;

  if ($tipo_archivo == 1) {
    //pdf
    if($type == 'promotor') {
      return reporte_periodos_promotor($web, $datos);  
    } else {
      return reporte_periodos_alumno($web, $datos);
    }
    
  } else {
    die('generar excel');
  }
}

/**
 * Genera el código y estructura del archivo pdf para promotores
 * @param  Class  $web   Objeto para hacer uso de smarty
 * @param  array $datos  Arreglo asociativo que puede tener campos con los nombres: cveperiodo, cvepromotor y letra como contenido
 * @return String Contenido principal del cuerpo html del pdf
 */
function reporte_periodos_promotor($web, $datos = null)
{
  global $html_code;
  $periodos = ""; //inicializa

  //obtener todos los promotores
  $sql = "select usuarios.cveusuario, usuarios.nombre AS \"nombre_usuario\", especialidad.nombre AS
  \"nombre_especialidad\", correo, especialidad.cveespecialidad AS \"especialidad_cve\", otro from
  usuarios
  inner join especialidad_usuario on especialidad_usuario.cveusuario = usuarios.cveusuario
  inner join especialidad on especialidad.cveespecialidad = especialidad_usuario.cveespecialidad
  where usuarios.cveusuario in (select cveusuario from usuario_rol where cverol=2)";
  $parameters = array();

  //si no se manda la cvepromotor, selecciona todos los promotores
  //si se envía la cvepromotor, selecciona a ése promotor específico
  if (isset($datos['cvepromotor'])) {
    $sql .= " and usuarios.cveusuario=?";
    $parameters = $datos['cvepromotor'];
  }

  $sql .= "order by usuarios.nombre";
  $promotores = $web->DB->GetAll($sql, $parameters);

  //obtener los periodos
  $parameters = array();
  if (isset($datos['cveperiodo'])) {
    //si se indica cveperiodo, se selecciona ése periodo específico
    $sql        = "select * from periodo where cveperiodo=?";
    $parameters = $datos['cveperiodo'];
  } else {
    //si no se indica cveperiodo, selecciona todos los periodos
    $sql = "select * from periodo order by cveperiodo";
  }
  $periodos = $web->DB->GetAll($sql, $parameters);

  for ($i = 0; $i < sizeof($promotores); $i++) {
    //checa si muestra el campo especialidad o el campo otro
    $especialidad = $promotores[$i]['nombre_especialidad'];
    if ($promotores[$i]['especialidad_cve'] == 'O') {
      $especialidad = $promotores[$i]['otro'];
    }

    //encabezado de promotor
    $html_code .= '
    <table class="table table-condensed">
      <tr class="active">
        <td width="130"><small>RFC</small><h4>' . $promotores[$i]['cveusuario'] . '</h4></td>
        <td width="200"><small>PROMOTOR</small><h4>' . $promotores[$i]['nombre_usuario'] . '</h4></td>
        <td width="190"><small>ESPECIALIDAD</small><h4>' . $especialidad . '</h4></td>
        <td colspan="2"><small>CORREO</small><h4>' . $promotores[$i]['correo'] . '</h4></td>
      </tr>';

    for ($j = 0; $j < sizeof($periodos); $j++) {
      //encabezado de periodo
      $html_code .= '
      <tr class="active"><td colspan="5">
        <h4>Periodo: ' . $periodos[$j]['fechainicio'] . ' : ' . $periodos[$j]['fechafinal'] . '</h4>
      </td></tr>';

      //obtiene los grupos del promotor por cada periodo
      $sql = "select distinct laboral.cveletra, letra, cvesala, nombre, titulo from laboral
      inner join abecedario on laboral.cveletra = abecedario.cve
      left join libro on laboral.cvelibro_grupal = libro.cvelibro
      where cveperiodo=? and cvepromotor=?";
      $parameters = array($periodos[$j]['cveperiodo'], $promotores[$i]['cveusuario']);
      if (isset($datos['letra'])) {
        $sql .= "and cveletra in (select cve from abecedario where letra=?)";
        $parameters = array($periodos[$j]['cveperiodo'], $promotores[$i]['cveusuario'], $datos['letra']);
      }

      $sql .= " order by letra";
      $grupos = $web->DB->GetAll($sql, $parameters);

      if (!isset($grupos[0])) {
        $html_code .= '<tr><td colspan="5"><h4>No hay grupos en este periodo</h4></td></tr>';

      } else {
        for ($k = 0; $k < sizeof($grupos); $k++) {
          $html_code .= '
          <tr class="active">
            <td><small>GRUPO</small><h4>' . $grupos[$k]['letra'] . '</h4></td>
            <td><small>SALA</small><h4>' . $grupos[$k]['cvesala'] . '</h4></td>
            <td><small>NOMBRE DEL GRUPO</small><h4>' . $grupos[$k]['nombre'] . '</h4></td>
            <td colspan="2"><small>LIBRO GRUPAL</small><h4>' . $grupos[$k]['titulo'] . '</h4></td>
          </tr>';

          //obtener los alumnos de ese grupo en ese periodo de ese promotor
          $sql = "select * from lectura
          where cveletra in (select cveletra from laboral where cveperiodo=?
                            and cvepromotor=?)
          and cveletra=? and cveperiodo=?";
          $parameters = array($periodos[$j]['cveperiodo'],
            $promotores[$i]['cveusuario'],
            $grupos[$k]['cveletra'], $periodos[$j]['cveperiodo']);
          $lecturas = $web->DB->GetAll($sql, $parameters);

          if (!isset($lecturas[0])) {
            $html_code .= '<tr><td colspan="5"><h4>No hay alumnos en este grupo</h4></td></tr>';
          } else {
            $html_code .= '
            <tr class="info"><td colspan="5"><h4>Lista de Alumnos</h4></td></tr>';

            for ($a = 0; $a < sizeof($lecturas); $a++) {

              //obtener alumnos de cada lectura, ya se verificó el periodo, promotor y grupo
              $sql = "select usuarios.cveusuario, usuarios.nombre AS \"usuario_nombre\", especialidad.nombre AS \"especialidad_nombre\", correo from usuarios
              left join especialidad_usuario on especialidad_usuario.cveusuario = usuarios.cveusuario
              left join especialidad on especialidad.cveespecialidad = especialidad_usuario.cveespecialidad
              inner join lectura on lectura.nocontrol = usuarios.cveusuario
              where usuarios.cveusuario=? and cveperiodo=? and cveletra=?";
              $parameters = array($lecturas[$a]['nocontrol'], $periodos[$j]['cveperiodo'], $grupos[$k]['cveletra']);
              $alumnos    = $web->DB->GetAll($sql, $parameters);

              if (!isset($alumnos[0])) {
                break;
              }

              //obtener resultados de calificaciones
              $sql        = "select * from evaluacion where cvelectura=?";
              $evaluacion = $web->DB->GetAll($sql, $lecturas[$a]['cvelectura']);

              if (!isset($evaluacion[0])) {
                $terminado = "No";

              } else {
                switch ($evaluacion[0]['terminado']) {
                  case 0:$terminado = "No";
                    break;
                  case 100:$terminado = "Si";
                    break;
                  default:$terminado = "No";
                    break;
                }
              }

              for ($b = 0; $b < sizeof($alumnos); $b++) {
                $html_code .= '
                <tr>
                  <td class="info">
                    <small>NO. CONTROL</small>
                    <h4>' . $alumnos[$b]['cveusuario'] . '</h4></td>
                  <td><small>ALUMNO</small>
                    <h4>' . $alumnos[$b]['usuario_nombre'] . '</h4>
                  </td>
                  <td><small>ESPECIALIDAD</small>
                    <h4>' . $alumnos[$b]['especialidad_nombre'] . '</h4></td>
                  <td><small>CORREO</small>
                    <h4>' . $alumnos[$b]['correo'] . '</h4></td>
                  <td><center><small>TERMINADO</small></center>
                    <center><h4>' . $terminado . '</h4></center>
                  </td>
                </tr>
                <tr>
                  <td><small>COMPRENSIÓN</small>
                    <h4>' . $evaluacion[0]['comprension'] . '</h4>
                  </td>
                  <td><small>MOTIVACIÓN</small>
                    <h4>' . $evaluacion[0]['motivacion'] . '</h4>
                  </td>
                  <td><small>PARTICIPACIÓN</small>
                    <h4>' . $evaluacion[0]['participacion'] . '</h4>
                  </td>
                  <td><small>ASISTENCIA</small>
                    <h4>' . $evaluacion[0]['asistencia'] . '</h4>
                  </td>
                  <td><small>ACTIVIDADES</small>
                    <h4>' . $evaluacion[0]['actividades'] . '</h4>
                  </td>
                </tr>';
              }

            } //for lecturas

          } //if-else lecturas

        } //for grupos

      } //if-else existencia de grupos

    } //for periodos
    $html_code .= "</table><div style='page-break-after:always;'></div>";
  } // for promotores

  //quita el último page-break-after porque pone una última página en blanco
  $html_code = substr($html_code, 0, -44);
  return $periodos;
}

/**
 * Genera el código y estructura del archivo pdf para alumnos
 * @param  Class  $web   Objeto para hacer uso de smarty
 * @param  array $datos  Arreglo asociativo que puede tener campos con los nombres: cveperiodo, nocontrol y letra como contenido
 * @return String Contenido principal del cuerpo html del pdf
 */
function reporte_periodos_alumno($web, $datos=null) {
  global $html_code;
  $periodos = ""; //inicializa
  
  //obtener todos los alumnos
  $sql = "select usuarios.cveusuario, usuarios.nombre AS \"nombre_usuario\", especialidad.nombre AS
  \"nombre_especialidad\", correo from
  usuarios
  inner join especialidad_usuario on especialidad_usuario.cveusuario = usuarios.cveusuario
  inner join especialidad on especialidad.cveespecialidad = especialidad_usuario.cveespecialidad
  where usuarios.cveusuario in (select cveusuario from usuario_rol where cverol=3)";
  $parameters = array();

  //si no se manda el nocontrol, selecciona todos los promotores
  //si se envía el nocontrol, selecciona a ése promotor específico
  if (isset($datos['nocontrol'])) {
    $sql .= " and usuarios.cveusuario=?";
    $parameters = $datos['nocontrol'];
  }

  $sql .= "order by usuarios.nombre";
  $alumnos = $web->DB->GetAll($sql, $parameters);
  
  //obtener los periodos
  $parameters = array();
  if (isset($datos['cveperiodo'])) {
    //si se indica cveperiodo, se selecciona ése periodo específico
    $sql        = "select * from periodo where cveperiodo=?";
    $parameters = $datos['cveperiodo'];
  } else {
    //si no se indica cveperiodo, selecciona todos los periodos
    $sql = "select * from periodo order by cveperiodo";
  }
  $periodos = $web->DB->GetAll($sql, $parameters);
  
  for ($i = 0; $i < sizeof($alumnos); $i++) {

    //encabezado de alumno
    $html_code .= '
    <table class="table table-condensed">
      <tr class="active">
        <td width="100"><small>NO. CONTROL</small><h4>' . $alumnos[$i]['cveusuario'] . '</h4></td>
        <td width="150" colspan="2"><small>ALUMNO</small><h4>' . $alumnos[$i]['nombre_usuario'] . '</h4></td>
        <td width="150"><small>ESPECIALIDAD</small><h4>'.$alumnos[$i]['nombre_especialidad'].'</h4></td>
        <td width="100"><small>CORREO</small><h4>' . $alumnos[$i]['correo'] . '</h4></td>
      </tr>';
      
      for ($j = 0; $j < sizeof($periodos); $j++) {
        
        //encabezado de periodo
        $html_code .= '
        <tr class="active"><td colspan="5">
          <h4>Periodo: ' . $periodos[$j]['fechainicio'] . ' : ' . $periodos[$j]['fechafinal'] . '</h4>
        </td></tr>';
        
        //obtiene los grupos del alumno por cada periodo
        $sql = "select distinct laboral.cveletra, letra, cvesala, nombre, titulo from laboral
        inner join abecedario on laboral.cveletra = abecedario.cve
        left join libro on laboral.cvelibro_grupal = libro.cvelibro
        where cveperiodo=? 
        and laboral.cveletra in (select cveletra from lectura where cveperiodo=? and nocontrol=?)";
        $parameters = array($periodos[$j]['cveperiodo'], $periodos[$j]['cveperiodo'], $alumnos[$i]['cveusuario']);
        
        //si envía cveletra, se escoge un grupo en específico
        if (isset($datos['letra'])) {
          $sql .= " and letra=?";
          $parameters = array($periodos[$j]['cveperiodo'], $periodos[$j]['cveperiodo'], $alumnos[$i]['cveusuario'], $datos['letra']);
        }
        $sql .= " order by letra";
        $grupos = $web->DB->GetAll($sql, $parameters);
        
        if (!isset($grupos[0])) {
          $html_code .= '<tr><td colspan="5"><h4>No hay grupos en este periodo</h4></td></tr>';
        
        } else {
          for ($k = 0; $k < sizeof($grupos); $k++) {
            $html_code .= '
            <tr class="active">
              <td><small>GRUPO</small><h4>' . $grupos[$k]['letra'] . '</h4></td>
              <td width="100"><small>SALA</small><h4>' . $grupos[$k]['cvesala'] . '</h4></td>
              <td width="100"><small>NOMBRE DEL GRUPO</small><h4>' . $grupos[$k]['nombre'] . '</h4></td>
              <td colspan="2"><small>LIBRO GRUPAL</small><h4>' . $grupos[$k]['titulo'] . '</h4></td>
            </tr>';
            
            //obtener los libros de ese alumno en ese grupo en ese periodo
            $sql = "select libro.cvelibro, titulo, lectura.cvelectura from libro
            inner join lista_libros on libro.cvelibro = lista_libros.cvelibro
            inner join lectura on lectura.cvelectura = lista_libros.cvelectura
            where lectura.cveperiodo=? and nocontrol=? 
            and cveletra=?";
            $parameters = array(
              $periodos[$j]['cveperiodo'],
              $alumnos[$i]['cveusuario'],
              $grupos[$k]['cveletra']
            );
            $libros = $web->DB->GetAll($sql, $parameters);
            
            if (!isset($libros[0])) { 
              $html_code .= '<tr><td colspan="5"><h4>No hay libros en este grupo</h4></td></tr>';
            } else {
              $html_code .= '<tr class="info"><td colspan="5"><h4>Lista de Libros</h4></td></tr>';
              
              for ($a = 0; $a < sizeof($libros); $a++) {
                //obtener resultados de calificaciones
                $sql        = "select * from evaluacion where cvelectura=?";
                $evaluacion = $web->DB->GetAll($sql, $libros[$a]['cvelectura']);
                
                if (!isset($evaluacion[0])) {
                  $terminado = "No";

                } else {
                  switch ($evaluacion[0]['terminado']) {
                    case 0:$terminado = "No";
                      break;
                    case 100:$terminado = "Si";
                      break;
                    default:$terminado = "No";
                      break;
                  }
                }
                
                $html_code .= '
                <tr>
                  <td class="info">
                    <small>CLAVE</small>
                    <h4>' . $libros[$a]['cvelibro'] . '</h4></td>
                  <td colspan="3"><small>TITULO</small>
                    <h4>' . $libros[$a]['titulo'] . '</h4>
                  </td>
                  <td><center><small>TERMINADO</small></center>
                    <center><h4>'.$terminado.'</h4></center>
                  </td>
                </tr>
                <tr>
                  <td><small>COMPRENSIÓN</small>
                    <h4>' . $evaluacion[0]['comprension'] . '</h4>
                  </td>
                  <td><small>MOTIVACIÓN</small>
                    <h4>' . $evaluacion[0]['motivacion'] . '</h4>
                  </td>
                  <td><small>PARTICIPACIÓN</small>
                    <h4>' . $evaluacion[0]['participacion'] . '</h4>
                  </td>
                  <td><small>ASISTENCIA</small>
                    <h4>' . $evaluacion[0]['asistencia'] . '</h4>
                  </td>
                  <td><small>ACTIVIDADES</small>
                    <h4>' . $evaluacion[0]['actividades'] . '</h4>
                  </td>
                </tr>';
              }//for libros
              
            }//end if libros
            
          }//end for grupos
          
        }//end if grupos
        
      }//end for periodos
      
      $html_code .= "</table><div style='page-break-after:always;'></div>";
  } //end for alumnos
  
  //quita el último page-break-after porque pone una última página en blanco
  $html_code = substr($html_code, 0, -44);
  return $periodos;
}
