<?php

class ReporteControllers extends Sistema
{
  /**
   * PROMOTORES
   */
  public function getAllPromotores()
  {
    $sql = "SELECT
    usuarios.cveusuario AS \"RFC\",
    usuarios.nombre AS \"PROMOTOR\",
    especialidad.nombre AS \"ESPECIALIDAD\",
    correo AS \"CORREO\",
    especialidad.cveespecialidad AS \"especialidad_cve\",
    otro
    FROM usuarios
    INNER JOIN especialidad_usuario ON especialidad_usuario.cveusuario = usuarios.cveusuario
    INNER JOIN especialidad ON especialidad.cveespecialidad=especialidad_usuario.cveespecialidad
    WHERE usuarios.cveusuario IN (SELECT cveusuario FROM usuario_rol WHERE cverol=2)
    ORDER BY usuarios.nombre";

    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $promotores = $this->DB->GetAll($sql);

    for ($i = 0; $i < sizeof($promotores); $i++) {
      if ($promotores[$i]['especialidad_cve'] == 'O') {
        unset($promotores[$i]['ESPECIALIDAD']);
        unset($promotores[$i]['especialidad_cve']);
        $promotores[$i]['ESPECIALIDAD'] = $promotores[$i]['otro'];
        unset($promotores[$i]['otro']);
      }
    }

    return $promotores;
  }

  /**
   * PROMOTOR
   */
  public function getPromotor($promotorId, $bandera = false)
  {
    $sql = "SELECT
    usuarios.cveusuario AS \"RFC\",
    usuarios.nombre AS \"PROMOTOR\",
    especialidad.nombre AS \"ESPECIALIDAD\",
    correo AS \"CORREO\",
    especialidad.cveespecialidad AS \"especialidad_cve\",
    otro
    FROM usuarios
    INNER JOIN especialidad_usuario ON especialidad_usuario.cveusuario = usuarios.cveusuario
    INNER JOIN especialidad ON especialidad.cveespecialidad=especialidad_usuario.cveespecialidad
    WHERE usuarios.cveusuario IN (SELECT cveusuario FROM usuario_rol WHERE cverol=2)
    AND usuarios.cveusuario='" . $promotorId . "'
    ORDER BY usuarios.nombre";

    if (!$bandera) {
      // solo los encabezados
      $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    } else {
      $this->DB->SetFetchMode(ADODB_FETCH_BOTH);
    }

    $promotor = $this->DB->GetAll($sql);
    if (sizeof($promotor) == 1) {
      if ($promotor[0]['especialidad_cve'] == 'O') {
        unset($promotor[0]['ESPECIALIDAD']);
        unset($promotor[0]['especialidad_cve']);
        $promotor[0]['ESPECIALIDAD'] = $promotor[0]['otro'];
        unset($promotor[0]['otro']);
        unset($promotor[0][4]);
        unset($promotor[0][5]);
        if ($bandera) {
          $promotor[0][2] = $promotor[0]['CORREO'];
          $promotor[0][3] = $promotor[0]['ESPECIALIDAD'];
        }
      } else {
        unset($promotor[0]['especialidad_cve']);
        unset($promotor[0]['otro']);
      }

      return $promotor;
    } else {
      //el query no regresó nada
      return null;
    }

  }

  /**
   * PERIODOS
   */
  public function getPeriodo($cveperiodo)
  {
    $sql = "SELECT * FROM periodo WHERE cveperiodo=?";
    return $this->DB->GetAll($sql, $cveperiodo);
  }

  public function getAllPeriodos()
  {
    $sql = "SELECT * FROM periodo ORDER BY cveperiodo";
    return $this->DB->GetAll($sql);
  }

  /**
   * GRUPOS
   * @param  $cveperiodo
   * @param  $cvepromotor
   * @param  $bandera, true==quiere todos los campos ; false==solo quiere encabezados
   */
  public function getAllGrupos($cveperiodo, $cvepromotor, $bandera = false)
  {
    $sql = "SELECT DISTINCT
    letra AS \"GRUPO\",
    cvesala AS \"SALA\",
    nombre AS \"NOMBRE\",
    titulo AS \"LIBRO_GRUPAL\",
    laboral.cveletra
    FROM laboral
    INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
    LEFT JOIN libro ON laboral.cvelibro_grupal = libro.cvelibro
    WHERE cveperiodo=? AND cvepromotor=?
    ORDER BY letra";

    if (!$bandera) {
      // solo los encabezados
      $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    } else {
      $this->DB->SetFetchMode(ADODB_FETCH_BOTH);
    }

    $grupos = $this->DB->GetAll($sql, array($cveperiodo, $cvepromotor));
    if (isset($grupos[0])) {
      if (!$bandera) {
        for ($i = 0; $i < count($grupos); $i++) {
          unset($grupos[$i]['cveletra']);
        }
      }
      return $grupos;
    }

    return null;
  }

