<?php
include('../../../sistema.php');
class Datos extends Sistema
{
	function API(){	
		header('Content-Type: aplication/json');	

		$metodo=$_SERVER['REQUEST_METHOD'];
		if(!isset($_GET['cveletra'])){
			//$this->status("Error", "No falta información del grupo");
			$this->obtenerAllmsj();
			die();
		}
		
		switch ($metodo) 
		{
			case 'POST':
   if(isset($_GET['receptor'])){
    $this->enviarMensajeU();
   }
   else{
    $this->enviarMensajeG();
   }
				//$this->APIactualizar($_GET['accion'],$_GET['id']);
				break;
			case 'GET':
				if(isset($_GET['receptor'])){
      				if(isset($_GET['cvemsj'])){
       					$this->obtenerMensaje();
     				}
     				else{
				      $this->obtenerMensajesU(); 
			     	}
				} else {					
					$this->obtenerMensajes();
				}
				break;
		}
	}

	function obtenerAllmsj(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		
		// $sql = "select * from laboral where cveperiodo = ? and cvepromotor = ?";
		// $grupo = $this->DB->GetAll($sql, array($cveperiodo, $_GET['usuario']));
		// if(!isset($grupo[0])){
		// 	$this->status("Error", "El grupo no existe y/o no tiene permisos para acceder a el");	
		// }

		$sql = "select * from msj where cveperiodo = ? and emisor = ?";
		$mensajes = $this->DB->GetAll($sql, array($cveperiodo, $_GET['usuario']));

	  	if(!isset($mensajes[0])){
	   		$this->status("Exito", "No hay mensajes actualmente");
	  	}

	  	$aux['mensajes'] = $mensajes;
		$mensajes = json_encode($aux);
		echo $mensajes;
	}	

	function obtenerMensajes(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		
		$sql = "select * from laboral where cveletra = ? and cveperiodo = ? and cvepromotor = ?";
		$grupo = $this->DB->GetAll($sql, array($_GET['cveletra'], $cveperiodo, $_GET['usuario']));
		if(!isset($grupo[0])){
			$this->status("Error", "El grupo no existe y/o no tiene permisos para acceder a el");	
		}

		$sql = "select * from msj where cveletra = ? and cveperiodo = ? and emisor = ? and tipo = 'GR'";
		$mensajes = $this->DB->GetAll($sql, array($_GET['cveletra'], $cveperiodo, $_GET['usuario']));

  		if(!isset($mensajes[0])){
		   $this->status("Exito", "No hay mensajes actualmente");
	  	}

	  	$aux['mensajes'] = $mensajes;
		$mensajes = json_encode($aux);
		echo $mensajes;
	}

	function obtenerMensajesU(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		
		$sql = "select * from laboral where cveletra = ? and cveperiodo = ? and cvepromotor = ?";
		$grupo = $this->DB->GetAll($sql, array($_GET['cveletra'], $cveperiodo, $_GET['usuario']));
		if(!isset($grupo[0])){
			$this->status("Error", "El grupo no existe y/o no tiene permisos para acceder a el");	
		}


		$sql = "select * from usuarios where cveusuario = ?";
		$usuario = $this->DB->GetAll($sql, $_GET['receptor']);
		if(!isset($usuario[0])){
			$this->status("Error", "El usuario no existe y/o no tiene permisos para acceder a el");	
		}

		$sql = "select * from msj where cveletra = ? and cveperiodo = ? and emisor = ? and receptor = ? and tipo = 'IN'";
		$mensajes = $this->DB->GetAll($sql, array($_GET['cveletra'], $cveperiodo, $_GET['usuario'], $_GET['receptor']));

  if(!isset($mensajes[0])){
   $this->status("Exito", "No hay mensajes actualmente");
  }
  		$aux['mensajes'] = $mensajes;
		$mensajes = json_encode($aux);
		echo $mensajes;
	}	

