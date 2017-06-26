<?php
include('../../sistema.php');
class Usuario extends Sistema
{
	function API(){	
		header('Content-Type: aplication/json');	
		
		$metodo=$_SERVER['REQUEST_METHOD'];
		switch ($metodo) 
		{
			case 'GET':
			default:
				if(!isset($_GET['usuario']) || !isset($_GET['password'])){
					$this->status("Error", "Falta información");
				}
						$this->APIvalidar($_GET['usuario'], $_GET['password']);
				break;
		}
	}

	

	function APIvalidar($usuario, $password){
		$sql = "select * from usuarios where cveusuario = ? and pass = ?";
		$parameters = array($usuario, $password);
		$usr = $this->DB->GetAll($sql, $parameters);
		if(!isset($usr[0])){
			$this->status("Error", "El usuario no existe");
		}

		$now = getdate();
	    $cadena2 = md5(md5(sha1($now['wday']."/".$now['month']."/".$now['year']."-".$now['hours'].":".$now['minutes'].":".$now['seconds']."-".$usuario."_".$password.rand(1,999999))).rand(1,1000000));
      	$cadena = $cadena2;
      	$sql = "insert into bitacora (cveusuario, pass, token, ipusuario) values (?, ?, ?, ?)";
      	$parameters = array($usuario, $password, $cadena, '192.168.1.1');
      	if($this->query($sql, $parameters) != true){
			$this->error("No se puede validar el usuario");
		}
		$sql = "select rol from rol where cverol in (select cverol from usuario_rol where cveusuario = ? )";
		$roles = $this->DB->GetAll($sql, $usuario);
		$token['roles'] = $roles;
		$token['token'] = $cadena;
		$token = json_encode($token);
      	echo $token;
	}
	
	 function status($status, $mensaje){
    $message['status'] = $status;
    $message['message'] = $mensaje;
    $message=json_encode($message);
    http_response_code(200);
    echo $message;
    die();
  }

}
$web = new Usuario;
if($web->authentication()){
	$web->API();
}
?>