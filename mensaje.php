<?php
include 'sistema.php';

if (isset($_GET['msj'])) {
  $cvemsj  = $_GET['msj'];
  $sql     = "SELECT * FROM msj WHERE cvemsj=?";
  $mensaje = $web->msj($sql, $cvemsj);
  $web->smarty->assign('mensaje', $mensaje);
  $web->smarty->display('mensajes_publicos.html');
  die();
}

$date  = getdate();
$fecha = date('Y-m-j');
$sql   = "SELECT cvemsj, introduccion, tipomsj.descripcion, usuarios.nombre, fecha, expira FROM msj
INNER JOIN tipomsj ON tipomsj.cvetipomsj = msj.tipo
INNER JOIN usuarios ON usuarios.cveusuario = msj.emisor
WHERE tipomsj.cvetipomsj='PU' AND expira >= ?
ORDER BY fecha";
$mensajes = $web->DB->GetAll($sql, $fecha);

if (!isset($mensajes[0])) {
  message('warning', 'No hay avisos por mostrar');
}

$web->smarty->assign('mensajes', $mensajes);
$web->smarty->display('mensajes_publicos.html');

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