  /**
   * LECTURAS
   * @param $cveperiodo
   * @param $cvepromotor
   * @param $cveletra
   */
  public function getAllLecturas($cveperiodo, $cvepromotor, $cveletra)
  {
    $sql = "SELECT *
    FROM lectura
    WHERE cveletra IN (
      SELECT cveletra
      FROM laboral
      WHERE cveperiodo=?
      AND cvepromotor=?
      )
    AND cveletra=?
    AND cveperiodo=?
    ORDER BY nocontrol";

    $parameters = array($cveperiodo, $cvepromotor, $cveletra, $cveperiodo);
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    return $this->DB->GetAll($sql, $parameters);
  }

  /**
   * ALUMNO
   * @param $nocontrol
   * @param $cveperiodo
   * @param $cveletra
   */
  public function getAlumno($nocontrol, $cveperiodo, $cveletra, $cvelectura)
  {
    $sql = "SELECT
    usuarios.cveusuario AS \"NOCONTROL\",
    usuarios.nombre AS \"NOMBRE\",
    especialidad.cveespecialidad AS \"ESPECIALIDAD\",
    correo AS \"CORREO\"
    FROM usuarios
    LEFT JOIN especialidad_usuario ON especialidad_usuario.cveusuario = usuarios.cveusuario
    LEFT JOIN especialidad ON especialidad.cveespecialidad = especialidad_usuario.cveespecialidad
    INNER JOIN lectura ON lectura.nocontrol = usuarios.cveusuario
    WHERE usuarios.cveusuario=?
    AND cveperiodo=?
    AND cveletra=?";

    $parameters = array($nocontrol, $cveperiodo, $cveletra);
    $this->DB->SetFetchMode(ADODB_FETCH_BOTH);
    $alumno = $this->DB->GetAll($sql, $parameters);

    $evaluacion             = $this->getEvaluacion($cvelectura);
    $alumno[0]['TERMINADO'] = $this->isTerminado($evaluacion);

    return $alumno;
  }

  /**
   * EVALUACION
   */
  public function getEvaluacion($cvelectura)
  {
    $sql = "SELECT * FROM evaluacion WHERE cvelectura=?";
    return $this->DB->GetAll($sql, $cvelectura);
  }

  /**
   * Situación del alumno
   */
  public function isTerminado($evaluacion)
  {
    if (!isset($evaluacion[0])) {
      return "No";
    } else {
      switch ($evaluacion[0]['terminado']) {
        case 0:return "No";
          break;
        case 100:return "Si";
          break;
        default:return "No";
          break;
      }
    }
  }

  /**
   * EVALUACION 2
   */
  public function getEvaluation($cvelectura)
  {
    $sql = "SELECT
    nocontrol AS \"NOCONTROL\",
    nombre AS \"NOMBRE\",
    comprension AS \"COMP\",
    participacion AS \"PART\",
    asistencia AS \"ASIS\",
    actividades AS \"ACTV\",
    reporte AS \"REP\",
    terminado AS \"TERMINADO\"
    FROM evaluacion
    INNER JOIN lectura ON lectura.cvelectura = evaluacion.cvelectura
    INNER JOIN usuarios ON lectura.nocontrol = usuarios.cveusuario
    WHERE evaluacion.cvelectura=?";
    $this->DB->SetFetchMode(ADODB_FETCH_BOTH);
    $evaluacion = $this->DB->GetAll($sql, $cvelectura);

    if (isset($evaluacion[0])) {
      if ($evaluacion[0][7] >= 70) {
        $evaluacion[0][7] = 'Si';
      } else {
        $evaluacion[0][7] = 'No';
      }
      $evaluacion[0]['TERMINADO'] = $evaluacion[0][7];
    }

    return $evaluacion;
  }

  public function getEspecialidad($opcion)
  {
    switch ($opcion) {
      case 'IA':
        return 'Ambiental';
        break;

      case 'IB':
        return 'Bioquímica';
        break;

      case 'IE':
        return 'Electrónica';
        break;

      case 'IGE':
        return 'Gestión';
        break;

      case 'II':
        return 'Informática';
        break;

      case 'IIN':
        return 'Industrial';
        break;

      case 'IM':
        return 'Mecatrónica';
        break;

      case 'IME':
        return 'Mecánica';
        break;

      case 'IQ':
        return 'Química';
        break;

      case 'ISC':
        return 'Sistemas';
        break;

      case 'LAE':
        return 'Administración';
        break;
    }
  }

  /**
   * Obtiene las observaciones de un grupo
   * @param $data debe contener: cveletra, cveperiodo y cvepromotor, en ése orden
   */
  public function getAllObservaciones($data)
  {
    $sql = "SELECT fecha, observacion FROM observacion
    WHERE cveletra=? AND cveperiodo=? AND cvepromotor=?
    ORDER BY cveobservacion";
    return $this->DB->GetAll($sql, array($data['cveletra'], $data['cveperiodo'], $data['cvepromotor']));
  }

}
