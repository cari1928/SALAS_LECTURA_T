<?php

class AlumnoDatosControllers extends Sistema
{
  public function __construct()
  {
    parent::__construct();
    $this->smarty->setCompileDir('../templates_c'); //para que no aparezca la carpeta alumno/templates_c
  }

  public function getUsuario($cveusuario, $cverol)
  {
    $sql = "SELECT u.cveusuario, u.nombre AS \"nombreUsuario\", e.nombre, eu.cveespecialidad, eu.otro, u.correo
      FROM usuarios u
      INNER JOIN especialidad_usuario eu ON eu.cveusuario = u.cveusuario
      INNER JOIN especialidad e ON e.cveespecialidad = eu.cveespecialidad
      INNER JOIN usuario_rol ur ON ur.cveusuario = u.cveusuario
      WHERE u.cveusuario=? AND ur.cverol=?";
    return $this->DB->GetAll($sql, array($cveusuario, $cverol));
  }

  public function updateUsuariosPass($arrData)
  {
    $sql = "UPDATE usuarios SET nombre=?, correo=? , pass=? WHERE cveusuario=?";
    return $this->query($sql, $arrData);
  }

  public function updateUsuarios($arrData)
  {
    $sql = "UPDATE usuarios SET nombre=?, correo=? WHERE cveusuario=?";

    // $this->debug($sql, false);
    // $this->debug(array($arrData));

    return $this->query($sql, $arrData);
  }

  public function updateEspUsuario($cveespecialidad, $otro, $cveusuario)
  {
    $sql = "UPDATE especialidad_usuario SET cveespecialidad=?, otro=? WHERE cveusuario=?";
    return $this->query($sql, array($cveespecialidad, $cveusuario));
  }
}
