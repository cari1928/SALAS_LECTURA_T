<?php
include '../sistema.php';
$web->smarty->assign('usuario', $_SESSION['nombre']);
$grupos = $web->grupos($_SESSION['cveUser']);
$web->smarty->assign('grupos', $grupos);

if ($_SESSION['roles'] == 'U') {
  $web->iniClases('usuario', "index datos");

  $rol = $_SESSION['roles'];
  if ($rol == "A") {
    $rol = 'Administrador';
  }
  if ($rol == "P") {
    $rol = 'Promotor';
  }
  if ($rol == "U") {
    $rol = 'Alumno';
  }

  if (!isset($_POST['datos'])) {
    $nombre  = $_SESSION['nombre'];
    $cveUser = $_SESSION['cveUser'];

    $sql      = "select correo from usuarios where cveusuario = '" . $cveUser . "'";
    $datos_rs = $web->DB->GetAll($sql);
    $corte    = explode("@", $datos_rs[0]['correo']);
    $coorreo  = $corte[0];
    contenidos($nombre, $cveUser, $rol, "", $coorreo, $web);
  } else {
    $coorreo         = $_POST['datos']['correo'] . "@itcelaya.edu.mx";
    $nombre          = $_POST['datos']['nombre'];
    $nomEspecialidad = $_POST['datos']['especialidad'];
    $cveUser         = $_SESSION['cveUser'];

    if ($_POST['datos']['pass'] == 'true') {

      if ($_POST['datos']['contrasena'] != "" && $_POST['datos']['contrasenaN'] != "" && $_POST['datos']['confcontrasenaN'] != "") {

        $cont     = $_POST['datos']['contrasena'];
        $cont     = md5($cont);
        $sql      = "select pass from usuarios where cveusuario='" . $cveUser . "'";
        $datos_rs = $web->DB->GetAll($sql);
        if ($datos_rs[0]['pass'] == $cont) {

          if ($_POST['datos']['contrasenaN'] == $_POST['datos']['confcontrasenaN']) {
            //  echo "aqui toy";
            if ($web->valida($coorreo)) {
              $sql      = "select correo from usuarios where correo='" . $coorreo . "' and cveusuario not in(select cveusuario from usuarios where cveusuario='" . $_SESSION['cveUser'] . "')";
              $datos_rs = $web->DB->GetAll($sql);
              if ($datos_rs == null) {
                $sql = "select cveespecialidad from especialidad where nombre='" . $nomEspecialidad . "'";

                $datos_rs        = $web->DB->GetAll($sql);
                $cveEspecialidad = $datos_rs[0]['cveespecialidad'];
                $sql             = "update usuarios set nombre='" . $nombre . "', cveespecialidad='" . $cveEspecialidad . "',
								pass=md5('" . $_POST['datos']['contrasenaN'] . "'),correo = '" . $coorreo . "' where cveusuario='" . $_SESSION['cveUser'] . "'";
                $web->query($sql);
                $_SESSION['nombre'] = $nombre;
                contenidos($nombre, $cveUser, $rol, "", $_POST['datos']['correo'], $web);
              } else {
                contenidos($nombre, $cveUser, $rol, "", $_POST['datos']['correo'], $web, "El correo ya existe");
              }
            } else {
              contenidos($nombre, $cveUser, $rol, "", $_POST['datos']['correo'], $web, "Correo invalido");
            }
          } else {
            contenidos($nombre, $cveUser, $rol, "No coinciden las nuevas contraseñas", $_POST['datos']['correo'], $web);
          }
        } else {
          contenidos($nombre, $cveUser, $rol, "No coincide la contraseña original", $_POST['datos']['correo'], $web);
        }
      }
    } else {
      if ($web->valida($coorreo)) {
        $sql                = "select correo from usuarios where correo='" . $coorreo . "' and cveusuario not in(select cveusuario from usuarios where cveusuario='" . $_SESSION['cveUser'] . "')";
        $datos_rs           = $web->DB->GetAll($sql);
        $_SESSION['nombre'] = $nombre;
        if ($datos_rs == null) {
          $sql             = "select cveespecialidad from especialidad where nombre='" . $nomEspecialidad . "'";
          $datos_rs        = $web->DB->GetAll($sql);
          $cveEspecialidad = $datos_rs[0]['cveespecialidad'];
          $sql             = "update usuarios set nombre='" . $nombre . "', cveespecialidad='" . $cveEspecialidad . "',correo = '" . $coorreo . "'
					where cveusuario='" . $_SESSION['cveUser'] . "'";
          $web->query($sql);
          contenidos($nombre, $cveUser, $rol, "", $_POST['datos']['correo'], $web);
        } else {
          contenidos($nombre, $cveUser, $rol, "", $_POST['datos']['correo'], $web, "El correo ya existe");
        }
      } else {
        contenidos($nombre, $cveUser, $rol, "", $_POST['datos']['correo'], $web, "Correo invalido");
      }

    }
  }

} else {
  $web->checklogin();
}

//metodos
function contenidos($nombre, $cveUser, $rol, $msgpass, $correo, $web, $msgcorreo = "")
{
  $nombre1 = $web->tipoCuenta();
  $web->smarty->assign('nombrecuenta', $nombre1);
  $web->smarty->assign('usuario', $_SESSION['nombre']);
  $web->smarty->assign('encabezado', '<h3>¡Bienvenido! <br>' . $_SESSION['cveUser'] . '-' . $_SESSION['nombre'] . '<br/></h3>');
  $web->smarty->assign('nombre', $nombre);
  $web->smarty->assign('correoEx', $correo);
  $web->smarty->assign('correo', $msgcorreo);
  $web->smarty->assign('cveUser', $cveUser);
  $web->smarty->assign('rol', "Tipo de cuenta:  " . $rol);
  $web->smarty->assign('combo', combo($cveUser, $web));
  $web->smarty->assign('contrasena', '');
  $web->smarty->assign('contrasena', '<label style= "color:red">' . $msgpass . '</label>');
  $web->smarty->display('datos.html');
}

function combo($cveUser, $web)
{
  $sql = "select especialidad.cveespecialidad, especialidad.nombre, pass
	from usuarios inner join especialidad on usuarios.cveespecialidad = especialidad.cveespecialidad
	where  cveusuario='" . $cveUser . "'";

  $datos_rs        = $web->DB->GetAll($sql);
  $cveEspecialidad = $datos_rs[0]["cveespecialidad"];
  $nomEspecialidad = $datos_rs[0]["nombre"];
  $sql             = "select * from especialidad ";
  $datos_rs        = $web->DB->GetAll($sql);
  $combito         = '<select name="datos[especialidad]" class="form-control" id="exampleInputEmail3" id="producto">';

  for ($i = 0; $i < count($datos_rs); $i++) {
    if ($datos_rs[$i][1] == $nomEspecialidad) {
      $combito .= '<option selected>' . $datos_rs[$i][1] . '</option>';
    } else {
      $combito .= '<option>' . $datos_rs[$i][1] . '</option>';
    }
  }
  $combito .= '</select>';
  return $combito;
}
