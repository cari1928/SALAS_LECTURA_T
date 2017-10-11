<?php
session_start();

include 'config.php';

define('PATHLIB', PATHAPP . LIB);
include PATHLIB . 'adodb/adodb.inc.php';
include PATHLIB . 'smarty/libs/Smarty.class.php';
include PATHLIB . 'phpmailer/PHPMailerAutoload.php';

//clases del sistema
class Conexion
{

  public function Conectar()
  {
    $this->server   = DB_DBMS;
    $this->host     = DB_HOST;
    $this->userdb   = DB_USER;
    $this->passdb   = DB_PASS;
    $this->database = DB_NAME;
    $this->DB       = &ADONewConnection($this->server);
    $this->DB->PConnect($this->host, $this->userdb, $this->passdb, $this->database);
  }
}

class Sistema extends Conexion
{
  //variables
  public $aceptacion = 'No guardado';
  public $rs         = '';
  public $query      = '';
  public $rol        = "";
  public $smarty;

  //Rutas
  public $route_msj      = "/home/slslctr/archivos/mensajes/";
  public $route_periodos = "/home/slslctr/archivos/periodos/";
  public $route_pdf      = "/home/slslctr/archivos/pdf/";
  public $route_images   = "/home/slslctr/Images/";

  public function combo($query, $selected = null, $ruta = "", $parameters = array(), $redireccion = null)
  {
    $datosList = $this->DB->GetAll($query, $parameters);
    if (!isset($datosList[0])) {
      return false;
    }

    $nombrescolumnas = array_keys($datosList[0]);
    $this->smarty->assign('selected', $selected);
    $this->smarty->assign('nombrecolumna', $nombrescolumnas[1]);
    $this->smarty->assign('nombrescolumnas', $nombrescolumnas);
    $this->smarty->assign('datos', $datosList);

    if ($redireccion != null) {
      $this->smarty->assign('redireccion', $redireccion);
    }

    return $this->smarty->fetch($ruta . 'select.component.html');
  }

  /**
   * Muestra informacion de los mensajes publicos
   */
  public function msj($sql, $array = array())
  {
    $datos = $this->DB->GetAll($sql, $array);
    if (!isset($datos[0])) {
      return 'e1';
    }

    // ¿para qué es nombrescolumnas?
    $nombrescolumnas = array_keys($datos[0]);
    $this->smarty->assign('nombrecolumna', $nombrescolumnas[1]);

    $this->smarty->assign('msj', $datos);
    $sql     = "SELECT nombre FROM usuarios WHERE cveusuario=?";
    $usuario = $this->DB->GetAll($sql, $datos[0][4]);
    if (!isset($usuario[0])) {
      return 'e2';
    }

    $this->smarty->assign('promotor', $usuario);
    return $this->smarty->fetch('msj.component.html');
  }

  /**
   * Ejecuta operación SQL de manera más sencilla que DB->GetAll
   * @param  String $query      Consulta SQL
   * @param  array $parameters  Contenedor de las incógnitas en $query
   */
  public function query($query, $parameters = array())
  {
    $this->query = $query;
    $this->rs    = $this->DB->Execute($this->query, $parameters);
    if ($this->DB->ErrorMsg()) {
      echo $this->DB->ErrorMsg();
      return false;
    } else {
      return true;
    }
  }

  public function __construct()
  {
    parent::Conectar();
    $this->smarty = new Smarty();
  }

  public function mostrartabla($query)
  {
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $this->query($query);
    $cantidadcolumnas  = $this->rs->_numOfFields;
    $cantidadregistros = $this->rs->_numOfRows;
    $nombrescolumnas   = array_keys($this->rs->fields);
    $datos             = $this->DB->GetAll($query);
    $this->smarty->assign('cantidadcolumnas', $cantidadcolumnas);
    $this->smarty->assign('cantidadregistros', $cantidadregistros);
    $this->smarty->assign('nombrescolumnas', $nombrescolumnas);
    $this->smarty->assign('datos', $datos);
    return $this->smarty->fetch('muestratabla.html');
  }

