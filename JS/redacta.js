$(document).on('ready', function()
{
  $("#file-1").fileinput(
  {
    browseClass: "btn btn-primary",
    browseLabel: "Subir archivo...",
    browseIcon: "<i class=\"glyphicon glyphicon-folder-open\"></i> ",
    removeClass: "btn btn-danger",
    removeLabel: "Cancelar",
    removeTitle: "Anula archivo seleccionado",
    removeIcon: "<i class=\"glyphicon glyphicon-trash\"></i> ",
    showUpload: false,
    previewClass: "bg-default",
    initialCaption: "Selecciona el archivo",
  });
});
