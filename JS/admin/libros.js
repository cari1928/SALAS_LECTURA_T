$(document).ready(function() {
	var table = $('#example').DataTable( {
    "ajax": "TextFiles/libros.txt",
    "columnDefs": [ {
        "targets": -2,
        "data": null,
        "defaultContent": "<center><a href='#' class='delete'><img src='../Images/cancelar.png'></a></center>"
    } ]
  } );
  
  $('#example tbody').on( 'click', 'a.delete', function () {
    event.preventDefault();
    
    var data = table.row( $(this).parents('tr') ).data();
		var url = data[5];
		
    swal({
      title: "¿Seguro? Se borrará toda la información relacionada", 
      text: "Si está seguro ingrese la contraseña de seguridad", 
      type: "input",
      inputType: "password",
      showCancelButton: true,
      confirmButtonText: "Aceptar",
      cancelButtonText: "Cancelar"
    }, function(typedPassword) {
    	// console.log(typedPassword);
    	if(typedPassword != "") {
    		url += "&infoc=" + typedPassword;
      	window.open(url, '_self');	
      	return false;
    	}
    });
  } );
} );