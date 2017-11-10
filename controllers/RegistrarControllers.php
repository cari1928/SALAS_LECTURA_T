<?php

class RegistrarControllers extends Sistema
{
  /**
   * Inserta en usuarios, usuario_rol y especialidad_usuario 
   */
  public function insertUser($arrUsuarios, $arrUsuarioRol, $arrEspUsuario) {
    if(!$this->insert('usuarios', $arrUsuarios)) {
      return 0;
    }
    if(!$this->insert('usuario_rol', $arrUsuarioRol)) {
      return 1;
    }
    if(!$this->insert('especialidad_usuario', $arrEspUsuario)) {
      return 2;
    }
    return 3;
  }
  
  /**
   * 
   */
  public function getCorreos() {
    $sql     = "SELECT correo, nombre FROM usuarios 
      WHERE cveusuario in (SELECT cveusuario FROM usuario_rol WHERE cverol=1)";
    return $this->DB->GetAll($sql);
  }
  
}