  public function tipoCuenta()
  {
    $sql = "select nombre from usuarios where cveusuario='" . $_SESSION['cveUser'] . "'";
    $this->DB->SetFetchMode(ADODB_FETCH_BOTH);
    $datos_rs = $this->DB->GetAll($sql);

    $nombre = $datos_rs[0]['nombre'];
    $cadena = explode(" ", $nombre);

    $this->smarty->assign('usuario', $cadena[0]);

    if ($_SESSION['roles'] == 'A') {
      return $cadena[0] . ' - Administrador';
    }
    if ($_SESSION['roles'] == 'P') {
      return $cadena[0] . ' - Promotor';
    }
    if ($_SESSION['roles'] == 'U') {
      return $cadena[0] . ' - Alumno';
    }
  }
  /**
   * Muestra una tabla html en base a varios parámetros
   */
  public function showTable($query, $direccion, $option, $add, $table, $extra = "")
  {
    $datos  = $this->DB->GetAll($query);
    $tabla2 = "<table class='table table-striped'>";
    switch ($add) {
      case 1: //se redirecciona a una página con add en su nombre
        $tabla2 .= "<tr><td colspan='4' align='right'>
                <a href='add" . $direccion . ".php?tabla=" . $table . "'>
                <img src='../Images/add.png' /></a></td></tr></table>";
        $this->smarty->assign('tabla2', $tabla2);
        break;
      case 2: //se redirecciona a la misma página
        $tabla2 .= "<tr><td colspan='4' align='right'>
                <a href='" . $direccion . ".php?tabla=" . $table . "&accion=form_insert'>
                <img src='../Images/add.png' /></a></td></tr></table>";
        $this->smarty->assign('tabla2', $tabla2);
        break;
    }
    if ($datos == false) {
      $tabla = '<label>No se encuentran elementos </label>';
      return $tabla;
    }
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $this->query($query);
    $cantidadcolumnas  = $this->rs->_numOfFields;
    $cantidadregistros = $this->rs->_numOfRows;
    if ($this->rs->fields == false) {
      $tabla = 'No hay ningun dato';
      return $tabla;
    }
    $tabla           = "<table class='table table-striped'>";
    $nombrescolumnas = array_keys($this->rs->fields);
    $datos           = $this->DB->GetAll($query);
    $cont            = 0;
    $cont2           = 0;
    while ($cont < $cantidadregistros + 1) {
      $tabla .= '<tr>';
      while ($cont2 < $cantidadcolumnas) {
        if ($cont == 0) {
          $tabla .= '<th>' . $nombrescolumnas[$cont2] . '</th>';
          if ($cont2 == $cantidadcolumnas - 1 && $option == 1) {
            $tabla .= '<th>Eliminar</th>';
            $tabla .= '<th>Actualizar</th>';
          }
          if ($cont2 == $cantidadcolumnas - 1 && ($option == 2 || $option == 3)) {
            $tabla .= '<th>Eliminar</th>';
            $tabla .= '<th>Actualizar</th>';
            $tabla .= '<th>Mostrar</th>';
          }
        } else {
          if ($option != 4 && $option != 5) {
            $tabla .= '<td>';
            $nomField = $nombrescolumnas[$cont2];
            $tabla .= $datos[$cont - 1][$nomField];
            $tabla .= '</td>';
          } else {
            if ($cont2 == 0) {
              $nomField  = $nombrescolumnas[0];
              $contenido = $datos[$cont - 1][$nomField];
              if ($option == 4) {
                if ($direccion == 'grupo') {
                  $nomField3  = $nombrescolumnas[2];
                  $contenido3 = $datos[$cont - 1][$nomField3];
                  $extra      = '&info3=' . $contenido3;
                }
                $nomField2  = $nombrescolumnas[1];
                $contenido2 = $datos[$cont - 1][$nomField2];
                $tabla .= '<td> <a href="' . $direccion . '.php?info1=' . $contenido . '&info2=' . $contenido2 . '' . $extra . '">';
              }
              if ($option == 5) {
                if ($direccion == 'grupoHistorial') {
                  $contenido2 = $datos[$cont - 1]['cveusuario'];
                  $tabla .= '<td> <a href="' . $direccion . '.php?info=' . $contenido2 . '&info1=' . $contenido . $extra . '">';
                } else {
                  $tabla .= '<td> <a href="' . $direccion . '.php?info1=' . $contenido . $extra . '">';
                }
              }
              if ($contenido != "GHOST") {
                $nomField = $nombrescolumnas[$cont2];
                $tabla .= $datos[$cont - 1][$nomField];
              }
              $tabla .= '</a></td>';
            } else {
              $tabla .= '<td>';
              $nomField = $nombrescolumnas[$cont2];
              $tabla .= $datos[$cont - 1][$nomField];
              $tabla .= '</td>';
            }
          }
        }
        if ($cantidadcolumnas == $cont2 + 1 && $cont != 0 && $option == 1) {
          $nomField   = $nombrescolumnas[0];
          $nomField2  = $nombrescolumnas[1];
          $contenido  = $datos[$cont - 1][$nomField];
          $contenido2 = $datos[$cont - 1][$nomField2];
          $tabla .= "<td> <a href='" . $direccion . ".php?info1=" . $contenido . "&info3=" . $contenido2 . "'><img src='../Images/cancelar.png' /> </a></td>";
          if ($add == 2) {
            $tabla .= "<td> <a href='" . $direccion . ".php?accion=form_update&info2=" . $contenido . "&info3=" . $contenido2 . "'><img src='../Images/edit.png' /> </a></td>";
          } else {
            $tabla .= "<td> <a href='update" . $direccion . ".php?info2=" . $contenido . "&info3=" . $contenido2 . "'><img src='../Images/edit.png'/> </a></td>";
          }
        }
        if ($cantidadcolumnas == $cont2 + 1 && $cont != 0 && $option == 2) {
          $nomField  = $nombrescolumnas[0];
          $contenido = $datos[$cont - 1][$nomField];
          $tabla .= "<td> <a href='" . $direccion . ".php?info1=" . $contenido . "'><img src='../Images/cancelar.png' /> </a></td>";
          if ($add == 2) {
            $tabla .= "<td> <a href='" . $direccion . ".php?accion=form_update&info2=" . $contenido . "'><img src='../Images/edit.png' /> </a></td>";
          } else {
            $tabla .= "<td> <a href='update" . $direccion . ".php?info2=" . $contenido . "'><img src='../Images/edit.png' /> </a></td>";
          }
          $tabla .= "<td> <a href='" . $extra . ".php?info1=" . $contenido . "'><img src='../Images/mostrar.png' /> </a></td>"; //Se colo co el $extra epara redireccionar a grupos
        }
        if ($cantidadcolumnas == $cont2 + 1 && $cont != 0 && $option == 3) {
          $nomField  = $nombrescolumnas[0];
          $contenido = $datos[$cont - 1][$nomField];
          $tabla .= "<td> <a href='" . $direccion . ".php?accion=delete&info1=" . $contenido . "'><img src='../Images/cancelar.png' /> </a></td>";
          if ($add == 2) {
            $tabla .= "<td> <a href='" . $direccion . ".php?accion=form_update&info2=" . $contenido . "'><img src='../Images/edit.png' /> </a></td>";
          } else {
            $tabla .= "<td> <a href='" . $direccion . ".php?info2=" . $contenido . "'><img src='../Images/edit.png' /> </a></td>";
          }
          $tabla .= "<td> <a href='show" . $direccion . ".php?info1=" . $contenido . "'><img src='../Images/libros.png' /> </a></td>";
        }
        $cont2++;
      }
      $cont2 = 0;
      $cont++;
      $tabla .= '</tr>';
    }
    $tabla .= '</table>';
    return $tabla;
  }

