var bandera=0;
      function mostrar()
      {
        var test = document.getElementsByName('pass');
         if(test[0].checked == true)
          {
            document.getElementById('oculto').style.display="block";
          }
          if(test[1].checked == true)
          {
            document.getElementById('oculto').style.display="none";
          }
        
      }
    