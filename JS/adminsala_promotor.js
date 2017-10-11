$(document).ready(function()
{
  $('#example').DataTable(
  {
    "ajax": "../admin/TextFiles/promodisponibles.txt",
    "columns": [
    {
      "data": "cveusuario"
    },
    {
      "data": "nombre"
    },
    {
      "data": "seleccion"
    }],
    "columnDefs": [
    {
      "className": "dt-center",
      "targets": "_all"
    }]
  });
});
