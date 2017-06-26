<?php
include "sistema.php";

if (isset($_GET['user'])) {
  $web->smarty->assign('user', $_GET['user']);
  $web->smarty->display('cambiacontrasena.html');
  die();
}

if (isset($_POST['datos'])) {
  $sql = "update usuarios set pass=md5('" . $_POST['datos']['contrasena'] . "') where cveusuario = '" . $_POST['datos']['cveUsuario'] . "'";
  $web->query($sql);

  $sql    = "select*from usuarios where cveusuario = '" . $_POST['datos']['cveUsuario'] . "'";
  $datos  = $web->DB->GetAll($sql);
  $rol    = $datos[0]['rol'];
  $nombre = $datos[0]['nombre'];
  $email  = $datos[0]['cveusuario'];
  $sql    = "update usuarios set clave = null where cveusuario='" . $email . "'";
  $web->query($sql);
  if ($rol == "U") {
    // Crear las variables de sesión
    $_SESSION['nombre']   = $nombre;
    $_SESSION['cveUser']  = $email;
    $_SESSION['logueado'] = true;
    $_SESSION['roles']    = $rol;
    header('Location: alumno');
  }
  if ($rol == "P") {
    // Crear las variables de sesión
    $_SESSION['nombre']   = $nombre;
    $_SESSION['cveUser']  = $email;
    $_SESSION['logueado'] = true;
    $_SESSION['roles']    = $rol;
    header('Location: promotor');
  }
  if ($rol == "A") {
    // Crear las variables de sesión
    $_SESSION['nombre']   = $nombre;
    $_SESSION['cveUser']  = $email;
    $_SESSION['logueado'] = true;
    $_SESSION['roles']    = $rol;
    header('Location: admin');
  }

}

header('Location: index.php');
