<?php

class Libros extends Sistema
{
//---------------------------------------------------------------------------------
    // function getAll() { //2016-09-29 getAllLibros -> getAll
    //regresa arreglo indexado, números y títulos
    public function getAll($query)
    {
        //2016-10-04
        $libros = array();
        foreach ($this->conn->query($query) as $fila) {
            array_push($libros, $fila);
        }
        return $libros;
    }
//--------------------------------------------------------------------------------
    //regresa arreglo asociativo, solo títulos
    public function fetchAll($query)
    {
        //2016-09-29 fetchAllLibros -> fetchAll
        $statement = $this->conn->Prepare($query);
        $statement->Execute();
        $libros = $statement->FetchAll(PDO::FETCH_ASSOC);
        return $libros;
    }
//---------------------------------------------------------------------------------
    public function getLibro($cvelibro)
    {
        $libros = array();
        if (is_numeric($cvelibro)) {
            $statement = $this->conn->Prepare('select * from libro where cvelibro=' . $cvelibro);
            $statement->Execute();
            $libros = $statement->FetchAll(PDO::FETCH_ASSOC);
        }
        return $libros;
    }
//---------------------------------------------------------------------------------
    public function deleteLibro($cvelibro)
    {
        //2016-09-29
        // $count = $this->conn->exec("DELETE FROM libro WHERE cvelibro=".$cvelibro);
        //Esto previene inyección SQL!!!
        // $sql  = "DELETE FROM libro WHERE cvelibro= :cvelibro";
        // $stmt = $this->conn->Prepare($sql);
        // $stmt->bindParam(':cvelibro', $cvelibro, PDO::PARAM_INT);
        // $stmt->execute();

        $sql = "delete from libro where cvelibro=" . $cvelibro;
        $web->query($sql);
    }
}
