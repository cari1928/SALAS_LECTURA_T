$(document).ready(function() {
  $('#example').DataTable( {
    "ajax": "../promotor/TextFiles/libros.txt",
    "columns": [
      { "data": "autor" },
      { "data": "titulo" },
      { "data": "editorial" },
      { "data": "opciones" }
    ],
    "columnDefs": [ {
      "className": "dt-center", 
			"targets": "_all"
		}]
  });
});