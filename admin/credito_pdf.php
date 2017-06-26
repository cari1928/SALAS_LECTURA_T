<?php
include '../sistema.php';

if ($_SESSION['roles'] != 'A') {
  $web->checklogin();
}

//Si se manda info 3 quiere decir que se utilizara el periodo que fue seleccionado en el historial
if(isset($_GET[info3])){
    $cveperiodo = $_GET[info3];
}
else{
    $cveperiodo = $web->periodo();
    if ($cveperiodo == "") {
        message("index alumnos reporte", "No hay periodo actual", $web);
    }
}

if(!isset($_GET['info2'])){
    message("index alumnos reporte", "No Altere la estructura de la pagina, falta la clave del usuario", $web);
}

$nocontrol = $_GET['info2'];

$sql="select * from usuarios where cveusuario = ? 
            and cveusuario in (select cveusuario from usuario_rol where cverol = 3)";
$datos_alumno = $web->DB->GetAll($sql, $nocontrol);

//verificar que el alumno exista
if(!isset($datos_alumno[0])){
    message("index alumnos reporte", "El alumno no existe", $web);
}

//Se realiza la consulta para obtener el estado de los libros del alumno
$sql = 'select e.estado from lista_libros
inner join estado e on e.cveestado = lista_libros.cveestado
inner join lectura on lectura.cvelectura = lista_libros.cvelectura
where lectura.nocontrol = ? and lectura.cveperiodo = ?';
$datos_libros = $web->DB->GetAll($sql, array($nocontrol, $cveperiodo));

//verificar si se obtuvo resultado
if(!isset($datos_libros[0])){
    message("index alumnos reporte", "Este alumno no ha leido ningun libro", $web);
}
$cont = 0;
for($i = 0; $i < sizeof($datos_libros); $i++){
    if($datos_libros[$i]['estado'] == 'Terminado'){
        $cont++;
    }
}

//verifica que si halla leido 7 libros o mas en este periodo
if($cont < 7){
    message("index alumnos reporte", "Este alumno no leyo la cantidad de libros requerida", $web);
}

//Se modifica la columna estado_credito de la tabla usuarios
//$sql = "update usuarios set estado_credito where cveusuario = ?";
//$web->query($sql, $nocontrol);

message("index alumnos reporte", "Aqui ya se deveria de mostrar el documento pdf", $web);

/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $iniClases Ruta a mostrar en links
 * @param  String $msg       Mensaje a desplegar
 * @param  $web              Para poder aplicar las funciones de $web
 * @param  String $cveusuario   Usado en caso de que se trate de un formulario de actualización
 */
function message($iniClases, $msg, $web, $cveusuario = null)
{
  $web->iniClases('admin', $iniClases);

  $web->smarty->assign('alert', 'danger');
  $web->smarty->assign('msg', $msg);
  $web->smarty->display('credito_pdf.html');
  die();
}