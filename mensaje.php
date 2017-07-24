<?php
include 'sistema.php';

// if (isset($_GET['info'])) {
//   $cvemsj  = $_GET['info'];
//   $sql     = "SELECT * FROM msj WHERE cvemsj=?";
//   $mensaje = $web->DB->GetAll($sql, $_GET['info']);
//   if(!isset($mensaje[0])) {
//     message('danger', 'No fue posible obtener el aviso');
//   }

//   $web->smarty->assign('aviso', $mensaje[0]);
//   $web->smarty->display('mensajes_publicos.html');
//   die();
// }

$date       = getdate();
$fecha      = date('Y-m-j');
$cveperiodo = $web->periodo();
if ($cveperiodo == "") {
  message('warning', "No hay periodo actual");
}

$sql = "SELECT cvemsj, introduccion, msj.descripcion, usuarios.nombre, fecha, expira FROM msj
  INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
  LEFT JOIN usuarios ON usuarios.cveusuario = msj.emisor
  WHERE tipomsj.cvetipomsj='PU' AND expira >= ? AND cveperiodo=?
  ORDER BY fecha";
$mensajes = $web->DB->GetAll($sql, array($fecha, $cveperiodo));
if (!isset($mensajes[0])) {
  message('warning', 'No hay avisos por mostrar');
}

$web->smarty->assign('mensajes', $mensajes);
$web->smarty->display('mensajes_publicos.html');

/**********************************************************************************************
 * FUNCIONES
 **********************************************************************************************/
/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $alert     Tipo de mensaje
 * @param  String $msg       Mensaje a desplegar
 */
function message($alert, $msg)
{
  global $web;
  $web->simple_message($alert, $msg);
  $web->smarty->display('mensajes_publicos.html');
  die();
}
