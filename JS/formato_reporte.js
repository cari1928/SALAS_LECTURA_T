$(document).on('ready', function()
{
  $("#file-1").fileinput(
  {
    allowedFileExtensions: ["pdf", "docx"],
    browseClass: "btn btn-primary",
    browseLabel: "Subir archivo...",
    browseIcon: "<i class=\"glyphicon glyphicon-folder-open\"></i> ",
    removeClass: "btn btn-danger",
    removeLabel: "Cancelar",
    removeTitle: "Anula archivo seleccionado",
    removeIcon: "<i class=\"glyphicon glyphicon-trash\"></i> ",
    uploadClass: "btn btn-info",
    uploadLabel: "Subir",
    uploadTitle: "Subir archivo seleccionado",
    uploadIcon: "<i class=\"glyphicon glyphicon-upload\"></i> ",
    previewClass: "bg-default",
    initialCaption: "Selecciona el archivo",
  });
});