  /*
   * MUESTRA LOS MENSAJES PUBLICOS
   * SOLO ES PRUEBA LO QUITARE Y LO MANDARE A index.php NIVEL PUBLICO
   */
  public function muestraMSJ($query, $tipo)
  {
    $datosmsj = $this->DB->GetAll($query);
    if (!isset($datosmsj[0])) {
      return false;
    }

    $nombrescolumnas = array_keys($datosmsj[0]);
    $this->smarty->assign('nombrecolumna', $nombrescolumnas[1]);
    $this->smarty->assign('datos', $datosmsj);
    return $this->smarty->fetch('msj.component.html');

    if ($tipo == 'PU') {
      $this->smarty->assign('nombrecolumna', $nombrescolumnas[1]);
      $this->smarty->assign('datos', $datosmsj);
      return $this->smarty->fetch('msj.component.html');
    }

    if ($tipo == 'PR') {

    }

    if ($tipo == 'G') {

    }
    $this->smarty->assign('nombrecolumna', $nombrescolumnas[1]);
  }

  /**
   * Inicializa variables necesarias para desplegar un template
   * Entre ellas la ruta a mostrar con links de la página
   *
   * @param  String $ubicacion Ubicación del template
   * @param  String $ruta      Estructura a poner links
   */
  public function iniClases($ubicacion, $ruta)
  {
    if (isset($_SESSION['bandera_roles'])) {
      $this->smarty->assign('bandera_roles', $_SESSION['bandera_roles']);
    }
    if ($ubicacion != null) {
      $nombre = $this->tipoCuenta();
      $this->smarty->assign('nombrecuenta', $nombre);
      $this->smarty->setTemplateDir('../templates/' . $ubicacion . '/');
    }

    $ruta = explode(" ", $ruta);
    $cad  = "<div>";
    for ($i = 0; $i < count($ruta); $i++) {
      if ($i < count($ruta) - 1) {
        $cad .= '<label><a href="' . $ruta[$i] . '.php">' . $ruta[$i] . '</a></label> > ';
      } else {
        $cad .= '<label>' . $ruta[$i] . '</label>';
      }
    }
    $cad .= "</div>";
    $this->smarty->assign('ruta', $cad);
  }

  public function smarty()
  {
    $this->smarty = new Smarty();
    $this->smarty->setTemplateDir(PATHAPP . TEMPLATES);
    $this->smarty->setCompileDir(PATHAPP . TEMPLATES_C);
    $this->smarty->setCacheDir(PATHAPP . CACHE);
    $this->smarty->setConfigDir(PATHAPP . CONFIGS);
    $this->smarty->debugging      = false;
    $this->smarty->caching        = true;
    $this->smarty->cache_lifetime = 0;
  }

  public function getAllRecords($query)
  {
    $this->query            = $query;
    $this->all_last_records = $this->DB->GetAll($this->query);
    if ($this->DB->ErrorMsg()) {
      die($this->DB->ErrorMsg());
    }
  }

