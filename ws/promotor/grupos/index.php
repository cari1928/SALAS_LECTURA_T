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
			case 'POST':
				$this->insertGrupo();
				//$this->APIactualizar($_GET['accion'],$_GET['id']);
				break;
			case 'GET':
				if(isset($_GET['idsala'])){
					if($_GET['idsala'] == 'libros'){
						$this->obtenerLibros();
					} else {
						$this->obtenerHorarios($_GET['idsala']);
					}
				} else {					
					$this->obtenerSalas();
				}
				break;
				
		}
	}
	

	function obtenerSalas(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		$sql = "select * from sala where cveperiodo = ?";
		$salas = $this->DB->GetAll($sql, $cveperiodo);
		if(!isset($salas[0])){
			$this->status("Error", "No hay salas actualmente");	
		}
		$salas = json_encode($salas);
		echo $salas;
	}

	function obtenerHorarios($cvesala){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}

		$sql = "select * from sala where cvesala = ?";

		$sala = $this->DB->GetAll($sql, $cvesala);
		if(!isset($sala[0])){
			$this->status("Error", "La sala seleccionada no existe");
		}
		$horas_json;
		
		$sql          = "select * from dia";
      	$dias         = $this->DB->GetAll($sql);

		for ($i = 1; $i <= sizeof($dias); $i++) {
        $sql = "select cvehoras, hora_inicial, hora_final from horas
        EXCEPT
        select horas.cvehoras, hora_inicial, hora_final from laboral
        inner join horas on laboral.cvehoras = horas.cvehoras
        inner join sala on sala.cvesala = laboral.cvesala
        where cvedia=? and ubicacion=? and laboral.cveperiodo=?
        order by hora_inicial, hora_final";
        $horas = $this->DB->GetAll($sql, array($i, $sala[0]['ubicacion'], $cveperiodo));

        if (isset($horas[0])) {
          $horas_json[$i.""] = $horas;
        }
      }
		$horas_json = json_encode($horas_json);
		echo $horas_json;
	}	

	function obtenerLibros(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		$sql = "select * from libro";
		$libros = $this->DB->GetAll($sql);
		if(!isset($libros[0])){
			$this->status("Error", "No hay libros actualmente");	
		}
		$libros = json_encode($libros);
		echo $libros;	
	}

	function insertGrupo(){
		$obj = json_decode(file_get_contents('php://input'));
		foreach ($obj as $laboral)
		{

			$cveperiodo = $this->periodo();
			if ($cveperiodo == ""){
				$this->status("Error", "No hay periodo actual");
			}

			$sql="select *  from horas where cvehoras = ? or cvehoras = ?";
			$hora = $this->DB->GetAll($sql, array($laboral->cvehoras, $laboral->cvehoras2));
			
			if($laboral->cvehoras != $laboral->cvehoras2){
				if(!isset($hora[1])){
					$this->status("Error", "No existe la hora seleccionada");
				}	
			} else {
				if(!isset($hora[0])){
					$this->status("Error", "No existe la hora seleccionada");
				}
			}
			
			$sql="select *  from dia where cvedia = ? or cvedia = ?";
			$dia = $this->DB->GetAll($sql, array($laboral->cvedia, $laboral->cvedia2));
			
			if($laboral->cvedia != $laboral->cvedia2){
				if(!isset($dia[1])){
					$this->status("Error", "No existe el dia seleccionado");
				}
			} else {
				if(!isset($dia[0])){
					$this->status("Error", "No existe el dia seleccionado");
				}
			}
			

			$sql="select *  from sala where cvesala = ?";
			$sala = $this->DB->GetAll($sql, $laboral->cvesala);
			if(!isset($sala[0])){
				$this->status("Error", "No existe la sala seleccionada");
			}

			$sql="select *  from usuarios where cveusuario = ?";
			$usuario = $this->DB->GetAll($sql, $laboral->cveusuario);
			if(!isset($usuario[0])){
				$this->status("Error", "No existe el promotor");
			}

			$sql="select *  from libro where cvelibro = ?";
			$libro = $this->DB->GetAll($sql, $laboral->cvelibro);
			if(!isset($libro[0])){
				$this->status("Error", "No existe el libro seleccionada");
			}

			$sql      = "select COALESCE(MAX(cveletra),0) as cveletra from laboral where cveperiodo=?";
		   	$grupo    = $this->DB->GetAll($sql, $cveperiodo);
		  	$grupo    = ($grupo[0]['cveletra'] + 1);
			
	  	  	$sql    = "select letra from abecedario where cve=?";
		  	$letra  = $this->DB->GetAll($sql, $grupo);
		  	$nombre = "SALA - " . $letra[0]['letra'];

			//Aqui ya va el insert
			$sql = "INSERT INTO laboral(cveperiodo, cvehoras, cvedia, cvesala, cveletra, nombre, cvepromotor, cvelibro_grupal) values(?, ?, ?, ?, ?, ?, ?, ?)";
            $parametros = array($cveperiodo, $laboral->cvehoras, $laboral->cvedia, $laboral->cvesala, $grupo, $nombre, $laboral->cveusuario, $laboral->cvelibro);
            if(!$this->query($sql, $parametros)){
            	$this->status("Error", "No se pudo crear el grupo");	
            }

            $sql = "INSERT INTO laboral(cveperiodo, cvehoras, cvedia, cvesala, cveletra, nombre, cvepromotor, cvelibro_grupal) values(?, ?, ?, ?, ?, ?, ?, ?)";
            $parametros = array($cveperiodo, $laboral->cvehoras2, $laboral->cvedia2, $laboral->cvesala, $grupo, $nombre, $laboral->cveusuario, $laboral->cvelibro);
            
            if(!$this->query($sql, $parametros)){
            	$sql = "select MAX(cvelaboral) as cveletra from laboral where cveperiodo=?";
		   		$cvelaboral = $this->DB->GetAll($sql, $cveperiodo);
		   		$sql = "DELETE FROM labral WHERE cvelaboral = ?";
		   		$this->query($sql, $cvelaboral);
            	$this->status("Error", "No se pudo crear el grupo");	
            }

            else{
            	$this->status("Exito", "Se ha creado el grupo correctamente");
            }
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