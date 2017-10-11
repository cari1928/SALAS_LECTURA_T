var bandera = 0;

function mostrar()
{
  var test = document.getElementsByName('datos[pass]');
  if (test[0].checked == true)
  {
    document.getElementById('oculto').style.display = "block";
    document.getElementById('r2').style.display = "inline";
    document.getElementById('l2').style.display = "inline";
    document.getElementById('r1').style.display = "none";
    document.getElementById('l1').style.display = "none";
  }
  if (test[1].checked == true)
  {
    document.getElementById('oculto').style.display = "none";
    document.getElementById('r2').style.display = "none";
    document.getElementById('l2').style.display = "none";
    document.getElementById('r1').style.display = "inline";
    document.getElementById('l1').style.display = "inline";
  }
}

function otro()
{
  var test = document.getElementsByName('datos[especialidad]');
  if (test[0].checked == false)
  {
    document.getElementById('carrera').style.display = "none";
    document.getElementById('especialidad').style.display = "block";
    document.getElementById('r3').style.display = "none";
    document.getElementById('l3').style.display = "none";
    document.getElementById('r4').style.display = "inline";
    document.getElementById('l4').style.display = "inline";
  }
  else
  {
    document.getElementById('carrera').style.display = "block";
    document.getElementById('especialidad').style.display = "none";
    document.getElementById('r3').style.display = "inline";
    document.getElementById('l3').style.display = "inline";
    document.getElementById('r4').style.display = "none";
    document.getElementById('l4').style.display = "none";
  }
}
