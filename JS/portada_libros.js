function show() {
  var test = document.getElementsByName('onoffswitch');
  if (test[0].checked == true) {
    document.getElementById('imgPortada').style.display = "none";
    document.getElementById('imgDefault').style.display = "block";
  }
  if (test[0].checked == false) {
    document.getElementById('imgPortada').style.display = "block";
    document.getElementById('imgDefault').style.display = "none";
  }
}