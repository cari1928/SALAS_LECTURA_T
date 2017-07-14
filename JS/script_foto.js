 window.URL = window.URL || window.webkitURL;
 navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia || function() {
   alert('Su navegador no soporta navigator.getUserMedia().');
 };
 jQuery(document).ready(function() {
   window.datosVideo = {
     'StreamVideo': null,
     'url': null
   };
   jQuery('#botonIniciar').on('click', function(e) {
     //Pedimos al navegador que nos de acceso a
     //algún dispositivo de video (la webcam)
     navigator.getUserMedia({
       'audio': false,
       'video': true
     }, function(streamVideo) {
       datosVideo.StreamVideo = streamVideo;
       datosVideo.url = window.URL.createObjectURL(streamVideo);
       jQuery('#camara').attr('src', datosVideo.url);
     }, function() {
       alert('No fue posible obtener acceso a la cámara.');
     });
   });
   jQuery('#botonDetener').on('click', function(e) {
     if (datosVideo.StreamVideo) {
       datosVideo.StreamVideo.stop();
       window.URL.revokeObjectURL(datosVideo.url);
     };
   });
   jQuery('#botonFoto').on('click', function(e) {
     //alert('Tomando foto');
     var oCamara,
       oFoto,
       oContexto,
       w, h;
     oCamara = jQuery('#camara');
     oFoto = jQuery('#foto');
     w = oCamara.width();
     h = oCamara.height();
     oFoto.attr({
       'width': w,
       'height': h
     });
     oContexto = oFoto[0].getContext('2d');
     oContexto.drawImage(oCamara[0], 0, 0, w, h);
     var canvas = document.getElementById("foto");
     var img = canvas.toDataURL("image/jpeg");
     // email = $("#email").val();
     //window.open("google.com.mx", "nuevo", "directories=no, location=no, menubar=no, scrollbars=yes, statusbar=no, tittlebar=no, width=400, height=400");
     $.ajax({
       type: "POST",
       url: "foto.php?accion=camara",
       data: {
         foto: img
       }
     }).done(function(msg) {
       $("#consola").html("<pre>" + msg);
     });
   });
 });