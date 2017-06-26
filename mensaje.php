<?php
include 'sistema.php';
if (isset($_GET['msj'])) 
{
	$cvemsj=$_GET['msj'];
	$sql="select*from msj where cvemsj='".$cvemsj."'";
	$mensaje=$web->msj($sql);
	$web->smarty->assign('mensaje',$mensaje);
	$web->smarty->display('mensajes_publicos.html');
	die();
}

$date = getdate();
$fecha = date('Y-m-j');
$sql="select cvemsj, introduccion, tipomsj.descripcion, usuarios.nombre, fecha, expira 
			from msj inner join tipomsj on tipomsj.cvetipomsj = msj.tipo
					 inner join usuarios on usuarios.cveusuario = msj.emisor
			where tipomsj.cvetipomsj='PU' and expira >= ? order by fecha";
$mensajes = $web->DB->GetAll($sql, $fecha);

if(!isset($mensajes[0])){
	message('No hay ningun aviso para mostrar',$web);
}

$web->smarty->assign('mensajes',$mensajes);
$web->smarty->display('mensajes_publicos.html');


/**
 * Método para mostrar el template form_alumnos cuando ocurre algún error
 * @param  String $msg       Mensaje a desplegar
 * @param  $web              Para poder aplicar las funciones de $web
 */
 
function message($msg, $web)
{
  $web->smarty->assign('alert', 'danger');
  $web->smarty->assign('msg', $msg);
  $web->smarty->display('mensajes_publicos.html');
  die();
}

?>