	function obtenerMensaje(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		
		$sql = "select * from laboral where cveletra = ? and cveperiodo = ? and cvepromotor = ?";
		$grupo = $this->DB->GetAll($sql, array($_GET['cveletra'], $cveperiodo, $_GET['usuario']));
		if(!isset($grupo[0])){
			$this->status("Error", "El grupo no existe y/o no tiene permisos para acceder a el");	
		}

		$sql = "select * from msj where cvemsj = ? and cveperiodo = ? and emisor = ?";
		$mensajes = $this->DB->GetAll($sql, array($_GET['cvemsj'], $cveperiodo, $_GET['usuario']));

		$mensajes = json_encode($mensajes);
		echo $mensajes;
	}


	function enviarMensajeU(){
		$obj = json_decode(file_get_contents('php://input'));
		foreach ($obj as $mensaje)
		{

			$cveperiodo = $this->periodo();
			if ($cveperiodo == ""){
				$this->status("Error", "No hay periodo actual");
			}

			$sql="select * from usuarios where cveusuario = ?";
			$usuario = $this->DB->GetAll($sql, $_GET['receptor']);
			
			if(!isset($usuario[0])){
				$this->status("Error", "No existe el receptor");
			}	
			
			$sql="select *  from laboral where cveletra = ? and cveperiodo = ?";
			$grupo = $this->DB->GetAll($sql, array($mensaje->cveletra, $cveperiodo));
			
			if(!isset($grupo[0])){
				$this->status("Error", "No existe el grupo seleccionado");
			}

			if($mensaje->descripcion == ""){
				$this->status("Error", "Agregue una descripcion");	
			}

			if($mensaje->introduccion == ""){
				$this->status("Error", "Agregue una introducción");
			}
			

			//Aqui ya va el insert
			$sql = "INSERT INTO msj (introduccion, descripcion, tipo, emisor, receptor, fecha, expira, cveletra, cveperiodo) values(?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $parametros = array($mensaje->introduccion, $mensaje->descripcion, 'IN', $_GET['usuario'], $_GET['receptor'], 'now()', $mensaje->expira, $mensaje->cveletra, $cveperiodo);
            
            if(!$this->query($sql, $parametros)){
            	$this->status("Error", "No se pudo crear el grupo");	
            }
        	$this->status("Exito", "Se ha envíado el mensaje correctamente");
		}
	}

	function enviarMensajeG(){
		$obj = json_decode(file_get_contents('php://input'));
		foreach ($obj as $mensaje)
		{

			$cveperiodo = $this->periodo();
			if ($cveperiodo == ""){
				$this->status("Error", "No hay periodo actual");
			}
			
			$sql="select *  from laboral where cveletra = ? and cveperiodo = ?";
			$grupo = $this->DB->GetAll($sql, array($mensaje->cveletra, $cveperiodo));
			
			if(!isset($grupo[0])){
				$this->status("Error", "No existe el grupo seleccionado");
			}
			

			if($mensaje->descripcion == ""){
				$this->status("Error", "Agregue una descripcion");	
			}

			if($mensaje->introduccion == ""){
				$this->status("Error", "Agregue una introducción");
			}
			

			//Aqui ya va el insert
			$sql = "INSERT INTO msj (introduccion, descripcion, tipo, emisor, fecha, expira, cveletra, cveperiodo) values(?, ?, ?, ?, ?, ?, ?, ?)";
            $parametros = array($mensaje->introduccion, $mensaje->descripcion, 'GR', $_GET['usuario'], 'now()', $mensaje->expira, $mensaje->cveletra, $cveperiodo);
            
            if(!$this->query($sql, $parametros)){
            	$this->status("Error", "No se pudo enviar el mensaje");	
            }
        	$this->status("Exito", "Se ha envíado el mensaje correctamente");
		}
	}
}

	

$web = new Datos;

if(!$web->authentication()){
	$web->status("Error", "Autenticación basica no valida");
}

if(!isset($_GET['usuario']) || !isset($_GET['password']) || !isset($_GET['token'])){
	$this->status("Error", "Falta información.");
}

if(!$web->verificar($_GET['usuario'], $_GET['password'], $_GET['token'])){
	die();
}

$web->API();
?>