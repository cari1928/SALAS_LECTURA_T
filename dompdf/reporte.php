<?php
//require the Composer autoloader
require '../lib/dompdf/vendor/autoload.php';
use Dompdf\Dompdf;

$table = '
<!DOCTYPE html>
<html>
<head>
    <title>Reporte</title> 
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <!--BOOTSTRAP-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    
    <style type="text/css">
        *
        {
            padding-bottom: 5px;
        }
    
        div#cap 
        {
            /*background-color: green;*/
            text-align: left;
            padding: 10px;
        }
        
        /*div#cap h1 */
        /*{*/
        /*    float: right;*/
        /*    margin-right: 150px;*/
        /*}*/
        
        .usuario
        {
            float: right;
        }
        
        .grupo
        {
            text-align: left;
        }
        
        .table_container
        {
            margin-top: 10px;
        }
        
        .table_header
        {
          font-size: 15px;
          font-weight: bold;
        }
    </style>
</head>
<body>

<div id="cap">
    <center>
      <h1>Salas de Lectura</h1>
      <span>Frase aquí</span>
    </center>
</div>

<!--<div class="logoHeader">-->
<!--    <div class="info" align="left">-->
<!--        <b>Instituto Tecnológico de Celaya</b><br>-->
<!--        <b>Telefono:</b><br>-->
<!--        <b>Correo:</b>-->
<!--    </div>-->
<!--</div>-->

<div class="subHeader">
    <!--<div class="usuario">-->
    <!--	<b>Promotor: </b>Promotor 1<br>-->
    <!--	<b>RFC: </b>777777777777<br>-->
    <!--	<b>Especialidad: </b>Centro de Información<br>-->
    <!--	<b>Correo: </b>7777777777777@hotmail.com-->
    <!--</div>-->
    
    <div class="grupo">
    	<b>Promotor: </b>Promotor 1<br>
    	<b>RFC: </b>777777777777<br>
    	<b>Especialidad: </b>Centro de Información<br>
    	<b>Correo: </b>7777777777777@hotmail.com
    </div>
</div>

<div class="container">
  <center>
      <label style="font-size: 16px">Periodo 43232 : 4324312</label><br>
      <!--<label style="font-size: 16px">Listado de ...</label>-->
  </center>
</div>

<!--specific information-->
<div class="table table-sp-info">
  <table class="table table-bordered">
    
    <tr>
      <td>#</td>
      <td>NOCONTROL</td>
      <td>NOMBRE</td>
      <td>ESPECIALIDAD</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>#</td>
      <td>FALTAS</td>
    </tr>
    
    <tr>
      <td>1</td>
      <td>12345678</td>
      <td>NOMBRE APELLIDO1 APELLIDO2</td>
      <td>Sistemas</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
      <td>0</td>
    </tr>
    
  </table>
</div>
 
</body>
</html>
';

$ejemplo_footer = "
<html>
<head>
  <style>
    @page { margin: 100px 25px; }
    header { position: fixed; top: -60px; left: 0px; right: 0px; background-color: lightblue; height: 50px; }
    footer { position: fixed; bottom: -60px; left: 0px; right: 0px; background-color: lightblue; height: 50px; }
    p { page-break-after: always; }
    p:last-child { page-break-after: never; }
  </style>
</head>
<body>
  <header>header on each page</header>
  <footer>footer on each page</footer>
  <main>
    <p>page1</p>
    <p>page2></p>
  </main>
</body>
</html>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($table);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('document.pdf', array('Attachment' => 0));
