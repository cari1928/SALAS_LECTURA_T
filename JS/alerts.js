$(document).ready(function(){
  $("a.delete").click(function(){
		
		event.preventDefault();
		var url = $(this).attr('href');
		
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
    		url += "&infoc="+typedPassword;
    		console.log(url);
      	window.open(url, '_self');	
    	}
      return false;
    });
	});
});