<?php
include('../../../sistema.php');
class Datos extends Sistema
{
	function API(){	
		header('Content-Type: aplication/json');	
		//$this->conexion();
		//print_r($_SERVER);
		$metodo=$_SERVER['REQUEST_METHOD'];
		switch ($metodo) 
		{
			case 'PUT':
				$this->actualizarDatos();
				//$this->APIactualizar($_GET['accion'],$_GET['id']);
				break;
			case 'GET':
				if(!isset($_GET['usuario']) || !isset($_GET['password']) || !isset($_GET['rol'])){
					$this->status("Error", "Falta información.");
				}
				$this->obtenerDatos($_GET['usuario'], $_GET['password'], $_GET['rol']);
				break;
				
		}
	}
	

	function obtenerDatos($usuario, $password, $rol){
		$sql = "select u.cveusuario, u.nombre AS \"usuario\", u.correo, e.nombre, r.cverol, r.rol, pass, e.cveespecialidad 
						from usuarios u inner join usuario_rol ur on u.cveusuario = ur.cveusuario
										inner join rol r on r.cverol = ur.cverol
										inner join especialidad_usuario ue on ue.cveusuario = u.cveusuario
										inner join especialidad e on e.cveespecialidad = ue.cveespecialidad
						where u.cveusuario = ? and u.pass = ? and r.rol = ?";
		$datos = $this->DB->GetAll($sql, array($usuario, $password, $rol));
		$datos = json_encode($datos);
		echo $datos;
	}

	function actualizarDatos(){
		$obj = json_decode(file_get_contents('php://input'));
		foreach ($obj as $datos)
		{
			$sql="select *  from usuarios where cveusuario = ?";
			$dat = $this->DB->GetAll($sql, $datos->cveusuario);
			if(!isset($dat[0])){
				$this->status("Error", "No existe el usuaruo con esa clave");
			}

			$sql="update usuarios set nombre = ?, correo = ? where cveusuario = ?";
			if($this->query($sql, array($datos->usuario, $datos->correo, $datos->cveusuario)) != true){
				$this->status("Error", "Los datos no se han actualizado correctamente");	
			}
		}
		$this->status("Exito","Los datos se han actualizado correctamente");
	}
}

	

$web = new Datos;
if(!$web->authentication()){
	$web->status("Error", "Autenticación basica no valida");
}
if(!$web->verificar($_GET['usuario'], $_GET['password'], $_GET['token'])){
	die();
}
$web->API();
?>