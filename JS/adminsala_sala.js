$(document).ready(function()
{
  $('#example').DataTable(
  {
    "ajax": "../admin/TextFiles/promosala.txt",
    "columns": [
    {
      "data": "cvesala"
    },
    {
      "data": "ubicacion"
    }],
    "columnDefs": [
    {
      "className": "dt-center",
      "targets": "_all"
    }]
  });
});
