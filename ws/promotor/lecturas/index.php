x<?php
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
            if(isset($_GET['cvelista'])){
               $this->calif_reporte();
            }else{
				  $this->updateAlumno();
            }
				//$this->APIactualizar($_GET['accion'],$_GET['id']);
				break;
			case 'GET':
            if(isset($_GET['cveletra'])){
               if(isset($_GET['nocontrol'])){
                  $this->obtenerAlumno();
               } else {
                  $this->obtenerAlumnos();
               }
            } else {
					$this->obtenerGrupos();
				}
				break;
				
		}
	}
	

	function obtenerGrupos(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		$sql = "select cvelaboral, letra, laboral.nombre, ubicacion, fechainicio, fechafinal, cveletra, d.cvedia, d.nombre, h.hora_inicial, h.hora_final, l.titulo, sala.cvesala from laboral 
				inner join sala on laboral.cvesala = sala.cvesala
				inner join horas h on h.cvehoras = laboral.cvehoras
				inner join dia d on d.cvedia = laboral.cvedia 
				inner join abecedario on laboral.cveletra = abecedario.cve 
				inner join libro l on l.cvelibro = laboral.cvelibro_grupal
				inner join periodo on laboral.cveperiodo= periodo.cveperiodo
				where cvepromotor=? and laboral.cveperiodo=?
				order by letra";
		$grupos = $this->DB->GetAll($sql, array($_GET['usuario'], $cveperiodo));
		if(!isset($grupos[0])){
			$this->status("Error", "No hay grupos actualmente");	
		}
		$aux['laboral'] = $grupos;
		$grupos = json_encode($aux);
		echo $grupos;
	}

	function obtenerAlumno(){
		$cveperiodo = $this->periodo();
		if ($cveperiodo == ""){
			$this->status("Error", "No hay periodo actual");
		}
		$sql = "SELECT distinct l.nocontrol, l.cvelectura, u.nombre, e.comprension, e.motivacion, e.participacion, e.terminado, e.asistencia, e.actividades, e.cveeval FROM lectura l INNER JOIN evaluacion e ON e.cvelectura = l.cvelectura INNER JOIN usuarios u on u.cveusuario = l.nocontrol INNER JOIN laboral on laboral.cveletra = l.cveletra WHERE l.cveperiodo = ? and l.nocontrol = ? and l.cveletra = ? and laboral.cvepromotor = ?";
		$datos = $this->DB->GetAll($sql, array($cveperiodo, $_GET['nocontrol'], $_GET['cveletra'], $_GET['usuario']));
		
		if(!isset($datos[0])){
			$this->status("Exito", "El alumno no pertenece al grupo");
		}

		$sql = "SELECT l.titulo, ll.calif_reporte, ll.cvelista, e.estado FROM lista_libros ll INNER JOIN libro l on l.cvelibro = ll.cvelibro INNER JOIN estado e on e.cveestado = ll.cveestado WHERE cvelectura = ? and cveperiodo = ?";
		$libros = $this->DB->GetAll($sql, array($datos[0]['cvelectura'], $cveperiodo));
      
      if(isset($libros[0])){
         $datos[0]['libros'] = $libros;
      }      
		
		$datos = json_encode($datos);
		echo $datos;
	}



	function obtenerAlumnos(){
      $cveperiodo = $this->periodo();
      if ($cveperiodo == ""){
         $this->status("Error", "No hay periodo actual");
      }
      $sql = "SELECT distinct l.cvelectura, l.nocontrol, u.nombre, l.cveletra, laboral.cvelaboral FROM lectura l INNER JOIN evaluacion e ON e.cvelectura = l.cvelectura INNER JOIN usuarios u on u.cveusuario = l.nocontrol INNER JOIN laboral on laboral.cveletra = l.cveletra
                  WHERE laboral.cveperiodo = ? and l.cveletra = ? and laboral.cvepromotor = ?";
      $datos = $this->DB->GetAll($sql, array($cveperiodo, $_GET['cveletra'], $_GET['usuario']));
      
      if(!isset($datos[0])){
         $this->status("Exito", "No hay alumnos actualmente");
      }

      $aux['alumnos'] = $datos;
      
      $datos = json_encode($aux);
      echo $datos;
   }

	

	function updateAlumno(){
		$obj = json_decode(file_get_contents('php://input'));
		foreach ($obj as $etiqueta => $calificar){

			$cveperiodo = $this->periodo();
			if ($cveperiodo == ""){
				$this->status("Error", "No hay periodo actual");
			}

         if($calificar->comprension < 0 || $calificar->comprension > 100 || $calificar->motivacion < 0 || $calificar->motivacion > 100 || $calificar->participacion < 0 || $calificar->participacion > 100 || $calificar->terminado < 0 || $calificar->terminado > 100 || $calificar->asistencia < 0 || $calificar->asistencia > 100 || $calificar->actividades < 0 || $calificar->actividades > 100 ){
            $this->status("Error", "Las calificaciones no entran en los rangos establecidos");
         }

         $sql="select *  from evaluacion where cveeval = ?";
         $evaluacion = $this->DB->GetAll($sql, $calificar->cveeval);
         
         if(!isset($evaluacion[0])){
          $this->status("Error", "No xisten la evaluacion seleccionda");
         }
         
         $sql = "UPDATE evaluacion SET comprension = ?, motivacion = ?, participacion = ?, terminado = ?, asistencia = ?, actividades = ? where cveeval = ?";
         $parametros = array($calificar->comprension, $calificar->motivacion, $calificar->participacion, $calificar->terminado, $calificar->asistencia, $calificar->actividades, $calificar->cveeval);

         if(!$this->query($sql, $parametros)){
            $this->status("Error", "No se pudo calificar al alumno correctamente");
         }
         $this->status("Exito", "Se ha calificado al alumno correctamente");
		}
	}

   function calif_reporte(){
      $obj = json_decode(file_get_contents('php://input'));
      foreach ($obj as $etiqueta => $calif_reporte){
         $sql = "SELECT * FROM lista_libros WHERE cvelista = ?";
         $lista = $this->DB->GetAll($sql, $calif_reporte->cvelista);
         if(!isset($lista[0])){
            $this->status("Error", "No existe el registro del libro a calficar");
         }

         $sql = "UPDATE lista_libros set calif_reporte = ? where cvelista = ?";
         if(!$this->query($sql, array($calif_reporte->calif_reporte, $calif_reporte->cvelista))){
            $this->status("Error", "No se pudo asignar la calificacion al reporte del libro");
         }
         $this->status("Exito", "Se ha calificado el reporte correctamente");
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
	echo "ya entre aqui";
   die();
}
$web->API();
?>