  public function valida($correo)
  {
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
      return true;
    }
    return false;
  }

  /**
   * Verifica si un elemento es numérico
   * @param  [type] $email Elemento a checar
   * @return boolean       Resultado de la verificación
   */
  public function validarEmail($email)
  {
    return is_numeric($email);
  }

/**
 * Obtiene la información que será desplegada en el Nav Bar del promotor o del alumno
 * @param  String $rfc [ID del promotor o el alumno]
 * @return String      [Lista de grupos que tiene el usuario o mensaje de error]
 */
  public function grupos($rfc)
  {
    $rol     = $_SESSION['roles'];
    $periodo = $this->periodo();
    if ($periodo == "") {
      return "No existentes";
    }
    if ($rol == 'P') {
      //es un promotor
      $sql = "SELECT DISTINCT letra, nombre, ubicacion FROM laboral
        INNER JOIN sala ON laboral.cvesala = sala.cvesala
        INNER JOIN abecedario ON laboral.cveletra = abecedario.cve
        WHERE cvepromotor=? AND laboral.cveperiodo=? ORDER BY letra";
      $datos_rs = $this->DB->GetAll($sql, array($_SESSION['cveUser'], $periodo));
    } else {
      //es un alumno
      $sql = "SELECT letra, la.nombre, ubicacion
        FROM lectura le
        INNER JOIN abecedario abc ON abc.cve = le.cveletra
        INNER JOIN laboral la ON la.cveletra = abc.cve
        INNER JOIN sala s ON s.cvesala = la.cvesala
        WHERE la.cveperiodo=? AND le.cveperiodo=? AND nocontrol=?
        ORDER BY letra";
      $datos_rs = $this->DB->GetAll($sql, array($periodo, $periodo, $_SESSION['cveUser']));
    }

    if (sizeof($datos_rs) == 0) {
      return "No existentes";
    } else {
      $cadena = '<li><a href= "grupos.php">Ver todos</a></li>';
      for ($i = 0; $i < sizeof($datos_rs); $i++) {
        $cadena .= '<li><a href= "grupo.php?info1=' . $datos_rs[$i][0];
        $cadena .= '">' . $datos_rs[$i][0] . ' - ' . $datos_rs[$i][1] . ' - ' .
          $datos_rs[$i][2] . '</a></li>';
      }
      return $cadena;
    }
  }
  /**
   * Muestra la tabla necesaria para que el promotor califique a sus alumnos
   * @param  Array $infos     Arreglo con grupo, cveletra y horario
   * @param  String $display [Indica si un elemento HTML será mostrado u ocultado]
   * @param  Array $aux      [description]
   * @return [String]        [Código HTML con la estructura de la tabla]
   */
  //$aux Array contiene dos campos: promoaux y periodaux
  public function evaluacion($infos, $display = "block", $aux = "")
  {
    if (isset($aux['promoaux'])) {
      $cvep = $aux['promoaux'];
    } else if ($_SESSION['roles'] != 'A') {
      $cvep = $_SESSION['cveUser'];
    }
    $query = "SELECT DISTINCT cveeval AS \"ID\", nocontrol AS \"Número de Ctrl\", nombre AS \"Alumno\",
    comprension AS \"Comprensión\", motivacion AS \"Motivación\", reporte AS \"Reporte\", tema AS \"Tema\",
    participacion AS \"Participación\", terminado AS \"Terminado\" FROM evaluacion
    INNER JOIN usuarios ON usuarios.cveusuario = evaluacion.nocontrol
    WHERE cveletra IN (SELECT cve FROM abecedario WHERE letra=?)
    AND cvepromotor=?
    ORDER BY nombre";
    $this->DB->SetFetchMode(ADODB_FETCH_ASSOC);
    $this->query($query, array($infos['grupo'], $cvep));
    $cantidadcolumnas  = $this->rs->_numOfFields;
    $cantidadregistros = $this->rs->_numOfRows;
    $tabla             = "<table class='table table-striped' width='500'>";
    $datos             = $this->DB->GetAll($query);
    if (!isset($datos[0]["Alumno"])) {
      $tabla = "No hay alumnos inscritos";
      return $tabla;
    }
    $nombrescolumnas = array_keys($this->rs->fields);
    $datos           = $this->DB->GetAll($query);
    $cont            = 0;
    $cont2           = 0;
    while ($cont < $cantidadregistros + 1) {
      $tabla .= "<tr> <form class='form-inline' action='grupo.php' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";
      $tabla .= '
                <input type="hidden" name="datos[grupo]" value="' . $infos['grupo'] . '" >
                <input type="hidden" name="datos[cvesala]" value="' . $infos['cvesala'] . '">
                <input type="hidden" name="datos[horario]" value="' . $infos['horario'] . '">';
      while ($cont2 < $cantidadcolumnas) {
        if ($cont == 0) {
          $tabla .= ' <th width="500"> ';
          $tabla .= $nombrescolumnas[$cont2];
          $tabla .= '</th>';
          if ($cont2 == $cantidadcolumnas - 1 && $display != null) {
            $tabla .= '<th> Opciones </th>';
          }
        } else {
          $tabla .= "<td width='500'> ";
          $nomField = $nombrescolumnas[$cont2];
          if ($cont2 < 3) {
            if ($display == 'none') {
              $tabla .= "<a href='updatealumnos.php?info2=" . $datos[$cont - 1]['Número de Ctrl'] . "'>" . $datos[$cont - 1][$nomField] . "</a>";
            } else {
              $tabla .= $datos[$cont - 1][$nomField];
            }
          } else {
            $tabla .= ' <div id="myprogress" style="position: relative; width: 100%; height: 30px; background-color: #ddd ;"> <div id="mybar" style="position: absolute; width: ' . $datos[$cont - 1][$nomField] . '% ; height: 100%; background-color: #4caf50 ;"> <div id="label" style="text-align: center; line-height: 30px; color: white ;"> ' . $datos[$cont - 1][$nomField] . ' % ';
            // if ($display != null) {
            $tabla .= '</div></div></div></br><center><input class="form-control" name="datos[' . $nomField . ']"  required value="' . $datos[$cont - 1][$nomField] . '" style="width:65px; display:' . $display . '    ;"maxlength="3"></center>';
            // }
          }
          if ($cont2 == $cantidadcolumnas - 1) {
            $display2   = 'none';
            $valorBoton = "Cambiar";
            if ($display == 'none') {
              $display2   = 'block';
              $valorBoton = "Eliminar";
              $tabla .= ' <input type="hidden" name="datos[promotor]" value="' . $aux['promoaux'] . '">';
              $tabla .= ' <input type="hidden" name="datos[periodo]" value="' . $aux['periodaux'] . '">';
            }
            $tabla .= ' <th>
                        <input type="hidden" name="datos[cveeval]"
                            value="' . $datos[$cont - 1]['ID'] . '">
                        <button type="submit" class="btn btn-danger"
                            name="datos[accion]" value="eliminar"
                            style="display:' . $display2 . '" >
                            <span class="glyphicon glyphicon-remove"
                                aria-hidden= "true"></span></button></th>';
          }
          $tabla .= "</td>";
        }
        $cont2++;
      }
      $cont2 = 0;
      $cont++;
      $tabla .= '</form></tr>';
    }
    $tabla .= '</table>';
    return $tabla;
  }

  public function checklogin()
  {
    if ($_SESSION['logueado'] == true) {
      if ($_SESSION['roles'] == 'A') {
        header('Location: ../admin/index.php');
      }
      if ($_SESSION['roles'] == 'P') {
        header('Location: ../promotor/index.php');
      }
      if ($_SESSION['roles'] == 'U') {
        header('Location: ../alumno/index.php');
      }
    } else {
      header('Location: ../index.php');
    }
  }

  /**
   * Gestiona el proceso de logueo: validaciones y creación de variables de sesión
   * @param  String $email      Clave del usuario, no es el email
   * @param  String $contrasena Contraseña ingresada por el usuario
   * @return boolean            Representa si el logueo fue exitoso
   */
  public function login($email, $contrasena, $usuario_clave = null, $validar = null)
  {
    $msj        = '';
    $contrasena = md5($contrasena);

    $sql      = "SELECT * FROM usuarios WHERE pass=? AND cveusuario=?";
    $datos_rs = $this->DB->GetAll($sql, array($contrasena, $email));

    //falta verificar si la contraseña que esta insertando es la clave que se mando por correo

    if (!isset($datos_rs[0])) {
      return false;
    }

    $this->aceptacion = $datos_rs[0]['validacion'];
    if ($this->aceptacion == 'Rechazado' || $this->aceptacion == '') {
      return false;
    }

    $sql   = "SELECT * FROM usuario_rol WHERE cveusuario=?";
    $roles = $this->DB->GetAll($sql, $email);

    $nombre = $datos_rs[0]["nombre"];
    $sql    = "UPDATE usuarios SET clave=null WHERE cveusuario=?";
    $this->query($sql, $email);

    $_SESSION['nombre']  = $nombre;
    $_SESSION['cveUser'] = $email;

    //no tiene ningún rol
    if (sizeof($roles) == 0) {
      $this->simple_message('danger', 'Su usuario no está registrado por completo');
      $this->iniClases(null, 'index login roles');
      $this->smarty->display('roles.html');
      die();

    } else if (sizeof($roles) == 1) {
      //tiene 1 rol
      $_SESSION['logueado'] = true;

      if ($roles[0]['cverol'] == 3) {
        $_SESSION['roles'] = 'U';
        header('Location: alumno');
      }

      if ($roles[0]['cverol'] == 2) {
        $_SESSION['roles'] = 'P';
        header('Location: promotor');
      }

      if ($roles[0]['cverol'] == 1) {
        $_SESSION['roles'] = 'A';

        if ($usuario_clave != null && $validar != null) {
          header('Location: admin/validar.php?accion=' . $validar . '&clave=' . $usuario_clave);
        }
        header('Location: admin');
      }

    } else {
      //tiene mas de 1 rol

      if ($usuario_clave != null && $validar != null &&
        ($roles[0]['cverol'] == 1 || $roles[1]['cverol'] == 1)) {
        $_SESSION['roles'] = 'A';
        header('Location: admin/validar.php?accion=' . $validar . '&clave=' . $usuario_clave);
      }

      $this->iniClases(null, 'index login roles');
      $this->smarty->assign('roles', $roles);
      $this->smarty->display('roles.html');
      exit;
    }
    return true;
  }

  public function logout()
  {
    unset($_SESSION);
    session_destroy();
  }

  public function recuperaId($email)
  {
    if ($this->validarEmail($email)) {
      $this->query("SELECT id FROM usuario WHERE email=?", $email);
      while (!$this->rs->EOF) {
        $id = $this->rs->fields['id'];
        $this->rs->MoveNext();
      }
      return $id;
    } else {
      $this->error('erroralingresarelmail');
    }
  }

  public function generaContrasena()
  {
    $num1   = rand(1, 1000000);
    $num1   = md5($num1);
    $num2   = rand(1, 1000000);
    $num2   = sha1($num2);
    $strnum = $num1 . $num2;
    $con    = strtolower(md5($strnum));
    return substr($con, 0, 8);
  }

  /**
   * Envíar correo electrónico
   * @param  String $destino Correo destino
   * @param  String $nombre  Nombre del destinatario
   * @param  String $asunto  Asunto del correo
   * @param  String $mensaje Contenido del correo
   * @return ???             Errores
   */
  public function sendEmail($destino, $nombre, $asunto, $mensaje)
  {
    $mail = new PHPMailer();
    $mail->IsSMTP();

    try {
      $mail->SMTPDebug  = MAIL_SMTPDEBUG; // enables SMTP debug information (for testing)
      $mail->SMTPAuth   = MAIL_SMTPAUTH; // enable SMTP authentication
      $mail->SMTPSecure = MAIL_SMTPSECURE; // sets the prefix to the servier
      $mail->Host       = MAIL_HOST; // sets GMAIL as the SMTP server
      $mail->Port       = MAIL_PORT; // set the SMTP port for the GMAIL server
      $mail->Username   = MAIL_USERNAME; // GMAIL username
      $mail->Password   = MAIL_PASS; // GMAIL password

      $mail->AddAddress($destino, $nombre);
      $mail->SetFrom(MAIL_USERNAME, 'SalasLectura');
      $mail->Subject = $asunto;
      $mail->AltBody = $mensaje; // optional - MsgHTML will create an alternate automatically
      $mail->MsgHTML($mensaje);

      $mail->Send();
      $this->smarty->display('templates/admin/index.html');
      echo "<center><h3>Revisa tu correo electronico</h3></center>";

    } catch (phpmailerException $e) {
      echo $e->errorMessage(); //Pretty error messages from PHPMailer
    } catch (Exception $e) {
      echo $e->getMessage(); //Boring error messages from anything else!
    }
  }

  public function comboP($query, $name)
  {
    $this->query($query);
    $campos = $this->DB->GetAll($query);
    $this->smarty->assign('name', $name);
    $this->smarty->assign('campos', $campos);
    return $this->smarty->fetch('agregaProducto . html');
  }

  public function comboM($query, $name)
  {
    $this->query($query);
    $campos = $this->DB->GetAll($query);
    $this->smarty->assign('name', $name);
    $this->smarty->assign('campos', $campos);
    return $this->smarty->fetch('agregaMarca . html');
  }

  public function comboC($query, $name)
  {
    $this->query($query);
    $campos = $this->DB->GetAll($query);
    $this->smarty->assign('name', $name);
    $this->smarty->assign('campos', $campos);
    return $this->smarty->fetch('agregaCliente . html');
  }

  public function validarContrasena($contrasena)
  {
    if (preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", $contrasena)) {
      return (true);
    } else {
      return (false);
    }
  }

  /**
   * [periodo description]
   * @param  [type] $web [description]
   * @return [type]      [description]
   */
  public function periodo()
  {
    $sql      = "SELECT * FROM periodo";
    $datos_rs = $this->DB->GetAll($sql);
    $date     = getdate();
    $fechaAct = $date['year'] . "-" . $date['mon'] . "-" . $date['mday'];
    $date1    = new DateTime($fechaAct);
    $cont     = 0;
    while ($cont < count($datos_rs)) {
      $date2 = new DateTime($datos_rs[$cont]['fechainicio']);
      $date3 = new DateTime($datos_rs[$cont]['fechafinal']);
      if ($date1 >= $date2 && $date1 <= $date3) {
        $cveperiodo = $datos_rs[$cont]['cveperiodo'];
      }
      $cont++;
    }
    if (isset($cveperiodo)) {
      $sql     = "SELECT fechainicio, fechafinal FROM periodo WHERE cveperiodo=?";
      $datos   = $this->DB->GetAll($sql, $cveperiodo);
      $periodo = "El periodo es: " . $datos[0]['fechainicio'] . " a " . $datos[0]['fechafinal'];
      $this->smarty->assign('periodo', $periodo);
      return $cveperiodo;
    } else {
      $this->smarty->assign('periodo', "No hay periodos actuales");
      return "";
    }
  }

  /**
   * Principalmente usado en la sección de administrador
   * Valida la contraseña cuando se va a eliminar algún elemento
   * @param  String $cveusuario Clave del usuario administrador
   * @return int    1 y 2 = mensaje de error, 3 = exito en la operación
   */
  public function valida_pass($cveusuario)
  {
    //verifica que se mande la contraseña
    if (!isset($_GET['infoc'])) {
      return 1;
    }

    //se valida la contraseña
    $pass     = md5($_GET['infoc']);
    $sql      = "SELECT * FROM usuarios WHERE cveusuario=? AND pass=?";
    $datos_rs = $this->DB->GetAll($sql, array($cveusuario, $pass));
    if (!isset($datos_rs[0])) {
      return 2;
    }
    return 3;
  }

  /**
   * Versión simple de la función message, asigna los elementos necesarios para mostrar mensajes
   * de error
   * @param  String $alert warning | danger principalmente
   * @param  String $msg   Mensaje de error a mostrar
   * @param  Class  $web   Objeto para poder hacer uso de smarty
   */
  public function simple_message($alert, $msg)
  {
    $this->smarty->assign('alert', $alert);
    $this->smarty->assign('msg', $msg);
  }

  /**
   * Para pruebas
   * @param  array $array Arreglo a mostrar
   * @return Contenido del arreglo
   */
  public function debug($array, $opt = true)
  {
    echo "<pre>";
    print_r($array);
    if ($opt) {
      die();
    }
  }

  public function debug_line($dato, $opt = true)
  {
    echo $dato . "<br>";
    if ($opt) {
      die();
    }
  }

  /**
   * get all assigned template vars
   * @return variables asignadas con smarty->assing y sus contenidos
   */
  public function getSmartyAssigns()
  {
    $pageName = $this->smarty->getTemplateVars();
    $this->debug($pageName);
  }

  public function authentication()
  {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
      header('WWW-Authenticate: Basic realm="Mi dominio"');
      header('HTTP/1.0 401 Unauthorized');
      echo 'Se a cancelado la autenticacion';
      exit;
    } else {
      if ($_SERVER['PHP_AUTH_USER'] == 'root' && $_SERVER['PHP_AUTH_PW'] == 'root') {
        return true;
      } else {
        echo 'Autenticacion no valida';
        return false;
      }
    }
    header('Content-Type: aplication/json');
  }

  public function guarda_foto($foto)
  {
    $encoded = $foto;
    $encoded = str_replace(' ', '+', $encoded);
    $encoded = str_replace('data:image/jpeg;base64,', '', $encoded);
    $image   = base64_decode($encoded);
    $sql     = "update usuarios set foto = ? where cveusuario = ?";
    $this->query($sql, array($_SESSION['cveUser'] . ".jpg", $_SESSION['cveUser']));
    file_put_contents("/home/slslctr/fotos/" . $_SESSION['cveUser'] . ".jpg", $image);
  }

  public function status($status, $mensaje)
  {
    $message['status']  = $status;
    $message['message'] = $mensaje;
    $message            = json_encode($message);
    http_response_code(200);
    echo $message;
    die();
  }

/*************************************************************************************************
 * QUERIES BASE DE DATOS
 *************************************************************************************************/
  public function getMessages($cvemsj)
  {
    $sql = "SELECT * FROM msj WHERE cvemsj=?";
    return $this->DB->GetAll($sql, $cvemsj);
  }

  /**
   *
   */
  public function getAll($arrColumns, $arrWhere = null, $table, $arrOrder = null)
  {
    $sql = "SELECT " . $this->setColumns($arrColumns) . " FROM " . $table;
    if (!is_null($arrWhere)) {
      $whColumns = $this->getFields($arrWhere);
      $sql .= " WHERE " . $this->setWhereColumns($whColumns);
    }
    if (!is_null($arrOrder)) {
      $sql .= " ORDER BY " . $this->setOrderColumns($arrOrder);
    }

    // if($table == 'horario') {
    //   $this->debug_line($sql, false);
    //   $this->debug($this->getParameters($arrWhere, $whColumns), false);
    // }

    return $this->DB->GetAll($sql, $this->getParameters($arrWhere, $whColumns));
  }

  public function setColumns($arrColumns)
  {
    $sql = "";
    for ($i = 0; $i < count($arrColumns); $i++) {
      $sql .= $arrColumns[$i];
      if ($i != count($arrColumns) - 1) {
        $sql .= ",";
      }
    }
    return $sql;
  }

  public function getFields($arrColumns)
  {
    return array_keys($arrColumns);
  }

  public function setWhereColumns($whColumns, $type = 1)
  {
    $sql = "";
    for ($i = 0; $i < count($whColumns); $i++) {

      $sql .= ($type == 1) ? $whColumns[$i] . "=?" : (($type == 2) ? $whColumns[$i] : "?");
      if ($i != count($whColumns) - 1) {
        $sql .= ($type == 1) ? " AND " : ", ";
      }
    }
    return $sql;
  }

  public function setOrderColumns($arrOrder)
  {
    $sql = "";
    for ($i = 0; $i < count($arrOrder); $i++) {
      $sql .= $arrOrder[$i];
      if ($i != count($arrOrder) - 1) {
        $sql .= ", ";
      }
    }
    return $sql;
  }

  public function getParameters($arrColumns, $arrKeys = null)
  {
    $tmp = array();
    if (is_null($arrKeys)) {
      return $tmp;
    }
    for ($i = 0; $i < count($arrColumns); $i++) {
      array_push($tmp, $arrColumns[$arrKeys[$i]]);
    }
    return $tmp;
  }

  /**
   *
   */
  public function update($arrColumns, $arrWhere, $table)
  {
    $columns   = $this->getFields($arrColumns);
    $whColumns = $this->getFields($arrWhere);
    $sql       = "UPDATE " . $table . " SET " . $this->setWhereColumns($columns);
    if (!is_null($arrWhere)) {
      $sql .= " WHERE " . $this->setWhereColumns($whColumns);
    }

    $cntColumns   = $this->getContent($arrColumns, $columns);
    $cntWhColumns = $this->getContent($arrWhere, $whColumns);

    // $this->debug($this->getQueryArray($cntColumns, $cntWhColumns), false);
    // $this->debug($sql);

    return $this->query($sql, $this->getQueryArray($cntColumns, $cntWhColumns));
  }

  public function getContent($arrData, $arrKey)
  {
    $tmp = array();
    for ($i = 0; $i < count($arrData); $i++) {
      array_push($tmp, $arrData[$arrKey[$i]]);
    }
    return $tmp;
  }

  public function getQueryArray($arrColumns, $whColumns = array())
  {
    $tmp = array();
    $j   = 0;

    for ($i = 0; $i < (count($arrColumns) + count($whColumns)); $i++) {
      if ($i < count($arrColumns)) {
        array_push($tmp, $arrColumns[$i]);
      } else {
        array_push($tmp, $whColumns[$j]);
        ++$j;
      }
    }
    return $tmp;
  }

  /**
   *
   */
  public function delete($table, $arrWhere = null)
  {
    $sql          = "DELETE FROM " . $table;
    $cntWhColumns = array();

    if (!is_null($arrWhere)) {
      $whColumns = $this->getFields($arrWhere);
      $sql .= " WHERE " . $this->setWhereColumns($whColumns);
      $cntWhColumns = $this->getContent($arrWhere, $whColumns);
    }

    // $this->debug($sql, false);
    // $this->debug($cntWhColumns, false);

    return $this->query($sql, $cntWhColumns);
  }

  /**
   *
   */
  public function insert($table, $arrValues)
  {
    $sql = "INSERT INTO " . $table;

    $heaColumns = $this->getFields($arrValues);
    $cntColumns = $this->getContent($arrValues, $heaColumns);

    $sql .= "(" . $this->setColumns($heaColumns) . ") VALUES(" . $this->setWhereColumns($cntColumns, 3) . ");";

    // $this->debug($sql, false);
    // $this->debug($cntColumns, false);

    return $this->query($sql, $cntColumns);
  }

  /**
   *
   */
  public function dbFunction($arrS, $function, $arrC)
  {
    $cntColumns = array();
    if ($arrS == '*') {
      $sql = "SELECT * FROM " . $function . "(" . $this->setColumns($arrC) . ");";
    } else {
      $heaColumns = $this->getFields($arrC);
      $cntColumns = $this->getContent($arrC, $heaColumns);
      $sql        = "SELECT " . $this->setColumns($arrS) . " FROM " . $function . "(" . $this->setWhereColumns($cntColumns, 3) . ");";
    }
    return $this->query($sql, $cntColumns);
  }
}

include 'controllers/ForoControllers.php';
include 'controllers/admin/LibrosControllers.php';
include 'controllers/admin/ReporteControllers.php';
include 'controllers/admin/PeriodosControllers.php';
include 'controllers/admin/PromotorControllers.php';
include 'controllers/admin/AdminGruposControllers.php';
include 'controllers/admin/AdminGrupoControllers.php';
include 'controllers/alumno/InscripcionControllers.php';
include 'controllers/alumno/GruposControllers.php';
include 'controllers/alumno/MsjControllers.php';
include 'controllers/alumno/AlumnoDatosControllers.php';
include 'controllers/promotor/ListAsiControllers.php';
include 'controllers/promotor/RedactaControllers.php';
include 'controllers/promotor/PromoSalaControllers.php';
include 'controllers/promotor/GrupoControllers.php';
include 'controllers/promotor/PromoGruposControllers.php';
include 'controllers/promotor/PromoLibrosControllers.php';

//instanciamos web
$web = new Sistema;
$web->smarty();
